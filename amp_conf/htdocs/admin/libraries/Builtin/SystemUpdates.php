<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\Builtin;

class SystemUpdates {

	// See framework/hooks/yum-* commands where these files are defined
	private $lockfile = "/dev/shm/yumwrapper/yum.lock";
	private $logfile = "/dev/shm/yumwrapper/output.log";

	/**
	 * This checks to make sure we have the Sysadmin module, and that the
	 * sysadmin module is activated. If neither of these things are true,
	 * this machine can't do system updates.
	 *
	 * @return bool
	 */
	public function canDoSystemUpdates() {
		if (!function_exists("sysadmin_get_license")) {
			return false;
		}
		$lic = sysadmin_get_license();
		return (isset($lic['hostid']));
	}

	/**
	 * Is a yum command in progress?
	 *
	 * We check to see if the yum.lock file exists. If we have
	 * issues with the screen command crashing, we may need to revisit
	 * this, and do more work than just checking if the file exists.
	 *
	 * @return bool
	 */
	public function isYumRunning() {
		return file_exists($this->lockfile);
	}


	/**
	 * Start yum-check-updates if it's not already
	 * running
	 *
	 */
	public function startCheckUpdates() {
		if ($this->isYumRunning()) {
			return true;
		}
		if (!is_dir("/var/spool/asterisk/incron")) {
			// Something's broken with this machine.
			throw new \Exception("Incron not configured, unable to manage system updates");
		}
		// incron hook
		touch("/var/spool/asterisk/incron/framework.yum-check-updates");
		// Wait up to 5 seconds for it to start
		$endafter = time()+5;
		while (time() < $endafter) {
			if ($this->isYumRunning()) {
				return true;
			}
			usleep(100000); // 1/10th of a second.
		}

		// If we made it here, the updates never started
		return false;
	}

	/**
	 * Parse the output of a yum command
	 *
	 * Returns an array of useful stuff.
	 *
	 * @return array [ ... ]
	 */
	public function parseYumOutput($filename = false) {
		if (!$filename) {
			throw new \Exception("No file to parse");
		}

		$retarr = [
			"timestamp" => 0,
			"commands" => [ ],
		];
		// Grab the contents of the output file
		if (file_exists($filename)) {
			$logfile = file($filename, FILE_IGNORE_NEW_LINES);
		} else {
			$logfile = [];
		}

		// Now find the important parts 
		foreach ($logfile as $lineno => $line) {

			// Was this a timestamp?
			if (strpos($line, "[") === 0) {
				$retarr['timestamp'] = $this->parseTimestamp($line);
				continue;
			}

			// Start of a command?
			if (strpos($line, "START") === 0) {
				if (!preg_match("/^START (\d+) (.+)/", $line, $out)) {
					// How?
					continue;
				}
				$retarr['commands'][$out[2]] = [ 'started' => $out[1] ];
				continue;
			}

			// Completion of a command?
			if (strpos($line, "STOP") === 0) {
				// STOP 1482988046 yum-check-updates 100 CnNhbmdvbWEtcGJ4Lm5vYXJjaCAgICAgICAgICAgICAgICAgICAgIDE2MTItMS5zbmc3ICAgICAgICAgICAgICAgICAgICAgIHNuZy1wa2dzCg==
				if (!preg_match("/^STOP (\d+) ([^\s]+) (\d+)(.*)$/", $line, $out)) {
					// ... Even more how?
					continue;
				}
				if (!is_array($retarr['commands'][$out[2]])) {
					throw new \Exception("Found a STOP before a START, error in $filename");
				}
				$retarr['commands'][$out[2]]['completed'] = $out[1];
				$retarr['commands'][$out[2]]['exitcode'] = $out[3];
				if (!empty($out[4])) {
					$retarr['commands'][$out[2]]['output'] = base64_decode(trim($out[4]));
				} else {
					$retarr['commands'][$out[2]]['output'] = "";
				}
				continue;
			}

			// Unknown line in file
			throw new \Exception("Unknown line '$line' on line number $lineno in file $filename");
		}

		// Completed parsing file
		return $retarr;
	}

	/**
	 * Parse a timestamp line, and return an (int) utime
	 *
	 * Timestamp lines look like this:
	 * [ timestamp: 2016-12-29 05:52:00 ] 
	 *
	 * @return int, will be zero if unable to parse.
	 */
	public function parseTimestamp($line) {
		if (!preg_match("/\[ timestamp: ([0-9:\-\s]+) \]/", $line, $out)) {
			return 0;
		}
		$dateint = strtotime($out[1]);
		if (!$dateint) {
			return 0;
		} // else
		return $dateint;
	}

	/**
	 * Return the current list of pending updates.
	 *
	 * @return array [ 	'lasttimestamp' => int, 'status' => {complete|inprogress|unknown}, 'updatesavail' => bool, 'rpms' => [ ... ] ]
	 */
	public function getPendingUpdates() {
		$updates = $this->parseYumOutput("/dev/shm/yumwrapper/output.log");
		$retarr = [ 'lasttimestamp' => $updates['timestamp'],
			'status' => 'unknown',
			'updatesavail' => false,
			'rpms' => []
		];

		// Do we have a start for 'yum-clean-metadata'?
		if (empty($updates['commands']['yum-clean-metadata'])) {
			// We don't know what's going on, just return unknown
			return $retarr;
		}

		// We have a start, so it's in progress.
		$retarr['status'] = 'inprogress';

		// Do we have a stdout for yum-check-updates?
		if (empty($updates['commands']['yum-check-updates']['output'])) {
			// no. Still in progress
			return $retarr;
		}

		// We do!
		$retarr['status'] = 'complete';

		// Are there any updates?
		$retarr['updatesavail'] = ($updates['commands']['yum-check-updates']['exitcode'] == 100);

		// Now we just need to parse that output into a list of RPMs to return.
		$retarr['rpms'] = $this->parseUpdates($updates['commands']['yum-check-updates']['output']);

		return $retarr;
	}

	/**
	 * Parse theoutput of yum-check-updates
	 *
	 * @return array
	 */
	public function parseUpdates($str) {
		$lines = explode("\n", $str);
		$rpms = [];
		foreach ($lines as $line) {
			if (!$line) {
				continue;
			}
			$linearr = preg_split("/\s+/", $line);
			$rpms[escapeshellcmd($linearr[0])] =  [ "newvers" => $linearr[1], "repo" => $linearr[2] ];
		}
		// Get our current versions
		$current = $this->getInstalledRpmVersions(array_keys($rpms));
		foreach ($current as $name => $ver) {
			if ($ver === false) {
				$rpms[$name]['installed'] = false;
				continue;
			}
			$rpms[$name]['installed'] = true;
			$rpms[$name]['currentversion'] = $ver;
		}
		return $rpms;
	}

	/**
	 * Run rpm to get the list of current versions
	 *
	 * Hand it an array of RPMs with the format of name.arch,
	 * and it returns the current version on the system
	 *
	 * @param array $rpms List of RPMS to query
	 *
	 * @retun array Key/Val
	 */
	public function getInstalledRpmVersions(array $rpms) {
		$retarr = [];
		// If this is an empty array, we don't need to do anything
		if (!$rpms) {
			return $retarr;
		}

		// Our RPM Command
		$cmd = '/usr/bin/rpm -q --queryformat "%{VERSION}.%{RELEASE}\n" '.join(" ", $rpms);
		exec($cmd, $output, $ret);
		if ($ret !== 0) {
			throw new \Exception("RPM command errored, tried to run '$cmd', exited with error $ret");
		}

		// Now go through our RPMs and match them with the output
		foreach ($rpms as $i => $name) {
			if (!isset($output[$i])) {
				throw new \Exception("Couldn't find entry $i for $name in output: ".json_encode($output));
			}
			$line = $output[$i];
			// Is this RPM not currently installed?
			// TODO: i18n of system? Will this break?
			if (strpos($line, "is not installed") !== false) {
				$retarr[$name] = false;
				continue;
			}
			// We explicitly don't ask for the RPM *name*, because rpms that "provide" the package, but aren't
			// called that, will return the wrong name. So we just blindly assume that the RPM version of
			// whatever was returned is correct. This may be wildly wrong, but it shouldn't affect any usability
			$retarr[$name] = $output[$i];
		}
		return $retarr;
	}
}

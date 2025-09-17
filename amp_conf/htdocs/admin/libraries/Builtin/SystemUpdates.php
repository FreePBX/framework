<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\Builtin;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

#[\AllowDynamicProperties]
class SystemUpdates {
	private $lock;
	// APT lock file for Debian systems
	private $aptLockFile = "/var/spool/asterisk/tmp/apt-list-upgradable.lock";
	//  i18n
	private $strarr = false; // This is overwritten in __construct
	private $cli = false;
	private $freepbx = null;

	public function __construct($cli = false) {
		$this->cli = $cli;
		// Can't use functions in class definitions
		$this->strarr = [ "complete" => _("(Complete)"), "unknown" => _("(Unknown)"), "inprogress" => _("(In Progress)"), "error" => _("General Error") ];
	}

	/**
	 * Get FreePBX object (lazy loading)
	 * @return \FreePBX
	 */
	private function getFreePBX() {
		if ($this->freepbx === null) {
			$this->freepbx = \FreePBX::create();
		}
		return $this->freepbx;
	}

	public function __destruct() {

	}

	/**
	 * Ajax handler.
	 */
	public function ajax($req) {
		if (!isset($req['action'])) {
			throw new \Exception("No action");
		}
		switch ($req['action']) {
		case 'getsysupdatepage':
			return $this->getSystemUpdatesPage();
		case 'startsysupdate':
			return $this->startSystemUpdate();
		case 'configuredebianbookworm':
			return $this->configureDebianBookworm();
		case 'getdebianbookwormpreview':
			return $this->getDebianBookwormPreview();
		case 'getdebianconfigstatus':
			return $this->getDebianConfigStatusViaHook();
		case 'refreshupgradablepackages':
			// Force refresh upgradable packages (bypasses cache)
			$result = $this->getPendingUpdate(true);
			// Verify cache was written
			$freepbx = $this->getFreePBX();
			$framework = $freepbx->Framework;
			$cached = $framework->getConfig('upgradable_packages_cache');
			return [
				'message' => 'Packages refreshed', 
				'data' => $result,
				'cached' => ($cached !== false),
				'count' => is_array($result) ? count($result) : 0
			];
		case 'refreshheldpackages':
			// Force refresh held packages (bypasses cache)
			$result = $this->getHeldPackages(true);
			// Verify cache was written
			$freepbx = $this->getFreePBX();
			$framework = $freepbx->Framework;
			$cached = $framework->getConfig('held_packages_cache');
			return [
				'message' => 'Held packages refreshed', 
				'data' => $result,
				'cached' => ($cached !== false),
				'count' => is_array($result) ? count($result) : 0
			];
		}
		throw new \Exception("Unknown action");
	}

	/**
	 * This checks to make sure we have the Sysadmin module, and that the
	 * sysadmin module is activated. If neither of these things are true,
	 * this machine can't do system updates.
	 *
	 * @return bool
	 */
	public function canDoSystemUpdates() {
		return false; // Disabling System update for 17/Debian based system as of now
		if(!\FreePBX::Modules()->checkStatus('sysadmin')) {
			return false;
		}
		\FreePBX::Modules()->loadFunctionsInc('sysadmin');
		if (!function_exists("sysadmin_get_license")) {
			return false;
		}
		$lic = sysadmin_get_license();
		//machineid is used since PHP 7+. for the earlier versions hostid is used
		return (isset($lic['machineid']));
	}

	/**
	 * Check if apt list --upgradable command is currently running
	 * 
	 * @return bool
	 */
	public function isAptRunning() {
		return file_exists($this->aptLockFile);
	}


	/**
	 * Start check-updates (legacy method - no-op for Debian systems)
	 * 
	 * @deprecated Not used for Debian/APT systems - kept for CLI compatibility only
	 * @return bool Always returns true
	 */
	public function startCheckUpdates() {
		// No-op for Debian/APT systems - kept for CLI compatibility
		return true;
	}

	/**
	 * Start system update (legacy method - no-op for Debian systems)
	 * 
	 * @deprecated Not used for Debian/APT systems - kept for CLI compatibility only
	 * @return bool Always returns true
	 */
	public function startYumUpdate() {
		// No-op for Debian/APT systems - kept for CLI compatibility
		return true;
	}

	/**
	 * Parse the output of a command log file (legacy method, not used for Debian)
	 *
	 * Returns an array of useful stuff.
	 *
	 * @deprecated Not used for Debian/APT systems
	 * @return array [ ... ]
	 */
	public function parseYumOutput($filename = false) {
		if (!$filename) {
			throw new \Exception("No file to parse");
		}

		$retarr = [
			"title" => false,
			"begintimestamp" => 0,
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

			// Title?
			if (strpos($line, "TITLE ") === 0) {
				if (!preg_match('/^TITLE (\d+) (.+)$/', $line, $out)) {
					continue;
				}
				$retarr['begintimestamp'] = $out[1];
				$retarr['title'] = $out[2];
				continue;
			}

			// Finish?
			if (strpos($line, "FINISH ") === 0) {
				$retarr['finishtimestamp'] = substr($line, 7);
				continue;
			}

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
	 * Return the current list of pending updates (legacy method for RPM systems)
	 *
	 * @deprecated Not used for Debian/APT systems - use getPendingUpdate() instead
	 * @return array [ 	'lasttimestamp' => int, 'status' => {complete|inprogress|unknown}, 'updatesavail' => bool, 'packages' => [ ... ] ]
	 */
	public function getPendingUpdates() {
		try {
			$updates = $this->parseYumOutput("/dev/shm/yumwrapper/yum-check-updates.log");
		} catch (\Exception $e) {
			@unlink("/dev/shm/yumwrapper/yum-check-updates.log");
			$retarr = [ 'lasttimestamp' => 0, 'status' => 'error', 'i18nstatus' => $this->strarr['unknown'],
				'updatesavail' => false, 'pbxupdateavail' => false, 'currentlog' => [ $e->getMessage() ], 'rpms' => [] ];
			return $retarr;
		}

		$retarr = [ 'lasttimestamp' => $updates['timestamp'],
			'status' => 'unknown',
			'i18nstatus' => $this->strarr['unknown'],
			'updatesavail' => false,
			'pbxupdateavail' => false,
			'currentlog' => [],
			'rpms' => []
		];

		// Do we have a title? If not, it was never run
		if ($updates['title'] !== 'yum-check-updates') {
			// Tell the browser to refresh after 1 sec.
			$retarr['retryafter'] = 1000;
			return $retarr;
		}

		// We should have some output that can be displayed to the user
		if (file_exists("/dev/shm/yumwrapper/yum-check-updates-current.log")) {
			$retarr['currentlog'] = file("/dev/shm/yumwrapper/yum-check-updates-current.log", FILE_IGNORE_NEW_LINES);
		}

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
			$retarr['i18nstatus'] = $this->strarr[$retarr['status']];
			return $retarr;
		}

		// We do!
		$retarr['status'] = 'complete';

		// Did check-updates fail?
		if ($updates['commands']['yum-check-updates']['exitcode'] == 1) {
			$retarr['status'] = 'error';
			$retarr['i18nstatus'] = $this->strarr['error'];
			return $retarr;
		}

		// Are there any updates?
		$retarr['updatesavail'] = ($updates['commands']['yum-check-updates']['exitcode'] == 100);

		// Now we just need to parse that output into a list of RPMs to return.
		$retarr['rpms'] = $this->parseUpdates($updates['commands']['yum-check-updates']['output']);

		// If there is a 'sangoma-pbx.noarch' RPM, we have a PBX upgrade available
		if (isset($retarr['rpms']['sangoma-pbx.noarch'])) {
			$retarr['pbxupdateavail'] = [ "name" => 'sangoma-pbx.noarch', "version" => $retarr['rpms']['sangoma-pbx.noarch']['newvers'] ];
		} else {
			$retarr['pbxupdateavail'] = false;
		}

		$retarr['i18nstatus'] = $this->strarr[$retarr['status']];
		return $retarr;
	}

	public function getPendingUpdate($forceRefresh = false) {
		try {
			$upgradablePackages = false;
			$cacheKey = 'upgradable_packages_cache';
			$cacheTimestampKey = 'upgradable_packages_timestamp';
			$cacheExpiry = 6 * 3600; // 6 hours in seconds
			
			// Check cache first unless forced refresh
			if (!$forceRefresh) {
				$freepbx = $this->getFreePBX();
				$framework = $freepbx->Framework;
				$cachedData = $framework->getConfig($cacheKey);
				$cachedTimestamp = $framework->getConfig($cacheTimestampKey);
				
				if ($cachedData !== false && $cachedTimestamp !== false) {
					$age = time() - (int)$cachedTimestamp;
					if ($age < $cacheExpiry) {
						// Cache is still valid
						return $cachedData;
					}
				}
			}
			
			// Cache expired or doesn't exist, or forced refresh - run apt command
			// Check if apt command is already running (lock protection)
			if ($this->isAptRunning()) {
				dbug('APT command already running, waiting for completion...');
				// Wait for the existing process to complete
				$jsonFilePath = '/var/spool/asterisk/tmp/upgradable_packages.json';
				$maxWait = 30; // Maximum 30 seconds to wait for existing process
				$waited = 0;
				while ($this->isAptRunning() && $waited < $maxWait) {
					sleep(1);
					$waited++;
				}
				// If lock is gone, check if we have the JSON file
				if (file_exists($jsonFilePath)) {
					$jsonContent = file_get_contents($jsonFilePath);
					$upgradablePackages = json_decode($jsonContent, true);
					if ($upgradablePackages !== false && is_array($upgradablePackages)) {
						// Cache the result from the other process
						$freepbx = $this->getFreePBX();
						$framework = $freepbx->Framework;
						$framework->setConfig($cacheKey, $upgradablePackages);
						$framework->setConfig($cacheTimestampKey, time());
						@unlink($jsonFilePath);
						return $upgradablePackages;
					}
				}
				// If we waited too long or no result, continue to trigger our own
			}
			
			// Acquire Symfony lock to prevent concurrent PHP requests
			if (!$this->getLock()->acquire()) {
				dbug('Could not acquire lock for apt command, another process may be running');
				// Try to read from cache one more time in case it was just updated
				$freepbx = $this->getFreePBX();
				$framework = $freepbx->Framework;
				$cachedData = $framework->getConfig($cacheKey);
				if ($cachedData !== false) {
					return $cachedData;
				}
				return false;
			}
			
			try {
				// Trigger the hook (hook will manage its own lock file)
				if (is_dir("/var/spool/asterisk/incron")) {
					if (file_exists("/var/spool/asterisk/incron/framework.list-system-updates")) {
						unlink("/var/spool/asterisk/incron/framework.list-system-updates");
					}
					touch("/var/spool/asterisk/incron/framework.list-system-updates");
					
					// Wait for the hook to complete - check for JSON file with timeout
					$jsonFilePath = '/var/spool/asterisk/tmp/upgradable_packages.json';
					$maxWait = 30; // Maximum 30 seconds (increased for slower systems)
					$waited = 0;
					$lockWasSet = $this->isAptRunning();
					
					// Wait for either JSON file to appear OR lock to be removed (hook finished)
					while (!file_exists($jsonFilePath) && $waited < $maxWait) {
						// If lock was set but is now gone, hook finished - check one more time for JSON
						if ($lockWasSet && !$this->isAptRunning()) {
							// Lock removed, hook finished - wait a bit more for JSON file
							sleep(1);
							break;
						}
						sleep(1);
						$waited++;
					}
					dbug('Wait completed: waited=' . $waited . 's, json exists=' . (file_exists($jsonFilePath) ? 'yes' : 'no') . ', lock running=' . ($this->isAptRunning() ? 'yes' : 'no'));
				} else {
					dbug('Incron not configured, unable to manage system updates');
				}
			} finally {
				// Release Symfony lock (hook manages its own lock file)
				$this->getLock()->release();
			}

			$jsonFilePath = '/var/spool/asterisk/tmp/upgradable_packages.json';
			if (file_exists($jsonFilePath)) {
				$jsonContent = file_get_contents($jsonFilePath);
				$upgradablePackages = json_decode($jsonContent, true);
				
				// Store in cache - always write to cache when we have data
				if ($upgradablePackages !== false && is_array($upgradablePackages)) {
					$freepbx = $this->getFreePBX();
					$framework = $freepbx->Framework;
					$result1 = $framework->setConfig($cacheKey, $upgradablePackages);
					$result2 = $framework->setConfig($cacheTimestampKey, time());
					dbug('Cached upgradable packages: ' . count($upgradablePackages) . ' packages. setConfig results: cache=' . ($result1 ? 'true' : 'false') . ', timestamp=' . ($result2 ? 'true' : 'false'));
					
					// Verify cache was written
					$verifyCache = $framework->getConfig($cacheKey);
					$verifyTimestamp = $framework->getConfig($cacheTimestampKey);
					dbug('Cache verification: cache exists=' . ($verifyCache !== false ? 'yes (' . count($verifyCache) . ' items)' : 'no') . ', timestamp exists=' . ($verifyTimestamp !== false ? 'yes (' . $verifyTimestamp . ')' : 'no'));
					
					// Remove JSON file after caching to prevent reading stale data
					@unlink($jsonFilePath);
				} else {
					dbug('Failed to decode upgradable packages JSON or empty result');
					// Remove invalid JSON file
					@unlink($jsonFilePath);
				}
			} else {
				dbug('JSON file not found: ' . $jsonFilePath);
			}
		} catch (\Exception $e) {
			dbug('Exception occurred: ' . $e->getMessage());
			$upgradablePackages = false;
		} finally {
			return $upgradablePackages;
		}
	}
	/**
	 * Parse the output of check-updates command (legacy method)
	 *
	 * @deprecated Not used for Debian/APT systems
	 * @return array
	 */
	public function parseUpdates($str) {
		$lines = explode("\n", $str);
		$packages = [];
		$wrapped = null; //https://bugzilla.redhat.com/show_bug.cgi?id=584525
		foreach ($lines as $line) {
			// If the line is blank, or, starts with a space, ignore.
			if (!$line || trim($line) === "" || $line[0] === " ") {
				continue;
			}

			// Ignore any error lines
			$txt_ignore = array(
				"Trying other mirror",
				"Operation too slow",
				"is listed more than once in the configuration",
			);
			foreach ($txt_ignore as $txt) {
				if (strpos($line, $txt) !== false) {
					continue 2;
				}
			}

			$linearr = preg_split("/\s+/", $line);

			// Ignore if it's an 'Obsoleting Packages' line
			if ($linearr[0] === "Obsoleting") {
				continue;
			}

			if(!isset($linearr[1])) {
				$packages[escapeshellcmd($linearr[0])] = ["newvers" => "", "repo" => ""];
				$wrapped = escapeshellcmd($linearr[0]);
				continue;
			}
			if(!empty($wrapped)) {
				$packages[$wrapped] =  [ "newvers" => (isset($linearr[1]) ? $linearr[1] : ""), "repo" => (isset($linearr[2]) ? $linearr[2] : "") ];
				$wrapped = null;
			} else {
				$packages[escapeshellcmd($linearr[0])] =  [ "newvers" => (isset($linearr[1]) ? $linearr[1] : ""), "repo" => (isset($linearr[2]) ? $linearr[2] : "") ];
			}
		}
		// Get our current versions (legacy - not used for Debian)
		$current = $this->getInstalledRpmVersions(array_keys($packages));
		foreach ($current as $name => $ver) {
			if ($ver === false) {
				$packages[$name]['installed'] = false;
				continue;
			}
			$packages[$name]['installed'] = true;
			$packages[$name]['currentversion'] = $ver;
		}
		return $packages;
	}
	public function checkNotInstalledPkg(array $rpmResult) {
		$first_word = 'package';
		$last_word = 'is not installed';
		if (empty($rpmResult)) {
			return false;
		}
		$lastElem = end($rpmResult);
		if ($lastElem && preg_match('/^' . $first_word . '\b(.*)\b' . $last_word .'$/' , $lastElem, $matches)) {
			return true;
		}
                return false;
        }



	/**
	 * Get installed package versions (legacy method for RPM systems)
	 *
	 * @deprecated Not used for Debian/APT systems
	 * @param array $packages List of packages to query
	 *
	 * @return array Key/Val
	 */
	public function getInstalledRpmVersions(array $packages) {
		$retarr = [];
		// If this is an empty array, we don't need to do anything
		if (!$packages) {
			return $retarr;
		}

		// Legacy RPM command (not used for Debian)
		$cmd = '/usr/bin/rpm -q --queryformat "%{NAME}.%{ARCH} %{VERSION}.%{RELEASE}\n" '.join(" ", $packages);
		exec($cmd, $output, $ret);
		if ($ret == 1 && $this->checkNotInstalledPkg($output)) {
			/* if last package in rpm -q is not installed then it will have 1 as return code
			 * so we have to check if last line of return output has "package not installed"
			 * or not, if this is present then ignore the error and continue with processing */
		} else {
			if ($ret !== 0 && $ret !== 6) {
				// 6 = new packages are going to be installed
				if (function_exists("freepbx_log")) {
					freepbx_log(FPBX_LOG_CRITICAL, sprintf(_("Update error: Tried to run '%s', exit code %s"), $cmd, $ret));
				}
				throw new \Exception(sprintf(_("Package query command errored. Exit code %s - see FreePBX log for more info."), $ret));
			}
		}

		// Map the output to a temporary dict
		$current = [];
		foreach ($output as $line) {
			$tmparr = explode(" ", $line);
			if (strpos($line, "is not installed") === false) {
				$name = $tmparr[0];
				$ver = $tmparr[1];
			} else {
				// It's not installed
				$name = $tmparr[1];
				$ver = false;
			}
			$current[$name] = $ver;
		}

		// Now go through our packages and match them with the output
		foreach ($packages as $i => $name) {
			if (!isset($current[$name])) {
				$retarr[$name] = false;
			} else {
				$retarr[$name] = $current[$name];
			}
		}
		return $retarr;
	}

	/**
	 * Get the Distro Version
	 */
	public function getDistroVersion() {
		if (!file_exists("/etc/sangoma/pbx-version")) {
			return "Unknown";
		}
		$vers = file("/etc/sangoma/pbx-version");
		// If it's empty, return an error
		if (!$vers || empty(trim($vers[0]))) {
			return "Error reading /etc/sangoma/pbx-version";
		}
		return trim($vers[0]);
	}

	/**
	 * Get the status of system update (legacy method - no-op for Debian)
	 * 
	 * @deprecated Not used for Debian/APT systems - kept for CLI compatibility only
	 * @return array Returns empty status array
	 */
	public function getYumUpdateStatus() {
		// No-op for Debian/APT systems - return empty complete status
		// Kept for CLI compatibility only
		return [
			'lasttimestamp' => 0,
			'status' => 'complete',
			'i18nstatus' => $this->strarr['complete'],
			'currentlog' => [],
		];
	}

	/**
	 * Render what is displayed in the System Updates tab (legacy method for RPM systems)
	 *
	 * @deprecated Not used for Debian/APT systems - this method is for RPM-based systems only
	 * 
	 * This is used both in page.modules as well as ajax when it's asking for updates.
	 *
	 * Note: This generates onclick=... HTML. There is a valid (but, possibly, poor) reason for
	 * this, in that the elements are deleted on every reload, and I'd have to REMAP each one
	 * of them, every time the page is loaded.  This could easily lead to memory leaks, or,
	 * forgetting to map a new one when it's created.  So I made the decision to use onclick,
	 * and you can yell at me about it if you want.  --xrobau 2017-01-03
	 *
	 * @return string html to be displayed
	 */
	public function getSystemUpdatesPage() {
		$html = "<h3>"._("System Update Details")."</h3>";
		$yumstatus = $this->getYumUpdateStatus();
		$pending = $this->getPendingUpdates();
		// Are we idle, or are we doing something?
		if ($yumstatus['status'] !== "inprogress" && $pending['status'] !== "inprogress") {
			$idle = true;
			$currentstatus = _("Idle");
		} else {
			$idle = false;
			$currentstatus = _("Working");
		}
		if($this->checkIfTestingRepoEnabled()) {
			$html .= show_help(_("This system has test repos enabled."), _('Test Repos Enabled'), false, false);
		}
		$html .= "<div class='row'>
			<div class='col-sm-3'>"._("Current System Update Status:")."</div>
			<div class='col-sm-5'>$currentstatus</div>
			<div class='col-sm-4'><button id='refreshpagebutton' class='btn btn-default pull-right' onclick='reload_system_updates_tab()'>"._("Refresh page")."</button></div>
		</div>\n";
		$html .= "<div class='row'>
			<div class='col-sm-3'>"._("Last Online Check Status:")."</div>
			<div class='col-sm-5' id='pendingstatus' data-value='".$pending['status']."'>".($pending['lasttimestamp'] != 0 ? \FreePBX::View()->humanDiff($pending['lasttimestamp']) : _("Never"))." &nbsp; ".$this->strarr[$pending['status']]."</div>
		</div>\n";
		$html .= "<div class='row'>
			<div class='col-sm-3'>"._("Last System Update:")."</div>";
		// If lasttimestamp isn't false, we should have updates for the user to watch.
		if ($yumstatus['lasttimestamp']) {
			$html .= "<div class='col-sm-5' id='yumstatus' data-value='".$yumstatus['status']."'><a class='clickable' onclick='show_sysupdate_modal()'>".\FreePBX::View()->humanDiff($yumstatus['lasttimestamp'])." &nbsp; ".$this->strarr[$yumstatus['status']]."</a></div>";
		} else {
			$html .= "<div class='col-sm-5' id='yumstatus' data-value='complete'>"._("Unknown (System updates not run since last reboot)")."</div>";
		}

		$html .= "</div>\n";

		// If we have a yum update log, make it available
		if ($yumstatus['currentlog']) {
			$html .= "<script>window.currentupdate = ".json_encode($yumstatus)."</script>\n";
		}

		// If we're not idle, don't bother with anything else.
		if (!$idle) {
			return $html;
		}

		$rpmcount = count($pending['rpms']);
		if ($rpmcount == 0) {
			if ($pending['status'] == "yumerror") {
				$rpmtext = _("Unable to run 'yum check-updates', can't check for updates");
			} else {
				$rpmtext = _("No updates currently required!");
			}
		} else {
			if ($rpmcount == 1) {
				$rpmtext = _("1 RPM available for upgrade");
			} else {
				$rpmtext = sprintf(_("%s RPMs available for upgrade"), $rpmcount);
			}
		}
		$html .= "<div class='row'>
			<div class='col-sm-3'>"._("Updates Available:")."</div>
			<div class='col-sm-5'>$rpmtext</div>
		</div>";

		if ($rpmcount == 0) {
			// Add the 'Check Online' button.
			$html .= "<div class='row'>
				<div class='col-sm-12'>
					<span class='pull-right'>
						<button id='checkonlinebutton' class='btn btn-default pull-right' onclick='run_yum_checkonline()'>"._("Check Online")."</button>
					</span>
				</div>
			</div>\n";
			return $html;
		}

		// We're here because we have some RPMS available. Lets display them.
		$html .= "<table class='table'><tr><th>"._("RPM Name")."</th><th>"._("New Version")."</th><th>"._("Installed Version")."</th></tr>\n";
		foreach ($pending['rpms'] as $rpmname => $tmparr) {
			if (!$tmparr['installed']) {
				$html .= "<tr><td>$rpmname</td><td>".$tmparr['newvers']."</td><td>"._("New Package")."</td></tr>";
			} else {
				if ($pending['pbxupdateavail'] && $pending['pbxupdateavail']['name'] == $rpmname) {
					// Make it stand out as a major upgrade
					$style = 'style="background: #FEE"';
				} else {
					$style = "";
				}
				$html .= "<tr $style><td>$rpmname</td><td>".$tmparr['newvers']."</td><td>".$tmparr['currentversion']."</td></tr>";
			}
		}
		$html .= "</table>";
		$html .= "<div class='row'>
			<div class='col-sm-12'>
				<span class='pull-right'>
					<button id='updatesystembutton' class='btn btn-default pull-right' onclick='update_rpms()'>"._("Update System")."</button>
					<button id='checkonlinebutton' class='btn btn-default pull-right' onclick='run_yum_checkonline()'>"._("Check Online")."</button>
				</span>
			</div>
		</div>\n";
		return $html;
	}

	/**
	 * Implement locks to avoid multiple things modifying system
	 *
	 * @return void
	 */
	private function getLock() {
		if(!empty($this->lock)) {
			return $this->lock;
		}
		$lockStore = new SemaphoreStore();
		$factory = new LockFactory($lockStore);
		$this->lock = $factory->createLock('systemupdates',7200);
		return $this->lock;
	}

	private function checkIfTestingRepoEnabled() {
		//check "sangoma-devel" rpm installed
        $ret = exec("/usr/bin/rpm -qa|grep sangoma-devel");
        if (!empty($ret)) {
			//check if the yum repo "sng7-testing" is enabled
			$ret = exec("/usr/bin/yum repolist | grep -i sng7-testing");
			if (!empty($ret)) {
				return true;
			}
        }
        return false;
	}

	public function checkBrokenRpm()
	{
		if(!\FreePBX::Modules()->checkStatus('sysadmin')) {
			return false;
		}
		// in future add the broken rpm version to this array
		$brokenRpms = [
			'sysadmin-5.6-5.6.48.sng.noarch',
			'sysadmin-5.6-5.6.49.sng.noarch',
			'sysadmin-5.6-5.6.50.sng.noarch',
			'sysadmin-5.6-5.6.51.sng.noarch',
		];
		$rpmNames = ['sysadmin'];
		if(count($rpmNames) > 1) {
			$moduleStr = implode("|",$rpmNames);
			$process = exec("rpm -qa | grep -E '$moduleStr'");
		} else {
			$process = exec("rpm -qa | grep '".$rpmNames[0]."'");
		}
		foreach ($brokenRpms as $vers) {
			if (strpos($process, $vers) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the Debian Version
	 */
	public function getDebianVersion() {
		if (!file_exists("/etc/debian_version")) {
			return "Unknown";
		}
		$vers = file("/etc/debian_version");
		// If it's empty, return an error
		if (!$vers || empty(trim($vers[0]))) {
			return "Error reading /etc/debian_version";
		}
		return trim($vers[0]);
	}


	/**
	 * Get preview of files that will be changed for Debian Bookworm configuration
	 * Uses hook system for secure root-level access
	 */
	public function getDebianBookwormPreview() {
		try {
			if (!is_dir("/var/spool/asterisk/incron")) {
				throw new \Exception("Incron not configured, unable to preview system changes");
			}
			
			$hook_file = "/var/spool/asterisk/incron/framework.debian-bookworm-preview";
			if (file_exists($hook_file)) {
				unlink($hook_file);
			}
			touch($hook_file);
			sleep(2); // Wait for the hook to complete
			
			$json_file = '/var/spool/asterisk/tmp/debian_bookworm_preview.json';
			if (file_exists($json_file)) {
				$json_content = file_get_contents($json_file);
				$preview_data = json_decode($json_content, true);
				return $preview_data;
			}
			
			// Fallback if hook fails
			return [
				'files_to_change' => [],
				'new_files' => [],
				'changes' => [],
				'error' => 'Unable to generate preview - hook system unavailable'
			];
		} catch (\Exception $e) {
			dbug('Exception in getDebianBookwormPreview: ' . $e->getMessage());
			return [
				'files_to_change' => [],
				'new_files' => [],
				'changes' => [],
				'error' => 'Preview failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Configure Debian Bookworm repositories and block Trixie
	 * Uses hook system for secure root-level access
	 */
	public function configureDebianBookworm() {
		try {
			if (!is_dir("/var/spool/asterisk/incron")) {
				throw new \Exception("Incron not configured, unable to configure system");
			}
			
			$hook_file = "/var/spool/asterisk/incron/framework.debian-bookworm-configure";
			if (file_exists($hook_file)) {
				unlink($hook_file);
			}
			touch($hook_file);
			sleep(3); // Wait for the hook to complete (longer for configuration)
			
			$json_file = '/var/spool/asterisk/tmp/debian_bookworm_configure.json';
			if (file_exists($json_file)) {
				$json_content = file_get_contents($json_file);
				$config_data = json_decode($json_content, true);
				return $config_data;
			}
			
			// Fallback if hook fails
			return [
				'success' => false,
				'messages' => [],
				'errors' => ['Unable to configure system - hook system unavailable']
			];
		} catch (\Exception $e) {
			dbug('Exception in configureDebianBookworm: ' . $e->getMessage());
			return [
				'success' => false,
				'messages' => [],
				'errors' => ['Configuration failed: ' . $e->getMessage()]
			];
		}
	}


	/**
	 * Get Debian configuration status via hook (for AJAX calls)
	 */
	public function getDebianConfigStatusViaHook() {
		try {
			if (!is_dir("/var/spool/asterisk/incron")) {
				throw new \Exception("Incron not configured, unable to manage system updates");
			}
			
			// Trigger the hook by touching a file
			$hook_file = "/var/spool/asterisk/incron/framework.debian-config-status";
			if (file_exists($hook_file)) {
				unlink($hook_file);
			}
			touch($hook_file);
			
			// Wait for the hook to complete
			sleep(2);
			
			// Read the results from the JSON file
			$json_file = '/var/spool/asterisk/tmp/debian_config_status.json';
			if (file_exists($json_file)) {
				$json_content = file_get_contents($json_file);
				$config_status = json_decode($json_content, true);
				return $config_status;
			}
			
			// Fallback if hook didn't work
			return $this->getDebianConfigStatus();
			
		} catch (\Exception $e) {
			dbug('Exception in getDebianConfigStatusViaHook: ' . $e->getMessage());
			return $this->getDebianConfigStatus();
		}
	}

	/**
	 * Get Debian configuration status for UI (fallback method)
	 * This is only used as a fallback when the hook system is not available
	 */
	public function getDebianConfigStatus() {
		$is_debian = file_exists('/etc/debian_version');
		if (!$is_debian) {
			return null;
		}

		$debian_version = $this->getDebianVersion();
		$is_debian_12 = strpos($debian_version, '12') === 0;

		// Fallback values - real detection is done via hook
		return [
			'is_debian' => true,
			'version' => $debian_version,
			'is_debian_12' => $is_debian_12,
			'is_trixie_blocked' => false,
			'is_using_stable' => $is_debian_12
		];
	}

	/**
	 * Get held packages from APT (uses cache and hook)
	 * @param bool $forceRefresh Force refresh bypassing cache
	 * @return array
	 */
	public function getHeldPackages($forceRefresh = false) {
		try {
			$heldPackages = false;
			$cacheKey = 'held_packages_cache';
			$cacheTimestampKey = 'held_packages_timestamp';
			$cacheExpiry = 6 * 3600; // 6 hours in seconds
			
			// Check cache first unless forced refresh
			if (!$forceRefresh) {
				$freepbx = $this->getFreePBX();
				$framework = $freepbx->Framework;
				$cachedData = $framework->getConfig($cacheKey);
				$cachedTimestamp = $framework->getConfig($cacheTimestampKey);
				
				if ($cachedData !== false && $cachedTimestamp !== false) {
					$age = time() - (int)$cachedTimestamp;
					if ($age < $cacheExpiry) {
						// Cache is still valid
						return $cachedData;
					}
				}
			}
			
			// Cache expired or doesn't exist, or forced refresh - run hook
			// Check if command is already running (lock protection)
			$heldLockFile = '/var/spool/asterisk/tmp/apt-list-held.lock';
			if (file_exists($heldLockFile)) {
				dbug('Held packages command already running, waiting for completion...');
				// Wait for the existing process to complete
				$jsonFilePath = '/var/spool/asterisk/tmp/held_packages.json';
				$maxWait = 30; // Maximum 30 seconds to wait for existing process
				$waited = 0;
				while (file_exists($heldLockFile) && $waited < $maxWait) {
					sleep(1);
					$waited++;
				}
				// If lock is gone, check if we have the JSON file
				if (file_exists($jsonFilePath)) {
					$jsonContent = file_get_contents($jsonFilePath);
					$heldPackages = json_decode($jsonContent, true);
					if ($heldPackages !== false && is_array($heldPackages)) {
						// Cache the result from the other process
						$freepbx = $this->getFreePBX();
						$framework = $freepbx->Framework;
						$framework->setConfig($cacheKey, $heldPackages);
						$framework->setConfig($cacheTimestampKey, time());
						@unlink($jsonFilePath);
						return $heldPackages;
					}
				}
				// If we waited too long or no result, continue to trigger our own
			}
			
			// Acquire Symfony lock to prevent concurrent PHP requests
			if (!$this->getLock()->acquire()) {
				dbug('Could not acquire lock for held packages command, another process may be running');
				// Try to read from cache one more time in case it was just updated
				$freepbx = $this->getFreePBX();
				$framework = $freepbx->Framework;
				$cachedData = $framework->getConfig($cacheKey);
				if ($cachedData !== false) {
					return $cachedData;
				}
				return [];
			}
			
			try {
				// Trigger the hook (hook will manage its own lock file)
				if (is_dir("/var/spool/asterisk/incron")) {
					if (file_exists("/var/spool/asterisk/incron/framework.list-held-packages")) {
						unlink("/var/spool/asterisk/incron/framework.list-held-packages");
					}
					touch("/var/spool/asterisk/incron/framework.list-held-packages");
					
					// Wait for the hook to complete - check for JSON file with timeout
					$jsonFilePath = '/var/spool/asterisk/tmp/held_packages.json';
					$maxWait = 30; // Maximum 30 seconds (increased for slower systems)
					$waited = 0;
					$lockWasSet = file_exists($heldLockFile);
					
					// Wait for either JSON file to appear OR lock to be removed (hook finished)
					while (!file_exists($jsonFilePath) && $waited < $maxWait) {
						// If lock was set but is now gone, hook finished - check one more time for JSON
						if ($lockWasSet && !file_exists($heldLockFile)) {
							// Lock removed, hook finished - wait a bit more for JSON file
							sleep(1);
							break;
						}
						sleep(1);
						$waited++;
					}
					dbug('Held packages wait completed: waited=' . $waited . 's, json exists=' . (file_exists($jsonFilePath) ? 'yes' : 'no') . ', lock running=' . (file_exists($heldLockFile) ? 'yes' : 'no'));
				} else {
					dbug('Incron not configured, unable to manage held packages');
				}
			} finally {
				// Release Symfony lock (hook manages its own lock file)
				$this->getLock()->release();
			}

			$jsonFilePath = '/var/spool/asterisk/tmp/held_packages.json';
			if (file_exists($jsonFilePath)) {
				$jsonContent = file_get_contents($jsonFilePath);
				$heldPackages = json_decode($jsonContent, true);
				
				// Store in cache - always write to cache when we have data
				if ($heldPackages !== false && is_array($heldPackages)) {
					$freepbx = $this->getFreePBX();
					$framework = $freepbx->Framework;
					$result1 = $framework->setConfig($cacheKey, $heldPackages);
					$result2 = $framework->setConfig($cacheTimestampKey, time());
					dbug('Cached held packages: ' . count($heldPackages) . ' packages. setConfig results: cache=' . ($result1 ? 'true' : 'false') . ', timestamp=' . ($result2 ? 'true' : 'false'));
					
					// Verify cache was written
					$verifyCache = $framework->getConfig($cacheKey);
					$verifyTimestamp = $framework->getConfig($cacheTimestampKey);
					dbug('Held packages cache verification: cache exists=' . ($verifyCache !== false ? 'yes (' . count($verifyCache) . ' items)' : 'no') . ', timestamp exists=' . ($verifyTimestamp !== false ? 'yes (' . $verifyTimestamp . ')' : 'no'));
					
					// Remove JSON file after caching to prevent reading stale data
					@unlink($jsonFilePath);
				} else {
					dbug('Failed to decode held packages JSON or empty result');
					// Remove invalid JSON file
					@unlink($jsonFilePath);
				}
			} else {
				dbug('Held packages JSON file not found: ' . $jsonFilePath);
			}
		} catch (\Exception $e) {
			dbug('Exception occurred in getHeldPackages: ' . $e->getMessage());
			$heldPackages = [];
		} finally {
			return ($heldPackages !== false && is_array($heldPackages)) ? $heldPackages : [];
		}
	}
}

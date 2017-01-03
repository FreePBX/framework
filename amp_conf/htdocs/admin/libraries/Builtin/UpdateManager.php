<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\Builtin;

class UpdateManager {

	/**
	 * Handle an Ajax request from the Updates Page
	 *
	 * @param array $req $_REQUEST
	 * @return array 
	 */
	public function ajax($req) {
		if (empty($req['action'])) {
			throw new \Exception("No action");
		}
		switch ($req['action']) {
		case "getmoduleupdates":
			return [ "status" => true, "result" => $this->getAvailModules() ];
		case "getsystemupdates":
			$s = new SystemUpdates();
			if (!$s->canDoSystemUpdates()) {
				return [ "systemupdates" => false, "updatesavail" => false, "updatespending" => [] ];
			} else {
				$pending = $s->getSystemUpdatesPending();
				return [ "systemupdates" => true, "updatesavail" => empty($pending), "updatespending" => $pending ];
			}
		case "updatescheduler":
			$this->updateUpdateSettings($req);
			return $this->getCurrentUpdateSettings();
		default:
			throw new \Exception(json_encode($req));
		}
	}

	/**
	 * Get current settings
	 *
	 * If the setting is not defined, return the default.
	 *
	 * @param bool $encode If false, return strings raw. Otherwise html escape special chars.
	 * @return array
	 */
	public function getCurrentUpdateSettings($encode = true) {
		$retarr = [
			"notification_emails" => "",
			"system_ident" => "",
			"auto_system_updates" => "emailonly", // This is ignored if not on a supported OS
			"auto_module_updates" => "enabled",
			"update_every" => "saturday",
			"update_period" => "4to8",
		];

		$fpbx = \FreePBX::create();

		foreach ($retarr as $k => $null) {
			$val = $fpbx->getConfig($k, "updates");
			if ($val) {
				if ($encode) {
					$retarr[$k] = htmlspecialchars($val, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8', false);
				} else {
					$retarr[$k] = $val;
				}
			}
		}

		// If ident is empty, take the one from settings
		if (!$retarr['system_ident']) {
			$retarr['system_ident'] = htmlspecialchars(\FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8', false);
			$fpbx->setConfig("system_ident", $retarr['system_ident'], "updates");
		}

		// If we don't have an email address, it may be in admin from
		// previous updates. Grab it (and delete it) from there.
		if (!$retarr['notification_emails']) {
			$oldemail = (string) $this->getOldEmailAddress(); // Make sure it's an empty string, not bool false.
			if ($oldemail) {
				$retarr['notification_emails'] = $oldemail;
				$fpbx->setConfig("notification_emails", $oldemail, "updates");
			}
		}

		return $retarr;
	}

	/**
	 * Update our updater settings.
	 *
	 * Yes. That's confusing. Sorry, non-english speakers.
	 *
	 * @return array all settings
	 */
	public function updateUpdateSettings($req) {

		$fpbx = \FreePBX::create();

		$current = $this->getCurrentUpdateSettings();

		// Check what we're currently allowed to change, and if it's different
		// from what was just submitted, change it!
		foreach ($current as $k => $c) {
			if (isset($req[$k]) && $req[$k] !== $c) {
				$fpbx->setConfig($k, $req[$k], "updates");
			}
		}

		return $this->getCurrentUpdateSettings();
	}

	/**
	 * Return the email address from the old admin table
	 */
	public function getOldEmailAddress($delete = true) {
		$sql = "SELECT `value` FROM `admin` WHERE `variable`='email'";

		$db = \FreePBX::Database();
		$row = $db->query($sql)->fetchColumn();
		if ($delete) {
			$db->query("DELETE FROM `admin` WHERE `variable`='email'");
		}
		return $row;
	}


	/**
	 * Update our crontab to be whatever it should be
	 *
	 * @return string
	 */
	public function updateCrontab() {

		$hourmaps = [
			"0to4" => [ 0, 3 ],
			"4to8" => [ 4, 7 ],
			"8to12" => [ 8, 11 ],
			"12to16" => [ 12, 15 ],
			"16to20" => [ 16, 19 ],
			"20to0" => [ 20, 23 ]
		];

		$daymaps = [
		    "day" => "*",
		    "sunday" => "0",
		    "monday" => "1",
		    "tuesday" => "2",
		    "wednesday" => "3",
		    "thursday" => "4",
		    "friday" => "5",
			"saturday" => "6"
		];

		$settings = $this->getCurrentUpdateSettings();

		// Get the day
		if (!isset($daymaps[$settings['update_every']])) {
			throw new \Exception("Unknown day '$day'");
		}
		$day = $daymaps[$day];

		// Pick a random hour
		if (!isset($hourmaps[$settings['update_period']])) {
			throw new \Exception("Unknown period '$period'");
		}
		$map = $hourmaps[$settings['update_period']];
		$hour = mt_rand($map[0], $map[1]);

		// Pick a random minute
		$min = mt_rand(0, 59);

		// Start by deleting any fwconsole commands in cron
		$cron = \FreePBX::Cron();
		$fwconsole = \FreePBX::Config()->get('AMPSBIN')."/fwconsole" ;
		$cron->removeAll($fwconsole);

		// Are our updates enabled?
		if ($settings['auto_module_updates'] === "enabled") {
			$cmd = "[ -e $exec ] && $exec ma --sendemails --willupdate listonline > /dev/null 2>&1";
		} elseif ($settings['auto_module_updates'] === "disabled") {
			// Only send security emails
			$cmd = "[ -e $exec ] && $exec ma --sendemails --securityonly listonline > /dev/null 2>&1";
		} else {
			$cmd = "[ -e $exec ] && $exec ma --sendemails listonline > /dev/null 2>&1";
		}

		// LEGACY: Make sure our old scheduler is gone - WIP
		// $cron->removeAll("/var/lib/asterisk/bin/freepbx-cron-scheduler.php");

		// Add the new job to check for updates.
		$cron->add([ "command" => $cmd, "minute" => $min, "hour" => $hour, "day" => $day ]);

		// If we are auto-installing, then run the update job an hour later.
		if ($settings['auto_module_updates'] === "enabled") {
			$cmd = "[ -e $exec ] && $exec ma installall > /dev/null 2>&1";
			if ($hour == 23) {
				$hour = 0;
				$day++;
			} else {
				$hour++;
			}
			$cron->add([ "command" => $cmd, "minute" => $min, "hour" => $hour, "day" => $day ]);
		}
	}


	/**
	 * Check to make sure that our cronjob is in place
	 *
	 * @return bool
	 */
	public function isCronjobInPlace() {
		$exec = \FreePBX::Config()->get('AMPBIN')."/module_admin";
		$crons = \FreePBX::Cron()->getAll();
		foreach ($crons as $line) {
			if (strpos($line, $exec) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send an email
	 *
	 * This checks to make sure it's not a duplicate of the last email that was sent, 
	 * but WILL send an email if it's been more than a week since the last email was
	 * sent.  This ensures that people get a regular ping from their server telling
	 * them that everything's OK.
	 *
	 * @param string $tag A way to differentiate between different emails
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param string $body
	 * @param int $priority
	 */
	public function sendEmail($tag, $to, $from, $subject, $message, $priority = 4, $force = false) {
		// If there's no 'to' address, give up.
		if (!$to) {
			return false;
		}
		// Grab the FreePBX Object
		$fpbx = \FreePBX::create();

		// Generate a hash of this email body and who it's being sent to
		$currenthash = hash("sha256", $message.$to);

		$previoushash = $fpbx->getConfig($tag, "emailhash");
		$lastsent = (int) $fpbx->getConfig($tag, "emailtimestamp");

		if (!$force && $currenthash === $previoushash && ($lastsent > time() - 604800)) {
			// Not sending, it's a dupe and it's too soon. Pretend we did.
			return true;
		}

		$em = new Email();
		$em->setTo(array($to));
		$em->setFrom($from);
		$em->setSubject($subject);
		$em->setBodyPlainText($message);
		$em->setPriority($priority);
		$result = $em->send();
		if ($result) {
			// Successfully sent!
			$fpbx->setConfig($tag, $currenthash, "emailhash");
			$fpbx->setConfig($tag, time(), "emailtimestamp");
		}
		return $result;
	}

	/** Clean up HTML in emails */
	public function cleanHtml($string) {
		return "    ".str_replace("<br>", "\n    ", $string);
	}

	/** Return summary of module updates */
	public function getAvailModules() {
		$cachedonline = \FreePBX::Modules()->getCachedOnlineData();

		// TODO: use $cachedonline['timestamp'] to figure this out
		$html = "<h3>"._("Last module update check: ")."1234 seconds ago</h3>";

		// Get our list of upgradeable modules (if any)
		$upgradeable = \FreePBX::Modules()->getUpgradeableModules($cachedonline['modules']);

		if (!$upgradeable) {
			$html .= _("No pending module updates.");
			return $html;
		}
		// We have modules. Create our table
		$html .= "<table class='table'><tr><th>"._("Module Name")."</th><th>"._("Current Version")."</th><th>"._("New Version")."</th></tr>\n";
		foreach ($upgradeable as $row) {
			$html .= "<tr><td>".$row['descr_name']."</td><td>".$row['local_version']."</td><td>".$row['online_version']."</td></tr>\n";
		}
		$html .= "</table>";

		return $html;
	}
}


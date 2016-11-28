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
	 * @return array
	 */
	public function getCurrentUpdateSettings($encode = true) {
		$retarr = [
			"notification_emails" => "",
			"system_ident" => "",
			"auto_system_updates" => "emailonly", // This is ignored if not on a supported OS
			"auto_module_updates" => "true",
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
			$fpbx->setConfig("system_ident", $req['system_ident'], "updates");
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
			if (!empty($req[$k]) && $req[$k] !== $c) {
				$fpbx->setConfig($k, $req[$k], "updates");
			}
		}

		return $this->getCurrentUpdateSettings();
	}
}


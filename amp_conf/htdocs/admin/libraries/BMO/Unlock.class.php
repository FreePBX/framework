<?php
/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX BMO
 * @author    Rob Thomas <rob.thomas@schmoozecom.com>
 * @license   AGPL v3
 */
/** 
 * Provides an 'easy unlock' function for automated remote login
 */

class Unlock extends FreePBX_Helpers {

	public function __construct($bmo, $var = null) {
		if ($var) {
			$this->checkUnlock($var);
		}
	}

	public function genUnlockKey() {

		// Check to see that REMOTEUNLOCK is enabled
		if (!$this->Config->get_conf_setting('REMOTEUNLOCK')) {
			return false;
		}

		// PHP 5.3 or higher.
		// Get 256 random bits
		$rand = openssl_random_pseudo_bytes(32);
		// Hash it
		$key = hash("sha256", $rand);
		if (strlen($key) != 64) {
			throw new Exception("Severe PHP Error - A sha256 hash was not 64 characters long. I recieved '$key'");
		}
		$this->setConfig('unlockkey', $key);
		return $key;
	}

	public function checkUnlock($var = null) {

		// Check to see that REMOTEUNLOCK is enabled
		if (!$this->Config->get_conf_setting('REMOTEUNLOCK')) {
			return false;
		}

		if (!$var) {
			return false;
		}

		$currentkey = $this->getConfig('unlockkey');
		if (!$currentkey) {
			return false;
		}

		if ($var !== $currentkey) {
			// Didn't match. Delete the key.
			$this->setConfig('unlockkey');
			return false;
		}

		// Woo. It passed. Unlock this session!
		return $this->unlockSession();
	}

	private function unlockSession() {
		if (!isset($_SESSION)) {
			return false;
		}

		// Delete this key, it's been used now.
		$this->setConfig('unlockkey');

		$_SESSION["AMP_user"] = new ampuser(FreePBX::$conf["AMPDBUSER"]);
		$_SESSION["AMP_user"]->setAdmin();
		define('FREEPBX_IS_AUTH', 'TRUE');

		return true;
	}
}

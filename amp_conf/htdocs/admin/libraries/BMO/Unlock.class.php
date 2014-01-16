<?php

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

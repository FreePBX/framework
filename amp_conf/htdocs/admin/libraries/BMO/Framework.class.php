<?php
/**
 * This is the FreePBX Big Module Object.
 *
 * Framework built-in BMO Class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Framework extends FreePBX_Helpers implements BMO {
	/** BMO Required Interfaces */
	public function install() {
	}
	public function uninstall() {
	}
	public function backup() {
	}
	public function restore($backup) {
	}
	public function runTests($db) {
		return true;
	}
	public function doConfigPageInit() {
	}

	public function ajaxRequest($req, &$setting) {
		if ($req == "unset" || $req == "pubkey") {
			$setting['authenticate'] = false;
			$setting['allowremote'] = false;
			return true;
		}
		return false; // Returning false, or anything APART from (bool) true will abort the request
	}

	public function ajaxHandler() {
		if ($_REQUEST['command'] == "pubkey") {
			print "Json encoded public key.\n";
			exit;
		} else {
			print "You didn't ask for anything. What were you expecting?\n";
			exit;
		}
	}
}

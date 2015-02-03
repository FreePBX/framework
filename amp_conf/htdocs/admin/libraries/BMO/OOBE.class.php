<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is part of the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2015 Sangoma Technologies
 */
class OOBE extends FreePBX_Helpers {

	// Is the out of box experience complete?
	public function isComplete() {
		return $this->getConfig("iscomplete");
	}

	// Which modules have pending OOBE pages to show?
	public function getPendingModules() {
		$all = $this->getOOBEModules();
		$complete = $this->getConfig("completed");
		if (!is_array($complete)) {
			return $all;
		}

		// Remove ones that are complete
		foreach ($complete as $m) {
			unset($all[$m]);
		}

		return $all;
	}

	private function completeOOBE($mod = false) {
		if (!$mod) {
			throw new \Exception("No module given to mark as complete");
		}
		$complete = $this->getConfig("completed");
		if (!is_array($complete)) {
			$complete = array($mod);
		} else {
			$complete[] = $mod;
		}

		$this->setConfig("completed", $complete);
	}

	// Which modules are providing OOBE pages?
	public function getOOBEModules() {
		return array("framework" => "Core System Setup", "sysadmin" => "System Administration");
	}

	public function showOOBE() {
		$pending = $this->getPendingModules();
		$current = key($pending);
		if ($current == "framework") {
			// That's us!
			return $this->createAdminAccount();
		} else {
			throw new \Exception("Unimplemented");
		}
	}

	private function createAdminAccount() {
		if (!isset($_REQUEST['username'])) {
			// Just show the view
			echo load_view("/var/www/html/admin/views/oobe.php");
		} else {
			$errors = $this->validateFrameworkOOBE($results);
			if ($errors) {
				// Something failed. Try again
				$results['errors'] = $errors;
				echo load_view("/var/www/html/admin/views/oobe.php", $results);
			} else {
				$this->createFreePBXAdmin($results);
				$this->completeOOBE("framework");
				return $this->showOOBE();
			}
		}
	}

	private function validateFrameworkOOBE(&$results) {
		if (isset($_REQUEST['username'])) {
			$username = trim($_REQUEST['username']);
		} else {
			$username = "";
		}

		if (isset($_REQUEST['password1'])) {
			$password1 = trim($_REQUEST['password1']);
		} else {
			$password1 = "";
		}

		if (isset($_REQUEST['password2'])) {
			$password2 = trim($_REQUEST['password2']);
		} else {
			$password2 = "";
		}

		if (isset($_REQUEST['email'])) {
			$email = trim($_REQUEST['email']);
		} else {
			$email = "";
		}
		$results = array();
		$errors = array();

		if (!$username){
			$errors[] = _('Please enter a username');
		}  else {
			$results['username'] = $username;
		}

		if (!$password1 || !$password2 || $password1 != $password2) {
			$errors[] = _('Password error. Please re-check');
		} elseif (strlen($password1) < 4) {
			$errors[] = _('Password too short');
		} else {
			$results['password'] = $password1;
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = _('Email addresses error');
		} else {
			$results['email'] = $email;
		}

		return $errors;
	}

	private function createFreePBXAdmin($settings) {
		// This will never, ever, overwrite an existing admin.
		$db = FreePBX::Database();
		$count = (int) $db->query("SELECT COUNT(`username`) FROM `ampusers`")->fetchColumn();
		if ($count !== 0) {
			throw new \Exception("Tried to add an admin user, but some users ($count) already exist.");
		}

		$sth = $db->prepare("INSERT INTO `ampusers` (`username`, `password_sha1`, `sections`) VALUES ( ?, ?, '*')");

		$sth->execute(array($settings['username'], sha1($settings['password'])));

		// TODO: REMOVE IN FREEPBX 14 - ARI is deprecated as of FreePBX 12
		// set ari password
		$freepbx_conf = FreePBX::Freepbx_conf();
		if ($freepbx_conf->conf_setting_exists('ARI_ADMIN_USERNAME') && $freepbx_conf->conf_setting_exists('ARI_ADMIN_PASSWORD')) {
			$freepbx_conf->set_conf_values( array('ARI_ADMIN_USERNAME' => $settings['username'], 'ARI_ADMIN_PASSWORD' => $settings['password']), true);
		}
		//set email address
		$cm =& cronmanager::create($db);
		$cm->save_email($settings['email']);
	}
}

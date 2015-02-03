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
	public function isComplete($type = "auth") {
		$complete = $this->getConfig("completed");
		if ($type == "noauth") {
			// We only care about framework if we're unauthed
			if (isset($complete['framework'])) {
				return true;
			} else {
				return false;
			}
		}
		// Otherwise....
		$pending = $this->getPendingModules();

		if ($pending) {
			return false;
		} else {
			return true;
		}
	}

	// Which modules have pending OOBE pages to show?
	public function getPendingModules($type = "auth") {

		if ($type == "noauth") {
			$all = array("framework" => "framework");
		} else {
			$all = $this->getOOBEModules();
		}

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
			$complete = array($mod => $mod);
		} else {
			$complete[$mod] = $mod;
		}

		$this->setConfig("completed", $complete);
	}

	// Which modules are providing OOBE pages?
	public function getOOBEModules() {
		$retarr = array("framework" => "Core System Setup");
		$mods = module_functions::create();
		$i = $mods->getinfo(false, false, true);
		foreach ($i as $modname => $arr) {
			if (!isset($arr['obe']) && !isset($arr['oobe'])) {
				continue;
			}
			$retarr[$modname] = $arr['name'];
		}

		return $retarr;
	}

	// Call a module's OOBE Hook
	public function runModulesOOBE($modname = false) {
		if (!$modname) {
			throw new \Exception("You didn't ask for a module");
		}

		$bmo = FreePBX::create();
		$mod = ucfirst($modname);

		// Firstly. Is that module already loaded? Pretty unlikely
		// at this stage of play..
		if (!class_exists("\\FreePBX\\modules\\$mod")) {
			// Unsurprisingly, it didn't. Let's load it.
			// We need to manually load it, as the autoloader WON'T.
			$hint = FreePBX::Config()->get("AMPWEBROOT")."/admin/modules/$modname/$mod.class.php";
			$this->injectClass($mod, $hint);
		}

		// Now we can instantiate it
		$obj = FreePBX::$mod();

		// Awesome. Now, what was that oobe function again...?
		$mods = module_functions::create();
		$tmparr = $mods->getinfo($modname);
		if (!isset($tmparr[$modname])) {
			throw new \Exception("Critical error with getinfo on $modname");
		}

		$i = $tmparr[$modname];

		if (isset($i['oobe'])) {
			$func = $i['oobe'];
		} else {
			$func = $i['obe'];
		}

		// Is someone taking crazy pills?
		if (!method_exists($obj, $func)) {
			print "I'm sorry. The module $modname said that it was providing an OOBE, but when I actually asked it for $func, it didn't exist. ";
			print "Please try again.\n";
			$this->completeOOBE($modname);
			return false;
		}

		// Awesome. Off you go then!
		return $obj->$func();
	}

	public function showOOBE($auth = "auth") {
		$pending = $this->getPendingModules($auth);

		// If there aren't any pending modules, return false
		// so config.php knows we didn't do anything, and can
		// continue on.
		if (!$pending) {
			return true;
		}

		$current = key($pending);

		if ($current == "framework") {
			// That's us!
			return $this->createAdminAccount();
		}

		// It's an external OOBE request.
		// This displays the output (if there is any)
		$ret = $this->runModulesOOBE($current);
		// If this returns bool true, then that means it's completed
		// its processing, and didn't output anything. We can mark it
		// as complete, and proceed on to the next one.
		if ($ret === true) {
			$this->completeOOBE($current);
			return $this->showOOBE();
		}

		// Otherwise, don't do anything else.
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

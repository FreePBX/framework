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
		return array("framework", "sysadmin");
	}

	public function showOOBE() {
		$pending = $this->getPendingModules();
		$current = array_shift($pending);
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
			echo "I dunno man\n";
		}
	}

}

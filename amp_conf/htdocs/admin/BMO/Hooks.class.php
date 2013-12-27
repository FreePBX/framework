<?php
// vim: set ai ts=4 sw=4 ft=php:

class Hooks {

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");

		$this->FreePBX = $freepbx;
	}

	public function getBMOHooks() {
		// Find all BMO Modules, query them for GUI and Dialplan hooks.
		
		$this->preloadBMOModules();
		$classes = get_declared_classes();

		// Find all the Classes that say they're BMO Objects
		foreach ($classes as $class) {
			$implements = class_implements($class);
			if (isset($implements['BMO']))
				$retarr[] = $class;
		}
		return $retarr;
	}

	private function preloadBMOModules() {
		// TODO: Find BMO Modules in /var/www/html/admin/modules
		// For the moment, we only care about PJSip
		$tmp = $this->FreePBX->PJSip;
	}
}

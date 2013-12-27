<?php
// vim: set ai ts=4 sw=4 ft=php:

class FileHooks {

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");

		$this->FreePBX = $freepbx;
	}

	public function processFileHooks($active_modules = null) {
		if ($active_modules == null)
			throw new Exception("BMO Doesn't know about modules yet. It needs to be told about them");

		$this->processOldHooks($active_modules);

		$this->processNewHooks();

	}

	private function processOldHooks($active_modules) {
		// Moved from retrieve_conf, and slightly tidied up
		foreach($active_modules as $mod) {
			$classname = $mod."_conf";

			if(class_exists($classname)) {
				if (method_exists($classname, "get_filename")) {

					$module = new $classname;
					$fn = $module->get_filename();

					if (empty($fn)) {
						// This module doesn't want to generate a file at the moment.
						continue;
					}

					// if the module returns an array, it wants to write multiple files
					// ** pinsets is an example of a module that does this
					if (is_array($fn)) {
						foreach($fn as $modconf) {
							$this->FreePBX->WriteConfig->writeConfig($modconf,$module->generateConf($modconf));
						}
					} else {
						if ($module->get_filename() != "") 
							$this->FreePBX->WriteConfig->writeConfig($module->get_filename(), $module->generateConf());
					}
				}
			}
		}
	}

	private function processNewHooks() {
		print "Get hooks, run 'em\n";
	}
}


<?php
// vim: set ai ts=4 sw=4 ft=php:

class FileHooks extends BMO {

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

					// Now we need one of these objects. Some of them support
					// ::create(), some don't.
					if (method_exists($classname, "create")) {
						$module = $classname::create();
					} else {
						$module = new $classname;
					}

					$fn = $module->get_filename();

					if (empty($fn)) {
						// This module doesn't want to generate a file at the moment.
						continue;
					}

					// if the module returns an array, it wants to write multiple files
					// ** pinsets is an example of a module that does this
					if (is_array($fn)) {
						foreach($fn as $modconf) { 
							$this->Performance->Stamp("oldfileHook-".$modconf."_start");
							$this->WriteConfig->writeConfig($modconf,$module->generateConf($modconf));
							$this->Performance->Stamp("oldfileHook-".$modconf."_stop");
						}
					} else {
						if ($module->get_filename() != "") 
							$this->Performance->Stamp("oldfileHook-".$module->get_filename()."_start");
							$this->WriteConfig->writeConfig($module->get_filename(), $module->generateConf());
							$this->Performance->Stamp("oldfileHook-".$module->get_filename()."_stop");
					}
				}
			}
		}
	}

	private function processNewHooks() {
		$hooks = $this->Hooks->getAllHooks();
		foreach ($hooks['ConfigFiles'] as $hook) {
			$this->Performance->Stamp("fileHook-".$hook."_start");
			// This is where we'd hook the output of files, if it was implemented.
			// As no-one wants it yet, I'm not going to bother.
			if (!method_exists($this->$hook, "getConfig"))
				throw new Exception("$hook asked to generate a config file, but, doesn't implement getConfig()");

			$tmpconf = $this->$hook->getConfig();

			// Here we want to hand off $tmpconf to other modules, if they somehow say they want to do something
			// with it. 

			if (!method_exists($this->$hook, "writeConfig"))
				throw new Exception("$hook asked to generate a config file, but, doesn't implement writeConfig()");

			$this->$hook->writeConfig($tmpconf);
			$this->Performance->Stamp("fileHook-".$hook."_stop");
		}
	}
}


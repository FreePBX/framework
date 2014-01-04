<?php
// vim: set ai ts=4 sw=4 ft=php:

class ModulesConf extends BMO {

	private $conf;

	public function __construct($freepbx = null) {

		$this->conf = $this->ConfigFile("modules.conf");
	}

	public function noload($module = null) {

		if ($module == null)
			throw new Exception("Wasn't given a module to noload");

		// Add module(s) to noload.
		$mods = array();

		$current = $this->conf->config->ProcessedConfig;

		if (is_array($module)) {
			foreach($module as $m) {
				if (!in_array($m, $current['modules']['noload']))
					$this->conf->addEntry("modules", "noload=$m");
			}
		} else {
			if (!in_array($module, $current['modules']['noload']))
				$this->conf->addEntry("modules", "noload=$module");
		}
	}

	public function removenoload($module = null) {
		if ($module == null)
			throw new Exception("Wasn't given a module to remove the noload tag from");

		if (is_array($module)) {
			foreach($module as $m) {
				$this->conf->removeEntry("modules", "noload", $m);
			}
		} else {
			$this->conf->removeEntry("modules", "noload", $module);
		}
	}
}


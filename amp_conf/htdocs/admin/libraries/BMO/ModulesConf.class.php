<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class ModulesConf {

	private $conf;
	public $ProcessedConfig;

	public function __construct() {

		$this->conf = \FreePBX::create()->ConfigFile("modules.conf");

		$this->ProcessedConfig =& $this->conf->config->ProcessedConfig;

		// Now, is it empty? We want some defaults..
		if (sizeof($this->ProcessedConfig) == 0 ) {
			$this->conf->addEntry("modules", "autoload=yes");
			$this->conf->addEntry("modules", "preload=pbx_config.so");
			$this->conf->addEntry("modules", "preload=chan_local.so");
			$this->conf->addEntry("modules", "preload=res_mwi_blf.so");
			$this->conf->addEntry("modules", "noload=chan_also.so");
			$this->conf->addEntry("modules", "noload=chan_oss.so");
			$this->conf->addEntry("modules", "noload=app_directory_odbcstorage.so");
			$this->conf->addEntry("modules", "noload=app_voicemail_odbcstorage.so");
		}
	}

	public function noload($module = null) {

		if ($module == null)
			throw new \Exception("Wasn't given a module to noload");

		$current = $this->conf->config->ProcessedConfig['modules'];

		if (is_array($module)) {
			foreach($module as $m) {
				if (!isset($current['noload']) || !is_array($current['noload']) || !in_array($m, $current['noload']))
					$this->conf->addEntry("modules", "noload=$m");
			}
		} else {
			if (!isset($current['noload']) || !is_array($current['noload']) || !in_array($module, $current['noload']))
				$this->conf->addEntry("modules", "noload=$module");
		}
	}

	public function removenoload($module = null) {
		if ($module == null)
			throw new \Exception("Wasn't given a module to remove the noload tag from");

		if (is_array($module)) {
			foreach($module as $m) {
				$this->conf->removeEntry("modules", "noload", $m);
			}
		} else {
			$this->conf->removeEntry("modules", "noload", $module);
		}
	}

	public function preload($module = null) {

		if ($module == null)
			throw new \Exception("Wasn't given a module to preload");

		$current = $this->conf->config->ProcessedConfig['modules'];

		if (is_array($module)) {
			foreach($module as $m) {
				if (!isset($current['preload']) || !is_array($current['preload']) || !in_array($m, $current['preload']))
					$this->conf->addEntry("modules", "preload=$m");
			}
		} else {
			if (!isset($current['preload']) || !is_array($current['preload']) || !in_array($module, $current['preload']))
				$this->conf->addEntry("modules", "preload=$module");
		}
	}

	public function removepreload($module = null) {
		if ($module == null)
			throw new \Exception("Wasn't given a module to remove the preload tag from");

		if (is_array($module)) {
			foreach($module as $m) {
				$this->conf->removeEntry("modules", "preload", $m);
			}
		} else {
			$this->conf->removeEntry("modules", "preload", $module);
		}
	}

	public function load($module = null) {

		if ($module == null)
			throw new Exception("Wasn't given a module to load");

		$current = $this->conf->config->ProcessedConfig['modules'];

		if (is_array($module)) {
			foreach($module as $m) {
				if (!isset($current['load']) || !is_array($current['load']) || !in_array($m, $current['load'])) {
					$this->conf->addEntry("modules", "load=$m");
				}
			}
		} else {
			if (!isset($current['load']) || !is_array($current['load']) || !in_array($module, $current['load'])) {
				$this->conf->addEntry("modules", "load=$module");
			}
		}
	}

	public function removeload($module = null) {
		if ($module == null)
			throw new Exception("Wasn't given a module to remove the load tag from");

		if (is_array($module)) {
			foreach($module as $m) {
				$this->conf->removeEntry("modules", "load", $m);
			}
		} else {
			$this->conf->removeEntry("modules", "load", $module);
		}
	}
}

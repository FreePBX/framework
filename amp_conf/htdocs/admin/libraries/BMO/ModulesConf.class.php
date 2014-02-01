<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX BMO
 * @author    Rob Thomas <rob.thomas@schmoozecom.com>
 * @license   AGPL v3
 */
class ModulesConf {

	private $conf;
	public $ProcessedConfig;

	public function __construct() {

		$this->conf = FreePBX::create()->ConfigFile("modules.conf");

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

	public function preload($module = null) {

		if ($module == null)
			throw new Exception("Wasn't given a module to preload");

		// Add module(s) to preload.
		$mods = array();

		$current = $this->conf->config->ProcessedConfig;

		if (is_array($module)) {
			foreach($module as $m) {
				if (!in_array($m, $current['modules']['preload']))
					$this->conf->addEntry("modules", "preload=$m");
			}
		} else {
			if (!in_array($module, $current['modules']['preload']))
				$this->conf->addEntry("modules", "preload=$module");
		}
	}

	public function removepreload($module = null) {
		if ($module == null)
			throw new Exception("Wasn't given a module to remove the preload tag from");

		if (is_array($module)) {
			foreach($module as $m) {
				$this->conf->removeEntry("modules", "preload", $m);
			}
		} else {
			$this->conf->removeEntry("modules", "preload", $module);
		}
	}
}


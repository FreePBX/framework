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
// This is a very basic interface to the existing 'module_functions' class.

class Modules {

	public $active_modules;

	public function __construct($freepbx = null) {

		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");
		$this->FreePBX = $freepbx;

		if (!class_exists('module_functions')) {
			throw new Exception("module_functions class missing? Bootstrap not run?");
		}

		$this->modclass = module_functions::create();
		$this->getActiveModules();
	}

	public function getActiveModules() {

		// If session isn't authenticated, we don't care about modules.
		if (!defined('FREEPBX_IS_AUTH') || !FREEPBX_IS_AUTH) {
			return array();
		}

		$this->active_modules = $this->modclass->getinfo(false, MODULE_STATUS_ENABLED);

		return $this->active_modules;
	}

	/**
	 * Return the BMO Class name for the page that has been requested
	 *
	 * This is used for GUI Hooks - for example, when a page is requested like
	 * 'config.php?display=pjsip&action=foo&other=wibble', this returns the class
	 * that generated the display 'pjsip'.
	 *
	 * @param $page Page name
	 * @return bool|string Class name, or false
	 */
	public function getClassName($page = null) {
		if ($page == null)
			throw new Exception("I can't find a module for a page that doesn't exist");

		// Search through all active modules..
		foreach ($this->active_modules as $key => $mod) {
			// ..and if we know about the menuitem that we've been asked..
			if (isset($mod['menuitems']) && is_array($mod['menuitems']) && isset($mod['menuitems'][$page])) {
				// ..is it a BMO Module?
				$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/";
				if (file_exists($path.$key."/".ucfirst($key).".class.php")) {
					return ucfirst($key);
				}
			}
		}
		return false;
	}

	/**
	 * Pass-through to modules_class->getinfo
	 */
	public function getInfo($modname) {
		return $this->modclass->getinfo($modname);
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * This defines the BMO Interfaces for FreePBX Modules to use
 */
include 'BMO.interface.php';

/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright 2013 Rob Thomas <rob.thomas@schmoozecom.com>
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

class FreePBX {

	// Static Object used for self-referencing. 
	private static $obj;

	/**
	 * Constructor
	 * 
	 * This Preloads the default libraries into the class. There should be
	 * very few of these, as they will normally get instantiated when
	 * they're asked for the first time.
	 * Currently this is only "Config". 
	 * 
	 * @return void     
	 * @access public   
	 */
	public function __construct() {
		$libraries = $this->listDefaultLibraries();

		$oldIncludePath = get_include_path();
		set_include_path(__DIR__.":".get_include_path());
		foreach ($libraries as $lib) {

			if (class_exists($lib)) 
				throw new Exception("Somehow, the class $lib already exists");

			include "$lib.class.php";
			$this->$lib = new $lib($this);
		}
		set_include_path($oldIncludePath);

		// Ensure the local object is available
		self::$obj = $this;
	}

	/**
	 * Alternative Constructor
	 *
	 * This allows the Current BMO to be referenced from anywhere, without
	 * needing to instantiate a new one. Calling $x = FreePBX::create() will
	 * create a new BMO if one has not already beeen created (unlikely!), or
	 * return a reference to the current one.
	 *
	 * @return object FreePBX BMO Object
	 */
	public static function create() {
		if (!isset(self::$obj))
			self::$obj = new FreePBX();

		return self::$obj;
	}

	/**
	 * PHP Magic __get - runs AutoLoader
	 * 
	 * @param $var Class Name
	 * @return $object New Object
	 * @access public 
	 */
	public function __get($var) {
		return $this->autoLoad($var);
	}

	/**
	 * PHP Magic __call - runs AutoLoader
	 * 
	 * @param $var Class Name
	 * @param $args Any params to be passed to the new object
	 * @return $object New Object
	 * @access public 
	 */
	public function __call($var, $args) {
		return $this->autoLoad($var, $args);
	}

	/**
	 * AutoLoader for BMO.
	 * 
	 * @return object
	 * @access private
	 */
	private function autoLoad() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0)
			throw new Exception("Nothing given to the AutoLoader");

		// If we have TWO arguments, we've been called by __called, if we only have 
		// one we've been called by __get.

		$args = func_get_args();
		$var = $args[0];

		if ($var == "FreePBX")
			throw new Exception("No. You ALREADY HAVE the FreePBX Object. You don't need another one.");

		// Ensure no-one's trying to include something with a path in it.
		if (strpos($var, "/") || strpos($var, ".."))
			throw new Exception("Invalid include given to AutoLoader - $var");

		// Does this exist as a default Library?
		if (file_exists(__DIR__."/$var.class.php")) {

			// If we don't HAVE the library already (eg, we may be __called numerous
			// times..)
			if (!class_exists($var))
				include "$var.class.php";

			// Now, we may have paramters (__call), or we may not..
			if (isset($args[1])) {
				// Currently we're only autoloading with one parameter.
				$this->$var = new $var($this, $args[1][0]);
			} else {
				$this->$var = new $var($this);
			}
			return $this->$var;
		}
		// Extra smarts in here later for loading stuff from modules?

		foreach(array_keys($this->Modules->getActiveModules()) as $module) {
			$path = $this->Config->get_conf_setting('AMPWEBROOT')."/admin/modules";

			if(file_exists($path."/".$module."/$var.class.php")) {
				if(!class_exists($var)) {
					include($path."/".$module."/$var.class.php");
				}

				// Now, we may have paramters (__call), or we may not..
				if (isset($args[1])) {
					// Currently we're only autoloading with one parameter.
					$this->$var = new $var($this, $args[1][0]);
				} else {
					$this->$var = new $var($this);
				}
				return $this->$var;
			} else {
				// print "Couldn't find $path/$module/$var.class.php<br /> \n";
			}
		}

		throw new Exception("Unable to find the Class $var to load");
	}

	/**
	 * Returns the Default Libraries to load
	 * 
	 * @return array
	 * @access private
	 */
	private function listDefaultLibraries() {
		return array("Config","Modules");
	}

	/**
	 * Check for hooks for the current GUI function
	 */

	public function doGUIHooks($thispage = null, &$currentcomponent) {
		if (!$thispage)
			return false;

		if ($hooks = $this->GuiHooks->getHooks($thispage)) {
			if (isset($hooks['hooks'])) {
				foreach ($hooks['hooks'] as $hook) {
					$this->GuiHooks->doHook($hook, $currentcomponent);
				}
			}
		}
	}

	/**
	 * Check for hooks in the current Dialplan function
	 */

	public function doDialplanHooks($request = null) {
		if (!$request)
			return false;

		return false;
	}
}

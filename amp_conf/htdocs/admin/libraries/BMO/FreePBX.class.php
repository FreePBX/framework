<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * This defines the BMO Interfaces for FreePBX Modules to use
 */
include 'BMO.interface.php';

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

class FreePBX extends FreePBX_Helpers {

	// Static Object used for self-referencing. 
	private static $obj;
	public static $conf;

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
	public function __construct(&$conf = null) {
		//TODO: load this another way
		global $astman;
		$libraries = $this->listDefaultLibraries();

		self::$conf = $conf;

		$oldIncludePath = get_include_path();
		set_include_path(__DIR__.":".get_include_path());
		foreach ($libraries as $lib) {

			if (class_exists($lib)) {
				throw new Exception("Somehow, the class $lib already exists. Are you trying to 'new' something?");
			} else {
				include "$lib.class.php";
			}
			$this->$lib = new $lib($this);
		}
		set_include_path($oldIncludePath);
		$this->astman = $astman;
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
	 * Returns the Default Libraries to load
	 * 
	 * @return array
	 * @access private
	 */
	private function listDefaultLibraries() {
		return array("Config","Modules");
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

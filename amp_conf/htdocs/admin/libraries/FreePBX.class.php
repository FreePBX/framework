<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is FreePBX Big Module Object.
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
 */

class FreePBX {
	public function __construct() {
		// Preload the default libraries into the class. There should be
		// very few of these, as they will normally get instantiated when
		// they're asked for the first time.
		//
		// Currently this is only "Config". 
		$libraries = $this->listDefaultLibraries();

		$oldIncludePath = get_include_path();
		set_include_path(__DIR__.":".get_include_path());
		foreach ($libraries as $lib) {

			if (class_exists($lib)) 
				throw new Exception("Somehow, the class $lib already exists");

			include "$lib.class.php";
			$this->$lib = new $lib($this);
		}
		// set_include_path($oldIncludePath);
	}

	public function __get($var) {
		return $this->autoLoad($var);
	}

	private function autoLoad() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0)
			throw new Exception("Nothing given to the AutoLoader");

		$args = func_get_args();
		$var = $args[0];
		if (strpos($var, "/") || strpos($var, ".."))
			throw new Exception("Invalid include given to AutoLoader - $var");

		// Does this exist as a default Library?
		if (file_exists(__DIR__."/$var.class.php")) {
			include "$var.class.php";
			$this->$var = new $var($this);
			return $this->$var;
		}
		throw new Exception("Unable to find the Class $var to load");
	}

	private function listDefaultLibraries() {
		return array("Config");
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is the FreePBX BMO Autoloading helper.
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

/**
 * DB_Helper catches the FreePBX object, and provides autoloading
 *
 * This is for use with FreePBX's BMO.
 */
class Self_Helper extends DB_Helper {

	public function __construct($freepbx = null) {
		if (!is_object($freepbx))
			throw new Exception("Need to be instantiated with a FreePBX Object");

		$this->FreePBX = FreePBX::create();
	}

	/**
	 * PHP Magic __get - runs AutoLoader if BMO doesn't already have the object.
	 * 
	 * @param $var Class Name
	 * @return $object New Object
	 * @access public 
	 */
	public function __get($var) {
		// Does the BMO know about this object already?
		if (isset(FreePBX::create()->$var)) {
			$this->$var = FreePBX::create()->$var;
			return $this->$var;
		}

		return $this->autoLoad($var);
	}

	/**
	 * PHP Magic __call - runs AutoLoader
	 *
	 * Note that this doesn't cache the object to BMO::$obj, just to
	 * $this->$obj
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
	 * This implements a half-arsed spl_autoload that ignore PSR1 and PSR4. I am
	 * admitting that at the start so no-one gets on my case about it. 
	 *
	 * However, as we're having no end of issues with PHP Autoloading things properly
	 * (as of PHP 5.3.3, which is our minimum version at this point in time), this will
	 * do in the interim.
	 *
	 * This tries to load the BMO Object called. It looks first in the BMO Library
	 * dir, which is assumed to be the same directory as this file. It then grabs
	 * a list of all active modules, and looks through them for the class requested.
	 *
	 * If it doesn't find it, it'll throw an exception telling you why.
	 *
	 * @return object
	 * @access private
	 */
	private function autoLoad() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0)
			throw new Exception("Nothing given to the AutoLoader");

		// If we have TWO arguments, we've been called by __call, if we only have 
		// one we've been called by __get.

		$args = func_get_args();
		$var = $args[0];

		if ($var == "FreePBX")
			throw new Exception("No. You ALREADY HAVE the FreePBX Object. You don't need another one.");

		// Ensure no-one's trying to include something with a path in it.
		if (strpos($var, "/") || strpos($var, ".."))
			throw new Exception("Invalid include given to AutoLoader - $var");

		// This will throw an Exception if it can't find the class.
		$this->loadObject($var);

		// Now, we may have paramters (__call), or we may not..
		if (isset($args[1]) && isset($args[1][0])) {
			// We do. We were __call'ed. Sanity check
			if (isset($args[1][1])) {
				throw new Exception("Multiple params to autoload (__call) not supported. Don't do that. Or re-write this.");
			}
			$this->$var = new $var($this, $args[1][0]);
		} else {
			$this->$var = new $var($this);
			FreePBX::create()->$var = $this->$var;

		}
		return $this->$var;
	}

	/** 
	 * Find the file for the object $objname
	 */
	private function loadObject($objname, $hint = null) {
		// If it already exists, we're fine.
		if (class_exists($objname)) {
			return true;
		}

		// This is the file we loaded the class from, for debugging later.
		$loaded = false;

		if ($hint) {
			if (!file_exists($hint)) {
				throw new Exception("I was asked to load $objname, with a hint of $hint, and it didn't exist");
			} else {
				$try = $hint;
			}
		} else {
			// Does this exist as a default Library inside BMO?
			$try = __DIR__."/$objname.class.php";
		}

		if (file_exists($try)) {
			include $try;
			$loaded = $try;
		} else {
			// It's a module, hopefully.
			// This is our root to search from
			$path = $this->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/";

			$active_modules = array_keys(FreePBX::create()->Modules->getActiveModules());
			foreach ($active_modules as $module) {
				// Lets try this one..
				//TODO: this needs to look with dirname not from webroot
				$try = $path.$module."/$objname.class.php";
				if(file_exists($try)) {
					include $try;
					$loaded = $try;
					break;
				}
			}
		}

		// Right, after all of this we should now have our object ready to create.
		if (!class_exists($objname)) {
			// Bad things have happened.
			if (!$loaded) {
				throw new Exception("I was unable to locate the BMO Class $objname. I looked everywhere for $objname.class.php");
			}

			// We loaded a file that claimed to represent that class, but didn't.
			throw new Exception("I loaded the file $try, but it doesn't define the class $objname");
		}

		return true;
	}

	/** Implement hints for autoloading */

	public function injectClass($classname, $hint = null) {
		$this->loadObject($classname, $hint);
		$this->autoLoad($classname);
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * This defines the BMO Interfaces for FreePBX Modules to use
 */
include 'BMO.interface.php';

/**
* Backwards compatibility for FreePBX 12 non namespaced
*/
interface BMO extends FreePBX\BMO {};
class FreePBX_Helpers extends FreePBX\FreePBX_Helpers {};
class Request_Helper extends FreePBX\Request_Helper {};
class DB_Helper extends FreePBX\DB_Helper {};
class Freepbx_conf extends FreePBX\Freepbx_conf {};

/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
class FreePBX extends FreePBX\FreePBX_Helpers {

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
		if(empty($conf)) {
			global $amp_conf;
			if(empty($amp_conf)) {
				throw new Exception("conf was empty!");
			}
			$conf = $amp_conf;
		}
		//TODO: load this another way
		global $astman;
		$libraries = $this->listDefaultLibraries();

		self::$conf = $conf;

        // Ensure the local object is available
        // but ensure this BEFORE we preload for any classes outside the scope of BMO
        self::$obj = $this;

		$oldIncludePath = get_include_path();
		set_include_path(__DIR__.":".get_include_path());
		foreach ($libraries as $lib) {

			if (class_exists($lib)) {
				throw new Exception("Somehow, the class $lib already exists. Are you trying to 'new' something?");
			} else {
				include "$lib.class.php";
			}
			$class = '\\FreePBX\\'.$lib;
			$this->$lib = new $class($this);
		}
		set_include_path($oldIncludePath);
		$this->astman = $astman;
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
		if (!isset(self::$obj)) {
			self::$obj = new FreePBX();
        }

		return self::$obj;
	}

	/**
	 * Shortcut to create
	 *
	 * Simplifies access to BMO by not requiring create() when a module is
	 * requested, by assuming that any static request to the FreePBX parent
	 * object is going to only be for a module.
	 * @return object FreePBX BMO Object
	 */

	static public function __callStatic($name, $var) {
		return FreePBX::create()->$name;
	}
	/**
	 * Returns the Default Libraries to load
	 *
	 * @return array
	 * @access private
	 */
	private function listDefaultLibraries() {
		return array("Database","Modules");
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

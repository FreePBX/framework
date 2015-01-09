<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * This is a very basic interface to the existing 'module_functions' class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

class Modules {

	public $active_modules;
	private $moduleMethods = array();

	public function __construct($freepbx = null) {

		if ($freepbx == null) {
			throw new Exception("Need to be instantiated with a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		if (!class_exists('module_functions')) {
			throw new Exception("module_functions class missing? Bootstrap not run?");
		}

		$this->modclass = module_functions::create();
		$this->getActiveModules();
	}

	/**
	 * Get all active modules
	 */
	public function getActiveModules() {
		// If session isn't authenticated, we don't care about modules.
		if (!defined('FREEPBX_IS_AUTH') || !FREEPBX_IS_AUTH) {
			return array();
		}
		if(empty($this->active_modules)) {
			$this->active_modules = $this->modclass->getinfo(false, MODULE_STATUS_ENABLED);
		}
		return $this->active_modules;
	}

	/**
	* Get Signature
	* @param string $modulename The raw module name
	* @param bool $cached     Get cached data or update the signature
	*/
	public function getSignature($modulename,$cached=true) {
		return $this->modclass->getSignature($modulename,$cached);
	}

	/**
	 * String invalid characters from a class name
	 * @param {string} $module The raw mdoule name.
	 */
	public function cleanModuleName($module) {
		$module = str_replace("-","dash",$module);
		return $module;
	}

	/**
	 * Check to see if said module has method and is publically callable
	 * @param {string} $module The raw module name
	 * @param {string} $method The method name
	 */
	public function moduleHasMethod($module, $method) {
		$module = ucfirst(strtolower($module));
		if(!empty($this->moduleMethods[$module]) && in_array($method, $this->moduleMethods[$module])) {
			return true;
		}
		$amods = array();
		foreach(array_keys($this->active_modules) as $mod) {
			$amods[] = $this->cleanModuleName($mod);
		}
		if(in_array($module,$amods)) {
			try {
				$rc = new \ReflectionClass($this->FreePBX->$module);
				if($rc->hasMethod($method)) {
					$reflection = new \ReflectionMethod($this->FreePBX->$module, $method);
					if ($reflection->isPublic()) {
						$this->moduleMethods[$module][] = $method;
						return true;
					}
				}
			} catch(\Exception $e) {}
		}
		return false;
	}

	/**
	 * Get all modules that have said method
	 * @param {string} $method The method name to look for
	 */
	public function getModulesByMethod($method) {
		$this->getActiveModules();
		$amods = array();
		foreach(array_keys($this->active_modules) as $mod) {
			$amods[] = $this->cleanModuleName($mod);
		}
		$methods = array();
		foreach($amods as $module) {
			if($this->moduleHasMethod($module,$method)) {
				$methods[] = $module;
			}
		}
		return $methods;
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
		$mods = $this->getActiveModules();
		if(empty($mods)) {return false;}
		foreach ($mods as $key => $mod) {
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

	/**
	 * Boolean return for checking a module's status
	 * @param {string} $modname Module Raw Name
	 * @param {constant} $status  Integer/Constant, status to compare to
	 */
	public function checkStatus($modname,$status=MODULE_STATUS_ENABLED) {
		$modinfo = $this->getInfo($modname);
		if(!empty($modinfo[$modname]) && $modinfo[$modname]['status'] == $status) {
			return true;
		} else {
			return false;
		}
	}
}

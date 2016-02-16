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
namespace FreePBX;
class Modules {

	public $active_modules;
	private $moduleMethods = array();

	// Cache for XML objects
	private $modulexml = array();

	public function __construct($freepbx = null) {

		if ($freepbx == null) {
			throw new \Exception("Need to be instantiated with a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		if (!class_exists('module_functions')) {
			throw new \Exception("module_functions class missing? Bootstrap not run?");
		}

		$this->modclass = \module_functions::create();
		//$this->getActiveModules();
	}

	/**
	 * Get all active modules
	 */
	public function getActiveModules() {
		// If session isn't authenticated, we don't care about modules.
		if (!defined('FREEPBX_IS_AUTH') || !FREEPBX_IS_AUTH) {
			$modules = $this->modclass->getinfo(false,MODULE_STATUS_ENABLED);
			$final = array();
			foreach($modules as $rawname => $data) {
				if(isset($data['authentication']) && $data['authentication'] == 'false') {
					$final[$rawname] = $data;
				}
			}
			$this->active_modules = $final;
		} else {
			if(empty($this->active_modules)) {
				$this->active_modules = $this->modclass->getinfo(false, MODULE_STATUS_ENABLED);
			}
		}

		return $this->active_modules;
	}

	/**
	 * Get destinations of every module
	 * This function might be slow, but it works from within bmo
	 * @return array Array of destinations
	 */
	public function getDestinations() {
		$this->loadAllFunctionsInc();
		$modules = $this->getActiveModules();
		$destinations = array();
		foreach($modules as $rawname => $data) {
			$funct = strtolower($rawname.'_destinations');
			$funct2 = strtolower($rawname.'_getdestinfo');
			if (function_exists($funct)) {
				\modgettext::push_textdomain($rawname);
				$index = ''; //used in certain situations but not here
				$destArray = $funct($index); //returns an array with 'destination' and 'description', and optionally 'category'
				\modgettext::pop_textdomain();
				if(!empty($destArray)) {
					foreach($destArray as $dest) {
						$destinations[$dest['destination']] = $dest;
						$destinations[$dest['destination']]['module'] = $rawname;
						$destinations[$dest['destination']]['name'] = $data['name'];
						if(function_exists($funct2)) {
							$info = $funct2($dest['destination']);
							$destinations[$dest['destination']]['edit_url'] = $info['edit_url'];
						}
					}
				}
			}
		}
		return $destinations;
	}

	/**
	 * Load all Function.inc.php files into FreePBX
	 */
	public function loadAllFunctionsInc() {
		$path = $this->FreePBX->Config->get("AMPWEBROOT");
		$modules = $this->getActiveModules();
		foreach($modules as $rawname => $data) {
			$ifiles = get_included_files();
			$relative = $rawname."/functions.inc.php";
			$absolute = $path."/admin/modules/".$relative;
			$needs_zend = isset($data['depends']['phpcomponent']) && stristr($data['depends']['phpcomponent'], 'zend');
			$licFileExists = glob ('/etc/schmooze/license-*.zl');
			$complete_zend = (!function_exists('zend_loader_install_license') || empty($licFileExists));
			if(file_exists($absolute)) {
				if ($needs_zend && class_exists('\Schmooze\Zend',false) && \Schmooze\Zend::fileIsLicensed($absolute) && $complete_zend) {
					continue;
				}
				$include = true;
				foreach($ifiles as $file) {
					if(strpos($file, $relative) !== false) {
						$include = false;
						break;
					}
				}
				if($include) {
					include $absolute;
				}
			}
		}
	}

	/**
	 * Try to load a functions.inc.php if not previously loaded
	 * @param  string $module The module rawname
	 */
	public function loadFunctionsInc($module) {
		if($this->checkStatus($module)) {
			$path = $this->FreePBX->Config->get("AMPWEBROOT");
			$ifiles = get_included_files();
			$relative = $module."/functions.inc.php";
			$absolute = $path."/admin/modules/".$relative;
			$data = \FreePBX::Modules()->getInfo($module);
			$needs_zend = isset($data[$module]['depends']['phpcomponent']) && stristr($data[$module]['depends']['phpcomponent'], 'zend');
			$licFileExists = glob ('/etc/schmooze/license-*.zl');
			$complete_zend = (!function_exists('zend_loader_install_license') || empty($licFileExists));
			if(file_exists($absolute)) {
				if ($needs_zend && class_exists('\Schmooze\Zend',false) && \Schmooze\Zend::fileIsLicensed($absolute) && $complete_zend) {
					return false;
				}
				$include = true;
				foreach($ifiles as $file) {
					if(strpos($file, $relative) !== false) {
						$include = false;
						break;
					}
				}
				if($include) {
					include $absolute;
				}
			}
		}
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
		$module = ucfirst(strtolower($module));
		return $module;
	}

	/**
	 * Check to see if said module has method and is publicly callable
	 * @param {string} $module The raw module name
	 * @param {string} $method The method name
	 */
	public function moduleHasMethod($module, $method) {
		$this->getActiveModules();
		$module = ucfirst(strtolower($module));
		if(!empty($this->moduleMethods[$module]) && in_array($method, $this->moduleMethods[$module])) {
			return true;
		}
		$amods = array();
		if(is_array($this->active_modules)) {
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
		if(is_array($this->active_modules)) {
			foreach(array_keys($this->active_modules) as $mod) {
				$amods[] = $this->cleanModuleName($mod);
			}
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
			throw new \Exception("I can't find a module for a page that doesn't exist");

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

	/**
	 * Parse a modules XML from filesystem
	 *
	 * This function loads a modules xml file from the filesystem, and return
	 * a simpleXML object.  This explicitly does NOT care about the active or
	 * inactive state of the module. It also caches the object, so this can
	 * be called multiple times without re-reading and re-generating the XML.
	 *
	 * @param (string) $modname Raw module name
	 * @returns (object) SimpleXML Object.
	 *
	 * @throws Exception if module does not exist
	 * @throws Exception if module xml file is not parseable
	 */

	public function getXML($modname = false) {
		if (!$modname) {
			throw new \Exception("No module name given");
		}

		// Do we have this in the cache?
		if (!isset($this->modulexml[$modname])) {
			// We haven't. Load it up!
			$moddir = $this->FreePBX->Config()->get("AMPWEBROOT")."/admin/modules/$modname";
			if (!is_dir($moddir)) {
				throw new \Exception("$moddir is not a directory");
			}

			$xmlfile = "$moddir/module.xml";
			if (!file_exists($xmlfile)) {
				throw new \Exception("$xmlfile does not exist");
			}

			$this->modulexml[$modname] = simplexml_load_file($xmlfile);
		}

		// Return it
		return $this->modulexml[$modname];
	}
}

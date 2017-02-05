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

	private static $count = 0;
	public $active_modules;
	private $moduleMethods = array();
	private $validLicense = null;

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

		self::$count++;
		if(self::$count > 1) {
			throw new \Exception("The 'Modules' class has loaded more than once! This is a serious error!");
		}
	}

	/**
	 * Get all active modules
	 * @method getActiveModules
	 * @param  boolean          $cached Whether to cache the results.
	 * @return array                   array of active modules
	 */
	public function getActiveModules($cached=true) {
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
			if(empty($this->active_modules) || !$cached) {
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
		$modules = $this->getActiveModules(false);
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
		$modules = $this->getActiveModules(false);
		foreach($modules as $rawname => $data) {
			$ifiles = get_included_files();
			$relative = $rawname."/functions.inc.php";
			$absolute = $path."/admin/modules/".$relative;
			$needs_zend = isset($data['depends']['phpcomponent']) && stristr($data['depends']['phpcomponent'], 'zend');
			if(file_exists($absolute)) {
				if ($needs_zend && class_exists('\Schmooze\Zend',false) && \Schmooze\Zend::fileIsLicensed($absolute) && !$this->loadLicensedFileCheck()) {
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
			if(file_exists($absolute)) {
				if ($needs_zend && class_exists('\Schmooze\Zend',false) && \Schmooze\Zend::fileIsLicensed($absolute) && !$this->loadLicensedFileCheck()) {
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
	 * Check to make sure we have a valid license on the system if it's needed
	 * This is so that commercial modules wont crash the system
	 * @return boolean True if we can load the file, false otherwise
	 */
	public function loadLicensedFileCheck() {
		if(!is_null($this->validLicense)) {
			return $this->validLicense;
		}
		$licFileExists = glob ('/etc/schmooze/license-*.zl');
		if(!function_exists('zend_loader_install_license') || empty($licFileExists)) {
			$this->validLicense = false;
			return false;
		}

		$path = $this->FreePBX->Config->get("AMPWEBROOT");
		$sclass = $path."/admin/modules/sysadmin/functions.inc/Schmooze.class.php";
		if (file_exists($sclass) && !class_exists('\Schmooze\Zend',false)) {
			$this->validLicense = false;
			include $sclass;
		}
		if (!class_exists('\Schmooze\Zend')) {
			// Schmooze class is broken somehow. Accidentally deleted, possibly?
			$this->validLicense = false;
			return false;
		}
		if (!\Schmooze\Zend::hasValidLic()) {
			$this->validLicense = false;
			return false;
		}
		$this->validLicense = true;
		return true;
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
		$this->getActiveModules(false);
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
	 * Get All Modules by module status
	 * @method getModulesByStatus
	 * @param  mixed            $status Can be: false, single status or arry of statuses
	 * @return array                     Array of modules
	 */
	public function getModulesByStatus($status=false) {
		return $this->modclass->getinfo(false, $status);
	}

	/**
	 * Get all modules that have said method
	 * @param {string} $method The method name to look for
	 */
	public function getModulesByMethod($method) {
		$this->getActiveModules(false);
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
		$mods = $this->getActiveModules(false);
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
	public function getInfo($modname=false, $status = false, $forceload = false) {
		return $this->modclass->getinfo($modname, $status, $forceload);
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

	/**
	 * Get the CACHED data from the last online check
	 *
	 * This will never request an update, no matter what.
	 *
	 * @return array
	 */
	public function getCachedOnlineData() {
		$modules = $this->modclass->getonlinexml(false, false, true);
		// Also grab the timestamp for when this was last updated
		$res = \FreePBX::Database()->query("select `time` FROM `module_xml` WHERE id = 'previous'")->fetchAll(\PDO::FETCH_ASSOC);
		if (!isset($res[0])) {
			$time = 0;
		} else {
			$time = $res[0]['time'];
		}
		$time = new \DateTime("@$time");
		return [ "timestamp" => $time, "modules" => $modules ];
	}

	public function getUpgradeableModules($onlinemodules) {
		// Our current modules on the filesystem
		//
		// Don't check for disabled modules. Refer to
		//    http://issues.freepbx.org/browse/FREEPBX-8380
		//    http://issues.freepbx.org/browse/FREEPBX-8628
		$local = $this->getInfo(false, [\MODULE_STATUS_ENABLED, \MODULE_STATUS_NEEDUPGRADE, \MODULE_STATUS_BROKEN], true);
		$upgrades = [];

		// Loop through our current ones and see if new ones are available online
		foreach ($local as $name => $cur) {
			if (isset($onlinemodules[$name])) {
				$new = $onlinemodules[$name];
				// If our current version is lower than the new version
				if (version_compare_freepbx($cur['version'], $new['version']) < 0) {
					// It's upgradeable.
					$upgrades[$name] = [
						'name' => $name,
						'local_version' => $cur['version'],
						'online_version' => $new['version'],
						'descr_name' => $new['name'],
					];
				}
			}
		}
		return $upgrades;
	}
}

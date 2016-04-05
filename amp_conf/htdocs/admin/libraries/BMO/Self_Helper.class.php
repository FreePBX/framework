<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * DB_Helper catches the FreePBX object, and provides autoloading
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Self_Helper extends DB_Helper {

	private $moduleNamespace = '\\FreePBX\\Modules\\';
	private $freepbxNamespace = '\\FreePBX\\';

	public function __construct($freepbx = null) {
		if (!is_object($freepbx)) {
			throw new \Exception("Need to be instantiated with a FreePBX Object");
		}
		$this->FreePBX = \FreePBX::create();
	}

	/**
	 * PHP Magic __get - runs AutoLoader if BMO doesn't already have the object.
	 *
	 * @param $var Class Name
	 * @return object New Object
	 */
	public function __get($var) {
		// Does the BMO know about this object already?
		if (isset(\FreePBX::create()->$var)) {
			$this->$var = \FreePBX::create()->$var;
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
	 * @return object New Object
	 */
	public function __call($var, $args) {
		return $this->autoLoad($var, $args);
	}

	/**
	* Used to inject a new class into the BMO construct
	* @param {string} $classname The class name
	* @param {string} $hint Where to find the class (directory)
	*/
	public function injectClass($classname, $hint = null) {
		$this->loadObject($classname, $hint);
		$this->autoLoad($classname);
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
	 * @return object The object as an object!
	 */
	private function autoLoad() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0) {
			throw new \Exception("Nothing given to the AutoLoader");
		}

		// If we have TWO arguments, we've been called by __call, if we only have
		// one we've been called by __get.

		$args = func_get_args();
		$var = $args[0];

		if ($var == "FreePBX") {
			throw new \Exception("No. You ALREADY HAVE the FreePBX Object. You don't need another one.");
		}

		// Ensure no-one's trying to include something with a path in it.
		if (strpos($var, "/") || strpos($var, "..")) {
			throw new \Exception("Invalid include given to AutoLoader - $var");
		}

		// This will throw an Exception if it can't find the class.
		$this->loadObject($var);
		$var = $this->Modules->cleanModuleName($var);

		$class = class_exists($this->moduleNamespace.$var,false) ? $this->moduleNamespace.$var : (class_exists($this->freepbxNamespace.$var,false) ? $this->freepbxNamespace.$var : $var);
		// Now, we may have paramters (__call), or we may not..
		if (isset($args[1]) && isset($args[1][0])) {
			// We do. We were __call'ed. Sanity check
			if (isset($args[1][1])) {
				throw new \Exception(_("Multiple params to autoload (__call) not supported. Don't do that. Or re-write this."));
			}
			if (class_exists($class,false)) {
				$this->$var = new $class($this, $args[1][0]);
			} else {
				throw new \Exception(sprintf(_("Unable to locate the FreePBX BMO Class '%s'"),$class));
			}
		} else {
			if (class_exists($class,false)) {

				if($var[0] != strtoupper($var[0])) {
					throw new \Exception(sprintf(_("BMO Objects must have their first letter capitalized. You provided %s"),$var));
				}
				$this->$var = new $class($this);
			} else {
				throw new \Exception(sprintf(_("Unable to locate the FreePBX BMO Class '%s'"),$class));
			}
			\FreePBX::create()->$var = $this->$var;

		}
		return $this->$var;
	}

	/**
	 * Find the file for the object
	 * @param string $objname The Object Name (same as class name, filename)
	 * @param string $hint The location of the Class file
	 * @return bool True if found or throws exception
	 */
	private function loadObject($objname, $hint = null) {
		$objname = str_replace('FreePBX\\modules\\','',$objname);
		$class = class_exists($this->moduleNamespace.$objname,false) ? $this->moduleNamespace.$objname : (class_exists($this->freepbxNamespace.$objname,false) ? $this->freepbxNamespace.$objname : $objname);

		// If it already exists, we're fine.
		if (class_exists($class,false) && $class != "Directory") {
			//do reflection tests for ARI junk, we **dont** want to load ARI
			$class = new \ReflectionClass($class);

			//this is a stop gap, remove in 13 or 14 when ARI is no longer used
			if(!$class->hasMethod('navMenu') && !$class->hasMethod('rank')) {
				return true;
			}
		}

		// This is the file we loaded the class from, for debugging later.
		$loaded = false;

		if ($hint) {
			if (!file_exists($hint)) {
				throw new \Exception(sprintf(_("Attempted to load %s with a hint of %s and it didn't exist"),$objname,$hint));
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
			$objname = $this->Modules->cleanModuleName($objname);
			$path = $this->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/";

			$active_modules = array_keys(\FreePBX::create()->Modules->getActiveModules());
			foreach ($active_modules as $module) {
				// Lets try this one..
				//TODO: this needs to look with dirname not from webroot
				$try = $path.$module."/$objname.class.php";
				if(file_exists($try)) {
					//Now we need to make sure this is not a revoked module!
					try {
						$signature = \FreePBX::Modules()->getSignature($module);
						if(!empty($signature['status'])) {
							$revoked = $signature['status'] & GPG::STATE_REVOKED;
							if($revoked) {
								return false;
							}
						}
					} catch(\Exception $e) {}

					$info = \FreePBX::Modules()->getInfo($module);
					$needs_zend = isset($info[$module]['depends']['phpcomponent']) && stristr($info[$module]['depends']['phpcomponent'], 'zend');
					$licFileExists = glob ('/etc/schmooze/license-*.zl');
					$complete_zend = (!function_exists('zend_loader_install_license') || empty($licFileExists));
					if ($needs_zend && class_exists('\Schmooze\Zend',false) && \Schmooze\Zend::fileIsLicensed($try) && $complete_zend) {
						break;
					}

					include $try;
					$loaded = $try;
					break;
				}
			}
		}

		// Right, after all of this we should now have our object ready to create.
		if (!class_exists($objname,false) && !class_exists($this->moduleNamespace.$objname,false) && !class_exists($this->freepbxNamespace.$objname,false)) {
			// Bad things have happened.
			if (!$loaded) {
				$sobjname = strtolower($objname);
				throw new \Exception(sprintf(_("Unable to locate the FreePBX BMO Class '%s'"),$objname) . sprintf(_("A required module might be disabled or uninstalled. Recommended steps (run from the CLI): 1) fwconsole ma install %s 2) fwconsole ma enable %s"),$sobjname,$sobjname));
				//die_freepbx(sprintf(_("Unable to locate the FreePBX BMO Class '%s'"),$objname), sprintf(_("A required module might be disabled or uninstalled. Recommended steps (run from the CLI): 1) amportal a ma install %s 2) amportal a ma enable %s"),$sobjname,$sobjname));
			}

			// We loaded a file that claimed to represent that class, but didn't.
			throw new \Exception(sprintf(_("Attempted to load %s but it didn't define the class %s"),$try,$objname));
		}

		return true;
	}
}

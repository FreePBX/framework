<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Hooks extends DB_Helper {

	private $hooks;

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new \Exception("Need to be instantiated with a FreePBX Object");

		$this->FreePBX = $freepbx;
	}

	/**
	 * Get all cached hooks
	 */
	public function getAllHooks() {
		$this->hooks = $this->getConfig('hooks');
		if (empty($this->hooks)) {
			$this->hooks = $this->updateBMOHooks();
		}
		return $this->hooks;
	}

	/**
	 * Update all cached hooks
	 */
	public function updateBMOHooks() {
		$this->activemods = $this->FreePBX->Modules->getActiveModules();
		$am = is_array($this->activemods) ? array_keys($this->activemods) : array();
		// Find all BMO Modules, query them for GUI, Dialplan, and configpageinit hooks.
		$this->preloadBMOModules();
		$classes = get_declared_classes();

		// Find all the Classes that say they're BMO Objects
		$bmomodules = array();
		foreach ($classes as $class) {
			$implements = class_implements($class);
			if (isset($implements['BMO']) || isset($implements['FreePBX\BMO']))
				$bmomodules[] = $class;
		}

		$allhooks = array();
		foreach ($bmomodules as $mod) {
			$rawname = strtolower(str_replace("FreePBX\\modules\\","",$mod));
			if(!in_array($rawname,$am)) {
				continue;
			}
			// Find GUI Hooks
			if (method_exists($mod, "myGuiHooks")) {
				$allhooks['GuiHooks'][$mod] = $this->FreePBX->$mod->myGuiHooks();
			}

			// Find Dialplan hooks (eg, called when retrieve_conf is run),
			// to modify the $ext object.
			if (method_exists($mod, "myDialplanHooks")) {
				$allhooks['DialplanHooks'][$mod] = $this->FreePBX->$mod->myDialplanHooks();
			}

			// Find ConfigPageInit hooks (called before the page is displayed,
			// used to catch 'submit' POST/GETs, or as an alternative to guihooks.
			if (method_exists($mod, "myConfigPageInits")) {
				$allhooks['ConfigPageInits'][$mod] = $this->FreePBX->$mod->myConfigPageInits();
			}

			// Discover if the module wants to write to any other files, which
			// is done with genConfig/writeConfig
			if (method_exists($mod, "writeConfig")) {
				$allhooks['ConfigFiles'][] = $mod;
			}
		}

		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/";
		foreach($this->activemods as $module => $data) {
			if(isset($data['hooks'])) {
				if(file_exists($path.$module.'/'.ucfirst($module).'.class.php') && file_exists($path.$module.'/module.xml')) {
						$xml = simplexml_load_file($path.$module.'/module.xml');
						foreach($xml->hooks as $modules) {
							foreach($modules as $m => $methods) {
								$hks = array();
								$namespace = isset($methods->attributes()->namespace) ? (string)$methods->attributes()->namespace : '';
								$priority = isset($methods->attributes()->priority) ? (string)$methods->attributes()->priority : '500';
								$class = isset($methods->attributes()->class) ? (string)$methods->attributes()->class : $m;
								$hookMod = !empty($namespace) ? $namespace . '\\' . $class : $class;
								foreach($methods->method as $method) {
									foreach($method->attributes() as $key => $value) {
										$hks[$key] = (string)$value;
									}
									$hks['method'] = (string)$method;
									$cm = $hks['callingMethod'];
									unset($hks['callingMethod']);
									if(isset($allhooks['ModuleHooks'][$hookMod][$cm][$module][$priority])) {
										while(isset($allhooks['ModuleHooks'][$hookMod][$cm][$module][$priority])) {
											$priority++;
										}
										$allhooks['ModuleHooks'][$hookMod][$cm][$module][$priority] = $hks;
									} else {
										$allhooks['ModuleHooks'][$hookMod][$cm][$module][$priority] = $hks;
									}
								}
							}
						}
				}
			}
		}

		$this->hooks = $allhooks;
		$this->setConfig('hooks',$this->hooks);
		return $allhooks;
	}

	/**
	 * Get priority sorted hook for a class and method
	 * @param  string $class  The class
	 * @param  string $method The method
	 * @return array         Sorted hooks
	 */
	public function returnHooksByClassMethod($class, $method) {
		$callingMethod = $method;
		$callingClass = $class;

		$this->activemods = $this->FreePBX->Modules->getActiveModules();
		$hooks = $this->getAllHooks();
		$return = array();

		$sortedHooks = array();
		if(!empty($hooks['ModuleHooks'][$callingClass]) && !empty($hooks['ModuleHooks'][$callingClass][$callingMethod])) {
			foreach($hooks['ModuleHooks'][$callingClass][$callingMethod] as $module => $hooks) {
				if(isset($this->activemods[$module])) {
					foreach($hooks as $priority => $hook) {
						$hook['module'] = ucfirst(strtolower($module));
						$hook['namespace'] = !empty($hook['namespace']) ? $hook['namespace'] . '\\' : '';
						if(isset($sortedHooks[$priority])) {
							while(isset($sortedHooks[$priority])) {
								$priority++;
							}
							$sortedHooks[$priority] = $hook;
						} else {
							$sortedHooks[$priority] = $hook;
						}
					}
				}
			}
		}
		ksort($sortedHooks);
		return $sortedHooks;
	}

	/**
	 * Call a hook from another method
	 * @param  string $class  The class name
	 * @param  string $method The method name
	 * @param  array $args   Args to pass to the function
	 */
	public function processHooksByClassMethod($class, $method, $args) {
		$sortedHooks = $this->returnHooksByClassMethod($class, $method);
		$return = array();
		if(!empty($sortedHooks)) {
			foreach($sortedHooks as $hook) {
				$module = $hook['module'];
				$namespace = $hook['namespace'];
				if(!class_exists($namespace.$hook['class'])) {
					//its active so lets get BMO to load it
					//basically we are hoping the module itself will load the right class
					//follow FreePBX BMO naming Schema
					try {
						$this->FreePBX->$module;
						if(!class_exists($namespace.$hook['class'])) {
							//Ok we really couln't find it. Give up
							throw new \Exception('Cant find '.$namespace.$hook['class']);
						}
					} catch(\Exception $e) {
						throw new \Exception('Cant find '.$namespace.$hook['class']."::: ".$e->getMessage());
					}
				}
				$meth = $hook['method'];
				//now send the method from that class the data!
				\modgettext::push_textdomain(strtolower($module));
				$return[$module] = call_user_func_array(array($this->FreePBX->$module, $meth), $args);
				\modgettext::pop_textdomain();
			}
		}
		//return the data from that class
		return $return;
	}

	/**
	 * Return all of the hooks sorted as an array
	 * @param integer $level Level of backtrace to do to discover the calling module
	 */
	public function returnHooks($level=1) {
		$this->activemods = $this->FreePBX->Modules->getActiveModules();
		$hooks = $this->getAllHooks();
		$o = debug_backtrace();
		$callingMethod = !empty($o[$level]['function']) ? $o[$level]['function'] : '';
		$callingClass = !empty($o[$level]['class']) ? $o[$level]['class'] : '';

		$return = array();

		$sortedHooks = array();

		if(!empty($hooks['ModuleHooks'][$callingClass]) && !empty($hooks['ModuleHooks'][$callingClass][$callingMethod])) {
			foreach($hooks['ModuleHooks'][$callingClass][$callingMethod] as $module => $hooks) {
				if(isset($this->activemods[$module])) {
					foreach($hooks as $priority => $hook) {
						$hook['module'] = ucfirst(strtolower($module));
						$hook['namespace'] = !empty($hook['namespace']) ? $hook['namespace'] . '\\' : '';
						if(isset($sortedHooks[$priority])) {
							while(isset($sortedHooks[$priority])) {
								$priority++;
							}
							$sortedHooks[$priority] = $hook;
						} else {
							$sortedHooks[$priority] = $hook;
						}
					}
				}
			}
		}
		ksort($sortedHooks);
		return $sortedHooks;
	}

	/**
	 * Process all cached hooks
	 */
	public function processHooks() {
		//get hooks from level "2" backtrace
		$sortedHooks = $this->returnHooks(2);
		$return = array();
		if(!empty($sortedHooks)) {
			foreach($sortedHooks as $hook) {
				$module = $hook['module'];
				$namespace = $hook['namespace'];
				if(!class_exists($namespace.$hook['class'])) {
					//its active so lets get BMO to load it
					//basically we are hoping the module itself will load the right class
					//follow FreePBX BMO naming Schema
					try {
						$this->FreePBX->$module;
						if(!class_exists($namespace.$hook['class'])) {
							//Ok we really couln't find it. Give up
							throw new \Exception('Cant find '.$namespace.$hook['class']);
						}
					} catch(\Exception $e) {
						throw new \Exception('Cant find '.$namespace.$hook['class']."::: ".$e->getMessage());
					}
				}
				$meth = $hook['method'];
				//now send the method from that class the data!
				\modgettext::push_textdomain(strtolower($module));
				$return[$module] = call_user_func_array(array($this->FreePBX->$module, $meth), func_get_args());
				\modgettext::pop_textdomain();
			}
		}
		//return the data from that class
		return $return;
	}

	/**
	 * This finds ALL BMO Style modules on the machine, and preloads them.
	 *
	 * This shouldn't happen on every page load.
	 */
	private function preloadBMOModules() {
		$this->activemods = $this->FreePBX->Modules->getActiveModules();
		foreach(array_keys($this->activemods) as $module) {
			$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/";
			if(file_exists($path.$module.'/'.ucfirst($module).'.class.php')) {
				$ucmodule = ucfirst($module);
				if(!class_exists($ucmodule) || !class_exists("FreePBX\\modules\\".$ucmodule)) {
					try { $this->FreePBX->$ucmodule; } catch (Exception $e) { }
				}
			}
		}
	}
}

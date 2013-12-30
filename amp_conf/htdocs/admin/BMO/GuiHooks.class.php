<?php
// vim: set ai ts=4 sw=4 ft=php:

class GuiHooks {

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");

		$this->FreePBX = $freepbx;
	}

	public function getConfigPageInit($module, $request, $currentcomponent) {
		return false;
	}

	public function getPreDisplay($module, $request) {
		return null;
	}

	public function getPostDisplay($module, $request) {
		return null;
	}

	public function getHooks($currentModule, $pageName = null) {

		$retarr = array();

		$allHooks = $this->FreePBX->Hooks->getAllHooks();
		foreach ($allHooks['GuiHooks'] as $module => $hookArr) {

			foreach ($hookArr as $key => $val) {

				// Check for INTERCEPT Hooks.
				if ($key == 'INTERCEPT') {
					if (is_array($val)) {
						foreach ($val as $page) {
							if ($pageName == $page) {
								$retarr['INTERCEPT'][$module] = $page;
							}
						}
					} else {
						if ($pageName == $val) {
							$retarr['INTERCEPT'][$module] = $val;
						}
					}
				} elseif (!is_string($val)) {
					throw new Exception("Handed unknown stuff by $module");
				}

				// Now check for normal hooks 
				if ($val == $currentModule)
					$retarr['hooks'][] = $module;
			}
		}

		$retarr['oldhooks'] = $this->getOldConfigPageInit();
		return $retarr;
	}

	public function doHook($moduleToCall, &$currentcomponent) {

		// Make sure we actually can load the module
		try {
			$mod = $this->FreePBX->$moduleToCall;
		} catch (Exception $e) {
			// Unable to find the module.
			return false;
		}

		// Now, does the hook actually exist?
		if (!method_exists($moduleToCall, "doGuiHook"))
			throw new Exception("$moduleToCall asked to hook, but $moduleToCall::doGuiHook() doesn't exist");

		// Yay. Do stuff.
		$mod->doGuiHook($currentcomponent);
	}

	public function needsIntercept($module, $filename) {
		$hooks = $this->getHooks($module, $filename);

		if (isset($hooks['INTERCEPT'])) {
			return true;
		}
		return false;
	}

	public function doIntercept($moduleToCall, $filename) {

		$hooks = $this->getHooks($module, $filename);

		if (!isset($hooks['INTERCEPT']))
			return true;

		$output = $this->getOutput($filename);

		foreach ($hooks['INTERCEPT'] as $moduleToCall => $file) {
			// Make sure we actually can load the module
			try {
				$mod = $this->FreePBX->$moduleToCall;
				// Now, does the hook actually exist?
				if (!method_exists($moduleToCall, "doGuiIntercept"))
					throw new Exception("$moduleToCall asked to intercept, but ${moduleToCall}->doGuiIntercept() doesn't exist");

				// Output is being passed as a reference.
				$mod->doGuiIntercept($filename, $output);
			} catch (Exception $e) {
				// Unable to find the module.
				echo "Intercept error from $moduleToCall - ".$e->getMessage()."<br />\n";
			}
		}

		echo $output;
	}


	private function getOutput($filename) {
		ob_start();
		include $filename;
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	private function getOldConfigPageInit($onlymodule = null) {

		$active_modules = $this->FreePBX->Modules->active_modules;

		foreach($active_modules as $key => $module) {
			// If we've been handed a modulename, only return the
			// hooks for that specific module.
			if ($onlymodule == null || $onlymodule == $key) {
				// Does this module have a _configpageinit function?
				$initfuncname = $key . '_configpageinit';
				if (function_exists($initfuncname) ) {
					$configpageinits[] = $initfuncname;
				}

				// Does the module have multiple items?
				if (is_array($module['items'])) {
					foreach($module['items'] as $itemKey => $itemName) {
						// Each item may have a configpageinit, too.
						$initfuncname = $key . '_' . $itemKey . '_configpageinit';
						if (function_exists($initfuncname)) {
							$configpageinits[] = $initfuncname;
						}
					}
				}
			}
		}

		if (isset($configpageinits))
			return $configpageinits;

		// None? This will be awesome, because it means all the old stuff 
		// has been rewritten. However, until then, we'll throw an exception
		// here because that SHOULDN'T BE HAPPENING. 
		//
		// Rob will buy you a beer if you get to remove these lines because it's
		// legitimately being triggered.
		if ($onlymodule == null)
			throw new Exception("No configpageinit's found. This is amazingly unlikely");

		return array();
	}

	public function doConfigPageInits($display = null) {
		if ($display == null)
			throw new Exception("Hooking into the main page is currently not supported. Sorry");

		$bmoHooks = $this->FreePBX->Hooks->getAllHooks();

		if (isset($bmoHooks['ConfigPageInits'])) {
			$class = $this->FreePBX->Modules->getClassName($display);
			$myHooks = $bmoHooks['ConfigPageInits'];
		} else {
			$myHooks = array();
			$class = false;
		}

		$myOldHooks = $this->getOldConfigPageInit();

		// Before we run any others, we want to make sure that THIS MODULE'S hooks are run first.
		// Firstly, do the OLD Hooks.
		$preOldHooks = $this->getOldConfigPageInit($display);
		foreach ($preOldHooks as $hook) {
			// Remove the hook from the ones we're going to run later
			unset($myOldHooks[$hook]);
			// Run it.
			$hook($display);
		}

		// New style module? Here, have your data..
		if ($class) {
			$this->doBMOConfigPage($class, $display);
			unset($myHooks[$class]);
		}

		// Now we've run the modules own stuff, now we can hand the request off to any other
		// modules that want it.

		// Firstly, old style hooks
		foreach ($myOldHooks as $hook) {
			$hook($display);
		}

		// And now the new-style module hooks.
		foreach ($myHooks as $mod => $arr) {
			if (in_array($display, $arr))
				$this->doBMOConfigPage($mod, $display);
		}
	}

	private function doBMOConfigPage($class, $display) {
		$this->FreePBX->$class->doConfigPageInit($display);
	}
}

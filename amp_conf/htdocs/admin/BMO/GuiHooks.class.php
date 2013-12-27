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

			foreach ($hookArr as $key => $arr) {
				// Check for INTERCEPT Hooks.
				if (is_array($arr) && $key == 'INTERCEPT') {
					foreach ($arr as $page) {
						if ($pageName == $page) {
							$retarr['INTERCEPT'][$module] = $page;
						}
					}
				} elseif (!is_string($arr)) {
					throw new Exception("Handed unknown stuff by $module");
				}

				// Now check for normal hooks 
				if ($arr == $currentModule)
					$retarr['hooks'][] = $module;
			}
		}
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
				$mod->doGuiIntercept($output);
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

}

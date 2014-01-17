<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * AJAX Handler
 *
 * Proof of concept
 */
class Ajax extends FreePBX_Helpers {

	public $settings = array( "authenticate" => true, "allowremote" => false );

	public function doRequest($module = null, $command = null) {
		if (!$module || !$command) {
			throw new Exception("Module or Command were null. Check your code.");
		}

		if (class_exists(ucfirst($module))) {
			throw new Exception("The class $module already existed. Ajax MUST load it, for security reasons");
		}

		// Is someone trying to be tricky with filenames?
		if (strpos($module, ".") !== false) {
			throw new Exception("Module requested invalid");
		}

		$ucMod = ucfirst($module);
		// OK, it doesn't exist. Let's see if it exists.
		$file = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/$module/$ucMod.class.php";
		
		// Note, that Self_Helper will throw an exception if the file doesn't exist, or if it does
		// exist but doesn't define the class.
		$this->injectClass($ucMod, $file);

		$thisModule = $this->$ucMod;
		if (!method_exists($thisModule, "ajaxRequest")) {
			$this->ajaxError(404, 'ajaxRequest not found');
		}

		$thisModule->ajaxRequest($command, $this->settings);

		if ($this->settings['allowremote']) {
			// You don't want to do this, honest.
			header('Access-Control-Allow-Origin: *');
		}

		if ($this->settings['authenticate']) {
			// TODO: Everything.
		}

		if (!method_exists($thisModule, "ajaxHandler")) {
			$this->ajaxError(404, 'ajaxHandler not found');
		}

	}


	public function ajaxError($errnum, $text = 'Unknown error') {
		header("HTTP/1.0 $errnum $text");
		print json_encode(array("error" => $text));
		exit;
	}

}




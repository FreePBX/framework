<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
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
		// Is it the hardcoded Framework module?
		if ($module == "framework") {
			$file = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/libraries/BMO/Framework.class.php";
			$ucMod = "Framework";
		} else {
			$ucMod = ucfirst($module);
			$file = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT')."/admin/modules/$module/$ucMod.class.php";
		}
		
		// Note, that Self_Helper will throw an exception if the file doesn't exist, or if it does
		// exist but doesn't define the class.
		$this->injectClass($ucMod, $file);

		$thisModule = $this->$ucMod;
		if (!method_exists($thisModule, "ajaxRequest")) {
			$this->ajaxError(501, 'ajaxRequest not found');
		}

		if (!$thisModule->ajaxRequest($command, $this->settings)) {
			$this->ajaxError(403, 'ajaxRequest declined');
		}

		if ($this->settings['allowremote'] === true) {
			// You don't want to do this, honest.
			header('Access-Control-Allow-Origin: *');
		} else {
			// Try to avoid CSRF issues.
			if (!isset($_SERVER['HTTP_REFERER'])) {
				$this->ajaxError(403, 'ajaxRequest declined - Referrer');
			}

			// Make sure the referrer is at least this host.
			if (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['HTTP_HOST']) {
				$this->ajaxError(403, 'ajaxRequest declined - Referrer');
			}

			// TODO: We should add tokens in here, as we're still vulnerable to CSRF via XSS.
		}

		if ($this->settings['authenticate']) {
			if (!isset($_SESSION['AMP_user'])) {
				$this->ajaxError(401, 'Not Authenticated');
			} else {
				define('FREEPBX_IS_AUTH', 'TRUE');
			}
		}

		if (!method_exists($thisModule, "ajaxHandler")) {
			$this->ajaxError(501, 'ajaxHandler not found');
		}

		// Right. Now we can actually do it!
		ob_start();
		$ret = $thisModule->ajaxHandler();
		$out = ob_get_clean();
		ob_end_flush();
		if ($out) {
			print $out;
		} else {
			print json_encode($ret);
		}
		exit;
	}

	public function ajaxError($errnum, $text = 'Unknown error') {
		header("HTTP/1.0 $errnum $text");
		print json_encode(array("error" => $text));
		exit;
	}
}


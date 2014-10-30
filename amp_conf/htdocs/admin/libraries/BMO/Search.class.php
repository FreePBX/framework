<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */


class Search extends FreePBX_Helpers {

	public function ajaxRequest($cmd, &$settings) {
		$settings['allowremote'] = true;
		$settings['authenticate'] = false;
		return true;
	}

	public function ajaxHandler() {
		$search = $this->getSearch();
		if ($search == "global") {
			return $this->globalSearch();
		} else {
			return "Derp";
		}
	}
	public function globalSearch() {
		$modules = FreePBX::Modules()->getActiveModules();
		$retarr = array();
		foreach ($modules as $m) {
			if (isset($m['items'])) {
				foreach ($m['items'] as $k => $v) {
					$retarr[] = array("text" => $v['name'], "type" => "get", "dest" => "?display=$k");
				}
			}
		}
		return $retarr;
	}

	public function moduleSearch($module, $str) {
		$module = htmlentities($module);
		// Lets see if the module exists
		try {
			$mod = $this->FreePBX->$module;
			if(!method_exists($mod, 'search')) {
				throw new Exception("No Search Method");
			}
		} catch (Exception $e) {
			return array(
				array("text" => "Module $module doesn't implement search", "type" => "text", "details" => $e->getMessage())
			);
		}
		$res = $mod->search($str);
		if (!is_array($res)) {
			return array(
				array("text" => "Search Error", "type" => "text", "details" => $res)
			);
		}
		return $res;
	}

	private function getSearch($str) {
		if (!isset($_REQUEST['command'])) {
			return false;
		}
		return $_REQUEST['command'];
	}

	private function whichModule() {
		return "core";
	}
}

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
		} elseif ($search == "local") {
			return $this->moduleSearch();
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

	public function moduleSearch() {
		// Ask all modules for their search results
		$modules = FreePBX::Modules()->getActiveModules();
		$results = array();
		foreach ($modules as $m) {
			// If this is a BMO Module, grab it and ask it for search results
			try {
				$module = ucfirst($m['rawname']);
				$mod = $this->FreePBX->$module;
				if(!method_exists($mod, 'search')) {
					continue;
				}
				$mod->search($_REQUEST, $results);
			} catch (Exception $e) {
				continue;
			}
		}
		$results[] = array("text" => "<h4>This is text</h4>", "type" => "text");
		$results[] = array("text" => "This is a query link", "type" => "get", "dest" => "?display=modules");
		$results[] = array("text" => "This is a relative link", "type" => "get", "dest" => "/admin/config.php");
		$results[] = array("text" => "This is an explicit link", "type" => "get", "dest" => "https://google.com.au");
		$results[] = array("text" => "<h3>Moar</h3>", "type" => "text");

		return $results;
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

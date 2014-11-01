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
		if (!isset($_REQUEST['query'])) {
			return array();
		}
		// Make the query string usable.
		$qs = htmlentities($_REQUEST['query'], ENT_QUOTES|ENT_HTML401, 'UTF-8', false);

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
				$mod->search($qs, $results);
			} catch (Exception $e) {
				continue;
			}
		}

		// Remove any results from the search that are unneeded.
		foreach ($results as $i => $r) {
			if ($r['type'] == "text" || isset($r['force'])) {
				// Always return text fields that were given back to us, or if the result
				// was forced to display.
				continue;
			}
			if (strpos($r['text'], $qs) === false) {
				// Doesn't match? Remove.
				unset($results[$i]);
			}
		}
		return $results;
	}

	private function getSearch() {
		if (!isset($_REQUEST['command'])) {
			return false;
		}
		return $_REQUEST['command'];
	}

	private function whichModule() {
		return "core";
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
* This is the FreePBX Big Module Object.
*
* License for all code of this FreePBX module can be found in the license file inside the module directory
* Copyright 2006-2014 Schmooze Com Inc.
*/

class View {
	private $freepbx;
	private $queryString = "";
	private $replaceState = false;
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Need to be instantiated with a FreePBX Object");
		}
		$this->freepbx = $freepbx;
	}

	/**
	 * This is a replace of the old redirect standard.
	 * It emulates the same functionality but instead using HTML5 pushState
	 * The javascript part of the code is in footer.php
	 * @url https://developer.mozilla.org/en-US/docs/Web/Guide/API/DOM/Manipulating_the_browser_history
	 * @return bool True if the URL will be replaced, false if the URL won't be changed
	 */
	public function redirect_standard() {
		if($this->replaceState) {
			throw new \Exception("Redirect Standard was called twice in the same page load. This is wrong");
		}
		$replace = false;
		$args = func_get_args();
		if(empty($args)) {
			return false;
		}
		parse_str($_SERVER['QUERY_STRING'], $params);
		$vars = array_keys($params);
		foreach($args as $arg) {
			if((!in_array($arg,$vars) && isset($_REQUEST[$arg])) || (in_array($arg,$vars) && isset($_REQUEST[$arg]) && $_REQUEST[$arg] != $params[$arg])) {
				$params[$arg] = $_REQUEST[$arg];
				$replace = true;
			}
		}
		if(!$replace) {
			return false;
		}
		$base = basename($_SERVER['PHP_SELF']);
		$this->queryString = $base."?".http_build_query($params);
		$this->replaceState = true;
		return true;
	}

	/**
	 * To run the javascript or not? A simple method to check the state
	 */
	public function replaceState() {
		return $this->replaceState;
	}

	/**
	 * Get the finalized Query String for replacement
	 */
	public function getQueryString() {
		return $this->queryString;
	}
}

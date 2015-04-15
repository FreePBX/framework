<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Shim to use the existing freepbx_conf() class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Config {

	public $freepbx_conf;

	public function __construct() {
		$this->freepbx_conf = freepbx_conf::create();
	}

	/**
	 * Quick hack to just pass everything we don't know about to the existing class.
	 *
	 * There's nothing wrong with the freepbx_conf class, it just needs to be converted
	 * to use PDO.
	 *
	 */
	private function autoload($cmd, $args = null) {
		if (method_exists($this->freepbx_conf, $cmd)) {
			// OK, just pass it along.. Probably should put a warning
			// in here at some point, to encourage people to move the
			// code across to here and tidy it up.
			return call_user_func_array(array($this->freepbx_conf, $cmd),  $args);
		}
		throw new \Exception("Unknown function ".$cmd);
	}
	public function __get($var) { return $this->autoload($var); }
	public function __call($var, $args) { return $this->autoload($var, $args); }
	public static function __callStatic($var, $args) { return $this->autoload($var, $args); }

}

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
 * Shim to use the existing freepbx_conf() class.
 */

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
		throw new Exception("Unknown function ".$cmd);
	}
	public function __get($var) { return $this->autoload($var); }
	public function __call($var, $args) { return $this->autoload($var, $args); }
	public static function __callStatic($var, $args) { return $this->autoload($var, $args); }

}

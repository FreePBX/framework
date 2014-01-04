<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright 2013 Rob Thomas <rob.thomas@schmoozecom.com>
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

class BMO_Autoloader {

	/**
	 * Constructor
	 * 
	 * Ensure we have a copy of the FreePBX Object that was handed to us.
	 *  
	 * @return void     
	 * @access public   
	 */
	public function __construct($obj = null) {
		if (!is_object($obj))
			throw new Exception("This Module (".get_class($this).") wasn't handed a FreePBX Object on creation. Use the Autoloader!");

		$this->FreePBX = $obj;
	}

	/**
	 * PHP Magic __get - runs AutoLoader
	 * 
	 * @param $var Class Name
	 * @return $object New Object
	 * @access public 
	 */
	public function __get($var) {
		return $this->autoLoad($var);
	}

	/**
	 * PHP Magic __call - runs AutoLoader
	 * 
	 * @param $var Class Name
	 * @param $args Any params to be passed to the new object
	 * @return $object New Object
	 * @access public 
	 */
	public function __call($var, $args) {
		return $this->autoLoad($var, $args);
	}

	/**
	 * AutoLoader for BMO.
	 *
	 * I'm tempted to Implement PSR4.
	 * 
	 * @return object
	 * @access private
	 */
	private function autoLoad($var, $args) {
		// TODO: Care about LOCAL Modules here.

		// Does the FreePBX Object know about this already?

		// This.. This feels wrong. But I can't think of a better
		// way to do it.
		$freepbx = FreePBX::create();
		// isset should NOT trigger the Autoloader.
		if (isset($freepbx->$var)) {
			return $freepbx->$var;
		}

		return FreePBX::autoLoad($var, $args);
	}
}

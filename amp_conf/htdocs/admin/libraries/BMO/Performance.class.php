<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Performance logging
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
class Performance {

	private $doperf = false;

	/**
	 * Turn Performance Logging on
	 */
	public function On() { $this->doperf = true; }

	/**
	 * Turn Performance logging off
	 */
	public function Off() { $this->doperf = false; }

	/**
	 * Generate a stamp to the output
	 *
	 * Prints out microtime and memory usage from PHP
	 *
	 * @param {string} $str The stamp send out
	 * @example "PERF/$str/".microtime()."/".memory_get_usage()."\n"
	 */
	public function Stamp($str) {
		if ($this->doperf)
			print "PERF/$str/".microtime()."/".memory_get_usage()."\n";
	}
}

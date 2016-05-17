<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2016 Sangoma Technologies
 */

namespace FreePBX;
class Monitoring {
	protected static $status = array(
		0 => "OK",
		1 => "WARNING",
		2 => "CRITICAL",
	);
	const OK = 0;
	const WARNING = 1;
	const CRITICAL = 2;

	/**
	 * Gets a textual representation of the status from an integer
	 * @param integer $level
	 * @return string
	 */
	public static function getStatus($level = 0) {
		$status = "UNKNOWN";

		if (in_array($level, array_keys(self::$status))) {
			$status = self::$status[$level];
		}
		return $status;
	}

	/**
	 * Generate a report for sensu
	 * @param array $output
	 * @param integer $level
	 */
	public static function report($output, $level = 0) {
		if (is_array($output) || is_object($output)) {
			$output['status'] = self::getStatus($level);

			$output = json_encode($output);
		}

		print $output;

		exit($level);
	}
}


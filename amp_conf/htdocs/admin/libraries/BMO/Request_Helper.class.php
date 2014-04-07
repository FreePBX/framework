<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is the FreePBX BMO $_REQUEST Helper
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
 * Request_Helper provides a consistent way to catch $_REQUEST
 *
 * This loads everything provided in the GET/POST into the Key Value store.
 * As this is implicitly safe, there is no need for extra sanity checking,
 * and makes coding a pile easier
 *
 * This is for use with FreePBX's BMO.
 */
class Request_Helper extends Self_Helper {

	/**
	 * Processes $_REQUEST and saves the required things.
	 * 
	 * @param array $ignoreVars Array of variables to not process. 
	 * @param string $ignoreRgexp Regular expression to match exclusions against.
	 *
	 * @return array Returns any _REQUEST variables that haven't been processed.
	 * @access private
	 */
	public function importRequest($ignoreVars = null, $ignoreRegexp = null) {

		$request = $_REQUEST;

		// Default ignoreVars
		if (!$ignoreVars) {
			$ignoreVars = array("display", "type", "category", "Submit");
		}

		if (is_array($ignoreVars)) {
			// Remove any variables we've been told to ignore.
			foreach ($ignoreVars as $i) {
				if(isset($request[$i])) {
					// Hang onto it, we return it later.
					$ignored[$i] = $request[$i];
					unset($request[$i]);
				}
			}
		}

		// Now, loop through everything else.
		foreach ($request as $key => $var) {

			// Do we have a regexp to check against?
			if (!empty($ignoreRegexp) && preg_match($ignoreRegexp, $key, $match)) {
				// It matched. Add it to ignored to be returned.
				$ignored[$key] = $var;
				continue;
			}

			// Is it a Radio button?  It'll return $r['foo'] = 'foo=var';
			if (preg_match("/${$key}=(.+)/", $var, $match)) {
				// It is.
				$this->setConfig($key, $match[1]);
				continue;
			}

			// Always replace _'s with .'s. Easier to do it here
			$key = str_replace("_", ".", $key);

			$this->setConfig($key, $var);
		}

		// Now we return what was left over.
		if (!is_array($ignored)) {
			return array();
		} else {
			return $ignored;
		}
	}
}

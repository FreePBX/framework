<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Request_Helper provides a consistent way to catch $_REQUEST
 *
 * This loads everything provided in the GET/POST into the Key Value store.
 * As this is implicitly safe, there is no need for extra sanity checking,
 * and makes coding a pile easier
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

class Request_Helper extends Self_Helper {

	/**
	 * Processes $_REQUEST and saves the required things.
	 *
	 * @param array $ignoreVars Array of variables to not process.
	 * @param string $ignoreRgexp Regular expression to match exclusions against.
	 *
	 * @return array Returns any _REQUEST variables that haven't been processed.
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

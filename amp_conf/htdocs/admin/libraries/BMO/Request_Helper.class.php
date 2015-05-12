<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Request_Helper provides a consistent way to catch and process $_REQUEST
 * accesses
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Request_Helper extends Self_Helper {

	private $overrides = array();
	public $classOverride = false;

	/**
	 * Processes $_REQUEST and imports the useful things to the KVstore.
	 *
	 * This loads everything provided in the GET/POST into the Key Value store.
	 * As this is implicitly safe, there is no need for extra sanity checking,
	 * and makes coding a pile easier.
	 * This does NOT use any of the Override features provided by
	 * getReq and setReq.
	 *
	 * @param array $ignoreVars Array of variables to not process.
	 * @param string $ignoreRgexp Regular expression to match exclusions against.
	 * @param string $id ID to store the contents against.
	 *
	 * @return array Returns any _REQUEST variables that haven't been processed.
	 */
	public function importRequest($ignoreVars = null, $ignoreRegexp = null, $id = "noid") {

		$request = $_REQUEST;
		$ignored = array();

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
			if (is_string($var) && preg_match("/^$key=(.+)$/", $var, $match)) {
				// It is.
				$this->setConfig($key, $match[1], $id);
				continue;
			}

			// Always replace _'s with .'s. Easier to do it here
			$key = str_replace("_", ".", $key);

			$this->setConfig($key, $var, $id);
		}

		// Now we return what was left over.
		if (!$ignored) {
			return array();
		} else {
			return $ignored;
		}
	}

	/**
	 * Get individual $_REQUEST variables, unsafely.
	 *
	 * Does an implicit check for not set. If a default is provided, return the default,
	 * if not set. If no default, and not set, return (bool) false. You probably don't
	 * want to use this.  Use the 'getReq' function, which will automatically encode
	 * and escape any potential attack vectors.
	 *
	 * @param string $var $_REQUEST variable to get
	 * @param bool|string $def Default to return if unset, bool false to not return defaults
	 *
	 * @return bool|string Returns the variable, or false if unset
	 */
	public function getReqUnsafe($var = null, $def = true) {
		if (!$var) {
			throw new \Exception("Wasn't given anything to get from REQUEST.");
		}

		// Do we have an override?
		if (isset($this->overrides[$var])) {
			return $this->overrides[$var];
		}

		// If it doesn't exist...
		if (!isset($_REQUEST[$var])) {
			if ($def === false) {
				// It doesn't exist, and we've been told not to return defaults.
				return false;
			}
			if ($def === true) {
				// We weren't given a default. Does the parent, or specified, class have a default
				// for this?
				if ($this->classOverride) {
					$class = $this->classOverride;
					$this->classOverride = false;
				} else {
					$class = get_class($this);
				}
				if (property_exists($class, "reqDefaults")) {
					$def = $class::$reqDefaults;
					if (isset($def[$var])) {
						return $def[$var];
					}
				}
				// No. No default, not set. Return an empty string
				return (string) "";
			} else {
				// We were given a default. Give it right back.
				return $def;
			}
		} else {
			// It exists!
			return $_REQUEST[$var];
		}
	}

	/**
	 * Get individual $_REQUEST variables, safely.
	 *
	 * Does an implicit check for not set, and returns (bool) false if it doesn't exist.
	 * If a default is provided, return the default if not set. If no default, and not
	 * set, return (string) ""  (an empty string).  This currently encodes everything
	 * that could possibly be nasty. We may relax this later.
	 *
	 * @param string $var $_REQUEST variable to get
	 * @param bool|string $def Default to return if unset, bool false to not return defaults
	 *
	 * @return bool|string Returns the safe, processed variable, or false if unset
	 */

	public function getReq($var = null, $def = true) {
		$ret = $this->getReqUnsafe($var, $def);
		if (is_array($ret)) {
			throw new \Exception("No-one's written anything to safe an array. Get on it!");
		}

		// Unicode attack mitigation:
		// Reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
		$ret = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
			'|[\x00-\x7F][\x80-\xBF]+'.
			'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
			'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
			'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
			'?', $ret );
		// Reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
		$ret = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
			'|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $ret );

		// Mitigate most other vectors
		$ret = htmlentities($ret, ENT_QUOTES, "UTF-8", false);

		// If any further attack vectors are discovered, put the mitigations here!

		return $ret;
	}

	/**
	 * Overrides or adds to whatever's in $_REQUEST
	 *
	 * This is used for backwards compatibility with previous modules, which used to
	 * add to $_REQUEST. This is a Read-Only variable in PHP 5.5 and higher, so we
	 * needed a way to replace it in the interim.  This is that way.  If you want to
	 * remove an override, set it to null. To delete a $_REQUEST variable, set it to
	 * (bool) false.
	 *
	 * @param string $var $_REQUEST variable to add/update
	 * @param bool|string $val value to set it to.
	 *
	 * @return void
	 */
	public function setReq($var = null, $val = null) {
		if ($var === null) {
			throw new \Exception("Don't know what you want me to update");
		}

		if ($val === null) {
			unset($this->overrides[$var]);
		} else {
			$this->overrides[$var] = $val;
		}
		return;
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * LoadConfig class
 * This class represents a way to load Asterisk Format Configuration files were
 * [section]key=value into a PHP hash such as array('section' => array('key' => 'value'))
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

class LoadConfig {

	private $RawConfigContents;
	public $PlainConfig;
	public $BaseConfig;
	public $ProcessedConfig;

	private $Filename;

	/**
	 * Setup the call to load config, same as loadConfig() method below
	 * just more direct
	 *
	 * @param object $freepbx the FreePBX BMO Object
	 * @param string $file The basename of the file to load
	 * @param string $hint The directory where the file lives
	 */
	public function __construct($freepbx = null, $file = null, $hint = "/etc/asterisk") {
		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");

		if ($file !== null)
			$this->loadConfig($file, $hint);
	}

	/**
	 * Loads and Processes a Configuration in the Asterisk Format
	 *
	 * This will attempt to load a file and then parse it
	 * the file must be in the asterisk configuration file format!
	 *
	 * Note: this function does not return said file!
	 *
	 * @param string $file The basename of the file to load
	 * @param string $hint The directory where the file lives
	 * @return bool True if pass
	 */
	public function loadConfig($file = null, $hint = "/etc/asterisk") {
		//clear old contents out
		$this->ProcessedConfig = $this->BaseConfig = $this->PlainConfig = $this->RawConfigContents = "";
		if ($file === null)
			throw new Exception("No file given to load");

		$filename = $this->validateFilename($file,$hint);

		$this->Filename = $filename;
		$config = file($filename, FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
		$this->RawConfigContents = $config;

		// Remove the header
		$this->stripHeader($config);
		$this->PlainConfig = $config;

		// Now remove the comments
		$this->stripComments($config);
		$this->BaseConfig = $config;

		// And break it into elements.
		$this->explodeConfig($config);
		return true;
	}

	/**
	 * Get Raw Contents of a Configuration File
	 *
	 * This will get the raw unprocessed contents of a configuration file
	 *
	 * Note: This will only work AFTER loadConfig has run
	 *
	 * @param string $file The basename of the file to load
	 * @return string Raw Contents of said file
	 */
	public function getRaw($file = null) {
		if ($file === null && !isset($this->RawConfigContents))
			throw new Exception("Asked for raw contents of a file, but was never asked to read a file");

		return $this->RawConfigContents;
	}

	/**
	 * Get The Processed Contents of a Configuration File
	 *
	 * This will process and return a configuration file in the Asterisk Configuration
	 * file format in a hashed format for processing
	 *
	 * @param string $file The basename of the file to load
	 * @param string $hint The directory where the file lives
	 * @param string $context The specific context to return, if not set then return all
	 * @return array The hashed configuration file
	 */
	public function getConfig($file = null, $hint = "/etc/asterisk", $context = null) {
		if ($file === null)
			throw new Exception("No file given to load");

		$this->loadConfig($file, $hint);

		return (!empty($context) && isset($this->ProcessedConfig[$context])) ? $this->ProcessedConfig[$context] : $this->ProcessedConfig;
	}

	/**
	 * Validate Filename
	 *
	 * This will validate the provided file name to make sure there isn't some hackery-dackery going on
	 *
	 * @param string $file The basename of the file to load
	 * @param string $hint The directory where the file lives
	 * @return string The complete file path
	 */
	private function validateFilename($file, $hint = "/etc/asterisk") {
		// Check to make sure it doesn't have any /'s or ..'s
		// in it. We're only allowed to write to /etc/asterisk or our hint

		if (strpos($file, "/") !== false)
			throw new Exception("$filename contains a /");
		if (strpos($file, "..") !== false)
			throw new Exception("$filename contains ..");

		$filename = $hint."/".$file;
		return $filename;
	}

	/**
	 * Strip Headers
	 *
	 * This completely Strips the header from the configuration file
	 *
	 * @param array $arr The Config File's array to remove headers from
	 */
	private function stripHeader(&$arr) {
		// Remove all headers in this file
		// First, take a copy of the array
		$myarr = $arr;

		// Now, go through my copy..
		foreach ($myarr as $id => $line) {
			// Note, not checking for empty lines, as FILE_SKIP_EMPTY_LINES does that for us.
			if (strpos($line, ";") === 0) {
				// Starts with a comment. Remove it.
				unset($arr[$id]);
			} else {
				// It's not a comment, which means we're past the header.
				// Stop now.
				break;
			}
		}
	}

	/**
	 * Strip Comments
	 *
	 * This completely Strips Comments from a file
	 *
	 * @param array $arr The Config File's array to remove comments from
	 */
	private function stripComments(&$arr) {
		// Remove all comments.
		// First, take a copy of the array
		$myarr = $arr;
		// Again, go through my copy...
		foreach ($myarr as $id => $line) {
			//Trim leading whitespace
			$line = trim($line);
			// Note, not checking for empty lines, as FILE_SKIP_EMPTY_LINES does that for us.
			if (strpos($line, ";") === 0) {
				// Starts with a comment. Remove it.
				unset($arr[$id]);
			}
		}
	}

	/**
	 * Explode Config
	 *
	 * This Explodes the Configuration File into arrays where <key>=<value> will be turned into ['key'] => value
	 *
	 * @param array $conf The Config File's array to parse
	 */
	private function explodeConfig($conf) {
		// Process the config we've been given, and return a useful array

		// Anything prior to the first section is in the magic 'HEADER' section
		$section = "HEADER";
		foreach ($conf as $entry) {
			if (preg_match("/\[(.+)\]/", $entry, $out)) {
				$section = $out[1];
				continue;
			}

			if (preg_match("/^(\S+)\s*(?:=>?)\s*(.+)?$/", $entry, $out)) {

				// If it doesn't have anything set, then we don't care.
				if (empty($out[2]))
					continue;

				if (isset($this->ProcessedConfig[$section]) && isset($this->ProcessedConfig[$section][$out[1]])) {
					// This already exists. Multiple definitions.
					if (!is_array($this->ProcessedConfig[$section][$out[1]])) {
						// This is the first time we've found this, so make it an array.

						$tmp = $this->ProcessedConfig[$section][$out[1]];
						unset($this->ProcessedConfig[$section][$out[1]]);
						$this->ProcessedConfig[$section][$out[1]][] = $tmp;
					}
					// It's an array, so we can just append to it.
					$this->ProcessedConfig[$section][$out[1]][] = $out[2];
				} else {
					$this->ProcessedConfig[$section][$out[1]] = $out[2];
				}
			} else if (preg_match("/^#include/", $entry)) {
				$this->ProcessedConfig[$section][] = $entry;
			} else if (trim($entry) == "") {
				continue;
			} else {
				throw new Exception("Coding Error - don't understand '$entry' from ".$this->Filename);
			}
		}
	}
}

<?php

class LoadConfig {

	private $RawConfigContents;
	public $PlainConfig;
	public $BaseConfig;
	public $ProcessedConfig;

	private $Filename;

	public function __construct($freepbx = null, $file = null) {
		if ($freepbx == null)
			throw new Exception("Need to be instantiated with a FreePBX Object");

		if ($file !== null)
			$this->loadConfig($file);
	}

	public function loadConfig($file = null) {
		if ($file === null)
			throw new Exception("No file given to load");

		$filename = $this->validateFilename($file);

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
	}

	public function getRaw($file = null) {
		if ($file === null && !isset($this->RawConfigContents))
			throw new Exception("Asked for raw contents of a file, but was never asked to read a file");

		if (!isset($this->RawConfigContents))
			$this->loadConfig($file);

		return $this->RawConfigContents;
	}

	public function getConfig($file = null) {
		if ($file === null && !isset($this->RawConfigContents))
			throw new Exception("Asked for contents of a file, but was never asked to read a file");

		if (!isset($this->RawConfigContents))
			$this->loadConfig($file);

		return $this->ProcessedConfig;
	}


	private function validateFilename($file) {
		// Check to make sure it doesn't have any /'s or ..'s
		// in it. We're only allowed to write to /etc/asterisk

		if (strpos($file, "/") !== false)
			throw new Exception("$filename contains a /");
		if (strpos($file, "..") !== false)
			throw new Exception("$filename contains ..");

		$filename = "/etc/asterisk/$file";
		return $filename;
	}

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

	private function stripComments(&$arr) {
		// Remove all comments.
		// First, take a copy of the array
		$myarr = $arr;
		// Again, go through my copy...
		foreach ($myarr as $id => $line) {
			// Note, not checking for empty lines, as FILE_SKIP_EMPTY_LINES does that for us.
			if (strpos($line, ";") === 0) {
				// Starts with a comment. Remove it.
				unset($arr[$id]);
			}
		}
	}

	private function explodeConfig($conf) {
		// Process the config we've been given, and return a useful array

		// Anything prior to the first section is in the magic 'HEADER' section
		$section = "HEADER";
		foreach ($conf as $entry) {
			if (preg_match("/\[(.+)\]/", $entry, $out)) {
				$section = $out[1];
				continue;
			}

			print "Looking at $entry\n";
			if (preg_match("/^\b(.+)\b(?:\s*=\s*|\s*=>\s*)(.+)?$/", $entry, $out)) {

				// If it doesn't have anything set, then we don't care.
				if (empty($out[2]))
					continue;

				if (isset($this->ProcessedConfig[$section][$out[1]])) {
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

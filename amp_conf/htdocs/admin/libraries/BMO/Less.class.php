<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object Wrapper for Less.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Andrew Nagy <andrew.nagy@schmoozecom.com>
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
 * @author    Andrew Nagy <andrew.nagy@schmoozecom.com>
 * @license   AGPL v3
 */
require_once dirname(dirname(__FILE__)).'/less/Less.php';

class Less extends Less_Parser {
	public function __construct($freepbx = null, $env = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		parent::__construct($env);
	}
	
	/**
	 * Parse a Directory to find the appropriate less files
	 *
	 * If a bootstrap.less file exists then parse that only (looking for imports)
	 * Otherwise just find the files to parse at the same time. This will return
	 * the generated CSS however it's highly advisable you end up using getCacheFile
	 *
	 * @param string $dir The directory housing the less files
	 * @param string $uri_root The uri root of the web request
	 * @return string The CSS file output
	 */
	public function parseDir($dir, $uri_root = '') {
		//Load bootstrap only if it exists as this will tell us the correct load order
		if(!file_exists($dir.'/cache')) {
			mkdir($dir.'/cache');
		}
		$this->SetOption('cache_dir',$dir.'/cache');
		$this->SetOption('compress',true);
		if(file_exists($dir."/bootstrap.less")) {
			$this->parseFile($dir."/bootstrap.less", $uri_root);
		} else {
			//load them all randomly. Probably in alpha order
			foreach(glob($dir."/*.less") as $file) {
				$this->parseFile($file, $uri_root);
			}
		}
		return $this->getCss();
	}
	
	/**
	 * Generates and Gets the Cached files
	 *
	 * This will generated a compiled less file into css format
	 * but it will cache it so that it doesnt happen unless the file has changed
	 *
	 * @param string $dir The directory housing the less files
	 * @param string $uri_root The uri root of the web request
	 * @return string the CSS filename
	 */
	public function getCachedFile($dir, $uri_root = '') {
		if(!file_exists($dir.'/cache')) {
			mkdir($dir.'/cache');
		}
		\Less_Cache::$cache_dir = $dir.'/cache';
		$files = array();
		if(file_exists($dir."/bootstrap.less")) {
			$to_cache = array( $dir."/bootstrap.less" => $uri_root );
			$filename = \Less_Cache::Get( $to_cache, array('compress' => true) );
		} else {
			//load them all randomly. Probably in alpha order
			foreach(glob($dir."/*.less") as $file) {
				$files = array( $file => $uri_root );
			}
			uksort($files, "cmp");
			$filename = \Less_Cache::Get( $files, array('compress' => true) );
		}
		return $filename;
	}
}
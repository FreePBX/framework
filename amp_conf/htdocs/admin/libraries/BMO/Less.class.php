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

	public function generateMainStyles() {
		global $amp_conf;
		$less_rel = '/admin/assets';
		$less_path = $amp_conf['AMPWEBROOT'].'/admin/assets/less';

		$less_dirs = array("bootstrap","freepbx","font-awesome");
		$out = array();
		$out['compiled_less_files'] = array();
		foreach($less_dirs as $dir) {
			$path = $less_path."/".$dir;
			if (is_dir($path)) {
				$file = $this->getCachedFile($path,$less_rel);
				$out['compiled_less_files'][$dir] = $dir.'/cache/'.$file;
			}
		}
		$extra_less_dirs = array("buttons");
		$out['extra_compiled_less_files'] = array();
		foreach($extra_less_dirs as $dir) {
			$path = $less_path."/".$dir;
			if (is_dir($path)) {
				$file = $this->getCachedFile($path,$less_rel);
				$out['extra_compiled_less_files'][$dir] = $dir.'/cache/'.$file;
			}
		}
		return $out;
	}

	public function generateModuleStyles($module) {
		global $amp_conf;
		$less_rel = '/admin/assets/' . $module;
		$less_path = $amp_conf['AMPWEBROOT'] . '/admin/modules/' . $module . '/assets/less';
		$files = array();
		if(file_exists($less_path)) {
			$files[] = $this->getCachedFile($less_path,$less_rel);
		}
		return $files;
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
			if(!mkdir($dir.'/cache')) {
				die_freepbx('Can Not Create Cache Folder at '.$dir.'/cache');
			}
		}
		$this->SetOption('cache_dir',$dir.'/cache');
		$this->SetOption('compress',true);
		$basename = basename($dir);
		if(file_exists($dir."/bootstrap.less")) {
			$this->parseFile($dir."/bootstrap.less", $uri_root);
		} elseif(file_exists($dir."/".$basename.".less")) {
			$this->parseFile($dir."/".$basename.".less", $uri_root);
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
			if(!mkdir($dir.'/cache')) {
				die_freepbx('Can Not Create Cache Folder at '.$dir.'/cache');
			}
		}
		\Less_Cache::$cache_dir = $dir.'/cache';
		$files = array();
		$basename = basename($dir);
		if(file_exists($dir."/bootstrap.less")) {
			$files = array( $dir."/bootstrap.less" => $uri_root );
			$filename = \Less_Cache::Get( $files, array('compress' => true) );
		} elseif(file_exists($dir."/".$basename.".less")) {
			$files = array( $dir."/".$basename.".less" => $uri_root );
			$filename = \Less_Cache::Get( $files, array('compress' => true) );
		} else {
			//load them all randomly. Probably in alpha order
			foreach(glob($dir."/*.less") as $file) {
				$files[$file] = $uri_root;
			}
			uksort($files, "strcmp");
			$filename = \Less_Cache::Get( $files, array('compress' => true) );
		}
		return $filename;
	}
}

<?php

/********************************************************************************************************************/
/* freepbxlib.install.php
 * 
 * These are used by install_amp and the framework install script to run updates
 *
 * These variables are required to be defined outside of this library. The purpose
 * of this is to allow the library to be used by both install_amp as well as the
 * framework which would potentially be accessing these from different locations.
 *
 * Examples:
 *
 * UPGRADE_DIR     dirname(__FILE__)."/upgrades"
 * MODULE_DIR      dirname(__FILE__)."/amp_conf/htdocs/admin/modules/"
 *
 * or (in framework for instance)
 *
 * MODULE_DIR      dirname(__FILE__)."/htdocs/admin/modules/"
 *
 * $debug = false;
 * $dryrun = false;
 */

function upgrade_all($version) {

	// **** Read upgrades/ directory

	outn("Checking for upgrades..");

	// read versions list from ugprades/
	$versions = array();
	$dir = opendir(UPGRADE_DIR);
	while ($file = readdir($dir)) {
		if (($file[0] != ".") && is_dir(UPGRADE_DIR."/".$file)) {
			$versions[] = $file;
		}
	}
	closedir($dir);

	// callback to use php's version_compare() to sort
	usort($versions, "version_compare_freepbx");


	// find versions that are higher than the current version
	$starting_version = false;
	foreach ($versions as $check_version) {
		if (version_compare_freepbx($check_version, $version) > 0) { // if check_version < version
			$starting_version = $check_version;
			break;
		}
	}

	// run all upgrades from the list of higher versions
	if ($starting_version) {
		$pos = array_search($starting_version, $versions);
		$upgrades = array_slice($versions, $pos); // grab the list of versions, starting at $starting_version
		out(count($upgrades)." found");
		run_upgrade($upgrades);

		/* Set the base version of key modules, currently core and framework, to the
	 	 * Version packaged with this tarball, if any. The expectation is that the
	 	 * packaging scripts will make these module version numbers the same as the
	 	 * release plus a '.0' which can be incremented for bug fixes delivered through
	 	 * the online system between main releases.
		 *
		 * added if function_exists because if this is being run from framework there is no
		 * need to reset the base version.
	 	 */
		if (function_exists('set_base_version')) {
			set_base_version();
		}

	} else {
		out("No upgrades found");
	}

}

//----------------------------------
// dependencies for upgrade_all


/** Invoke upgrades
 * @param $versions array	The version upgrade scripts to run
 */
function run_upgrade($versions) {
	global $dryrun;
	
	foreach ($versions as $version) {
		out("Upgrading to ".$version."..");
		install_upgrade($version);
		if (!$dryrun) {
			setversion($version);
		}
		out("Upgrading to ".$version."..OK");
	}
}

//get the version number
function install_getversion() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'version'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		return false;
	}
	return $results[0][0];
}

//set the version number
function setversion($version) {
	global $db;
	$sql = "UPDATE admin SET value = '".$version."' WHERE variable = 'version'";
	debug($sql);
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage()); 
	}
}

/** Install a particular version
 */
function install_upgrade($version) {
	global $db;
	global $dryrun;
	global $amp_conf;
	
	$db_engine = $amp_conf["AMPDBENGINE"];
	
	if (is_dir(UPGRADE_DIR."/".$version)) {
		// sql scripts first
		$dir = opendir(UPGRADE_DIR."/".$version);
		while ($file = readdir($dir)) {
			if (($file[0] != ".") && is_file(UPGRADE_DIR."/".$version."/".$file)) {
				if ( (strtolower(substr($file,-4)) == ".sqlite") && ($db_engine == "sqlite") ) {
					install_sqlupdate( $version, $file );
				}
				elseif ((strtolower(substr($file,-4)) == ".sql") && 
						( ($db_engine  == "mysql")  ||  ($db_engine  == "pgsql") || ($db_engine == "sqlite3") ) ) {
					install_sqlupdate( $version, $file );
				}
			}
		}

                // now non sql scripts
                $dir = opendir(UPGRADE_DIR."/".$version);
                while ($file = readdir($dir)) {
                        if (($file[0] != ".") && is_file(UPGRADE_DIR."/".$version."/".$file)) {
                                if ((strtolower(substr($file,-4)) == ".sql") || (strtolower(substr($file,-7)) == ".sqlite")) {
                                        // sql scripts were dealt with first
                                } else if (strtolower(substr($file,-4)) == ".php") {
                                        out("-> Running PHP script ".UPGRADE_DIR."/".$version."/".$file);
                                        if (!$dryrun) {
                                                run_included(UPGRADE_DIR."/".$version."/".$file);
                                        }

                                } else if (is_executable(UPGRADE_DIR."/".$version."/".$file)) {
                                        out("-> Executing ".UPGRADE_DIR."/".$version."/".$file);
                                        if (!$dryrun) {
                                                exec(UPGRADE_DIR."/".$version."/".$file);
                                        }
                                } else {
                                        error("-> Don't know what to do with ".UPGRADE_DIR."/".$version."/".$file);
                                }
                        }
                }

	}
}


function checkDiff($file1, $file2) {
	// diff, ignore whitespace and be quiet
	exec("diff -wq ".escapeshellarg($file2)." ".escapeshellarg($file1), $output, $retVal);
	return ($retVal != 0);
}

function amp_mkdir($directory, $mode = "0755", $recursive = false) {
	debug("mkdir ".$directory.", ".$mode);
	$ntmp = sscanf($mode,"%o",$modenum); //assumes all inputs are octal
	if (version_compare(phpversion(), 5.0) < 0) {
		// php <5 can't recursively create directories
		if ($recursive) {
			$output = false;
			$return_value = false;
			exec("mkdir -m ".$mode." -p ".$directory,  $output, $return_value);
			return ($return_value == 0);
		} else {
			return mkdir($directory, $modenum);
		}
	} else {
		return mkdir($directory, $modenum, $recursive);
	}
}

/** Recursively copy a directory
 */
function recursive_copy($dirsourceparent, $dirdest, &$md5sums, $dirsource = "") {
	global $dryrun;
	global $check_md5s;
	global $amp_conf;
	global $asterisk_conf;
	global $install_moh;
	global $make_links;

	// total # files, # actually copied
	$num_files = $num_copied = 0;
	
	if ($dirsource && ($dirsource[0] != "/")) $dirsource = "/".$dirsource;
	
	if (is_dir($dirsourceparent.$dirsource)) $dir_handle = opendir($dirsourceparent.$dirsource);
	
	/*
	echo "dirsourceparent: "; var_dump($dirsourceparent);
	echo "dirsource: "; var_dump($dirsource);
	echo "dirdest: "; var_dump($dirdest);
	*/
	
	while (isset($dir_handle) && ($file = readdir($dir_handle))) {
		if (($file!=".") && ($file!="..") && ($file != "CVS") && ($file != ".svn")) {
			$source = $dirsourceparent.$dirsource."/".$file;
			$destination =  $dirdest.$dirsource."/".$file;
			
			if ($dirsource == "" && $file == "mohmp3" && !$install_moh) {
				// skip to the next dir
				continue;
			}

			
			// configurable in amportal.conf
			if (strpos($destination,"htdocs_panel")) {
				$destination=str_replace("/htdocs_panel",trim($amp_conf["FOPWEBROOT"]),$destination);
			} else {
				$destination=str_replace("/htdocs",trim($amp_conf["AMPWEBROOT"]),$destination);
			}
			$destination=str_replace("/htdocs_panel",trim($amp_conf["FOPWEBROOT"]),$destination);
//			$destination=str_replace("/cgi-bin",trim($amp_conf["AMPCGIBIN"]),$destination);
			if(strpos($dirsource, 'modules') === false) $destination=str_replace("/bin",trim($amp_conf["AMPBIN"]),$destination);
			$destination=str_replace("/sbin",trim($amp_conf["AMPSBIN"]),$destination);
			
			// the following are configurable in asterisk.conf
			$destination=str_replace("/astetc",trim($asterisk_conf["astetcdir"]),$destination);
			$destination=str_replace("/mohmp3",trim($asterisk_conf["astvarlibdir"])."/mohmp3",$destination);
			$destination=str_replace("/astvarlib",trim($asterisk_conf["astvarlibdir"]),$destination);
			if(strpos($dirsource, 'modules') === false) $destination=str_replace("/agi-bin",trim($asterisk_conf["astagidir"]),$destination);
			if(strpos($dirsource, 'modules') === false) $destination=str_replace("/sounds",trim($asterisk_conf["astvarlibdir"])."/sounds",$destination);

			// if this is a directory, ensure destination exists
			if (is_dir($source)) {
				if (!file_exists($destination)) {
					if ((!$dryrun) && ($destination != "")) {
						amp_mkdir($destination, "0750", true);
					}
				}
			}
			
			//var_dump($md5sums);
			if (!is_dir($source)) {
				$md5_source = preg_replace("|^/?amp_conf/|", "/", $source);

				if ($check_md5s && file_exists($destination) && isset($md5sums[$md5_source]) && (md5_file($destination) != $md5sums[$md5_source])) {
					// double check using diff utility (and ignoring whitespace)
					// This is a somewhat edge case (eg, the file doesn't match
					// it's md5 sum from the previous version, but no substantial
					// changes exist compared to the current version), but it 
					// pervents a useless prompt to the user.
					if (checkDiff($source, $destination)) {
						$overwrite = ask_overwrite($source, $destination);
					} else {
						debug("NOTE: MD5 for ".$destination." was different, but `diff` did not detect any (non-whitespace) changes: overwriting");
						$overwrite = true;
					}
				} else {
					$overwrite = true;
				}
				
				$num_files++;
				if ($overwrite) {
					debug(($make_links ? "link" : "copy")." ".$source." -> ".$destination);
					if (!$dryrun) {
						if ($make_links) {
							// symlink, unlike copy, doesn't overwrite - have to delete first
							if (is_link($destination) || file_exists($destination)) {
								unlink($destination);
							}
							symlink($_ENV["PWD"]."/".$source, $destination);
						} else {
							copy($source, $destination);
						}
						$num_copied++;
					}
				} else {
					debug("not overwriting ".$destination);
				}
			} else {
				//echo "recursive_copy($dirsourceparent, $dirdest, $md5sums, $dirsource/$file)";
				list($tmp_num_files, $tmp_num_copied) = recursive_copy($dirsourceparent, $dirdest, $md5sums, $dirsource."/".$file);
				$num_files += $tmp_num_files;
				$num_copied += $tmp_num_copied;
			}
		}
	}
	
	if (isset($dir_handle)) closedir($dir_handle);
	
	return array($num_files, $num_copied);
}

function read_md5_file($filename) {
	$md5 = array();
	if (file_exists($filename)) {
		foreach (file($filename) as $line) {
			if (preg_match("/^([a-f0-9]{32})\s+(.*)$/", $line, $matches)) {
				$md5[ "/".$matches[2] ] = $matches[1];
			}
		}
	}
	return $md5;
}

/** Include a .php file
 * This is a function just to keep a seperate context
 */
function run_included($file) {
	global $db;
	global $amp_conf;
	
	include($file);
}

function install_sqlupdate( $version, $file )
{
	global $db;
	global $dryrun;

	out("-> Running SQL script ".UPGRADE_DIR."/".$version."/".$file);
	// run sql script
	$fd = fopen(UPGRADE_DIR."/".$version."/".$file, "r");
	$data = "";
	while (!feof($fd)) {
		$data .= fread($fd, 1024);
	}
	fclose($fd);

	preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER).*);\s*\n/Us", $data, $matches);
	
	foreach ($matches[1] as $sql) {
		debug($sql);
		if (!$dryrun) {
			$result = $db->query($sql); 
			if(DB::IsError($result)) {     
				fatal($result->getDebugInfo()."\" while running ".$file."\n"); 
			}
		}
	}
}

/********************************************************************************************************************/

?>

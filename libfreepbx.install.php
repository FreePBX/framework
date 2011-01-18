<?php
//This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//    Copyright 2007, Philippe Lindheimer
//

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
	if (version_compare(phpversion(), '5.0') < 0) {
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

  $moh_subdir = isset($amp_conf['MOHDIR']) ? trim(trim($amp_conf['MOHDIR']),'/') : 'mohmp3';

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
			
			if ($dirsource == "" && $file == "moh" && !$install_moh) {
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
			$destination=str_replace("/moh",trim($asterisk_conf["astvarlibdir"])."/$moh_subdir",$destination);
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
/*                          FREEPBX SETTINGS (AMPORTAL.CONF) DEFINED HERE                                           */
/********************************************************************************************************************/
//
// TODO: find a good way to extract the required localization strings for the tools to pickup
//
// freepbx_settings_init()
// this is where we initialize all the freepbx_settings (amportal.conf). This will be run with install_amp and every
// time we run the framework installer, so new settings can be added here that are framework wide. It may make send to
// break this out separtely but for now we'll keep it here since this is already part of the ifrastructure that is
// used by both install_amp and the framework install/upgrade script.
//
function freepbx_settings_init($commit_to_db = false) {
  global $amp_conf;

  include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');

  $freepbx_conf =& freepbx_conf::create();

  $set['value'] = '';
  $set['defaultval'] =& $set['value'];
  $set['readonly'] = 0;
  $set['hidden'] = 0;
  $set['level'] = 0;
  $set['module'] = '';
  $set['emptyok'] = 0;


  //
  // CATEGORY: Advanced Settings Display
  //
  $set['category'] = 'Advanced Settings Details';

  // AS_DISPLAY_DETAIL_LEVEL
  $set['value'] = '0';
  $set['options'] = '0,1,2,3,4,5,6,7,8,9,10';
  $set['description'] = 'This will filter which settings that are displayed on this Advanced Settings page. The higher the level, the more obscure settings will be shown. Settings at higher levels are unlikely to be of interest to most users and could be more volatile to breaking your system if set wrong.';
  $set['level'] = 0;
  $set['readonly'] = 0;
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('AS_DISPLAY_DETAIL_LEVEL',$set);
  $set['readonly'] = 0;
  $set['level'] = 0;

  // AS_DISPLAY_HIDDEN_SETTINGS
  $set['value'] = false;
  $set['description'] = 'This will display settings that are normally hidden by the system. These settings are often internally used settings that are not of interest to most users.';
  $set['level'] = 0;
  $set['readonly'] = 0;
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('AS_DISPLAY_HIDDEN_SETTINGS',$set);
  $set['readonly'] = 0;
  $set['level'] = 0;

  // AS_DISPLAY_READONLY_SETTINGS
  $set['value'] = false;
  $set['description'] = 'This will display settings that are readonly. These settings are often internally used settings that are not of interest to most users. Since they are readonly they can only be viewed.';
  $set['level'] = 0;
  $set['readonly'] = 0;
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('AS_DISPLAY_READONLY_SETTINGS',$set);
  $set['readonly'] = 0;
  $set['level'] = 0;

  //
  // CATEGORY: System Setup
  //
  $set['category'] = 'System Setup';

  // AMPENGINE
  $set['value'] = 'asterisk';
  $set['options'] = 'asterisk';
  $set['description'] = 'The telephony backend engine being used, asterisk is the only option currently.';
  $set['level'] = 3;
  $set['readonly'] = 1;
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('AMPENGINE',$set);
  $set['readonly'] = 0;
  $set['level'] = 0;

  // AUTHTYPE
  $set['value'] = 'database';
  $set['options'] = 'database,none,webserver';
  $set['description'] = 'Authentication type to use for web admin. If type set to <b>database</b>, the primary AMP admin credentials will be the AMPDBUSER/AMPDBPASS above. When using database you can create users that are restricted to only certain module pages. When set to none, you should make sure you have provided security at the apache level. When set to webserver, FreePBX will expect authentication to happen at the apache level, but will take the user credentials and apply any restrictions as if it were in database mode.';
  $set['level'] = 3;
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('AUTHTYPE',$set);
  $set['level'] = 0;

  // AMPEXTENSIONS
  $set['value'] = 'extensions';
  $set['options'] = 'extensions,deviceanduser';
  $set['description'] = 'Sets the extension behavior in FreePBX.  If set to <b>extensions</b>, Devices and Users are administered together as a unified Extension, and appear on a single page. If set to <b>deviceanduser</b>, Devices and Users will be administered seperately. Devices (e.g. each individual line on a SIP phone) and Users (e.g. <b>101</b>) will be configured independent of each other, allowing association of one User to many Devices, or allowing Users to login and logout of Devices.';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('AMPEXTENSIONS',$set);

  // AMPVMUMASK
  $set['value'] = '007';
  $set['description'] = 'Defaults to 077 allowing only the asterisk user to have any permission on VM files. If set to something like 007, it would allow the group to have permissions. This can be used if setting apache to a different user then asterisk, so that the apache user (and thus ARI) can have access to read/write/delete the voicemail files. If changed, some of the voicemail directory structures may have to be manually changed.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPVMUMASK',$set);
  $set['level'] = 0;

  // AMPWEBADDRESS
  $set['value'] = '';
  $set['description'] = 'This is the address of your Web Server. It is mostly obsolete and derived when not supplied and will be phased out, but there are still some areas expecting a variable to be set and if you are using it this will migrate your value.';
  $set['emptyok'] = 1;
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPWEBADDRESS',$set);
  $set['level'] = 0;
  $set['emptyok'] = 0;

  // ARI_ADMIN_USERNAME
  $set['value'] = '';
  $set['description'] = 'This is the default admin name used to allow an administrator to login to ARI bypassing all security. Change this to whatever you want, dont forget to change the ARI_ADMIN_PASSWORD as well.Default = not set';
  $set['emptyok'] = 1;
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('ARI_ADMIN_USERNAME',$set);
  $set['emptyok'] = 0;

  // ARI_ADMIN_PASSWORD
  $set['value'] = 'ari_password';
  $set['description'] = 'This is the default admin password to allow an administrator to login to ARI bypassing all security. Change this to a secure password.Default = not set';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('ARI_ADMIN_PASSWORD',$set);

  // AMPASTERISKUSER
  $set['value'] = 'asterisk';
  $set['description'] = 'The user Asterisk should be running as, used by freepbx_engine. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPASTERISKUSER',$set);
  $set['level'] = 0;

  // AMPASTERISKGROUP
  $set['value'] = 'asterisk';
  $set['description'] = 'The user group Asterisk should be running as, used by freepbx_engine. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPASTERISKGROUP',$set);
  $set['level'] = 0;

  // AMPASTERISKWEBUSER
  $set['value'] = 'asterisk';
  $set['description'] = 'The user your httpd should be running as, used by freepbx_engine. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPASTERISKWEBUSER',$set);
  $set['level'] = 0;

  // AMPASTERISKWEBGROUP
  $set['value'] = 'asterisk';
  $set['description'] = 'The user group your httpd should be running as, used by freepbx_engine. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPASTERISKWEBGROUP',$set);
  $set['level'] = 0;

  // AMPDEVUSER
  $set['value'] = 'asterisk';
  $set['description'] = 'The user that various device directories should be set to, used by freepbx_engine. Examples include /dev/zap, /dev/dahdi, /dev/misdn, /dev/mISDN and /dev/dsp. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPDEVUSER',$set);
  $set['level'] = 0;

  // AMPDEVGROUP
  $set['value'] = 'asterisk';
  $set['description'] = 'The user group that various device directories should be set to, used by freepbx_engine. Examples include /dev/zap, /dev/dahdi, /dev/misdn, /dev/mISDN and /dev/dsp. Most systems should not change this.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPDEVGROUP',$set);
  $set['level'] = 0;


  //
  // CATEGORY: Dialplan and Operational
  //
  $set['category'] = 'Dialplan and Operational';

  // AMPBADNUMBER
  $set['value'] = true;
  $set['description'] = 'Generate the bad-number context which traps any bogus number or feature code and plays a message to the effect. If you use the Early Dial feature on some Grandstream phones, you will want to set this to false.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('AMPBADNUMBER',$set);
  $set['level'] = 0;

  // CWINUSEBUSY
  $set['value'] = true;
  $set['description'] = 'For extensions that have CW enabled, report unanswered CW calls as <b>busy</b> (resulting in busy voicemail greeting). If set to no, unanswered CW calls simply report as <b>no-answer</b>.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('CWINUSEBUSY',$set);

  // ZAP2DAHDICOMPAT
  $set['value'] = false;
  $set['description'] = 'If set to true, FreePBX will check if you have chan_dadhi installed. If so, it will automatically use all your ZAP configuration settings (devices and trunks) and silently convert them, under the covers, to DAHDI so no changes are needed. The GUI will continue to refer to these as ZAP but it will use the proper DAHDI channels. This will also keep Zap Channel DIDs working.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('ZAP2DAHDICOMPAT',$set);

  // DYNAMICHINTS
  $set['value'] = false;
  $set['description'] = 'If true, Core will not statically generate hints, but instead make a call to the AMPBIN php script, and generate_hints.php through an Asterisk #exec call. This requires Asterisk.conf to be configured with <b>execincludes=yes<b> set in the [options] section.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('DYNAMICHINTS',$set);

  // ENABLECW
  $set['value'] = true;
  $set['description'] = 'Enable call waiting by default when an extension is created (Default is yes). Set to <b>no</b> to if you dont want phones to be commissioned with call waiting already enabled. The user would then be required to dial the CW feature code (*70 default) to enable their phone. Most installations should leave this alone. It allows multi-line phones to receive multiple calls on their line appearances.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('ENABLECW',$set);

  // FCBEEPONLY
  $set['value'] = false;
  $set['description'] = 'When set to true, a beep is played instead of confirmation message when activating/de-activating: CallForward, CallWaiting, DayNight, DoNotDisturb and FindMeFollow.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('FCBEEPONLY',$set);

  // USEDEVSTATE
  $set['value'] = false;
  $set['description'] = 'If this is set, it assumes that you are running Asterisk 1.4 or higher and want to take advantage of the func_devstate.c backport available from Asterisk 1.6. This allows custom hints to be created to support BLF for server side feature codes such as daynight, followme, etc';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('USEDEVSTATE',$set);

  // USEGOOGLEDNSFORENUM
  $set['value'] = false;
  $set['description'] = 'Setting this flag will generate the required global variable so that enumlookup.agi will use Google DNS 8.8.8.8 when performing an ENUM lookup. Not all DNS deals with NAPTR record, but Google does. There is a drawback to this as Google tracks every lookup. If you are not comfortable with this, do not enable this setting. Please read Google FAQ about this: <b>http://code.google.com/speed/public-dns/faq.html#privacy</b>.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('USEGOOGLEDNSFORENUM',$set);
  $set['level'] = 0;

  // DISABLECUSTOMCONTEXTS
  $set['value'] = false;
  $set['description'] = 'Normally FreePBX auto-generates a custom context that may be usable for adding custom dialplan to modify the normal behavior of FreePBX. It takes a good understanding of how Asterisk processes these includes to use this and in many of the cases, there is no useful application. All includes will result in a WARNING in the Asterisk log if there is no context found to include though it results in no errors. If you know that you want the includes, you can set this to true. If you comment it out FreePBX will revert to legacy behavior and include the contexts.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('DISABLECUSTOMCONTEXTS',$set);
  $set['level'] = 0;


  // NOOPTRACE
  $set['value'] = '0';
  $set['options'] = '0,1,2,3,4,5,6,7,8,9,10';
  $set['description'] = 'Some modules will generate lots of Noop() commands proceeded by a [TRACE](trace_level) that can be used during development or while trying to trace call flows. These Noop() commands serve no other purpose so if you do not want to see excessive Noop()s in your dialplan you can set this to 0. The higher the number the more detailed level of trace Noop()s will be generated';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('NOOPTRACE',$set);

  // DIVERSIONHEADER
  $set['value'] = false;
  $set['description'] = 'If this value is set to true, then calls going out your outbound routes that originate from outside your PBX and were subsequently forwarded through a call forward, ring group, follow-me or other means, will have a SIP diversion header added to the call with the original incoming DID assuming there is a DID available. This is useful with some carriers that may require this under certain circumstances.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('DIVERSIONHEADER',$set);

  // CFRINGTIMERDEFAULT
  $opts = array();
  for ($i=-1;$i<=120;$i++) {
      $opts[]=$i;
  }
  $set['value'] = '0';
  $set['options'] = $opts;
  $set['description'] = 'This is the default time in seconds to try and connect a call that has been call forwaded by the server side CF, CFU and CFB options. (If your phones use client side CF such as SIP redirects, this will not have any affect) If set to the default of 0, it will use the standard ring timer. If set to -1 it will ring the forwarded number with no limit which is consistent with the behavior of some existing PBX systems. If set to any other value, it will ring for that duration before diverting the call to the users voicemail if they have one. This can be overriden for each extension.';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('CFRINGTIMERDEFAULT',$set);
  unset($opts);  

  //
  // CATEGORY: Directory Layout
  //
  $set['category'] = 'Directory Layout';

  // AMPBIN
  $set['value'] = '/var/lib/asterisk/bin';
  $set['description'] = 'Location of the FreePBX command line scripts.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPBIN',$set);

  // AMPSBIN
  $set['value'] = '/usr/sbin';
  $set['description'] = 'Where (root) command line scripts are located.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPSBIN',$set);
    
  // AMPWEBROOT
  $set['value'] = '/var/www/html';
  $set['description'] = 'The path to Apache webroot (leave off trailing slash).';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPWEBROOT',$set);

  // ASTAGIDIR
  $set['value'] = '/var/lib/asterisk/agi-bin';
  $set['description'] = 'This is the default directory for Asterisks agi files.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTAGIDIR',$set);

  // ASTETCDIR
  $set['value'] = '/etc/asterisk';
  $set['description'] = 'This is the default directory for Asterisks configuration files.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTETCDIR',$set);

  // ASTLOGDIR
  $set['value'] = '/var/log/asterisk';
  $set['description'] = 'This is the default directory for Asterisks log files.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTLOGDIR',$set);

  // ASTMODDIR
  $set['value'] = '/usr/lib/asterisk/modules';
  $set['description'] = 'This is the default directory for Asterisks modules.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTMODDIR',$set);

  // ASTSPOOLDIR
  $set['value'] = '/var/spool/asterisk';
  $set['description'] = 'This is the default directory for Asterisks spool directory.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTSPOOLDIR',$set);

  // ASTRUNDIR
  $set['value'] = '/var/run/asterisk';
  $set['description'] = 'This is the default directory for Asterisks run files.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTRUNDIR',$set);

  // ASTVARLIBDIR
  $set['value'] = '/var/lib/asterisk';
  $set['description'] = 'This is the default directory for Asterisks lib files.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('ASTVARLIBDIR',$set);

  // AMPCGIBIN
  $set['value'] = '/var/www/cgi-bin ';
  $set['description'] = 'The path to Apache cgi-bin dir (leave off trailing slash).';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('AMPCGIBIN',$set);

  // MOHDIR
  $set['value'] = 'moh';
  $set['description'] = 'This is the subdirectory for the MoH files/directories which is located in ASTVARLIBDIR if not specified it will default to mohmp3 for backward compatibility.';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('MOHDIR',$set);
  $set['level'] = 0;


  //
  // CATEGORY: GUI Behavior
  //
  $set['category'] = 'GUI Behavior';

  // CHECKREFERER
  $set['value'] = true;
  $set['description'] = 'When set to the default value of true, all requests into FreePBX that might possibly add/edit/delete settings will be validated to assure the request is coming from the server. This will protect the system from CSRF (cross site request forgery) attacks. It will have the effect of preventing legitimately entering URLs that could modify settings which can be allowed by changing this field to false.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('CHECKREFERER',$set);

  // MODULEADMINWGET
  $set['value'] = false;
  $set['description'] = 'Module Admin normally tries to get its online information through direct file open type calls to URLs that go back to the freepbx.org server. If it fails, typically because of content filters in firewalls that dont like the way PHP formats the requests, the code will fall back and try a wget to pull the information. This will often solve the problem. However, in such environment there can be a significant timeout before the failed file open calls to the URLs return and there are often 2-3 of these that occur. Setting this value will force FreePBX to avoid the attempt to open the URL and go straight to the wget calls.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('MODULEADMINWGET',$set);

  // USECATEGORIES
  $set['value'] = true;
  $set['description'] = 'Controls if the menu items in the admin interface are sorted by category (true) or sorted alphebetically with no categories shown (false). Defaults = true';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('USECATEGORIES',$set);

  // SERVERINTITLE
  $set['value'] = false;
  $set['description'] = 'Precede browser title with the server name.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('SERVERINTITLE',$set);

  // RELOADCONFIRM
  $set['value'] = true;
  $set['description'] = 'When set to false, will bypass the confirm on Reload Box.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('RELOADCONFIRM',$set);

  // BADDESTABORT
  $set['value'] = false;
  $set['description'] = 'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired.';
  $set['level'] = 3;
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('BADDESTABORT',$set);
  $set['level'] = 0;

  // XTNCONFLICTABORT
  $set['value'] = false;
  $set['description'] = 'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired.';
  $set['level'] = 3;
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('XTNCONFLICTABORT',$set);
  $set['level'] = 0;

  // CUSTOMASERROR
  $set['value'] = true;
  $set['description'] = 'If false, then the Destination Registry will not report unknown destinations as errors. This should be left to the default true and custom destinations should be moved into the new custom apps registry.';
  $set['level'] = 2;
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('CUSTOMASERROR',$set);
  $set['level'] = 0;


  //
  // CATEGORY: Asterisk Manager
  //
  $set['category'] = 'Asterisk Manager';

  // AMPMGRPASS
  $set['value'] = 'amp111';
  $set['description'] = 'Password for accessing the Asterisk Manager Interface (AMI), you must change this in manager.conf if changed here.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('AMPMGRPASS',$set);
  $set['level'] = 0;

  // AMPMGRUSER
  $set['value'] = 'admin';
  $set['description'] = 'Username for accessing the Asterisk Manager Interface (AMI), you must change this in manager.conf if changed here.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('AMPMGRUSER',$set);
  $set['level'] = 0;

  // ASTMANAGERHOST
  $set['value'] = 'localhost';
  $set['description'] = 'Hostname for the Asterisk Manager';
  $set['type'] = CONF_TYPE_TEXT;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('ASTMANAGERHOST',$set);
  $set['level'] = 0;

  $set['type'] = CONF_TYPE_UINT;

  // ASTMANAGERPORT
  $set['value'] = '5038';
  $set['description'] = 'Port for the Asterisk Manager';
  $set['type'] = CONF_TYPE_UINT;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('ASTMANAGERPORT',$set);
  $set['level'] = 0;

  // ASTMANAGERPROXYPORT
  $set['value'] = '';
  $set['description'] = 'Optional port for an Asterisk Manager Proxy';
  $set['type'] = CONF_TYPE_UINT;
  $set['emptyok'] = 1;
  $set['level'] = 2;
  $freepbx_conf->define_conf_setting('ASTMANAGERPROXYPORT',$set);
  $set['level'] = 0;
  $set['emptyok'] = 0;


  //
  // CATEGORY: Developer and Customization
  //
  $set['category'] = 'Developer and Customization';
  $set['level'] = 2;

  // FPBXDBUGFILE
  $set['value'] = '/tmp/freepbx_debug.log';
  $set['description'] = 'Full path and name of FreePBX debug file. Used by the dbug() funciton by developers.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('FPBXDBUGFILE',$set);

  // DEVEL
  $set['value'] = false;
  $set['description'] = 'This enables several debug features geared towards developers, including some page load timing information, some debug information in Module Admin, use of original CSS files and other future capabilities will be enabled.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('DEVEL',$set);

  // DEVELRELOAD
  $set['value'] = false;
  $set['description'] = 'Forces the "Apply Configuration Changes" reload bar to always be present even when not necessary.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('DEVELRELOAD',$set);

  // PRE_RELOAD
  $set['value'] = '';
  $set['description'] = 'Optional script to run just prior to doing an extension reload to Asterisk through the manager after pressing Apply Configuration Changes in the GUI.';
  $set['emptyok'] = 1;
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('PRE_RELOAD',$set);
  $set['emptyok'] = 0;

  // POST_RELOAD
  $set['value'] = '';
  $set['description'] = 'Automatically execute a script after applying changes in the AMP admin. Set POST_RELOAD to the script you wish to execute after applying changes. If POST_RELOAD_DEBUG=true, you will see the output of the script in the web page.';
  $set['emptyok'] = 1;
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('POST_RELOAD',$set);
  $set['emptyok'] = 0;

  // POST_RELOAD_DEBUG
  $set['value'] = false;
  $set['description'] = 'Display debug output for script used if POST_RELOAD is used.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('POST_RELOAD_DEBUG',$set);

  // AMPLOCALBIN
  $set['value'] = '';
  $set['description'] = 'If this directory is defined, retrieve_conf will check for a file called <i>retrieve_conf_post_custom</i> and if that file exists, it will be included after other processing thus having full access to the current environment for addtional customization.';
  $set['type'] = CONF_TYPE_DIR;
  $freepbx_conf->define_conf_setting('AMPLOCALBIN',$set);

  // AMPDISABLELOG
  $set['value'] = true;
  $set['description'] = 'Whether or not to invoke the FreePBX log facility.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('AMPDISABLELOG',$set);

  // AMPENABLEDEVELDEBUG
  $set['value'] = false;
  $set['description'] = 'Whether or not to include log messages marked as <b>devel-debug</b> in the log system.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('AMPENABLEDEVELDEBUG',$set);

  // AMPSYSLOGLEVEL
  $set['value'] = 'LOG_ERR';
  $set['options'] = 'LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG, LOG_SQL,SQL';
  $set['description'] = 'Where to log if enabled, SQL, LOG_SQL logs to old MySQL table, others are passed to syslog system to determine where to log.';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('AMPSYSLOGLEVEL',$set);
  
  // DISABLE_CSS_AUTOGEN
  $set['value'] = false;
  $set['description'] = 'Stops the automatic generation of a stripped CSS file that replaces the primary sheet, usually mainstyle.css.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('DISABLE_CSS_AUTOGEN',$set);


  //
  // CATEGORY: Flash Operator Panel
  //
  $set['category'] = 'Flash Operator Panel';
  $set['level'] = 0;

  // FOPSORT
  $set['value'] = 'extension';
  $set['options'] = 'extension,lastname';
  $set['description'] = 'How FOP sort extensions. By Last Name [lastname] or by Extension [extension].';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('FOPSORT',$set);

  // FOPWEBROOT
  $set['value'] = '/var/www/html/panel';
  $set['description'] = 'Path to the Flash Operator Panel webroot (leave off trailing slash).';
  $set['type'] = CONF_TYPE_DIR;
  $set['level'] = 4;
  $freepbx_conf->define_conf_setting('FOPWEBROOT',$set);
  $set['level'] = 0;

  // FOPPASSWORD
  $set['value'] = 'passw0rd';
  $set['description'] = 'Password for performing transfers and hangups in the Flash Operator Panel (FOP).';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('FOPPASSWORD',$set);

  // FOPDISABLE
  $set['value'] = false;
  $set['description'] = 'Set to true to disable FOP in interface and retrieve_conf.  Useful for sqlite3 or if you do not want FOP.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('FOPDISABLE',$set);

  // FOPRUN
  $set['value'] = true;
  $set['description'] = 'Set to true if you want FOP started by freepbx_engine (amportal_start), false otherwise.';
  $set['type'] = CONF_TYPE_BOOL;
  $freepbx_conf->define_conf_setting('FOPRUN',$set);


  //
  // CATEGORY: Remote CDR Database
  //
  $set['category'] = 'Remote CDR Database';
  $set['level'] = 3;
  $set['emptyok'] = 1;

  // CDRDBHOST
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX.<br>Hostname of db server if not the same as AMPDBHOST.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBHOST',$set);

  // CDRDBNAME
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX.<br>Name of database used for cdr records.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBNAME',$set);

  // CDRDBPASS
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX.<br>Password for connecting to db if its not the same as AMPDBPASS.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBPASS',$set);

  // CDRDBPORT
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX.<br>Port number for db host.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBPORT',$set);

  // CDRDBTABLENAME
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX. Name of the table in the db where the cdr is stored. cdr is default.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBTABLENAME',$set);

  // CDRDBTYPE
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX. Defaults to your configured AMDBENGINE.';
  $set['options'] = ',mysql,postgres';
  $set['type'] = CONF_TYPE_SELECT;
  $freepbx_conf->define_conf_setting('CDRDBTYPE',$set);

  // CDRDBUSER
  $set['value'] = '';
  $set['description'] = 'DO NOT set this unless you know what you are doing. Only used if you dont use the default values provided by FreePBX. Username to connect to db with if it is not the same as AMPDBUSER.';
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('CDRDBUSER',$set);


  //
  // CATEGORY: Remote CDR Database
  //
  $set['category'] = 'Styling and Logos';
  $set['level'] = 1;
  $set['emptyok'] = 0;

  // AMPADMINLOGO
  $set['value'] = '';
  $set['description'] = 'Legacy setting, use BRAND_IMAGE_FREEPBX_RIGHT in the future. If set, this will override BRAND_IMAGE_FREEPBX_RIGHT. Overrides the standard logo that is to be displayed at the TOP RIGHT of the admin screen. This enables you to customize the look of the administration screen. NOTE: images need to be saved in the ..../admin/images directory of your AMP install. This image should be 55px in height.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('AMPADMINLOGO',$set);

  // BRAND_IMAGE_HIDE_NAV_BACKGROUND
  $set['value'] = false;
  $set['description'] = 'Hide the configured left navigation bar background.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_HIDE_NAV_BACKGROUND',$set);

  // BRAND_IMAGE_SHADOW_SIDE_BACKGROUND
  $set['value'] = 'images/shadow-side-background.png';
  $set['description'] = 'Styling image.';
  $set['emptyok'] = 1;
  $set['type'] = CONF_TYPE_TEXT;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_SHADOW_SIDE_BACKGROUND',$set);

  // BRAND_IMAGE_FREEPBX_RIGHT
  $set['value'] = 'images/logo.png';
  $set['description'] = 'Right upper logo. Use this setting instead of AMPADMINLOGO. Path is relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_RIGHT',$set);

  // BRAND_IMAGE_FREEPBX_LEFT
  $set['value'] = 'images/freepbx_large.png';
  $set['description'] = 'Left upper logo.  Path is relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_LEFT',$set);

  // BRAND_IMAGE_FREEPBX_FOOT
  $set['value'] = 'images/freepbx_small.png';
  $set['description'] = 'Logo in footer.  Path is relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_FOOT',$set);

  // BRAND_IMAGE_RELOAD_LOADING
  $set['value'] = 'images/loading.gif';
  $set['description'] = 'Image used during a reload, default is animated GIF eating the * (asterisk).  Path is relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_RELOAD_LOADING',$set);

  // BRAND_FREEPBX_ALT_LEFT
  $set['value'] = '';
  $set['description'] = 'alt attribute to use in place of image and title hover value. Defaults to FreePBX';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_FREEPBX_ALT_LEFT',$set);

  // BRAND_FREEPBX_ALT_RIGHT
  $set['value'] = '';
  $set['description'] = 'alt attribute to use in place of image and title hover value. Defaults to FreePBX';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_FREEPBX_ALT_RIGHT',$set);

  // BRAND_FREEPBX_ALT_FOOT
  $set['value'] = '';
  $set['description'] = 'alt attribute to use in place of image and title hover value. Defaults to FreePBX';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_FREEPBX_ALT_FOOT',$set);

  // BRAND_IMAGE_FREEPBX_LINK_LEFT
  $set['value'] = '';
  $set['description'] = 'link to follow when clicking on logo, defaults to http://www.freepbx.org';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_LINK_LEFT',$set);

  // BRAND_IMAGE_FREEPBX_LINK_RIGHT
  $set['value'] = '';
  $set['description'] = 'link to follow when clicking on logo, defaults to http://www.freepbx.org';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_LINK_RIGHT',$set);

  // BRAND_IMAGE_FREEPBX_LINK_FOOT
  $set['value'] = '';
  $set['description'] = 'link to follow when clicking on logo, defaults to http://www.freepbx.org';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_IMAGE_FREEPBX_LINK_FOOT',$set);

  // BRAND_HIDE_LOGO_RIGHT
  $set['value'] = false;
  $set['description'] = 'Setting to true will hide the upper right logo.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_HIDE_LOGO_RIGHT',$set);

  // BRAND_HIDE_HEADER_VERSION
  $set['value'] = false;
  $set['description'] = 'Setting to true will hide the FreePBX version information below the left upper header.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_HIDE_HEADER_VERSION',$set);

  // BRAND_HIDE_HEADER_MENUS
  $set['value'] = false;
  $set['description'] = 'Setting to true will hide the complete horizontal menu bar in the header.';
  $set['type'] = CONF_TYPE_BOOL;
  $set['emptyok'] = 0;
  $freepbx_conf->define_conf_setting('BRAND_HIDE_HEADER_MENUS',$set);

  // BRAND_CSS_ALT_MAINSTYLE
  $set['value'] = '';
  $set['description'] = 'Set this to replace the default mainstyle.css style sheet with your own, relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_CSS_ALT_MAINSTYLE',$set);

  // BRAND_CSS_CUSTOM
  $set['value'] = '';
  $set['description'] = 'Optional custom CSS style sheet included after the primary one and any module specific ones are loaded, relative to admin.';
  $set['type'] = CONF_TYPE_TEXT;
  $set['emptyok'] = 1;
  $freepbx_conf->define_conf_setting('BRAND_CSS_CUSTOM',$set);


  // The following settings are used in various modules prior to 2.9. If they are found in amportal.conf then we
  // retain their values until the individual modules are updated and their install scripts run where a full
  // configuration (descriptions, defaults, etc.) will be provided and maintained. This provides just enough to
  // carry the setting through the migration since most upgrades will run framework or install_amp followed by the
  // module install scripts.
  //
  $module_migrate['AMPPLAYKEY'] = CONF_TYPE_TEXT;
  $module_migrate['AMPBACKUPEMAILFROM'] = CONF_TYPE_TEXT;
  $module_migrate['AMPBACKUPSUDO'] = CONF_TYPE_BOOL;
  $module_migrate['USEQUEUESTATE'] = CONF_TYPE_BOOL;
  $module_migrate['DASHBOARD_INFO_UPDATE_TIME'] = CONF_TYPE_UINT;
  $module_migrate['DASHBOARD_STATS_UPDATE_TIME'] = CONF_TYPE_UINT;
  $module_migrate['SSHPORT'] = CONF_TYPE_UINT;
  $module_migrate['MAXCALLS'] = CONF_TYPE_UINT;
  $module_migrate['AMPMPG123'] = CONF_TYPE_BOOL;
  $module_migrate['PARKINGPATCH'] = CONF_TYPE_BOOL;

  $mod_set['value'] = '';
  $mod_set['defaultval'] = '';
  $mod_set['readonly'] = 0;
  $mod_set['hidden'] = 1;
  $mod_set['level'] = 10;
  $mod_set['module'] = '';
  $mod_set['category'] = 'Under Migration';
  $mod_set['emptyok'] = 1;
  $mod_set['description'] = 'This setting is being migrated and will be initialized by its module install script on upgrad.';
  foreach ($module_migrate as $setting => $type) {
    if (isset($amp_conf[$setting])  && !$freepbx_conf->conf_setting_exists($setting)) {
      $val = $amp_conf[$setting];

      // since this came from a conf file, change any 'false' that will otherwise turn to true
      //
      if ($type == CONF_TYPE_BOOL) switch (strtolower($val)) {
      case 'false':
      case 'no':
      case 'off':
        $val = false;
      break;
      }
      $mod_set['value'] = $val;
      $mod_set['type'] = $type;
      $freepbx_conf->define_conf_setting($setting,$mod_set);
    }
  }

  if ($commit_to_db) {
    $freepbx_conf->commit_conf_settings();
  }
}

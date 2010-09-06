<?php /* $id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/featurecodes.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/components.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/notifications.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/moduleHook.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/modulelist.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/cronmanager.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/ampuser.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/xml2Array.class.php');

require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'libraries/module.functions.php');

$amp_conf_defaults = array(
	'AMPDBENGINE'    => array('std' , 'mysql'),
	'AMPDBNAME'      => array('std' , 'asterisk'),
	'AMPENGINE'      => array('std' , 'asterisk'),
	'ASTMANAGERPORT' => array('std' , '5038'),
	'ASTMANAGERHOST' => array('std' , 'localhost'),
	'AMPDBHOST'      => array('std' , 'localhost'),
	'AMPDBUSER'      => array('std' , 'asteriskuser'),
	'AMPDBPASS'      => array('std' , 'amp109'),
	'AMPMGRUSER'     => array('std' , 'admin'),
	'AMPMGRPASS'     => array('std' , 'amp111'),
	'FOPPASSWORD'    => array('std' , 'passw0rd'),
	'FOPSORT'        => array('std' , 'extension'),
	'AMPSYSLOGLEVEL '=> array('std' , 'LOG_ERR'),
	'ARI_ADMIN_PASSWORD' => array('std' , 'ari_password'),

	'ASTETCDIR'      => array('dir' , '/etc/asterisk'),
	'ASTMODDIR'      => array('dir' , '/usr/lib/asterisk/modules'),
	'ASTVARLIBDIR'   => array('dir' , '/var/lib/asterisk'),
	'ASTAGIDIR'      => array('dir' , '/var/lib/asterisk/agi-bin'),
	'ASTSPOOLDIR'    => array('dir' , '/var/spool/asterisk/'),
	'ASTRUNDIR'      => array('dir' , '/var/run/asterisk'),
	'ASTLOGDIR'      => array('dir' , '/var/log/asterisk'),
	'AMPBIN'         => array('dir' , '/var/lib/asterisk/bin'),
	'AMPSBIN'        => array('dir' , '/usr/sbin'),
	'AMPWEBROOT'     => array('dir' , '/var/www/html'),
	'FOPWEBROOT'     => array('dir' , '/var/www/html/panel'),
	'MOHDIR'         => array('dir' , '/mohmp3'),
	'FPBXDBUGFILE'	 => array('dir' , '/tmp/freepbx_debug.log'),

	'USECATEGORIES'  => array('bool' , true),
	'ENABLECW'       => array('bool' , true),
	'CWINUSEBUSY'    => array('bool' , true),
	'FOPRUN'         => array('bool' , true),
	'AMPBADNUMBER'   => array('bool' , true),
	'DEVEL'          => array('bool' , false),
	'DEVELRELOAD'    => array('bool' , false),
	'CUSTOMASERROR'  => array('bool' , true),
	'DYNAMICHINTS'   => array('bool' , false),
	'BADDESTABORT'   => array('bool' , false),
	'SERVERINTITLE'  => array('bool' , false),
	'XTNCONFLICTABORT' => array('bool' , false),
	'USEDEVSTATE'    => array('bool' , false),
	'MODULEADMINWGET'=> array('bool' , false),
	'AMPDISABLELOG'  => array('bool' , true),
	'AMPENABLEDEVELDEBUG'=> array('bool' , false),
	'AMPMPG123'       => array('bool' , true),
	'FOPDISABLE'      => array('bool' , false),
	'ZAP2DAHDICOMPAT' => array('bool' , false),
	'USEQUEUESTATE'   => array('bool' , false),
	'CHECKREFERER'    => array('bool' , true),
	'USEDIALONE'      => array('bool' , false),
	'RELOADCONFIRM'   => array('bool' , true),
	'DISABLECUSTOMCONTEXTS'   => array('bool' , false),

);

function parse_amportal_conf($filename) {
	global $amp_conf_defaults;

	/* defaults
	 * This defines defaults and formating to assure consistency across the system so that
	 * components don't have to keep being 'gun shy' about these variables.
	 * 
	 */
	$file = file($filename);
	if (is_array($file)) {
		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_!<>\"\']+)\s*$/",$line,$matches)) {
				$conf[ $matches[1] ] = $matches[2];
			}
		}
	} else {
		die_freepbx("<h1>".sprintf(_("Missing or unreadable config file (%s)...cannot continue"), $filename)."</h1>");
	}
	
	// set defaults
	foreach ($amp_conf_defaults as $key=>$arr) {

		switch ($arr[0]) {
			// for type dir, make sure there is no trailing '/' to keep consistent everwhere
			//
			case 'dir':
				if (!isset($conf[$key]) || trim($conf[$key]) == '') {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = rtrim($conf[$key],'/');
				}
				break;
			// booleans:
			// "yes", "true", "on", true, 1 (case-insensitive) will be treated as true, everything else is false
			//
			case 'bool':
				if (!isset($conf[$key])) {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = ($conf[$key] === true || strtolower($conf[$key]) == 'true' || $conf[$key] === 1 || $conf[$key] == '1' 
					                                    || strtolower($conf[$key]) == 'yes' ||  strtolower($conf[$key]) == 'on');
				}
				break;
			default:
				if (!isset($conf[$key])) {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = trim($conf[$key]);
				}
		}
	}
	return $conf;
}

function parse_asterisk_conf($filename) {
	//TODO: Should the correction of $amp_conf be passed by refernce and optional?
	//
	global $amp_conf;
	$conf = array();
		
	$convert = array(
		'astetcdir'    => 'ASTETCDIR',
		'astmoddir'    => 'ASTMODDIR',
		'astvarlibdir' => 'ASTVARLIBDIR',
		'astagidir'    => 'ASTAGIDIR',
		'astspooldir'  => 'ASTSPOOLDIR',
		'astrundir'    => 'ASTRUNDIR',
		'astlogdir'    => 'ASTLOGDIR'
	);

	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([a-zA-Z0-9]+)\s* => \s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			$conf[ $matches[1] ] = rtrim($matches[2],"/ \t");
		}
	}

	// Now that we parsed asterisk.conf, we need to make sure $amp_conf is consistent
	// so just set it to what we found, since this is what asterisk will use anyhow.
	//
	foreach ($convert as $ast_conf_key => $amp_conf_key) {
		if (isset($conf[$ast_conf_key])) {
			$amp_conf[$amp_conf_key] = $conf[$ast_conf_key];
		}
	}
	return $conf;
}

/** check if a specific extension is being used, or get a list of all extensions that are being used
 * @param mixed     an array of extension numbers to check against, or if boolean true then return list of all extensions
 * @param array     a hash of module names to search for callbacks, otherwise global $active_modules is used
 * @return array    returns an empty array if exten not in use, or any array with usage info, or of all usage 
 *                  if exten is boolean true
 * @description     Upon passing in an array of extension numbers, this api will query all modules to determine if any
 *                  are using those extension numbers. If so, it will return an array with the usage information
 *                  as described below, otherwise an empty array. If passed boolean true, it will return an array
 *                  of the same format with all extensions on the system that are being used.
 *
 *                  $exten_usage[$module][$exten]['description'] // description of the extension
 *                                               ['edit_url']    // a url that could be invoked to edit extension
 *                                               ['status']      // Status: INUSE, RESERVED, RESTRICTED
 */
function framework_check_extension_usage($exten=true, $module_hash=false) {
	global $active_modules;
	$exten_usage = array();

	if (!is_array($module_hash)) {
		$module_hash = $active_modules;
	}

	if (!is_array($exten) && $exten !== true) {
		$exten = array($exten);
	}

	foreach(array_keys($module_hash) as $mod) {
		$function = $mod."_check_extensions";
		if (function_exists($function)) {
			$prev_domain = textdomain(NULL);
			if (isset($_COOKIE['lang']) && is_dir("./modules/$mod/i18n/".$_COOKIE['lang'])) {
				bindtextdomain($mod,"./modules/$mod/i18n");
				bind_textdomain_codeset($mod, 'utf8');
				textdomain($mod);
				$module_usage = $function($exten);
			} else {
				textdomain('amp');
				$module_usage = $function($exten);
			}
			if (!empty($module_usage)) {
				$exten_usage[$mod] = $module_usage;
			}
			textdomain($prev_domain);
		}
	}
	if ($exten === true) {
		return $exten_usage;
	} else {
    $exten_matches = array();
		foreach (array_keys($exten_usage) as $mod) {
			foreach ($exten as $test_exten) {
				if (isset($exten_usage[$mod][$test_exten])) {
					$exten_matches[$mod][$test_exten] = $exten_usage[$mod][$test_exten];
				}
			}
		}
	}
	return $exten_matches;
}

/** check if a specific destination is being used, or get a list of all destinations that are being used
 * @param mixed     an array of destinations to check against, or if boolean true then return list of all destinations in use
 * @param array     a hash of module names to search for callbacks, otherwise global $active_modules is used
 * @return array    returns an empty array if destination not in use, or any array with usage info, or of all usage 
 *                  if dest is boolean true
 * @description     Upon passing in an array of destinations, this api will query all modules to determine if any
 *                  are using that destination. If so, it will return an array with the usage information
 *                  as described below, otherwise an empty array. If passed boolean true, it will return an array
 *                  of the same format with all destinations on the system that are being used.
 *
 *                  $dest_usage[$module][]['dest']        // The destination being used
 *                                        ['description'] // Description of who is using it
 *                                        ['edit_url']    // a url that could be invoked to edit the using entity
 *                                               
 */
function framework_check_destination_usage($dest=true, $module_hash=false) {
	global $active_modules;

	$dest_usage = array();
	$dest_matches = array();

	if (!is_array($module_hash)) {
		$module_hash = $active_modules;
	}

	if (!is_array($dest) && $dest !== true) {
		$dest = array($dest);
	}

	foreach(array_keys($module_hash) as $mod) {
		$function = $mod."_check_destinations";
		if (function_exists($function)) {
			$prev_domain = textdomain(NULL);
			if (isset($_COOKIE['lang']) && is_dir("./modules/$mod/i18n/".$_COOKIE['lang'])) {
				bindtextdomain($mod,"./modules/$mod/i18n");
				bind_textdomain_codeset($mod, 'utf8');
				textdomain($mod);
				$module_usage = $function($dest);
			} else {
				textdomain('amp');
				$module_usage = $function($dest);
			}
			if (!empty($module_usage)) {
				$dest_usage[$mod] = $module_usage;
			}
			textdomain($prev_domain);
		}
	}
	if ($dest === true) {
		return $dest_usage;
	} else {
		/*
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => 'Annoucement: '.$result['description'],
			'edit_url' => 'config.php?display=announcement&type='.$type.'&extdisplay='.urlencode($thisid),
		);
		*/
		foreach (array_keys($dest_usage) as $mod) {
			foreach ($dest as $test_dest) {
				foreach ($dest_usage[$mod] as $dest_item) {
					if ($dest_item['dest'] == $test_dest) {
						$dest_matches[$mod][] = $dest_item;
					}
				}
			}
		}
	}
	return $dest_matches;
}

/** provide optional alert() box and formatted url info for extension conflicts
 * @param array     an array of extensions that are in conflict obtained from framework_check_extension_usage
 * @param boolean   default false. True if url and descriptions should be split, false to combine (see return)
 * @param boolean   default true. True to echo an alert() box, false to bypass the alert box
 * @return array    returns an array of formatted URLs with descriptions. If $split is true, retuns an array
 *                  of the URLs with each element an array in the format of array('label' => 'description, 'url' => 'a url')
 * @description     This is used upon detecting conflicting extension numbers to provide an optional alert box of the issue
 *                  by a module which should abort the attempt to create the extension. It also returns an array of
 *                  URLs that can be displayed by the module to show the conflicting extension(s) and links to edit
 *                  them or further interogate. The resulting URLs are returned in an array either formatted for immediate
 *                  display or split into a description and the raw URL to provide more fine grained control (or use with guielements).
 */
function framework_display_extension_usage_alert($usage_arr=array(),$split=false,$alert=true) {
	$url = array();
	if (!empty($usage_arr)) {
		$conflicts=0;
		foreach($usage_arr as $rawmodule => $properties) {
			foreach($properties as $exten => $details) {
				$conflicts++;
				if ($conflicts == 1) {
					switch ($details['status']) {
						case 'INUSE':
							$str = "Extension $exten not available, it is currently used by ".htmlspecialchars($details['description']).".";
							if ($split) {
								$url[] =  array('label' => "Edit: ".htmlspecialchars($details['description']),
								                 'url'  =>  $details['edit_url'],
								               );
							} else {
								$url[] =  "<a href='".$details['edit_url']."'>Edit: ".htmlspecialchars($details['description'])."</a>";
							}
							break;
						default:
						$str = "This extension is not available: ".htmlspecialchars($details['description']).".";
					}
				} else {
					if ($split) {
						$url[] =  array('label' => "Edit: ".htmlspecialchars($details['description']),
						                 'url'  =>  $details['edit_url'],
													 );
					} else {
						$url[] =  "<a href='".$details['edit_url']."'>Edit: ".htmlspecialchars($details['description'])."</a>";
					}
				}
			}
		}
		if ($conflicts > 1) {
			$str .= sprintf(" There are %s additonal conflicts not listed",$conflicts-1);
		}
	}
	if ($alert) {
		echo "<script>javascript:alert('$str')</script>";
	}
	return($url);
}

/** check if a specific destination is being used, or get a list of all destinations that are being used
 * @param mixed     an array of destinations to check against
 * @param array     a hash of module names to search for callbacks, otherwise global $active_modules is used
 * @return array    array with a message and tooltip to display usage of this destination
 * @description     This is called to generate a label and tooltip which summarized the usage of this
 *                  destination and a tooltip listing all the places that use it
 *
 */
function framework_display_destination_usage($dest, $module_hash=false) {

	if (!is_array($dest)) {
		$dest = array($dest);
	}
	$usage_list = framework_check_destination_usage($dest, $module_hash);
	if (!empty($usage_list)) {
		$usage_count = 0;
		$str = null;
		foreach ($usage_list as $mod_list) {
			foreach ($mod_list as $details) {
				$usage_count++;
				$str .= $details['description']."<br />";
			}
		}
		$object = $usage_count > 1 ? _("Objects"):_("Object");
		return array('text' => '&nbsp;'.sprintf(dgettext('amp',"Used as Destination by %s %s"),$usage_count, dgettext('amp',$object)),
		             'tooltip' => $str,
							 	);
	} else {
		return array();
	}
}

/** determines which module a list of destinations belongs to, and if the destination object exists
 * @param mixed     an array of destinations to check against
 * @param array     a hash of module names to search for callbacks, otherwise global $active_modules is used
 * @return array    an array structure with informaiton about the destinations (see code)
 * @description     Mainly used by framework_list_problem_destinations. This function will find the module
 *                  that a destination belongs to and determine if the object still exits. This allow it to
 *                  either obtain the identify, identify it as an object that has been deleted, or identify
 *                  it as an unknown destination, usually a custom destination.
 *
 */
function framework_identify_destinations($dest, $module_hash=false) {
	global $active_modules;
	static $dest_cache = array();

	$dest_results = array();

	$dest_usage = array();
	$dest_matches = array();

	if (!is_array($module_hash)) {
		$module_hash = $active_modules;
	}

	if (!is_array($dest)) {
		$dest = array($dest);
	}

	foreach ($dest as $target) {
		if (isset($dest_cache[$target])) {
			$dest_results[$target] = $dest_cache[$target];
		} else {
			$found_owner = false;
			foreach(array_keys($module_hash) as $mod) {
				$function = $mod."_getdestinfo";
				if (function_exists($function)) {
					$prev_domain = textdomain(NULL);
					if (isset($_COOKIE['lang']) && is_dir("./modules/$mod/i18n/".$_COOKIE['lang'])) {
						bindtextdomain($mod,"./modules/$mod/i18n");
						bind_textdomain_codeset($mod, 'utf8');
						textdomain($mod);
						$check_module = $function($target);
					} else {
						textdomain('amp');
						$check_module = $function($target);
					}
					textdomain($prev_domain);
					if ($check_module !== false) {
						$found_owner = true;
						$dest_cache[$target] = array($mod => $check_module);
						$dest_results[$target] = $dest_cache[$target];
						break;
					}
				}
			}
			if (! $found_owner) {
				//echo "Not Found: $target\n";
				$dest_cache[$target] = false;
				$dest_results[$target] = $dest_cache[$target];
			}
		}
	}
	return $dest_results;
}

/** create a comprehensive list of all destinations that are problematic
 * @param array     an array of destinations to check against
 * @param bool      set to true if custome (unknown) destinations should be reported
 * @return array    an array of the destinations that are empty, orphaned or custom
 * @description     This function will scan the entire system and identify destinations
 *                  that are problematic. Either empty, orphaned or an unknow custom
 *                  destinations. An orphaned destination is one that should belong
 *                  to a module but the object it would have pointed to does not exist
 *                  because it was probably deleted.
 */
function framework_list_problem_destinations($module_hash=false, $ignore_custom=false) {
	global $active_modules;

	if (!is_array($module_hash)) {
		$module_hash = $active_modules;
	}

	$my_dest_arr = array();
	$problem_dests = array();

	$all_dests = framework_check_destination_usage(true, $module_hash);

	foreach ($all_dests as $dests) {
		foreach ($dests as $adest) {
			if (!empty($adest['dest'])) {
				$my_dest_arr[] = $adest['dest'];
			}
		}
	}
	$my_dest_arr = array_unique($my_dest_arr);

	$identities = framework_identify_destinations($my_dest_arr, $module_hash);

	foreach ($all_dests as $dests) {
		foreach ($dests as $adest) {
			if (empty($adest['dest'])) {
				$problem_dests[] = array('status' => 'EMPTY', 
					                       'dest' => $adest['dest'],
					                       'description' => $adest['description'],
					                       'edit_url' => $adest['edit_url'],
															  );
			} else if ($identities[$adest['dest']] === false){
				if ($ignore_custom) {
					continue;
				}
				$problem_dests[] = array('status' => 'CUSTOM', 
					                       'dest' => $adest['dest'],
					                       'description' => $adest['description'],
					                       'edit_url' => $adest['edit_url'],
															  );
			} else if (is_array($identities[$adest['dest']])){
				foreach ($identities[$adest['dest']] as $details) {
					if (empty($details)) {
						$problem_dests[] = array('status' => 'ORPHAN', 
						                         'dest' => $adest['dest'],
						                         'description' => $adest['description'],
						                         'edit_url' => $adest['edit_url'],
						                        );

					}
					break; // there is only one set per array
				}
			} else {
				echo "ERROR?\n";
				var_dump($adest);
			}
		}
	}
	return $problem_dests;
}

/** sort the hash based on the inner key
 */
function _framework_sort_exten($a, $b) {
	$a_key = array_keys($a);
	$a_key = $a_key[0];
	$b_key = array_keys($b);
	$b_key = $b_key[0];
	if ($a_key == $b_key) {
		return 0;
	} else {
		return ($a_key < $b_key) ? -1 : 1;
	}
}

/** create a comprehensive list of all extensions conflicts
 * @return array    an array of the destinations that are empty, orphaned or custom
 * @description     This returns an array structure with information about all
 *                  extension numbers that are in conflict. This means the same number
 *                  is being used by 2 or more modules and the results will be ambiguous
 *                  which one will be ignored when dialed. See the code for the
 *                  structure of the retured array.
 */
function framework_list_extension_conflicts($module_hash=false) {
	global $active_modules;

	if (!is_array($module_hash)) {
		$module_hash = $active_modules;
	}

	$exten_list = framework_check_extension_usage(true,$module_hash);

	/** Bookkeeping hashes
 	*  full_hash[]     will contain the first extension encountered
 	*  conflict_hash[] will contain any subsequent extensions if conflicts
 	*
 	*  If there are conflicts, the full set is what is in conflict_hash + the
 	*  first extension encoutnered in full_hash[]
 	*/
	$full_hash = array();
	$conflict_hash = array();

	foreach ($exten_list as $mod => $mod_extens) {
		foreach ($mod_extens as $exten => $details) {
			if (!isset($full_hash[$exten])) {
				$full_hash[$exten] = $details;
			} else {
				$conflict_hash[] = array($exten => $details);
			}
		}
	}

	// extract conflicting remaining extension from full_hash but needs to be unique
	//
	if (!empty($conflict_hash)) {
		$other_conflicts = array();
		foreach ($conflict_hash as $item)  {
			foreach (array_keys($item) as $exten) {
				$other_conflicts[$exten] = $full_hash[$exten];
			}
		}
		foreach ($other_conflicts as $exten => $details) {
			$conflict_hash[] = array($exten => $details);
		}
		usort($conflict_hash, "_framework_sort_exten");
		return $conflict_hash;
	}
}

/** Expands variables from amportal.conf 
 * Replaces any variables enclosed in percent (%) signs with their value
 * eg, "%AMPWEBROOT%/admin/functions.inc.php"
 */
function expand_variables($string) {
	global $amp_conf;
	$search = $replace = array();
	foreach ($amp_conf as $key=>$value) {
		$search[] = '%'.$key.'%';
		$replace[] = $value;
	}
	return str_replace($search, $replace, $string);
}

// returns true if extension is within allowed range
function checkRange($extension){
	$low = isset($_SESSION["AMP_user"]->_extension_low)?$_SESSION["AMP_user"]->_extension_low:'';
	$high = isset($_SESSION["AMP_user"]->_extension_high)?$_SESSION["AMP_user"]->_extension_high:'';
	
	if ((($extension >= $low) && ($extension <= $high)) || ($low == '' && $high == ''))
		return true;
	else
		return false;
}

function getAmpAdminUsers() {
	global $db;

	$sql = "SELECT username FROM ampusers WHERE sections='*'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die_freepbx($sql."<br>\n".$results->getMessage());
	}
	return $results;
}

function getAmpUser($username) {
	global $db;
	
	$sql = "SELECT username, password_sha1, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = '".$db->escapeSimple($username)."'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die_freepbx($sql."<br>\n".$results->getMessage());
	}
	
	if (count($results) > 0) {
		$user = array();
		$user["username"] = $results[0][0];
		$user["password_sha1"] = $results[0][1];
		$user["extension_low"] = $results[0][2];
		$user["extension_high"] = $results[0][3];
		$user["deptname"] = $results[0][4];
		$user["sections"] = explode(";",$results[0][5]);
		return $user;
	} else {
		return false;
	}
}

// returns true if department string matches dept for this user
function checkDept($dept){
	$deptname = isset($_SESSION["AMP_user"])?$_SESSION["AMP_user"]->_deptname:null;
	
	if ( ($dept == null) || ($dept == $deptname) )
		return true;
	else
		return false;
}

/**
 * returns true if asterisk is running with chan_dahdi
 *
 * @return bool
 */
function ast_with_dahdi() {
	global $version;
	global $astman;
	global $amp_conf;
	global $chan_dahdi_loaded;

  // determine once, subsequent calls will use this
  global $ast_with_dahdi;

  if (isset($ast_with_dahdi)) {
    return $ast_with_dahdi;
  }
	
	if (empty($version)) {
		$engine_info = engine_getinfo();
		$version = $engine_info['version'];
	}
		
	if (version_compare($version, '1.4', 'ge') && $amp_conf['AMPENGINE'] == 'asterisk') {		
    if ($amp_conf['ZAP2DAHDICOMPAT']) {
      $ast_with_dahdi = true;
      $chan_dahdi_loaded = true;
      return true;
    } else if (isset($astman) && $astman->connected()) {
			// earlier revisions of 1.4 ahd dadhi loaded but still running as zap, so if ZapScan is present, we assume
      // that is the mode it is running in.
			$response = $astman->send_request('Command', array('Command' => 'show applications like ZapScan'));
			if (!preg_match('/1 Applications Matching/', $response['data'])) {
        $ast_with_dahdi = true;
        $chan_dahdi_loaded = true;
				return $ast_with_dahdi;
			} else {
        $chan_dahdi_loaded = false;
			}
		}
	}
  $ast_with_dahdi = false;
  return $ast_with_dahdi;
}

function engine_getinfo() {
	global $amp_conf;
	global $astman;

	switch ($amp_conf['AMPENGINE']) {
		case 'asterisk':
			if (isset($astman) && $astman->connected()) {
				//get version (1.4)
				$response = $astman->send_request('Command', array('Command'=>'core show version'));
				if (preg_match('/No such command/',$response['data'])) {
					// get version (1.2)
					$response = $astman->send_request('Command', array('Command'=>'show version'));
				}
				$verinfo = $response['data'];
			} else {
				// could not connect to asterisk manager, try console
				$verinfo = exec('asterisk -V');
			}
			
			if (preg_match('/Asterisk (\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[4], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk SVN-(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[4], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk SVN-branch-(\d+(\.\d+)*)-r(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => $matches[1].'.'.$matches[4], 'additional' => $matches[4], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk SVN-trunk-r(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => '1.6', 'additional' => $matches[1], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk SVN-.+-(\d+(\.\d+)*)-r(-?(\S*))-(.+)/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[3], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk [B].(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => '1.2', 'additional' => $matches[3], 'raw' => $verinfo);
			} elseif (preg_match('/Asterisk [C].(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => '1.4', 'additional' => $matches[3], 'raw' => $verinfo);
			}

			return array('engine'=>'ERROR-UNABLE-TO-PARSE', 'version'=>'0', 'additional' => '0', 'raw' => $verinfo);
		break;
	}
	return array('engine'=>'ERROR-UNSUPPORTED-ENGINE-'.$amp_conf['AMPENGINE'], 'version'=>'0', 'additional' => '0', 'raw' => $verinfo);
}

if (!function_exists('version_compare_freepbx')) {
	/* verison_compare that works with freePBX version numbers
 	*/
	function version_compare_freepbx($version1, $version2, $op = null) {
        	$version1 = str_replace("rc","RC", strtolower($version1));
        	$version2 = str_replace("rc","RC", strtolower($version2));
			if (!is_null($op)) {
				return version_compare($version1, $version2, $op);
			} else {
				return version_compare($version1, $version2);
			}
	}
}

/* queries database using PEAR.
*  $type can be query, getAll, getRow, getCol, getOne, etc
*  $fetchmode can be DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC, DB_FETCHMODE_OBJECT
*  returns array, unless using getOne
*/
function sql($sql,$type="query",$fetchmode=null) {
	global $db;
	$results = $db->$type($sql,$fetchmode);
	if(DB::IsError($results)) {
		die_freepbx($results->getDebugInfo() . "SQL - <br /> $sql" );
	}
	return $results;
}

/**  Format input so it can be safely used as a literal in a query. 
 * Literals are values such as strings or numbers which get utilized in places
 * like WHERE, SET and VALUES clauses of SQL statements.
 * The format returned depends on the PHP data type of input and the database 
 * type being used. This simply calls PEAR's DB::smartQuote() function
 * @param  mixed  The value to go into the database
 * @return string  A value that can be safely inserted into an SQL query
 */
function q(&$value) {
	global $db;
	return $db->quoteSmart($value);
}

// sql text formatting -- couldn't see that one was available already
function sql_formattext($txt) {
	global $db;
	if (isset($txt)) {
		$fmt = $db->escapeSimple($txt);
		$fmt = "'" . $fmt . "'";
	} else {
		$fmt = 'null';
	}

	return $fmt;
}

function die_freepbx($text, $extended_text="", $type="FATAL") {
  $trace = print_r(debug_backtrace(),true);
	if (function_exists('fatal')) {
		// "custom" error handler 
		// fatal may only take one param, so we suppress error messages because it doesn't really matter
		@fatal($text."\n".$trace, $extended_text, $type);
	} else if (isset($_SERVER['REQUEST_METHOD'])) {
		// running in webserver
		echo "<h1>".$type." ERROR</h1>\n";
		echo "<h3>".$text."</h3>\n";
		if (!empty($extended_text)) {
			echo "<p>".$extended_text."</p>\n";
		}
    echo "<h4>"._("Trace Back")."</h4>";
    echo "<pre>$trace</pre>";
	} else {
		// CLI
		echo "[$type] ".$text." ".$extended_text."\n";
    echo "Trace Back:\n";
    echo $trace;
	}

	// always ensure we exit at this point
	exit(1);
}

//tell application we need to reload asterisk
function needreload() {
	global $db;
	$sql = "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die_freepbx($sql."<br>\n".$result->getMessage()); 
	}
}

function check_reload_needed() {
	global $db;
	global $amp_conf;
	$sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
	$row = $db->getRow($sql);
	if(DB::IsError($row)) {
		die_freepbx($sql."<br>\n".$row->getMessage());
	}
	return ($row[0] == 'true' || $amp_conf['DEVELRELOAD']);
}

function do_reload() {
	global $amp_conf, $asterisk_conf, $db, $astman, $version;

	if (empty($version)) {
		$engine_info = engine_getinfo();
		$version = $engine_info['version'];
	}
	
	$notify =& notifications::create($db);
	
	$return = array('num_errors'=>0,'test'=>'abc');
	$exit_val = null;
	
	if (isset($amp_conf["PRE_RELOAD"]) && !empty($amp_conf['PRE_RELOAD']))  {
		exec( $amp_conf["PRE_RELOAD"], $output, $exit_val );
		
		if ($exit_val != 0) {
			$desc = sprintf(_("Exit code was %s and output was: %s"), $exit_val, "\n\n".implode("\n",$output));
			$notify->add_error('freepbx','reload_pre_script', sprintf(_('Could not run %s script.'), $amp_conf['PRE_RELOAD']), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx', 'reload_pre_script');
		}
	}
	
	$retrieve = $amp_conf['AMPBIN'].'/retrieve_conf 2>&1';
	//exec($retrieve.'&>'.$asterisk_conf['astlogdir'].'/freepbx-retrieve.log', $output, $exit_val);
	exec($retrieve, $output, $exit_val);
	
	// retrive_conf html output
	$return['retrieve_conf'] = 'exit: '.$exit_val.'<br/>'.implode('<br/>',$output);
	
	if ($exit_val != 0) {
		$return['status'] = false;
		$return['message'] = sprintf(_('Reload failed because retrieve_conf encountered an error: %s'),$exit_val);
		$return['num_errors']++;
		$notify->add_critical('freepbx','RCONFFAIL', _("retrieve_conf failed, config not applied"), $return['message']);
		return $return;
	}
	
	if (!isset($astman) || !$astman) {
		$return['status'] = false;
		$return['message'] = _('Reload failed because FreePBX could not connect to the asterisk manager interface.');
		$return['num_errors']++;
		$notify->add_critical('freepbx','RCONFFAIL', _("retrieve_conf failed, config not applied"), $return['message']);
		return $return;
	}
	$notify->delete('freepbx', 'RCONFFAIL');
	
	//reload MOH to get around 'reload' not actually doing that.
	$astman->send_request('Command', array('Command'=>'moh reload'));
	
	//reload asterisk
  if (version_compare($version,'1.4','lt')) {
	  $astman->send_request('Command', array('Command'=>'reload'));	
  } else {
	  $astman->send_request('Command', array('Command'=>'module reload'));	
  }
	
	$return['status'] = true;
	$return['message'] = _('Successfully reloaded');
	
	
	if ($amp_conf['FOPRUN'] && !$amp_conf['FOPDISABLE']) {
		//bounce op_server.pl
		$wOpBounce = $amp_conf['AMPBIN'].'/bounce_op.sh';
		exec($wOpBounce.' &>'.$asterisk_conf['astlogdir'].'/freepbx-bounce_op.log', $output, $exit_val);
		
		if ($exit_val != 0) {
			$desc = _('Could not reload the FOP operator panel server using the bounce_op.sh script. Configuration changes may not be reflected in the panel display.');
			$notify->add_error('freepbx','reload_fop', _('Could not reload FOP server'), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx','reload_fop');
		}
	}
	
	//store asterisk reloaded status
	$sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'"; 
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		$return['message'] = _('Successful reload, but could not clear reload flag due to a database error: ').$db->getMessage();
		$return['num_errors']++;
	}
	
	if (isset($amp_conf["POST_RELOAD"]) && !empty($amp_conf['POST_RELOAD']))  {
		exec( $amp_conf["POST_RELOAD"], $output, $exit_val );
		
		if ($exit_val != 0) {
			$desc = sprintf(_("Exit code was %s and output was: %s"), $exit_val, "\n\n".implode("\n",$output));
			$notify->add_error('freepbx','reload_post_script', sprintf(_('Could not run %s script.'), 'POST_RELOAD'), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx', 'reload_post_script');
		}
	}
	
	return $return;
}

//get the version number
function getversion() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'version'";
	$results = $db->getRow($sql);
	if(DB::IsError($results)) {
		die_freepbx($sql."<br>\n".$results->getMessage());
	}
	return $results[0];
}

//get the version number
function get_framework_version() {
	global $db;
	$sql = "SELECT version FROM modules WHERE modulename = 'framework' AND enabled = 1";
	$version = $db->getOne($sql);
	if(DB::IsError($version)) {
		die_freepbx($sql."<br>\n".$version->getMessage());
	}
	return $version;
}

// draw list for users and devices with paging
// $skip has been dprecated, used to be used to page-enate
function drawListMenu($results, $skip, $type, $dispnum, $extdisplay, $description=false) {
	
	$index = 0;
	echo "<ul>\n";
	if ($description !== false) {
 		echo "\t<li><a ".($extdisplay=='' ? 'class="current"':'')." href=\"config.php?type=".$type."&display=".$dispnum."\">"._("Add")." ".$description."</a></li>\n";
	}
	if (isset($results)) {
		foreach ($results as $key=>$result) {
			$index= $index + 1;
			echo "\t<li><a".($extdisplay==$result[0] ? ' class="current"':''). " href=\"config.php?type=".$type."&display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]} &lt;{$result[0]}&gt;</a></li>\n";
		}
	}
	echo "</ul>\n";
}

// this function returns true if $astman is defined and set to something (implying a current connection, false otherwise.
// this function no longer puts out an error message, it is up to the caller to handle the situation. 
// Should probably be changed (at least name) to check if a connection is available to the current engine)
//
function checkAstMan() {
	global $astman;

	return ($astman)?true:false;
}

/* merge_ext_followme($dest) {
 * 
 * The purpose of this function is to take a destination
 * that was either a core extension OR a findmefollow-destination
 * and convert it so that they are merged and handled just like
 * direct-did routing
 *
 * Assuming an extension number of 222:
 *
 * The two formats that existed for findmefollow were:
 *
 * ext-findmefollow,222,1
 * ext-findmefollow,FM222,1
 *
 * The one format that existed for core was:
 *
 * ext-local,222,1
 *
 * In all those cases they should be converted to:
 *
 * from-did-direct,222,1
 *
 */
function merge_ext_followme($dest) {

	if (preg_match("/^\s*ext-findmefollow,(FM)?(\d+),(\d+)/",$dest,$matches) ||
	    preg_match("/^\s*ext-local,(FM)?(\d+),(\d+)/",$dest,$matches) ) {
				// matches[2] => extn
				// matches[3] => priority
		return "from-did-direct,".$matches[2].",".$matches[3];
	} else {
		return $dest;
	}
}

/** Recursively read voicemail.conf (and any included files)
 * This function is called by getVoicemailConf()
 */
function parse_voicemailconf($filename, &$vmconf, &$section) {
	if (is_null($vmconf)) {
		$vmconf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*(\d+)\s*=>\s*(\d*),(.*),(.*),(.*),(.*)\s*([;#].*)?/",$line,$matches)) {
				// "mailbox=>password,name,email,pager,options"
				// this is a voicemail line	
				$vmconf[$section][ $matches[1] ] = array("mailbox"=>$matches[1],
									"pwd"=>$matches[2],
									"name"=>$matches[3],
									"email"=>$matches[4],
									"pager"=>$matches[5],
									"options"=>array(),
									);
								
				// parse options
				//output($matches);
				foreach (explode("|",$matches[6]) as $opt) {
					$temp = explode("=",$opt);
					//output($temp);
					if (isset($temp[1])) {
						list($key,$value) = $temp;
						$vmconf[$section][ $matches[1] ]["options"][$key] = $value;
					}
				}
			} else if (preg_match('/^(?:\s*)#include(?:\s+)["\']{0,1}([^"\']*)["\']{0,1}(\s*[;#].*)?$/',$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = trim($matches[1]);
				} else {
					// relative path
					$filename =  dirname($filename)."/".trim($matches[1]);
				}
				
				parse_voicemailconf($filename, $vmconf, $section);
				
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$vmconf[$section][ $matches[1] ] = $matches[2];
			}
		}
		fclose($fd);
	}
}

/** Write the voicemail.conf file
 * This is called by saveVoicemail()
 * It's important to make a copy of $vmconf before passing it. Since this is a recursive function, has to
 * pass by reference. At the same time, it removes entries as it writes them to the file, so if you don't have
 * a copy, by the time it's done $vmconf will be empty.
*/
function write_voicemailconf($filename, &$vmconf, &$section, $iteration = 0) {
	global $amp_conf;
	if ($iteration == 0) {
		$section = null;
	}
	
	$output = array();
		
	// if the file does not, copy if from the template.
	// TODO: is this logical?
	if (!file_exists($filename)) {
		if (!copy( rtrim($amp_conf["ASTETCDIR"],"/")."/voicemail.conf.template", $filename )){
			return;
		}
	}
	
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^(\s*)(\d+)(\s*)=>(\s*)(\d*),(.*),(.*),(.*),(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// "mailbox=>password,name,email,pager,options"
				// this is a voicemail line
				//DEBUG echo "\nmailbox";
				
				// make sure we have something as a comment
				if (!isset($matches[10])) {
					$matches[10] = "";
				}
				
				// $matches[1] [3] and [4] are to preserve indents/whitespace, we add these back in
				
				if (isset($vmconf[$section][ $matches[2] ])) {	
					// we have this one loaded
					// repopulate from our version
					$temp = & $vmconf[$section][ $matches[2] ];
					
					$options = array();
					foreach ($temp["options"] as $key=>$value) {
						$options[] = $key."=".$value;
					}
					
					$output[] = $matches[1].$temp["mailbox"].$matches[3]."=>".$matches[4].$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options).$matches[10];
					
					// remove this one from $vmconf
					unset($vmconf[$section][ $matches[2] ]);
				} else {
					// we don't know about this mailbox, so it must be deleted
					// (and hopefully not JUST added since we did read_voiceamilconf)
					
					// do nothing
				}
				
			} else if (preg_match('/^(\s*)#include(\s+)["\']{0,1}([^"\']*)["\']{0,1}(\s*[;#].*)?$/',$line,$matches)) {
				// include another file
				//DEBUG echo "\ninclude ".$matches[3]."<blockquote>";
				
				// make sure we have something as a comment
				if (!isset($matches[4])) {
					$matches[4] = "";
				}
				
				if ($matches[3][0] == "/") {
					// absolute path
					$include_filename = trim($matches[3]);
				} else {
					// relative path
					$include_filename =  dirname($filename)."/".trim($matches[3]);
				}
				
				$output[] = trim($matches[0]);
				write_voicemailconf($include_filename, $vmconf, $section, $iteration+1);
				
				//DEBUG echo "</blockquote>";
				
			} else if (preg_match("/^(\s*)\[(.+)\](\s*[;#].*)?$/",$line,$matches)) {
				// section name
				//DEBUG echo "\nsection";
				
				// make sure we have something as a comment
				if (!isset($matches[3])) {
					$matches[3] = "";
				}
				
				// check if this is the first run (section is null)
				if ($section !== null) {
					// we need to add any new entries here, before the section changes
					//DEBUG echo "<blockquote><i>";
					//DEBUG var_dump($vmconf[$section]);
					if (isset($vmconf[$section])){  //need this, or we get an error if we unset the last items in this section - should probably automatically remove the section/context from voicemail.conf
						foreach ($vmconf[$section] as $key=>$value) {
							if (is_array($value)) {
								// mailbox line
								
								$temp = & $vmconf[$section][ $key ];
								
								$options = array();
								foreach ($temp["options"] as $key1=>$value) {
									$options[] = $key1."=".$value;
								}
								
								$output[] = $temp["mailbox"]." => ".$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options);
								
								// remove this one from $vmconf
								unset($vmconf[$section][ $key ]);
								
							} else {
								// option line
								
								$output[] = $key."=".$vmconf[$section][ $key ];
								
								// remove this one from $vmconf
								unset($vmconf[$section][ $key ]);
							}
						}
					} 
					//DEBUG echo "</i></blockquote>";
				}
				
				$section = strtolower($matches[2]);
				$output[] = $matches[1]."[".$section."]".$matches[3];
				$existing_sections[] = $section; //remember that this section exists

			} else if (preg_match("/^(\s*)([a-zA-Z0-9-_]+)(\s*)=(\s*)(.*?)(\s*[;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				//DEBUG echo "\noption line";
				
				
				// make sure we have something as a comment
				if (!isset($matches[6])) {
					$matches[6] = "";
				}
				
				if (isset($vmconf[$section][ $matches[2] ])) {
					$output[] = $matches[1].$matches[2].$matches[3]."=".$matches[4].$vmconf[$section][ $matches[2] ].$matches[6];
					
					// remove this one from $vmconf
					unset($vmconf[$section][ $matches[2] ]);
				} 
				// else it's been deleted, so we don't write it in
				
			} else {
				// unknown other line -- probably a comment or whitespace
				//DEBUG echo "\nother: ".$line;
				
				$output[] = str_replace(array("\n","\r"),"",$line); // str_replace so we don't double-space
			}
		}
		
		if (($iteration == 0) && (is_array($vmconf))) {
			// we need to add any new entries here, since it's the end of the file
			//DEBUG echo "END OF FILE!! <blockquote><i>";
			//DEBUG var_dump($vmconf);
			foreach (array_keys($vmconf) as $section) {
				if (!in_array($section,$existing_sections))  // If this is a new section, write the context label
					$output[] = "[".$section."]";
				foreach ($vmconf[$section] as $key=>$value) {
					if (is_array($value)) {
						// mailbox line
						
						$temp = & $vmconf[$section][ $key ];
						
						$options = array();
						foreach ($temp["options"] as $key=>$value) {
							$options[] = $key."=".$value;
						}
						
						$output[] = $temp["mailbox"]." => ".$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options);
						
						// remove this one from $vmconf
						unset($vmconf[$section][ $key ]);
						
					} else {
						// option line
						
						$output[] = $key."=".$vmconf[$section][ $key ];
						
						// remove this one from $vmconf
						unset($vmconf[$section][$key ]);
					}
				}
			}
			//DEBUG echo "</i></blockquote>";
		}
		
		fclose($fd);
		
		//DEBUG echo "\n\nwriting ".$filename;
		//DEBUG echo "\n-----------\n";
		//DEBUG echo implode("\n",$output);
		//DEBUG echo "\n-----------\n";
		
		// write this file back out
		
		if ($fd = fopen($filename, "w")) {
			fwrite($fd, implode("\n",$output)."\n");
			fclose($fd);
		}
}

/*
 * $goto is the current goto destination setting
 * $i is the destination set number (used when drawing multiple destination sets in a single form ie: digital receptionist)
 * esnure that any form that includes this calls the setDestinations() javascript function on submit.
 * ie: if the form name is "edit", and drawselects has been called with $i=2 then use onsubmit="setDestinations(edit,2)"
 * $table specifies if the destinations will be drawn in a new <tr> and <td>
 * 
 */   
function drawselects($goto,$i,$show_custom=false, $table=true) {
	global $tabindex, $active_modules, $drawselect_destinations, $drawselects_module_hash; 
	$html=$destmod=$errorclass=$errorstyle='';

	if($table){$html.='<tr><td colspan=2>';}//wrap in table tags if requested

	if(!isset($drawselect_destinations)){ 
		//check for module-specific destination functions
		foreach($active_modules as $rawmod => $module){
			$funct = strtolower($rawmod.'_destinations');
		
			//if the modulename_destinations() function exits, run it and display selections for it
			if (function_exists($funct)) {
				$destArray = $funct(); //returns an array with 'destination' and 'description', and optionally 'category'
				if(is_Array($destArray)) {
					foreach($destArray as $dest){
						$cat=(isset($dest['category'])?$dest['category']:$module['displayname']);
						$drawselect_destinations[$cat][] = $dest;
						$drawselects_module_hash[$cat] = $rawmod;
					}
				}
			}
		}
		//sort destination alphabeticaly		
		ksort($drawselect_destinations);
		ksort($drawselects_module_hash);
	}
	//set varibales as arrays for the rare (imposible?) case where there are none
  if(!isset($drawselect_destinations)){$drawselect_destinations=array();}
  if(!isset($drawselects_module_hash)){$drawselects_module_hash = array();}

	$foundone=false;
	$tabindex_needed=true;
	//get the destination module name if we have a $goto, add custom if there is an issue
	if($goto){
		foreach($drawselects_module_hash as $mod => $description){
			foreach($drawselect_destinations[$mod] as $destination){
				if($goto==$destination['destination']){
					$destmod=$mod;
			}
		}
	}
	if($destmod==''){//if we havnt found a match, display error dest
		$destmod='Error';
		$drawselect_destinations['Error'][]=array('destination'=>$goto, 'description'=>'Bad Dest: '.$goto, 'class'=>'drawselect_error');
		$drawselects_module_hash['Error']='error';
	}
}	

	//draw "parent" select box
	$style=' style="'.(($destmod=='Error')?'background-color:red;':'background-color:white;').'"';
	$html.='<select name="goto'.$i.'" class="destdropdown" '.$style.' tabindex="'.++$tabindex.'">';
	$html.='<option value="" style="background-color:white;">== '._('choose one').' ==</option>';
	foreach($drawselects_module_hash as $mod => $disc){
		/* We bind to the hosting module's domain. If we find the translation there we use it, if not
		 * we try the default 'amp' domain. If still no luck, we will try the _() which is the current
		 * module's display since some old translation code may have stored it localy but should migrate */
		bindtextdomain($drawselects_module_hash[$mod],"modules/".$drawselects_module_hash[$mod]."/i18n");
		bind_textdomain_codeset($drawselects_module_hash[$mod], 'utf8');
		$label_text=dgettext($drawselects_module_hash[$mod],$mod);
		if($label_text==$mod){$label_text=dgettext('amp',$label_text);}
		if($label_text==$mod){$label_text=_($label_text);}
		/* end i18n */
		$selected=($mod==$destmod)?' SELECTED ':' ';
		$style=' style="'.(($mod=='Error')?'background-color:red;':'background-color:white;').'"';
		$html.='<option value="'.str_replace(' ','_',$mod).'"'.$selected.$style.'>'.$label_text.'</option>';
	}
	$html.='</select> ';
	
	//draw "children" select boxes
	$tabindexhtml=' tabindex="'.++$tabindex.'"';//keep out of the foreach so that we dont increment it
	foreach($drawselect_destinations as $cat=>$destination){
		$style=(($cat==$destmod)?'':'display:none;');
		if($cat=='Error'){$style.=' '.$errorstyle;}//add error style
		$style=' style="'.(($cat=='Error')?'background-color:red;':$style).'"';
		$html.='<select name="'.str_replace(' ','_',$cat).$i.'" '.$tabindexhtml.$style.' class="destdropdown2">';
		foreach($destination as $dest){
			$selected=($goto==$dest['destination'])?'SELECTED ':' ';
		// This is ugly, but I can't think of another way to do localization for this child object
		    if(dgettext('amp',"Terminate Call") == $dest['category']) {
    			$child_label_text = dgettext('amp',$dest['description']);
			}
		    else {
			$child_label_text=$dest['description'];
			}
			$style=' style="'.(($cat=='Error')?'background-color:red;':'background-color:white;').'"';
			$html.='<option value="'.$dest['destination'].'" '.$selected.$style.'>'.$child_label_text.'</option>';
		}
		$html.='</select>';
	}
	if(isset($drawselect_destinations['Error'])){unset($drawselect_destinations['Error']);}
	if(isset($drawselects_module_hash['Error'])){unset($drawselects_module_hash['Error']);}
	if($table){$html.='</td></tr>';}//wrap in table tags if requested
	
	return $html;
}

/* below are legacy functions required to allow pre 2.0 modules to function (ie: interact with 'extensions' table) */

	//add to extensions table - used in callgroups.php
	function legacy_extensions_add($addarray) {
		global $db;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('".$addarray[0]."', '".$addarray[1]."', '".$addarray[2]."', '".$addarray[3]."', '".$addarray[4]."', '".$addarray[5]."' , '".$addarray[6]."')";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die_freepbx($sql."<br>\n".$result->getMessage());
		}
		return $result;
	}
	
	//delete extension from extensions table
	function legacy_extensions_del($context,$exten) {
		global $db;
		$sql = "DELETE FROM extensions WHERE context = '".$db->escapeSimple($context)."' AND `extension` = '".$db->escapeSimple($exten)."'";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die_freepbx($sql."<br>\n".$result->getMessage());
		}
		return $result;
	}
	
	//get args for specified exten and priority - primarily used to grab goto destination
	function legacy_args_get($exten,$priority,$context) {
		global $db;
		$sql = "SELECT args FROM extensions WHERE extension = '".$db->escapeSimple($exten)."' AND priority = '".$db->escapeSimple($priority)."' AND context = '".$db->escapeSimple($context)."'";
		list($args) = $db->getRow($sql);
		return $args;
	}

/* end legacy functions */


function get_headers_assoc($url ) {
	$url_info=parse_url($url);
	if (isset($url_info['scheme']) && $url_info['scheme'] == 'https') {
		$port = isset($url_info['port']) ? $url_info['port'] : 443;
		@$fp=fsockopen('ssl://'.$url_info['host'], $port, $errno, $errstr, 10);
	} else {
		$port = isset($url_info['port']) ? $url_info['port'] : 80;
		@$fp=fsockopen($url_info['host'], $port, $errno, $errstr, 10);
	}
	if ($fp) {
		stream_set_timeout($fp, 10);
		$head = "HEAD ".@$url_info['path']."?".@$url_info['query'];
		$head .= " HTTP/1.0\r\nHost: ".@$url_info['host']."\r\n\r\n";
		fputs($fp, $head);
		while(!feof($fp)) {
			if($header=trim(fgets($fp, 1024))) {
				$sc_pos = strpos($header, ':');
				if ($sc_pos === false) {
					$headers['status'] = $header;
				} else {
					$label = substr( $header, 0, $sc_pos );
					$value = substr( $header, $sc_pos+1 );
					$headers[strtolower($label)] = trim($value);
				}
			}
		}
		return $headers;
	} else {
		return false;
	}
}

function execSQL( $file ) {
	global $db;
	$data = null;
	
	// run sql script
	$fd = fopen( $file ,"r" );
	
	while (!feof($fd)) { 
		$data .= fread($fd, 1024); 
	}
	fclose($fd);
	
	preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);
	foreach ($matches[1] as $sql) {
		$result = $db->query($sql);
		if(DB::IsError($result)) { return false; }
	}
  return true;
}

// Dragged this in from page.modules.php, so it can be used by install_amp. 
function runModuleSQL($moddir,$type){
	trigger_error("runModuleSQL() is depreciated - please use _module_runscripts(), or preferably module_install() or module_enable() instead", E_USER_WARNING);
	_module_runscripts($moddir, $type);
}

/** Replaces variables in a string with the values from ampconf
 * eg, "%AMPWEBROOT%/admin" => "/var/www/html/admin"
 */
function ampconf_string_replace($string) {
	global $amp_conf;
	
	$target = array();
	$replace = array();
	
	foreach ($amp_conf as $key=>$value) {
		$target[] = '%'.$key.'%';
		$replace[] = $value;
	}
	
	return str_replace($target, $replace, $string);
}

/** Log a debug message to a debug file
 * @param  string   debug message to be printed
 * @param  string   optional mode, default 'a'
 * @param  string   optinal filename, default /tmp/freepbx_debug.log
 */
function freepbx_debug($string, $option='a', $filename='/tmp/freepbx_debug.log') {
	$fh = fopen($filename,$option);
	fwrite($fh,date("Y-M-d H:i:s")."\n");//add timestamp
	if (is_array($string) || is_object($string)) {
		fwrite($fh,print_r($string,true)."\n");
	} else {
		fwrite($fh,$string."\n");
	}
	fclose($fh);
}
 /* 
  * FreePBX Debuging function
  * This function can be called as follows:
  * dbug() - will just print a time stamp to the debug log file ($amp_conf['FPBXDBUGFILE'])
  * dbug('string') - same as above + will print the string
  * dbug('string',$array) - same as above + will print_r the array after the message
  * dbug($array) - will print_r the array with no message (just a time stamp)  
  * dbug('string',$array,1) - same as above + will var_dump the array
  * dbug($array,1) - will var_dump the array with no message  (just a time stamp)
  * 	 
 	*/  
function dbug(){
	$opts=func_get_args();
	//call_user_func_array('freepbx_debug',$opts);
	$dump=0;
	//sort arguments
	switch(count($opts)){
		case 1:
			$msg=$opts[0];
		break;
		case 2:
			if(is_array($opts[0])||is_object($opts[0])){
				$msg=$opts[0];
				$dump=$opts[1];
			}else{
				$disc=$opts[0];
				$msg=$opts[1];
			}
		break;
		case 3:
			$disc=$opts[0];
			$msg=$opts[1];
			$dump=$opts[2];
		break;	
	}
	if($disc){$disc=' \''.$disc.'\':';}
	$txt=date("Y-M-d H:i:s").$disc."\n"; //add timestamp
	dbug_write($txt,1);
	if($dump==1){//force output via var_dump
		ob_start();
		var_dump($msg);
		$msg=ob_get_contents();
		ob_end_clean();
		dbug_write($msg."\n");
	}elseif(is_array($msg)||is_object($msg)){
		dbug_write(print_r($msg,true)."\n");
	}else{
		dbug_write($msg."\n");
	}
}

// PHP 4 does not have file_put_contents so create an aproximation of what the real function does
//
if (!function_exists('file_put_contents')) {
  function file_put_contents($filename, $data, $flags='', $context=null) {
    $option = $flags == FILE_APPEND ? 'a' : 'w';
    if ($context !== null) {
      $fd = @fopen($filename, $option);
    } else {
      $fd = @fopen($filename, $option, false, $context);
    }
    if (!$fd) {
      return false;
    }
    if (is_array($data)) {
      $data = implode('',$data);
    } else if (is_object($data)) {
      $data = print_r($data,true);
    }
    $bytes = fwrite($fd,$data);
    fclose($fd);

    return $bytes;
  }
}

function dbug_write($txt,$check=''){
	global $amp_conf;
	$append=FILE_APPEND;
	//optionaly ensure that dbug file is smaller than $max_size
	if($check){
		$max_size=52428800;//hardcoded to 50MB. is that bad? not enough?
		$size=filesize($amp_conf['FPBXDBUGFILE']);
		$append=(($size > $max_size)?'':FILE_APPEND);
	}
	file_put_contents($amp_conf['FPBXDBUGFILE'],$txt, $append);
}
/** Log an error to the (database-based) log
 * @param  string   The section or script where the error occurred
 * @param  string   The level/severity of the error. Valid levels: 'error', 'warning', 'debug', 'devel-debug'
 * @param  string   The error message
 */
function freepbx_log($section, $level, $message) {
	global $db;
	global $debug; // This is used by retrieve_conf
	global $amp_conf;

	if (isset($debug) && ($debug != false)) {
		print "[DEBUG-$section] ($level) $message\n";
	}
	if (!$amp_conf['AMPENABLEDEVELDEBUG'] && strtolower(trim($level)) == 'devel-debug') {
		return true;
	}
        
	if (!$amp_conf['AMPDISABLELOG']) {
		switch (strtoupper($amp_conf['AMPSYSLOGLEVEL'])) {
			case 'LOG_EMERG':
				syslog(LOG_EMERG,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_ALERT':
				syslog(LOG_ALERT,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_CRIT':
				syslog(LOG_CRIT,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_ERR':
				syslog(LOG_ERR,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_WARNING':
				syslog(LOG_WARNING,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_NOTICE':
				syslog(LOG_NOTICE,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_INFO':
				syslog(LOG_INFO,"FreePBX-[$level][$section] $message");
				break;
			case 'LOG_DEBUG':
				syslog(LOG_DEBUG,"FreePBX-[$level][$section] $message");
				break;
			case 'SQL':
			case 'LOG_SQL':
			default:
				$sth = $db->prepare("INSERT INTO freepbx_log (time, section, level, message) VALUES (NOW(),?,?,?)");
				$db->execute($sth, array($section, $level, $message));
				break;
		}

	}
}

/** Abort all output, and redirect the browser's location.
 *
 * Useful for returning to the user to a GET location immediately after doing
 * a successful POST operation. This avoids the "this page was sent via POST, resubmit?"
 * message in the users browser, and also overwrites the POST page as a location in 
 * the browser's URL history (eg, they can't press the back button and end up re-submitting
 * the page).
 *
 * @param string   The url to go to
 * @param bool     If execution should stop after the function. Defaults to true
 */
function redirect($url, $stop_processing = true) {
	// TODO: If I don't call ob_end_clean() then is output buffering still on? Do I need to run it off still?
	//       (note ob_end_flush() results in the same php NOTICE so not sure how to turn it off. (?ob_implicit_flush(true)?)
	//
	@ob_end_clean();
	@header('Location: '.$url);
	if ($stop_processing) exit;
}

/** Abort all output, and redirect the browser's location using standard
 * FreePBX user interface variables. By default, will take POST/GET variables
 * 'type' and 'display' and pass them along in the URL. 
 * Also accepts a variable number of parameters, each being the name of a variable
 * to pass on. 
 * 
 * For example, calling redirect_standard('extdisplay','test'); will take $_REQUEST['type'], 
 * $_REQUEST['display'], $_REQUEST['extdisplay'], and $_REQUEST['test'],
 * and if any are present, use them to build a GET string (eg, "config.php?type=setup&
 * display=somemodule&extdisplay=53&test=yes", which is then passed to redirect() to send the browser
 * there.
 *
 * redirect_standard_continue does exactly the same thing but does NOT abort processing. This
 * is used when you wish to do a redirect but there is a possibility of other hooks still needing
 * to continue processing. Note that this is used in core when in 'extensions' mode, as both the
 * users and devices modules need to hook into it together.
 *
 * @param string  (optional, variable number) The name of a variable from $_REQUEST to 
 *                pass on to a GET URL.
 *
 */
function redirect_standard( /* Note. Read the next line. Varaible No of Paramas */ ) {
	$args = func_get_Args();

        foreach (array_merge(array('type','display'),$args) as $arg) {
                if (isset($_REQUEST[$arg])) {
                        $urlopts[] = $arg.'='.urlencode($_REQUEST[$arg]);
                }
        }
        $url = $_SERVER['PHP_SELF'].'?'.implode('&',$urlopts);
        redirect($url);
}

function redirect_standard_continue( /* Note. Read the next line. Varaible No of Paramas */ ) {
	$args = func_get_Args();

        foreach (array_merge(array('type','display'),$args) as $arg) {
                if (isset($_REQUEST[$arg])) {
                        $urlopts[] = $arg.'='.urlencode($_REQUEST[$arg]);
                }
        }
        $url = $_SERVER['PHP_SELF'].'?'.implode('&',$urlopts);
        redirect($url, false);
}

//This function calls modulename_contexts()
//expects a returned array which minimally includes 'context' => the actual context to include
//can also define 'description' => the display for this context - if undefined will be set to 'context'
//'module' => the display for the section this should be listed under defaults to modlue display (can be used to group subsets within one module)
//'parent' => if including another context automatically includes this one, list the parent context
//'priority' => default sort order for includes range -50 to +50, 0 is default
//'enabled' => can be used to flag a context as disabled and it won't be included, but will not have its settings removed.
//'extension' => can be used to tag with an extension for checkRange($extension)
//'dept' => can be used to tag with a department for checkDept($dept)
//	this defaults to false for disabled modules.
function freepbx_get_contexts() {
	$modules = module_getinfo(false, array(MODULE_STATUS_ENABLED, MODULE_STATUS_DISABLED, MODULE_STATUS_NEEDUPGRADE));
	
	$contexts = array();
	
	foreach ($modules as $modname => $mod) {
                $funct = strtolower($modname.'_contexts');
		if (function_exists($funct)) {
			// call the  modulename_contexts() function
			$contextArray = $funct();
			if (is_array($contextArray)) {
				foreach ($contextArray as $con) {
					if (isset($con['context'])) {
						if (!isset($con['description'])) {
							$con['description'] = $con['context'];
						}
						if (!isset($con['module'])) {
							$con['module'] = $mod['displayName'];
						}
						if (!isset($con['priority'])) {
							$con['priority'] = 0;
						}
						if (!isset($con['parent'])) {
							$con['parent'] = '';
						}
						if (!isset($con['extension'])) {
							$con['extension'] = null;
						}
						if (!isset($con['dept'])) {
							$con['dept'] = null;
						}
						if ($mod['status'] == MODULE_STATUS_ENABLED) {
							if (!isset($con['enabled'])) {
								$con['enabled'] = true;
							}
						} else {
							$con['enabled'] = false;
						}
						$contexts[ $con['context'] ] = $con;
					}
				}
			}
		}
	}
	return $contexts;
}

?>

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

require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'featurecodes.class.php');
require_once( (defined('AMP_BASE_INCLUDE_PATH') ? AMP_BASE_INCLUDE_PATH.'/' : '').'components.class.php');

define('MODULE_STATUS_NOTINSTALLED', 0);
define('MODULE_STATUS_DISABLED', 1);
define('MODULE_STATUS_ENABLED', 2);
define('MODULE_STATUS_NEEDUPGRADE', 3);
define('MODULE_STATUS_BROKEN', -1);

class modulelist {
	var $_loaded = false;
	var $module_array = array();
	var $_db;

	function &create(&$db) {
		static $obj;
		if (!isset($obj)) {
			$obj = new modulelist($db);
		}
		return $obj;
	}
	function modulelist(&$db) {
		$this->_db =& $db;
		$module_serialized = sql("SELECT `data` FROM `module_xml` WHERE `id` = 'mod_serialized'","getOne");
		if (isset($module_serialized) && $module_serialized) {
			$this->module_array = (unserialize($module_serialized));
			$this->_loaded = true;
		}
	}
	function is_loaded() {
		return $this->_loaded;
	}
	function initialize(&$module_list) {
		$this->module_array = $module_list;
		$module_serialized = $this->_db->escapeSimple(serialize($this->module_array));
		sql("DELETE FROM `module_xml` WHERE `id` = 'mod_serialized'");
		sql("INSERT INTO `module_xml` (`id`, `time`, `data`) VALUES ('mod_serialized', '".time()."','".$module_serialized."')");
		$this->_loaded = true;
	}
	function invalidate() {
		unset($this->module_array);
		sql("DELETE FROM `module_xml` WHERE `id` = 'mod_serialized'");
		$this->_loaded = false;
	}
}

define("NOTIFICATION_TYPE_CRITICAL", 100);
define("NOTIFICATION_TYPE_SECURITY", 200);
define("NOTIFICATION_TYPE_UPDATE",  300);
define("NOTIFICATION_TYPE_ERROR",    400);
define("NOTIFICATION_TYPE_WARNING" , 500);
define("NOTIFICATION_TYPE_NOTICE",   600);

class notifications {

	var $not_loaded = true;
	var $notification_table = array();
	var $_db;
		
	function &create(&$db) {
		static $obj;
		if (!isset($obj)) {
			$obj = new notifications($db);
		}
		return $obj;
	}

	function notifications(&$db) {
		$this->_db =& $db;
	}


	function add_critical($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_CRITICAL, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}
	function add_security($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_SECURITY, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}
	function add_update($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_UPDATE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}
	function add_error($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_ERROR, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}
	function add_warning($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_WARNING, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}
	function add_notice($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=true) {
		$this->_add_type(NOTIFICATION_TYPE_NOTICE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
	}


	function list_critical($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_CRITICAL, $show_reset);
	}
	function list_security($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_SECURITY, $show_reset);
	}
	function list_update($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_UPDATE, $show_reset);
	}
	function list_error($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_ERROR, $show_reset);
	}
	function list_warning($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_WARNING, $show_reset);
	}
	function list_notice($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_NOTICE, $show_reset);
	}
	function list_all($show_reset=false) {
		return $this->_list("", $show_reset);
	}


	function reset($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "UPDATE notifications SET reset = 1 WHERE module = $module AND id = $id";
		sql($sql);
	}

	function delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id";
		sql($sql);
	}

	function safe_delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id AND candelete = 1";
		sql($sql);
	}

	/* Internal functions
	 */

	function _add_type($level, $module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		if ($this->not_loaded) {
			$this->notification_table = $this->_list("",true);
			$this->not_loaded = false;
		}

		$existing_row = false;
		foreach ($this->notification_table as $row) {
			if ($row['module'] == $module && $row['id'] == $id ) {
				$existing_row = $row;
				break;
			}
		}
		// Found an existing row - check if anything changed or if we are suppose to reset it
		//
		$candelete = $candelete ? 1 : 0;
		if ($existing_row) {

			if (($reset && $existing_row['reset'] == 1) || $existing_row['level'] != $level || $existing_row['display_text'] != $display_text || $existing_row['extended_text'] != $extended_text || $existing_row['link'] != $link || $existing_row['candelete'] == $candelete) {

				// If $reset is set to the special case of PASSIVE then the updates will not change it's value in an update
				//
				$reset_value = ($reset == 'PASSIVE') ? $existing_row['reset'] : 0;

				$module        = q($module);
				$id            = q($id);
				$level         = q($level);
				$display_text  = q($display_text);
				$extended_text = q($extended_text);
				$link          = q($link);
				$now = time();
				$sql = "UPDATE notifications SET
					level = $level,
					display_text = $display_text,
					extended_text = $extended_text,
					link = $link,
					reset = $reset_value,
					candelete = $candelete,
					timestamp = $now
					WHERE module = $module AND id = $id
				";
				sql($sql);

				// TODO: I should really just add this to the internal cache, but really
				//       how often does this get called that if is a big deal.
				$this->not_loaded = true;
			}
		} else {
			// No existing row so insert this new one
			//
			$now           = time();
			$module        = q($module);
			$id            = q($id);
			$level         = q($level);
			$display_text  = q($display_text);
			$extended_text = q($extended_text);
			$link          = q($link);
			$sql = "INSERT INTO notifications 
				(module, id, level, display_text, extended_text, link, reset, candelete, timestamp)
				VALUES 
				($module, $id, $level, $display_text, $extended_text, $link, 0, $candelete, $now)
			";
			sql($sql);

			// TODO: I should really just add this to the internal cache, but really
			//       how often does this get called that if is a big deal.
			$this->not_loaded = true;
		}
	}

	function _list($level, $show_reset=false) {

		$level = q($level);
		$where = array();

		if (!$show_reset) {
			$where[] = "reset = 0";
		}

		switch ($level) {
			case NOTIFICATION_TYPE_CRITICAL:
			case NOTIFICATION_TYPE_SECURITY:
			case NOTIFICATION_TYPE_UPDATE:
			case NOTIFICATION_TYPE_ERROR:
			case NOTIFICATION_TYPE_WARNING:
			case NOTIFICATION_TYPE_NOTICE:
				$where[] = "level = $level ";
				break;
			default:
		}
		$sql = "SELECT * FROM notifications ";
		if (count($where)) {
			$sql .= " WHERE ".implode(" AND ", $where);
		}
		$sql .= " ORDER BY level, module";

		$list = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
		return $list;
	}
	/* Returns the number of active notifications
	 */
	function get_num_active() {
		$sql = "SELECT COUNT(id) FROM notifications WHERE reset = 0";
		return sql($sql,'getOne');
	}
}

class cronmanager {
	/**
	 * note: time is the hour time of day a job should run, -1 indicates don't care
	 */

	function &create(&$db) {
		static $obj;
		if (!isset($obj)) {
			$obj = new cronmanager($db);
		}
		return $obj;
	}

	function cronmanager(&$db) {
		$this->_db =& $db;
	}

	function save_email($address) {
		$address = q($address);
		sql("DELETE FROM admin WHERE variable = 'email'");
		sql("INSERT INTO admin (variable, value) VALUES ('email', $address)");
	}

	function get_email() {
		$sql = "SELECT value FROM admin WHERE variable = 'email'";
		return sql($sql, 'getOne');
	}

	function save_hash($id, &$string) {
		$hash = md5($string);
		$id = q($id);
		sql("DELETE FROM admin WHERE variable = $id");
		sql("INSERT INTO admin (variable, value) VALUE ($id, '$hash')");
	}

	function check_hash($id, &$string) {
		$id = q($id);
		$sql = "SELECT value FROM admin WHERE variable = $id LIMIT 1";
		$hash = sql($sql, "getOne");
		return ($hash == md5($string));
	}

	function enable_updates($freq=24) {
		global $amp_conf;

		$night_time = array(19,20,21,22,23,0,1,2,3,4,5);
		$run_time = $night_time[rand(0,10)];
		$command = $amp_conf['AMPBIN']."/module_admin listonline";
		$lasttime = 0;

		$sql = "SELECT * FROM cronmanager WHERE module = 'module_admin' AND id = 'UPDATES'";
		$result = sql($sql, "getAll",DB_FETCHMODE_ASSOC);
		if (count($result)) {
			$sql = "UPDATE cronmanager SET
			          freq = '$freq',
							  command = '$command'
						  WHERE
						    module = 'module_admin' AND id = 'UPDATES'	
			       ";
		} else {
			$sql = "INSERT INTO cronmanager 
		        	(module, id, time, freq, lasttime, command)
							VALUES
							('module_admin', 'UPDATES', '$run_time', $freq, 0, '$command')
						";
		}
		sql($sql);
	}

	function disable_updates() {
		sql("DELETE FROM cronmanager WHERE module = 'module_admin' AND id = 'UPDATES'");
	}

	function updates_enabled() {
		$results = sql("SELECT module, id FROM cronmanager WHERE module = 'module_admin' AND id = 'UPDATES'",'getAll');
		return count($results);
	}

	/** run_jobs()
	 *  select all entries that need to be run now and run them, then update the times.
	 *  
	 *  1. select all entries
	 *  2. foreach entry, if its paramters indicate it should be run, then run it and
	 *     update it was run in the time stamp.
	 */
	function run_jobs() {

		$errors = 0;
		$error_arr = array();

		$now = time();
		$jobs = sql("SELECT * FROM cronmanager","getAll", DB_FETCHMODE_ASSOC);
		foreach ($jobs as $job) {
			$nexttime = $job['lasttime'] + $job['freq']*3600; 
			if ($nexttime <= $now) {
				if ($job['time'] >= 0 && $job['time'] < 24) {
					$date_arr = getdate($now);
					// Now if lasttime is 0, then we want this kicked off at the proper hour
					// after wich the frequencey will set the pace for same time each night
					//
					if (($date_arr['hours'] != $job['time']) && !$job['lasttime']) {
						continue;
					}
				} 
			} else {
				// no need to run job, time is not up yet
				continue;
			}
			// run the job
			exec($job['command'],$job_out,$ret);
			if ($ret) {
				$errors++;
				$error_arr[] = array($job['command'],$ret);

				// If there where errors, let's print them out in case the script is being debugged or running
				// from cron which will then put the errors out through the cron system.
				//
				foreach ($job_out as $line) {
					echo $line."\n";
				}
			} else {
				$module = $job['module'];
				$id =     $job['id'];
				$sql = "UPDATE cronmanager SET lasttime = $now WHERE module = '$module' AND id = '$id'";
				sql($sql);
			}
		}
		if ($errors) {
			$nt =& notifications::create($db);
			$text = sprintf(_("Cronmanager encountered %s Errors"),$errors);
			$extext = _("The following commands failed with the listed error");
			foreach ($error_arr as $item) {
				$extext .= "<br />".$item[0]." (".$item[1].")";
			}
			$nt->add_error('cron_manager', 'EXECFAIL', $text, $extext, '', true, true);
		}
	}
}

class ampuser {
	var $username;
	var $_password;
	var $_extension_high;
	var $_extension_low;
	var $_deptname;
	var $_sections;
	
	function ampuser($username) {
		$this->username = $username;
		if ($user = getAmpUser($username)) {
			$this->_password = $user["password_sha1"];
			$this->_extension_high = $user["extension_high"];
			$this->_extension_low = $user["extension_low"];
			$this->_deptname = $user["deptname"];
			$this->_sections = $user["sections"];
		} else {
			// user doesn't exist
			$this->_password = false;
			$this->_extension_high = "";
			$this->_extension_low = "";
			$this->_deptname = "";
			$this->_sections = array();
		}
	}
	
	/** Give this user full admin access
	*/
	function setAdmin() {
		$this->_extension_high = "";
		$this->_extension_low = "";
		$this->_deptname = "";
		$this->_sections = array("*");
	}
	
	function checkPassword($password) {
		// strict checking so false will never match
		return ($this->_password === $password);
	}
	
	function checkSection($section) {
		// if they have * then it means all sections
		return in_array("*", $this->_sections) || in_array($section, $this->_sections);
	}
}

/* Usage
Grab some XML data, either from a file, URL, etc. however you want. Assume storage in $strYourXML;

$xml = new xml2Array($strYourXML);
xml array is in $xml->data;
	This is basically an array version of the XML data (no attributes), striaght-up. If there are
	multiple items with the same name, they are split into a numeric sub-array, 
	eg, <items><item test="123">foo</item><item>bar</item></items>
	becomes: array('item' => array(0=>array('item'=>'foo'), 1=>array('item'=>'foo'))
attributes are in $xml->attributes;
	These are stored with xpath type paths, as $xml->attributes['/items/item/0']["test"] == "123"
	

Other way (still works, but not as nice):

$objXML = new xml2Array();
$arrOutput = $objXML->parse($strYourXML);
print_r($arrOutput); //print it out, or do whatever!

*/

class xml2Array {
	var $arrOutput = array();
	var $resParser;
	var $strXmlData;
	
	var $attributes;
	var $data;
	
	function xml2Array($strInputXML = false) {
		if (!empty($strInputXML)) {
			$this->data = $this->parseAdvanced($strInputXML);
		}
	}
	
	function parse($strInputXML) {
	
			$this->resParser = xml_parser_create ();
			xml_set_object($this->resParser,$this);
			xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
			
			xml_set_character_data_handler($this->resParser, "tagData");
		
			$this->strXmlData = xml_parse($this->resParser,$strInputXML );
			if(!$this->strXmlData) {
				die_freepbx(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($this->resParser)),
			xml_get_current_line_number($this->resParser)));
			}
							
			xml_parser_free($this->resParser);
			
			return $this->arrOutput;
	}
	function tagOpen($parser, $name, $attrs) {
		$tag=array("name"=>$name,"attrs"=>$attrs); 
		@array_push($this->arrOutput,$tag);
	}
	
	function tagData($parser, $tagData) {
		if(trim($tagData)) {
			if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
				$this->arrOutput[count($this->arrOutput)-1]['tagData'] .= "\n".$tagData;
			} 
			else {
				$this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
			}
		}
	}
	
	function tagClosed($parser, $name) {
		@$this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
		array_pop($this->arrOutput);
	}
	
	function recursive_parseLevel($items, &$attrs, $path = "") {
		$array = array();
		foreach (array_keys($items) as $idx) {
			@$items[$idx]['name'] = strtolower($items[$idx]['name']);
			
			$multi = false;
			if (isset($array[ $items[$idx]['name'] ])) {
				// this child is already set, so we're adding multiple items to an array 
				
				if (!is_array($array[ $items[$idx]['name'] ]) || !isset($array[ $items[$idx]['name'] ][0])) {
					// hasn't already been made into a numerically-indexed array, so do that now
					// we're basically moving the current contents of this item into a 1-item array (at the 
					// original location) so that we can add a second item in the code below
					$array[ $items[$idx]['name'] ] = array( $array[ $items[$idx]['name'] ] );

					if (isset($attrs[ $path.'/'.$items[$idx]['name'] ])) {
						// move the attributes to /0
						$attrs[ $path.'/'.$items[$idx]['name'].'/0' ] = $attrs[ $path.'/'.$items[$idx]['name'] ];
						unset($attrs[ $path.'/'.$items[$idx]['name'] ]);
					}
				}
				$multi = true;
			}
			
			if ($multi) {	
				$newitem = &$array[ $items[$idx]['name'] ][];
			} else {
				$newitem = &$array[ $items[$idx]['name'] ];
			}
			
			
			if (isset($items[$idx]['children']) && is_array($items[$idx]['children'])) {
				$newitem = $this->recursive_parseLevel($items[$idx]['children'], $attrs, $path.'/'.$items[$idx]['name']);
			} else if (isset($items[$idx]['tagData'])) {
				$newitem = $items[$idx]['tagData'];
			} else {
				$newitem = false;
			}
			
			if (isset($items[$idx]['attrs']) && is_array($items[$idx]['attrs']) && count($items[$idx]['attrs'])) {
				$attrpath = $path.'/'.$items[$idx]['name'];
				if ($multi) {
					$attrpath .= '/'.(count($array[ $items[$idx]['name'] ])-1);
				}
				foreach ($items[$idx]['attrs'] as $name=>$value) {
					$attrs[ $attrpath ][ strtolower($name) ] = $value;
				}
			}
		}
		return $array;
	}
	
	function parseAdvanced($strInputXML) {
		$array = $this->parse($strInputXML);
		$this->attributes = array();
		return $this->data = $this->recursive_parseLevel($array, $this->attributes);
	}
}

/*
	Return a much more manageable assoc array with module data.
*/
class xml2ModuleArray extends xml2Array {
	function parseModulesXML($strInputXML) {
		$array = $this->parseAdvanced($strInputXML);
		if (isset($array['xml'])) {
			foreach ($array['xml'] as $key=>$module) {
				if ($key == 'module') {
					// copy the structure verbatim
					$modules[ $module['name'] ] = $module;
				}
			}
		}
		
		// if you are confused about what's happening below, uncomment this why we do it
		// echo "<pre>"; print_r($arrOutput); echo "</pre>";
		
		// ignore the regular xml garbage ([0]['children']) & loop through each module
		if(!is_array($arrOutput[0]['children'])) return false;
		foreach($arrOutput[0]['children'] as $module) {
			if(!is_array($module['children'])) return false;
			// loop through each modules's tags
			foreach($module['children'] as $modTags) {
					if(isset($modTags['children']) && is_array($modTags['children'])) {
						$$modTags['name'] = $modTags['children'];
						// loop if there are children (menuitems and requirements)
						foreach($modTags['children'] as $subTag) {
							$subTags[strtolower($subTag['name'])] = $subTag['tagData'];
						}
						$$modTags['name'] = $subTags;
						unset($subTags);
					} else {
						// create a variable for each tag we find
						$$modTags['name'] = $modTags['tagData'];
					}

			}
			// now build our return array
			$arrModules[$RAWNAME]['rawname'] = $RAWNAME;    // This has to be set
			$arrModules[$RAWNAME]['displayName'] = $NAME;    // This has to be set
			$arrModules[$RAWNAME]['version'] = $VERSION;     // This has to be set
			$arrModules[$RAWNAME]['type'] = isset($TYPE)?$TYPE:'setup';
			$arrModules[$RAWNAME]['category'] = isset($CATEGORY)?$CATEGORY:'Unknown';
			$arrModules[$RAWNAME]['info'] = isset($INFO)?$INFO:'http://www.freepbx.org/wiki/'.$RAWNAME;
			$arrModules[$RAWNAME]['location'] = isset($LOCATION)?$LOCATION:'local';
			$arrModules[$RAWNAME]['items'] = isset($MENUITEMS)?$MENUITEMS:null;
			$arrModules[$RAWNAME]['requirements'] = isset($REQUIREMENTS)?$REQUIREMENTS:null;
			$arrModules[$RAWNAME]['md5sum'] = isset($MD5SUM)?$MD5SUM:null;
			//print_r($arrModules);
			//unset our variables
			unset($NAME);
			unset($VERSION);
			unset($TYPE);
			unset($CATEGORY);
			unset($AUTHOR);
			unset($EMAIL);
			unset($LOCATION);
			unset($MENUITEMS);
			unset($REQUIREMENTS);
			unset($MD5SUM);
		}
		//echo "<pre>"; print_r($arrModules); echo "</pre>";

		return $arrModules;
	}
}

class moduleHook {
	var $hookHtml = '';
	var $arrHooks = array();
	
	function install_hooks($viewing_itemid,$target_module,$target_menuid = '') {
		global $active_modules;

    /*  Loop though all active modules and find which ones have hooks.
     *  Then process those hooks. Note we split this into two loops
     *  because of #4057, if drawselects() is called from within a hook
     *  it's interaction with the same $active_modules array renders the
     *  foreach loop done after that module and execution ends.
     */
    $our_hooks = array();
		foreach($active_modules as $this_module) {
			// look for requested hooks for $module
			// ie: findme_hook_extensions()
			$funct = $this_module['rawname'] . '_hook_' . $target_module;
			if( function_exists( $funct ) ) {
			  // remember who installed hooks
			  // we need to know this for processing form vars
			  $this->arrHooks[] = $this_module['rawname'];
        $our_hooks[$this_module['rawname']] = $funct;
      }
    }
    foreach($our_hooks as $thismod => $funct) {
			if (isset($_COOKIE['lang']) && is_dir("./modules/$thismod/i18n/".$_COOKIE['lang'])) {
				bindtextdomain($thismod,"./modules/$thismod/i18n");
				bind_textdomain_codeset($thismod, 'utf8');
				textdomain($thismod);
				if ($hookReturn = $funct($target_menuid, $viewing_itemid)) {
					$this->hookHtml .= $hookReturn;
				}
				textdomain('amp');
			} else {
				if ($hookReturn = $funct($target_menuid, $viewing_itemid)) {
					$this->hookHtml .= $hookReturn;
				}
			}
		}
	} 
	// process the request from the module we hooked
	function process_hooks($viewing_itemid, $target_module, $target_menuid, $request) {
		if(is_array($this->arrHooks)) {
			foreach($this->arrHooks as $hookingMod) {
				// check if there is a processing function
				$funct = $hookingMod . '_hookProcess_' . $target_module;
				if( function_exists( $funct ) ) {
					$funct($viewing_itemid, $request);
				}
			}
		}
	}
}

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
	
	if (!$amp_conf['ZAP2DAHDICOMPAT']) {
    $ast_with_dahdi = false;
    return $ast_with_dahdi;
	}
	
	if (empty($version)) {
		$engine_info = engine_getinfo();
		$version = $engine_info['version'];
	}
		
	if (version_compare($version, '1.4', 'ge') && $amp_conf['AMPENGINE'] == 'asterisk') {		
		if (isset($astman) && $astman->connected()) {
			$response = $astman->send_request('Command', array('Command' => 'module show like chan_dahdi'));
			if (preg_match('/1 modules loaded/', $response['data'])) {
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
    echo "<h4>Trace Back</h4>";
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
	$html.='<option value="" style="background-color:white;">== '._('chose one').' ==</option>';
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
		$html.='<option value="'.str_replace(' ','_',$mod).'"'.$selected.$style.'>'.$mod.'</option>';
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
			$style=' style="'.(($cat=='Error')?'background-color:red;':'background-color:white;').'"';
			$html.='<option value="'.$dest['destination'].'" '.$selected.$style.'>'.$dest['description'].'</option>';
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

/***********************************************************************************************************
                                       Module functions 
************************************************************************************************************/
 
/** Get the latest module.xml file for this FreePBX version. 
 * Caches in the database for 5 mintues.
 * If $module is specified, only returns the data for that module.
 * If the module is not found (or none are available for whatever reason),
 * then null is returned.
 *
 * Sets the global variable $module_getonlinexml_error to true if an error
 * occurred getting the module from the repository, false if no error occurred,
 * or null if the repository wasn't checked. Note that this may change in the 
 * future if we decide we need to return more error codes, but as long as it's
 * a php zero-value (false, null, 0, etc) then no error happened.
 */
function module_getonlinexml($module = false, $override_xml = false) { // was getModuleXml()
	global $amp_conf;
	global $db;
	global $module_getonlinexml_error;  // okay, yeah, this sucks, but there's no other good way to do it without breaking BC
	$module_getonlinexml_error = null;
	$got_new = false;
	$skip_cache = false;
	
	$result = sql("SELECT * FROM module_xml WHERE id = 'xml'",'getRow',DB_FETCHMODE_ASSOC);
	$data = $result['data'];

	// Check if the cached module xml is for the same repo as being requested
	// if not, then we get it anyhow
	//
	$repo_url = ($override_xml === false) ? "http://mirror.freepbx.org/" : $override_xml;
	$result2 = sql("SELECT * FROM module_xml WHERE id = 'module_repo'",'getRow',DB_FETCHMODE_ASSOC);
	$last_repo = $result2['data'];
	if ($last_repo !== $repo_url) {
		sql("DELETE FROM module_xml WHERE id = 'module_repo'");
		$data4sql = $db->escapeSimple($repo_url);
		sql("INSERT INTO module_xml (id,time,data) VALUES ('module_repo',".time().",'".$data4sql."')");
		$skip_cache = true;
	}

	// if the epoch in the db is more than 2 hours old, or the xml is less than 100 bytes, then regrab xml
	// Changed to 5 minutes while not in release. Change back for released version.
	//
	// used for debug, time set to 0 to always fall through
	// if((time() - $result['time']) > 0 || strlen($result['data']) < 100 ) {
	if((time() - $result['time']) > 300 || $skip_cache || strlen($data) < 100 ) {
		$version = getversion();
		// we need to know the freepbx major version we have running (ie: 2.1.2 is 2.1)
		preg_match('/(\d+\.\d+)/',$version,$matches);
		//echo "the result is ".$matches[1];
		if ($override_xml) {
			$fn = $override_xml."modules-".$matches[1].".xml";
		} else {
			$fn = "http://mirror.freepbx.org/modules-".$matches[1].".xml";
			// echo "(From default)"; //debug
		}
		//$fn = "/usr/src/freepbx-modules/modules.xml";
		if (!$amp_conf['MODULEADMINWGET']) {
			$data = @ file_get_contents($fn);
		} else {
			$data = "";
		}

		if (empty($data)) {
			exec("wget -O - $fn 2> /dev/null", $data_arr, $retcode);
			$data = implode("\n",$data_arr);
			$module_getonlinexml_error = ($retcode == 0)?false:true;
		}
		
		$old_xml = array();
		$got_new = false;
		if (!empty($data)) {
			// Compare the download to our current XML to see if anything changed for the notification system.
			//
			$sql = "SELECT data FROM module_xml WHERE id = 'xml'";
			$old_xml = sql($sql, "getOne");
			$got_new = true;
			// remove the old xml
			sql("DELETE FROM module_xml WHERE id = 'xml'");
			// update the db with the new xml
			$data4sql = $db->escapeSimple($data);
			sql("INSERT INTO module_xml (id,time,data) VALUES ('xml',".time().",'".$data4sql."')");
		}
	}
	
	if (empty($data)) {
		// no data, probably couldn't connect online, and nothing cached
		return null;
	}
	
	$parser = new xml2ModuleArray($data);
	$xmlarray = $parser->parseAdvanced($data);
	
	if ($got_new) {
		module_update_notifications($old_xml, $xmlarray, ($old_xml == $data4sql));
	}

	if (isset($xmlarray['xml']['module'])) {
	
		if ($module != false) {
			foreach ($xmlarray['xml']['module'] as $mod) {
				if ($module == $mod['rawname']) {
					return $mod;
				}
			}
			return null;
		} else {
			$modules = array();
			foreach ($xmlarray['xml']['module'] as $mod) {
				$modules[ $mod['rawname'] ] = $mod;
			}
			return $modules;
		}
	}
	return null;
}

/**  Determines if there are updates we don't already know about and posts to notification
 *   server about those updates.
 *
 */
function module_update_notifications(&$old_xml, &$xmlarray, $passive) {
	global $db;

	$notifications =& notifications::create($db); 

	$reset_value = $passive ? 'PASSIVE' : false;
	$old_parser = new xml2ModuleArray($old_xml);
	$old_xmlarray = $old_parser->parseAdvanced($old_xml);

	$new_modules = array();
	if (count($xmlarray)) {
		foreach ($xmlarray['xml']['module'] as $mod) {
			$new_modules[$mod['rawname']] = $mod;
		}
	}
	$old_modules = array();
	if (count($old_xmlarray)) {
		foreach ($old_xmlarray['xml']['module'] as $mod) {
			$old_modules[$mod['rawname']] = $mod;
		}
	}

	// If keys (rawnames) are different then there are new modules, create a notification.
	// This will always be the case the first time it is run since the xml is empty.
	//
	// TODO: if old_modules is empty, should I populate it from getinfo to at find out what
	//       is installed or otherwise, just skip it since it is the first time?
	//
	$diff_modules = array_diff_assoc($new_modules, $old_modules);
	$cnt = count($diff_modules);
	if ($cnt) {
		$extext = _("The following new modules are available for download. Click delete icon on the right to remove this notice.")."<br />";
		foreach ($diff_modules as $mod) {
			$extext .= $mod['rawname']." (".$mod['version'].")<br />";
		}
		$notifications->add_notice('freepbx', 'NEWMODS', sprintf(_('%s New modules are available'),$cnt), $extext, '', $reset_value, true);
	}

	// Now check if any of the installed modules need updating
	//
	module_upgrade_notifications($new_modules, $reset_value);
}

/** Compare installed (enabled or disabled) modules against the xml to generate or
 *  update the noticiation table of which modules have available updates. If the list
 *  is empty then delete the notification.
 */
function module_upgrade_notifications(&$new_modules, $passive_value) {
	global $db;
	$notifications =& notifications::create($db); 

	$installed_status = array(MODULE_STATUS_ENABLED, MODULE_STATUS_DISABLED);
	$modules_local = module_getinfo(false, $installed_status);

	$modules_upgradable = array();
	foreach (array_keys($modules_local) as $name) {
		if (isset($new_modules[$name])) {
			if (version_compare_freepbx($modules_local[$name]['version'], $new_modules[$name]['version']) < 0) {
				$modules_upgradable[] = array(
					'name' => $name,
					'local_version' => $modules_local[$name]['version'],
					'online_version' => $new_modules[$name]['version'],
				);
			}
		}
	}
	$cnt = count($modules_upgradable);
	if ($cnt) {
		if ($cnt == 1) {
			$text = _("There is 1 module available for online upgrade");
		} else {
			$text = sprintf(_("There are %s modules available for online upgrades"),$cnt);
		}
		$extext = "";
		foreach ($modules_upgradable as $mod) {
			$extext .= sprintf(_("%s (current: %s)"), $mod['name'].' '.$mod['online_version'], $mod['local_version'])."\n";
		}
		$notifications->add_update('freepbx', 'NEWUPDATES', $text, $extext, '', $passive_value);
	} else {
		$notifications->delete('freepbx', 'NEWUPDATES');
	}
}

/** Looks through the modules directory and modules database and returns all available
 * information about one or all modules
 * @param string  (optional) The module name to query, or false for all module
 * @param mixed   (optional) The status(es) to show, using MODULE_STATUS_* constants. Can
 *                either be one value, or an array of values.
 */
function module_getinfo($module = false, $status = false, $forceload = false) {

	global $amp_conf, $db;
	$modules = array();
	
	if ($module) {
		// get info on only one module
		$xml = _module_readxml($module);
		if (!is_null($xml)) {
			$modules[$module] = $xml;
			// if status is anything else, it will be updated below when we read the db
			$modules[$module]['status'] = MODULE_STATUS_NOTINSTALLED;
		}
		
		// query to get just this one
		$sql = 'SELECT * FROM modules WHERE modulename = "'.$module.'"';
	} else {
		// create the modulelist so it is static and does not need to be recreated
		// in subsequent calls
		//
		$modulelist =& modulelist::create($db);
		if ($forceload) {
			$modulelist->invalidate();
		}
		if (!$modulelist->is_loaded()) {
			// initialize list with "builtin" module
			$module_list = array('builtin');

			// read modules dir for module names
			$dir = opendir($amp_conf['AMPWEBROOT'].'/admin/modules');
			while ($file = readdir($dir)) {
				if (($file != ".") && ($file != "..") && ($file != "CVS") && 
			    	($file != ".svn") && ($file != "_cache") && 
			    	is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file)) {
					$module_list[] = $file;
				}
			}

			// read the xml for each
			foreach ($module_list as $file) {
				$xml = _module_readxml($file);
				if (!is_null($xml)) {
					$modules[$file] = $xml;
					// if status is anything else, it will be updated below when we read the db
					$modules[$file]['status'] = MODULE_STATUS_NOTINSTALLED;
				}
			}
			closedir($dir);

			// query to get everything
			$sql = 'SELECT * FROM modules';
		}
	}
	// determine details about this module from database
	// modulename should match the directory name
	
	if ($module || !$modulelist->is_loaded()) {
		$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die_freepbx($sql."<br>\n".$results->getMessage());
		}
	
		if (is_array($results)) {
			foreach($results as $row) {
				if (isset($modules[ $row['modulename'] ])) {
					if ($row['enabled'] != 0) {
					
						// check if file and registered versions are the same
						// version_compare returns 0 if no difference
						if (version_compare_freepbx($row['version'], $modules[ $row['modulename'] ]['version']) == 0) {
							$modules[ $row['modulename'] ]['status'] = MODULE_STATUS_ENABLED;
						} else {
							$modules[ $row['modulename'] ]['status'] = MODULE_STATUS_NEEDUPGRADE;
						}
					
					} else {
						$modules[ $row['modulename'] ]['status'] = MODULE_STATUS_DISABLED;
					}
				} else {
					// no directory for this db entry
					$modules[ $row['modulename'] ]['status'] = MODULE_STATUS_BROKEN;
				}
				$modules[ $row['modulename'] ]['dbversion'] = $row['version'];
			}
		}

		// "builtin" module is always enabled
		$modules['builtin']['status'] = MODULE_STATUS_ENABLED;
	} 
	if (!$module && !$modulelist->is_loaded()) {
		$modulelist->initialize($modules);
	}

	if ($status === false) {
		if (!$module) {
			return $modulelist->module_array;
		} else {
			return $modules;
		}
	} else {
		if (!$module) {
			$modules =  $modulelist->module_array;
		}
		if (!is_array($status)) {
			// make a one element array so we can use in_array below
			$status = array($status);
		}
		foreach (array_keys($modules) as $name) {
			if (!in_array($modules[$name]['status'], $status)) {
				// not found in the $status array, remove it
				unset($modules[$name]);
			}
		}
		return $modules;
	}
}

/** Check if a module meets dependencies. 
 * @param  mixed  The name of the module, or the modulexml Array
 * @return mixed  Returns true if dependencies are met, or an array 
 *                containing a list of human-readable errors if not.
 *                NOTE: you must use strict type checking (===) to test
 *                for true, because  array() == true !
 */
function module_checkdepends($modulename) {
	
	// check if we were passed a modulexml array, or a string (name)
	// ensure $modulexml is the modules array, and $modulename is the name (as a string)
	if (is_array($modulename)) {
		$modulexml = $modulename;
		$modulename = $modulename['rawname'];
	} else {
		$modulexml = module_getinfo($modulename);
	}
	
	$errors = array();
	
	// special handling for engine
	$engine_dependency = false; // if we've found ANY engine dependencies to check
	$engine_matched = false; // if an engine dependency has matched
	$engine_errors = array(); // the error strings for engines
	
	if (isset($modulexml['depends'])) {
		foreach ($modulexml['depends'] as $type => $requirements) {
			// if only a single item, make it an array so we can use the same code as for multiple items
			// this is because if there is  <module>a</module><module>b</module>  we will get array('module' => array('a','b'))
			if (!is_array($requirements)) {
				$requirements = array($requirements);
			}
			
			foreach ($requirements as $value) {
				switch ($type) {
					case 'version':
						if (preg_match('/^(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d*[beta|alpha|rc|RC]?\d+(\.[^\.]+)*)$/i', $value, $matches)) {
							// matches[1] = operator, [2] = version
							$installed_ver = getversion();
							$operator = (!empty($matches[1]) ? $matches[1] : 'ge'); // default to >=
							$compare_ver = $matches[2];
							if (version_compare_freepbx($installed_ver, $compare_ver, $operator) ) {
								// version is good
							} else {
								$errors[] = _module_comparison_error_message('FreePBX', $compare_ver, $installed_ver, $operator);
							}
						}
					break;
					case 'phpversion':
						/* accepted formats
						   <depends>
							   <phpversion>5.1.0<phpversion>       TRUE: if php is >= 5.1.0
								 <phpversion>gt 5.1.0<phpversion>    TRUE: if php is > 5.1.0
							</depends>
						*/
						if (preg_match('/^(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d*[beta|alpha|rc|RC]?\d+(\.[^\.]+)*)$/i', $value, $matches)) {
							// matches[1] = operator, [2] = version
							$installed_ver = phpversion();
							$operator = (!empty($matches[1]) ? $matches[1] : 'ge'); // default to >=
							$compare_ver = $matches[2];
							if (version_compare($installed_ver, $compare_ver, $operator) ) {
								// php version is good
							} else {
								$errors[] = _module_comparison_error_message('PHP', $compare_ver, $installed_ver, $operator);
							}
						} 
					break;
					case 'phpcomponent':
						/* accepted formats
						   <depends>
							   <phpversion>zlib<phpversion>        TRUE: if extension zlib is loaded
								 <phpversion>zlib 1.2<phpversion>    TRUE: if extension zlib is loaded and >= 1.2
								 <phpversion>zlib gt 1.2<phpversion> TRUE: if extension zlib is loaded and > 1.2
							</depends>
						*/
						if (preg_match('/^([a-z0-9_]+)(\s+(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d+(\.\d*[beta|alpha|rc|RC]*\d+)+))?$/i', $value, $matches)) {
							// matches[1] = extension name, [3]=comparison operator, [4] = version
							$compare_ver = isset($matches[4]) ? $matches[4] : '';
							if (extension_loaded($matches[1])) {
								if (empty($compare_ver)) {
									// extension is loaded and no version specified
								} else {
									if (($installed_ver = phpversion($matches[1])) != '') {
										$operator = (!empty($matches[3]) ? $matches[3] : 'ge'); // default to >=
										if (version_compare($installed_ver, $compare_ver, $operator) ) {
											// version is good
										} else {
											$errors[] = _module_comparison_error_message("PHP Component ".$matches[1], $compare_ver, $installed_ver, $operator);
										}
									} else {
										$errors[] = _module_comparison_error_message("PHP Component ".$matches[1], $compare_ver, "<no version info>", $operator);
									}
								}
							} else {
								if ($compare_version == '') {
									$errors[] = sprintf(_('PHP Component %s is required but missing from you PHP installation.'), $matches[1]);
								} else {
									$errors[] = sprintf(_('PHP Component %s version %s is required but missing from you PHP installation.'), $matches[1], $compare_version);
								}
							}
						}	
					break;
					case 'module':
						// Modify to allow versions such as 2.3.0beta1.2
						if (preg_match('/^([a-z0-9_]+)(\s+(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d+(\.\d*[beta|alpha|rc|RC]*\d+)+))?$/i', $value, $matches)) {
							// matches[1] = modulename, [3]=comparison operator, [4] = version
							$modules = module_getinfo($matches[1]);
							if (isset($modules[$matches[1]])) {
								$needed_module = "<strong>".(isset($modules[$matches[1]]['name'])?$modules[$matches[1]]['name']:$matches[1])."</strong>";
								switch ($modules[$matches[1]]['status'] ) {
									case MODULE_STATUS_ENABLED:
										if (!empty($matches[4])) {
											// also doing version checking
											$installed_ver = $modules[$matches[1]]['dbversion'];
											$compare_ver = $matches[4];
											$operator = (!empty($matches[3]) ? $matches[3] : 'ge'); // default to >=
											
											if (version_compare_freepbx($installed_ver, $compare_ver, $operator) ) {
												// version is good
											} else {
												$errors[] = _module_comparison_error_message($needed_module.' module', $compare_ver, $installed_ver, $operator);
											}
										}
									break;
									case MODULE_STATUS_BROKEN:
										$errors[] = sprintf(_('Module %s is required, but yours is broken. You should reinstall '.
										                      'it and try again.'), $needed_module);
									break;
									case MODULE_STATUS_DISABLED:
										$errors[] = sprintf(_('Module %s is required, but yours is disabled.'), $needed_module);
									break;
									case MODULE_STATUS_NEEDUPGRADE:
										$errors[] = sprintf(_('Module %s is required, but yours is disabled because it needs to '.
										                      'be upgraded. Please upgrade %s first, and then try again.'), 
															$needed_module, $needed_module);
									break;
									default:
									case MODULE_STATUS_NOTINSTALLED:
										$errors[] = sprintf(_('Module %s is required, yours is not installed.'), $needed_module);
									break;
								}
							} else {
								$errors[] = sprintf(_('Module %s is required.'), $matches[1]);
							}
						}
					break;
					case 'file': // file exists
						// replace embedded amp_conf %VARIABLES% in string
						$file = ampconf_string_replace($value);
						
						if (!file_exists( $file )) {
							$errors[] = sprintf(_('File %s must exist.'), $file);
						}
					break;
					case 'engine':
						/****************************
						 *  NOTE: there is special handling for this check. We want to "OR" conditions, instead of
						 *        "AND"ing like the rest of them. 
						 */
						
						// we found at least one engine, so mark that we're matching this 
						$engine_dependency = true;
						
						if (preg_match('/^([a-z0-9_]+)(\s+(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d+(\.[^\.]+)*))?$/i', $value, $matches)) {
							// matches[1] = engine, [3]=comparison operator, [4] = version
							$operator = (!empty($matches[3]) ? $matches[3] : 'ge'); // default to >=
							
							$engine = engine_getinfo();
							if (($engine['engine'] == $matches[1]) &&
							    (empty($matches[4]) || !version_compare($matches[4], $engine['version'], $operator))
							   ) {
							   
								$engine_matched = true;
							} else {
								// add it to the error messages
								if ($matches[4]) {
									// version specified
									$operator_friendly = str_replace(array('gt','ge','lt','le','eq','ne'), array('>','>=','<','<=','=','not ='), $operator);
									$engine_errors[] = $matches[1].' ('.$operator_friendly.' '.$matches[4].')';
								} else {
									// no version
									$engine_errors[] = $matches[1];
								}
							}
						}
					break;
				}
			}
		}
		
		// special handling for engine
		// if we've had at least one engine dependency check, and no engine dependencies matched, we have an error
		if ($engine_dependency && !$engine_matched) {
		
			$engineinfo = engine_getinfo();
			$yourengine = $engineinfo['engine'].' '.$engineinfo['version'];
			// print it nicely
			if (count($engine_errors) == 1) {
				$errors[] = sprintf(_('Requires engine %s, you have: %s'),$engine_errors[0],$yourengine);
			} else {
				$errors[] = sprintf(_('Requires one of the following engines: %s; you have: %s'),implode(', ', $engine_errors),$yourengine);
			}
		}
	}
	
	if (count($errors) > 0) {
		return $errors;
	} else {
		return true;
	}
}

function _module_comparison_error_message($module, $reqversion, $version, $operator) {
	switch ($operator) {
		case 'lt': case '<':
			return sprintf(_('A %s version below %s is required, you have %s'), $module, $reqversion, $version);
		break;
		case 'le': case '<=';
			return sprintf(_('%s version %s or below is required, you have %s'), $module, $reqversion, $version);
		break;
		case 'gt': case '>';
			return sprintf(_('A %s version newer than %s required, you have %s'), $module, $reqversion, $version);
		break;
		case 'ne': case '!=': case '<>':
			return sprintf(_('Your %s version (%s) is incompatible.'), $version, $reqversion);
		break;
		case 'eq': case '==': case '=': 
			return sprintf(_('Only %s version %s is compatible, you have %s'), $module, $reqversion, $version);
		break;
		default:
		case 'ge': case '>=':
			return sprintf(_('%s version %s or higher is required, you have %s'), $module, $reqversion, $version);
	}
}

/** Finds all the enabled modules that depend on a given module
 * @param  mixed  The name of the module, or the modulexml Array
 * @return array  Array containing the list of modules, or false if no dependencies
 */
function module_reversedepends($modulename) {
	// check if we were passed a modulexml array, or a string (name)
	// ensure $modulename is the name (as a string)
	if (is_array($modulename)) {
		$modulename = $modulename['rawname'];
	}
	
	$modules = module_getinfo(false, MODULE_STATUS_ENABLED);
	
	$depends = array();
	
	foreach (array_keys($modules) as $name) {
		if (isset($modules[$name]['depends'])) {
			foreach ($modules[$name]['depends'] as $type => $requirements) {
				if ($type == 'module') {
					// if only a single item, make it an array so we can use the same code as for multiple items
					// this is because if there is  <module>a</module><module>b</module>  we will get array('module' => array('a','b'))
					if (!is_array($requirements)) {
						$requirements = array($requirements);
					}
					
					foreach ($requirements as $value) {
						if (preg_match('/^([a-z0-9_]+)(\s+(>=|>|=|<|<=|!=)?\s*(\d(\.\d)*))?$/i', $value, $matches)) {
							// matches[1] = modulename, [3]=comparison operator, [4] = version
							
							// note, we're not checking version here. Normally this function is used when
							// uninstalling a module, so it doesn't really matter anyways, and version
							// dependency should have already been checked when the module was installed
							if ($matches[1] == $modulename) {
								$depends[] = $name;
							}
						}
					}
				}
			}
		}
	}
	
	return (count($depends) > 0) ? $depends : false;
}

/** Enables a module
 * @param string    The name of the module to enable
 * @param bool      If true, skips status and dependency checks
 * @return  mixed   True if succesful, array of error messages if not succesful
 */
function module_enable($modulename, $force = false) { // was enableModule
	$modules = module_getinfo($modulename);
	
	if ($modules[$modulename]['status'] == MODULE_STATUS_ENABLED) {
		return array(_("Module ".$modulename." is already enabled"));
	}
	
	// doesn't make sense to skip this on $force - eg, we can't enable a non-installed or broken module
	if ($modules[$modulename]['status'] != MODULE_STATUS_DISABLED) {
		return array(_("Module ".$modulename." cannot be enabled"));
	}
	
	if (!$force) { 
		if (($errors = module_checkdepends($modules[$modulename])) !== true) {
			return $errors;
		}
	}
	
	// disabled (but doesn't needupgrade or need install), and meets dependencies
	_module_setenabled($modulename, true);
	needreload();
	return true;
}

/** Downloads the latest version of a module
 * and extracts it to the directory
 * @param string    The name of the module to install
 * @param bool      If true, skips status and dependency checks
 * @param string    The name of a callback function to call with progress updates.
                    function($action, $params). Possible actions:
                      getinfo: while downloading modules.xml
                      downloading: while downloading file; params include 'read' and 'total'
                      untar: before untarring
                      done: when complete
 * @return  mixed   True if succesful, array of error messages if not succesful
 */

// was fetchModule 
function module_download($modulename, $force = false, $progress_callback = null, $override_svn = false, $override_xml = false) { 
	global $amp_conf;

	if ($time_limit = ini_get('max_execution_time')) {
		set_time_limit($time_limit);
	}
	
	// size of download blocks to fread()
	// basically, this controls how often progress_callback is called
	$download_chunk_size = 12*1024;
	
	// invoke progress callback
	if (function_exists($progress_callback)) {
		$progress_callback('getinfo', array('module'=>$modulename));
	}
			
	$res = module_getonlinexml($modulename, $override_xml);
	if ($res == null) {
		return array(_("Module not found in repository"));
	}
	
	$file = basename($res['location']);
	$filename = $amp_conf['AMPWEBROOT']."/admin/modules/_cache/".$file;
	// if we're not forcing the download, and a file with the target name exists..
	if (!$force && file_exists($filename)) {
		// We might already have it! Let's check the MD5.
		$filedata = "";
		if ( $fh = @ fopen($filename, "r") ) {
			while (!feof($fh)) {
				$filedata .= fread($fh, 8192);
			}
			fclose($fh);
		}
		
		if (isset($res['md5sum']) && $res['md5sum'] == md5 ($filedata)) {
			// Note, if there's no MD5 information, it will redownload
			// every time. Otherwise theres no way to avoid a corrupt
			// download
			
			// invoke progress callback
			if (function_exists($progress_callback)) {
				$progress_callback('untar', array('module'=>$modulename, 'size'=>filesize($filename)));
			}
			
			/* We will explode the tarball in the cache directory and then once successful, remove the old module before before
			 * moving the new one over. This way, things like removed files end up being removed instead of laying around
			 *
			 * TODO: save old module being replaced, if there is an old one.
			 */
			exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $output, $exitcode);
			if ($exitcode != 0) {
				return array(sprintf(_('Could not remove %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'.$modulenam));
			}
			exec("tar zxf ".escapeshellarg($filename)." -C ".escapeshellarg($amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'), $output, $exitcode);
			if ($exitcode != 0) {
				return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['AMPWEBROOT'].'/admin/modules/_cache'));
			}
			exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
			if ($exitcode != 0) {
				return array(sprintf(_('Could not remove old module %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename));
			}
			exec("mv ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
			if ($exitcode != 0) {
				return array(sprintf(_('Could not move %s to %s'), $amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $amp_conf['AMPWEBROOT'].'/admin/modules/'));
			}
			
			// invoke progress_callback
			if (function_exists($progress_callback)) {
				$progress_callback('done', array('module'=>$modulename));
			}
			
			return true;
		} else {
			unlink($filename);
		}
	}
	
	if ($override_svn) {
		$url = $override_svn.$res['location'];
	} else {
		$url = "http://mirror.freepbx.org/modules/".$res['location'];
	}
	
	if (!($fp = @fopen($filename,"w"))) {
		return array(sprintf(_("Error opening %s for writing"), $filename));
	}
	
	$headers = get_headers_assoc($url);
	
	$totalread = 0;
	// invoke progress_callback
	if (function_exists($progress_callback)) {
		$progress_callback('downloading', array('module'=>$modulename, 'read'=>$totalread, 'total'=>$headers['content-length']));
	}
	
	// Check MODULEADMINWGET first so we don't execute the fopen() if set
	//
	if ($amp_conf['MODULEADMINWGET'] || !$dp = @fopen($url,'r')) {
		exec("wget -O $filename $url 2> /dev/null", $filedata, $retcode);
		if ($retcode != 0) {
			return array(sprintf(_("Error opening %s for reading"), $url));
		} else {
			if (!$dp = @fopen($filename,'r')) {
				return array(sprintf(_("Error opening %s for reading"), $url));
			}
		}
	}
	
	$filedata = '';
	while (!feof($dp)) {
		$data = fread($dp, $download_chunk_size);
		$filedata .= $data;
		$totalread += strlen($data);
		if (function_exists($progress_callback)) {
			$progress_callback('downloading', array('module'=>$modulename, 'read'=>$totalread, 'total'=>$headers['content-length']));
		}
	}
	fwrite($fp,$filedata);
	fclose($dp);
	fclose($fp);
	
	
	if (is_readable($filename) !== TRUE ) {
		return array(sprintf(_('Unable to save %s'),$filename));
	}
	
	// Check the MD5 info against what's in the module's XML
	if (!isset($res['md5sum']) || empty($res['md5sum'])) {
		//echo "<div class=\"error\">"._("Unable to Locate Integrity information for")." {$filename} - "._("Continuing Anyway")."</div>";
	} else if ($res['md5sum'] != md5 ($filedata)) {
		unlink($filename);
		return array(sprintf(_('File Integrity failed for %s - aborting'), $filename));
	}
	
	// invoke progress callback
	if (function_exists($progress_callback)) {
		$progress_callback('untar', array('module'=>$modulename, 'size'=>filesize($filename)));
	}

	/* We will explode the tarball in the cache directory and then once successful, remove the old module before before
	 * moving the new one over. This way, things like removed files end up being removed instead of laying around
	 *
	 * TODO: save old module being replaced, if there is an old one.
	 *
	 */
	exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not remove %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'.$modulenam));
	}
	exec("tar zxf ".escapeshellarg($filename)." -C ".escapeshellarg($amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'), $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['AMPWEBROOT'].'/admin/modules/_cache'));
	}
	exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not remove old module %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename));
	}
	exec("mv ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not move %s to %s'), $amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $amp_conf['AMPWEBROOT'].'/admin/modules/'));
	}

	// invoke progress_callback
	if (function_exists($progress_callback)) {
		$progress_callback('done', array('module'=>$modulename));
	}

	return true;
}


function module_handleupload($uploaded_file) {
	global $amp_conf;
	$errors = array();
	
	if (!isset($uploaded_file['tmp_name']) || !file_exists($uploaded_file['tmp_name'])) {
		$errors[] = _("Error finding uploaded file - check your PHP and/or web server configuration");
		return $errors;
	}
	
	if (!preg_match('/\.(tar\.gz|tgz)$/', $uploaded_file['name'])) {
		$errors[] = _("File must be in tar+gzip (.tgz or .tar.gz) format");
		return $errors;
	}
	
	if (!preg_match('/^([A-Za-z][A-Za-z0-9_]+)\-([0-9a-zA-Z]+(\.[0-9a-zA-Z]+)*)\.(tar\.gz|tgz)$/', $uploaded_file['name'], $matches)) {
		$errors[] = _("Filename not in correct format: must be modulename-version.tar.gz (eg. custommodule-0.1.tar.gz)");
		return $errors;
	} else {
		$modulename = $matches[1];
		$moduleversion = $matches[2];
	}
	
	$temppath = $amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'.uniqid("upload");
	if (! @mkdir($temppath) ) {
		return array(sprintf(_("Error creating temporary directory: %s"), $temppath));
	}
	$filename = $temppath.'/'.$uploaded_file['name'];
	
	move_uploaded_file($uploaded_file['tmp_name'], $filename);
	
	exec("tar ztf ".escapeshellarg($filename), $output, $exitcode);
	if ($exitcode != 0) {
		$errors[] = _("Error untaring uploaded file. Must be a tar+gzip file");
		return $errors;
	}
	
	foreach ($output as $line) {
		// make sure all lines start with "modulename/"
		if (!preg_match('/^'.$modulename.'\//', $line)) {
			$errors[] = 'File extracting to invalid location: '.$line;
		}
	}
	if (count($errors)) {
		return $errors;
	}

	/* We will explode the tarball in the cache directory and then once successful, remove the old module before before
	 * moving the new one over. This way, things like removed files end up being removed instead of laying around
	 *
	 * TODO: save old module being replaced, if there is an old one.
	 *
	 */
	exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not remove %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'.$modulenam));
	}
	exec("tar zxf ".escapeshellarg($filename)." -C ".escapeshellarg($amp_conf['AMPWEBROOT'].'/admin/modules/_cache/'), $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['AMPWEBROOT'].'/admin/modules/_cache'));
	}
	exec("rm -rf ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not remove old module %s to install new version'), $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename));
	}
	exec("mv ".$amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename ".$amp_conf['AMPWEBROOT']."/admin/modules/$modulename", $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not move %s to %s'), $amp_conf['AMPWEBROOT']."/admin/modules/_cache/$modulename", $amp_conf['AMPWEBROOT'].'/admin/modules/'));
	}

	exec("rm -rf ".$temppath, $output, $exitcode);
	if ($exitcode != 0) {
		$errors[] = sprintf(_('Error removing temporary directory: %s'), $temppath);
	}
	
	if (count($errors)) {
		return $errors;
	}
	
	// finally, module installation is successful
	return true;
}

/** Installs or upgrades a module from it's directory
 * Checks dependencies, and enables
 * @param string   The name of the module to install
 * @param bool     If true, skips status and dependency checks
 * @return mixed   True if succesful, array of error messages if not succesful
 */
function module_install($modulename, $force = false) {
	global $db, $amp_conf;

	if ($time_limit = ini_get('max_execution_time')) {
		set_time_limit($time_limit);
	}

	$modules = module_getinfo($modulename);
	
	// make sure we have a directory, to begin with
	$dir = $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename;
	if (!is_dir($dir)) {
		return array(_("Cannot find module"));
	}
	
	// read the module.xml file
	$modules = module_getinfo($modulename);
	if (!isset($modules[$modulename])) {
		return array(_("Could not read module.xml"));
	}
	
	// don't force this bit - we can't install a broken module (missing files) 
	if ($modules[$modulename]['status'] == MODULE_STATUS_BROKEN) {
		return array(_("Module ".$modules[$modulename]['rawname']." is broken and cannot be installed. You should try to download it again."));
	}
	
	if (!$force) {
	
		if (!in_array($modules[$modulename]['status'], array(MODULE_STATUS_NOTINSTALLED, MODULE_STATUS_NEEDUPGRADE))) {
			//return array(_("This module is already installed."));
			// This isn't really an error, we just exit
			return true;
		}
		
		// check dependencies
		if (is_array($errors = module_checkdepends($modules[$modulename]))) {
			return $errors;
		}
	}
	
	// run the scripts
	if (!_module_runscripts($modulename, 'install')) {
		return array(_("Failed to run installation scripts"));
	}
	
	if ($modules[$modulename]['status'] == MODULE_STATUS_NOTINSTALLED) {
		// customize INSERT query
		$sql = "INSERT INTO modules (modulename, version, enabled) values ('".$db->escapeSimple($modules[$modulename]['rawname'])."','".$db->escapeSimple($modules[$modulename]['version'])."', 1);";
	} else {
		// just need to update the version
		$sql = "UPDATE modules SET version='".$db->escapeSimple($modules[$modulename]['version'])."' WHERE modulename = '".$db->escapeSimple($modules[$modulename]['rawname'])."'";
	}
	
	// run query
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		return array(sprintf(_("Error updating database. Command was: %s; error was: %s "), $sql, $results->getMessage()));
	}
	
	// module is now installed & enabled, invalidate the modulelist class since it is now stale
	$modulelist =& modulelist::create($db);
	$modulelist->invalidate();

	// edit the notification table to list any remaining upgrades available or clear
	// it if none are left. It requres a copy of the most recent module_xml to compare
	// against the installed modules.
	//
	$sql = 'SELECT data FROM module_xml WHERE id = "xml"';
	$data = sql($sql, "getOne");
	$parser = new xml2ModuleArray($data);
	$xmlarray = $parser->parseAdvanced($data);
	$new_modules = array();
	if (count($xmlarray)) {
		foreach ($xmlarray['xml']['module'] as $mod) {
			$new_modules[$mod['rawname']] = $mod;
		}
	}
	module_upgrade_notifications($new_modules, 'PASSIVE');
	needreload();
	return true;
}

/** Disable a module, but reqmains installed
 * @param string   The name of the module to disable
 * @param bool     If true, skips status and dependency checks
 * @return mixed   True if succesful, array of error messages if not succesful
*/
function module_disable($modulename, $force = false) { // was disableModule
	$modules = module_getinfo($modulename);
	if (!isset($modules[$modulename])) {
		return array(_("Specified module not found"));
	}
	
	if (!$force) {
		if ($modules[$modulename]['status'] != MODULE_STATUS_ENABLED) {
			return array(_("Module not enabled: cannot disable"));
		}
		
		if ( ($depmods = module_reversedepends($modulename)) !== false) {
			return array(_("Cannot disable: The following modules depend on this one: ").implode(',',$depmods));
		}
	}
	
	_module_setenabled($modulename, false);
	needreload();
	return true;
}

/** Uninstall a module, but files remain
 * @param string   The name of the module to install
 * @param bool     If true, skips status and dependency checks
 * @return mixed   True if succesful, array of error messages if not succesful
 */
function module_uninstall($modulename, $force = false) {
	global $db;
	
	$modules = module_getinfo($modulename);
	if (!isset($modules[$modulename])) {
		return array(_("Specified module not found"));
	}
	
	if (!$force) {
		if ($modules[$modulename]['status'] == MODULE_STATUS_NOTINSTALLED) {
			return array(_("Module not installed: cannot uninstall"));
		}
		
		if ( ($depmods = module_reversedepends($modulename)) !== false) {
			return array(_("Cannot disable: The following modules depend on this one: ").implode(',',$depmods));
		}
	}
	
	$sql = "DELETE FROM modules WHERE modulename = '".$db->escapeSimple($modulename)."'";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		return array(_("Error updating database: ").$results->getMessage());
	}
	
	if (!_module_runscripts($modulename, 'uninstall')) {
		return array(_("Failed to run un-installation scripts"));
	}
	
	needreload();
	return true;
}

/** Totally deletes a module
 * @param string   The name of the module to install
 * @param bool     If true, skips status and dependency checks
 * @return mixed   True if succesful, array of error messages if not succesful
 */
function module_delete($modulename, $force = false) {
	global $amp_conf;
	
	$modules = module_getinfo($modulename);
	if (!isset($modules[$modulename])) {
		return array(_("Specified module not found"));
	}
	
	if ($modules[$modulename]['status'] != MODULE_STATUS_NOTINSTALLED) {
		if (is_array($errors = module_uninstall($modulename, $force))) {
			return $errors;
		}
	}
	
	// delete module directory
	//TODO : do this in pure php
	$dir = $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename;
	if (!is_dir($dir)) {
		return array(sprintf(_("Cannot delete directory %s"), $dir));
	}
	if (strpos($dir,"..") !== false) {
		die_freepbx("Security problem, denying delete");
	}
	exec("rm -r ".escapeshellarg($dir),$output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_("Error deleting directory %s (code %d)"), $dir, $exitcode));
	}
	
	// uninstall will have called needreload() if necessary
	return true;
}

/** Internal use only */
function _module_setenabled($modulename, $enabled) {
	global $db;
	$sql = 'UPDATE modules SET enabled = '.($enabled ? '1' : '0').' WHERE modulename = "'.$db->escapeSimple($modulename).'"';
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die_freepbx($sql."<br>\n".$results->getMessage());
	}
	$modulelist =& modulelist::create($db);
	$modulelist->invalidate();
}

function _module_readxml($modulename) {
	global $amp_conf;
	switch ($modulename) {
		case 'builtin': // special handling
			$dir = $amp_conf['AMPWEBROOT'];
			$xmlfile = $dir.'/admin/module-builtin.xml';
		break;
		default:
			$dir = $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename;
			$xmlfile = $dir.'/module.xml';
		break;
	}

	if (file_exists($xmlfile)) {
		$data = file_get_contents($xmlfile);
		//$parser = new xml2ModuleArray($data);
		//$xmlarray = $parser->parseModulesXML($data);
		$parser = new xml2Array($data);
		$xmlarray = $parser->data;
		if (isset($xmlarray['module'])) {
			// add a couple fields first
			$xmlarray['module']['name'] = str_replace("\n&\n","&",$xmlarray['module']['name']);
			$xmlarray['module']['displayname'] = $xmlarray['module']['name'];
			if (isset($xmlarray['module']['description'])) {
				$xmlarray['module']['description'] = trim(str_replace("\n","",$xmlarray['module']['description']));
			}
			if (isset($xmlarray['module']['menuitems'])) {
				
				foreach ($xmlarray['module']['menuitems'] as $item=>$displayname) {
					$displayname = str_replace("\n&\n","&",$displayname);
					$xmlarray['module']['menuitems'][$item] = $displayname;
					$path = '/module/menuitems/'.$item;
					
					// find category
					if (isset($parser->attributes[$path]['category'])) {
						$category = str_replace("\n&\n","&",$parser->attributes[$path]['category']);
					} else if (isset($xmlarray['module']['category'])) {
						$category = str_replace("\n&\n","&",$xmlarray['module']['category']);
					} else {
						$category = 'Basic';
					}
					
					// find type
					if (isset($parser->attributes[$path]['type'])) {
						$type = $parser->attributes[$path]['type'];
					} else if (isset($xmlarray['module']['type'])) {
						$type = $xmlarray['module']['type'];
					} else {
						$type = 'setup';
					}
					
					// sort priority
					if (isset($parser->attributes[$path]['sort'])) {
						// limit to -10 to 10
						if ($parser->attributes[$path]['sort'] > 10) {
							$sort = 10;
						} else if ($parser->attributes[$path]['sort'] < -10) {
							$sort = -10;
						} else {
							$sort = $parser->attributes[$path]['sort'];
						}
					} else {
						$sort = 0;
					}

					// setup basic items array
					$xmlarray['module']['items'][$item] = array(
						'name' => $displayname,
						'type' => $type,
						'category' => $category,
						'sort' => $sort,
					);
					
					// add optional values:
					$optional_attribs = array(
						'href', // custom href
						'target', // custom target frame
						'display', // display= override
						'needsenginedb', // set to true if engine db access required (e.g. astman access)
						'needsenginerunning', // set to true if required to run
						'access', // set to all if all users should always have access
					);
					foreach ($optional_attribs as $attrib) {
						if (isset($parser->attributes[$path][ $attrib ])) {
							$xmlarray['module']['items'][$item][ $attrib ] = $parser->attributes[$path][ $attrib ];
						}
					}
					
				}
			}
			return $xmlarray['module'];
		}
	}
	return null;
}

// Temporarily copied here, for people that haven't upgraded their
// IVR module..

function modules_getversion($modname) {
	return _modules_getversion($modname); 
}

// This returns the version of a module
function _modules_getversion($modname) {
	global $db;

	$sql = "SELECT version FROM modules WHERE modulename = '".$db->escapeSimple($modname)."'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if (isset($results['version'])) 
		return $results['version'];
	else
		return null;
}

/** Updates the version field in the module table
 * Should only be called internally
 */
function _modules_setversion($modname, $vers) {
	global $db;

	return ;
}

/** Run the module install/uninstall scripts
 * @param string  The name of the module
 * @param string  The action to perform, either 'install' or 'uninstall'
 * @return boolean  If the action was succesful
 */
function _module_runscripts($modulename, $type) {
	global $amp_conf;
	$db_engine = $amp_conf["AMPDBENGINE"];
	
	$moduledir = $amp_conf["AMPWEBROOT"]."/admin/modules/".$modulename;
	if (!is_dir($moduledir)) {
		return false;
	}
	
	switch ($type) { 
		case 'install':
			// install sql files
			$sqlfilename = "install.sql";
			
			if (is_file($moduledir.'/'.$sqlfilename)) {
				execSQL($moduledir.'/'.$sqlfilename);
			}
			
			// then run .php scripts
			_modules_doinclude($moduledir.'/install.php', $modulename);
		break;
		case 'uninstall':
			// run uninstall .php scripts first
			_modules_doinclude($moduledir.'/uninstall.php', $modulename);
			
			$sqlfilename = "uninstall.sql";
			
			// then uninstall sql files 
			if (is_file($moduledir.'/'.$sqlfilename)) {
				execSQL($moduledir.'/'.$sqlfilename);
			}
			
		break;
		default:
			return false;
	}
	
	return true;
}

function _modules_doinclude($filename, $modulename) {
	// we provide the following variables to the included file (as well as $filename and $modulename)
	global $db, $amp_conf, $asterisk_conf;
	
	if (file_exists($filename) && is_file($filename)) {
		include_once($filename);
	}
}

/* module_get_annoucements()

	Get's any annoucments, security warnings, etc. that may be related to the current freepbx version. Also
	transmits a uniqueid to help track the number of installations using the online module admin system.
	The uniqueid used is completely anonymous and not trackable.
*/
function module_get_annoucements() {
	global $db;
	global $amp_conf;
	$firstinstall=false;
	$type=null;

	$sql = "SELECT * FROM module_xml WHERE id = 'installid'";
	$result = sql($sql,'getRow',DB_FETCHMODE_ASSOC);

	// if not set so this is a first time install
	// get a new hash to account for first time install
	//
	if (!isset($result['data']) || trim($result['data']) == "") {

		$firstinstall=true;
		$install_hash = _module_generate_unique_id();
		$installid = $install_hash['uniqueid'];
		$type = $install_hash['type'];

		// save the hash so we remeber this is a first time install
		//
		$data4sql = $db->escapeSimple($installid);
		sql("INSERT INTO module_xml (id,time,data) VALUES ('installid',".time().",'".$data4sql."')");
		$data4sql = $db->escapeSimple($type);
		sql("INSERT INTO module_xml (id,time,data) VALUES ('type',".time().",'".$data4sql."')");

	// Not a first time so save the queried hash and check if there is a type set
	//
	} else {
		$installid=$result['data'];
		$sql = "SELECT * FROM module_xml WHERE id = 'type'";
		$result = sql($sql,'getRow',DB_FETCHMODE_ASSOC);

		if (isset($result['data']) && trim($result['data']) != "") {
			$type=$result['data'];
		}
	}

	// Now we have the id and know if this is a firstime install so we can get the announcement
	//
	$options = "?installid=".urlencode($installid);

	if (trim($type) != "") {
		$options .= "&type=".urlencode($type);
	}
	if ($firstinstall) {
		$options .= "&firstinstall=yes";
	}
	$engver=engine_getinfo();
	if ($engver['engine'] == 'asterisk' && trim($engver['engine']) != "") {
		$options .="&astver=".urlencode($engver['version']);
	} else {
		$options .="&astver=".urlencode($engver['raw']);
	}

	$fn = "http://mirror.freepbx.org/version-".getversion().".html".$options;
	if (!$amp_conf['MODULEADMINWGET']) {
		$announcement = @ file_get_contents($fn);
	} else {
		$announcement = '';
	}
	if (empty($announcement)) {
		$fn2 = str_replace('&','\\&',$fn);
		exec("wget -O - $fn2 2>> /dev/null", $data_arr, $retcode);
		$announcement = implode("\n",$data_arr);
	}
	return $announcement;
}

/* Assumes properly formated input, which is ok since
   this is a private function and error checking is done
	 through proper regex scanning above

	 Returns: random md5 hash
 */
function _module_generate_random_id($type=null, $mac=null) {

	if (trim($mac) == "") {
		$id['uniqueid'] = md5(mt_rand());
	} else {
		// MD5 hash of the MAC so it is not identifiable
		//
		$id['uniqueid'] = md5($mac);
	}
	$id['type'] = $type;

	return $id;
}

/* _module_generate_unique_id

	The purpose of this function is to generate a unique id that will try
	and regenerate the same unique id on a system if called multiple
	times. The id is unique but is not in any way identifable so that
	privacy is not compromised.

	Returns:

	Array: ["uniqueid"] => unique_md5_hash
	       ["type"]     => type_passed_in
  
*/
function _module_generate_unique_id($type=null) {

	// Array of macs that require identification so we know these are not
	// 'real' systems. Either home setups or test environments
	//
	$ids = array('000C29' => 'vmware',
	             '000569' => 'vmware',
	             '00163E' => 'xensource'
	            ); 
	$mac_address = array();
	$chosen_mac = null;

	// TODO: put proper path in places for ifconfig, try various locations where it may be if
	//       non-0 return code.
	//
	exec('/sbin/ifconfig',$output, $return);

	if ($return != '0') {

		// OK try another path
		//
		exec('ifconfig',$output, $return);

		if ($return != '0') {
			// No seed available so return with random seed
			return _module_generate_random_id($type);
		}
	}

	// parse the output of ifconfig to get list of MACs returned
	//
	foreach ($output as $str) {
		// make sure each line contains a valid MAC and IP address and then
		//
		if (preg_match("/([0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5})/", $str, $mac)) {
			$mac_address[] = strtoupper(preg_replace("/:/","",$mac[0]));
		}
	}

	if (trim($type) == "") {
		foreach ($mac_address as $mac) {
			$id = substr($mac,0,6);

			// If we care about this id, then choose it and set the type
			// we only choose the first one we see
			//
			if (array_key_exists($id,$ids)) {
				$chosen_mac = $mac;
				$type = $ids[$id];
				break;
			}
		}
	}

	// Now either we have a chosen_mac, we will use the first mac, or if something went wrong
	// and there is nothing in the array (couldn't find a mac) then we will make it purely random
	//
  if ($type == "vmware" || $type == "xensource") {
		// vmware, xensource machines will have repeated macs so make random
		return _module_generate_random_id($type);
	} else if ($chosen_mac != "") {
		return _module_generate_random_id($type, $chosen_mac);
	} else if (isset($mac_address[0])) {
		return _module_generate_random_id($type, $mac_address[0]);
	} else {
		return _module_generate_random_id($type);
	}
} 

function module_run_notification_checks() {
	global $db;
	$modules_needup = module_getinfo(false, MODULE_STATUS_NEEDUPGRADE);
	$modules_broken = module_getinfo(false, MODULE_STATUS_BROKEN);
	
	$notifications =& notifications::create($db);
	if ($cnt = count($modules_needup)) {
		$text = (($cnt > 1) ? sprintf(_('You have %s disabled modules'), $cnt) : _('You have a disabled module'));
		$desc = _('The following modules are disabled because they need to be upgraded:')."\n".implode(", ",array_keys($modules_needup));
		$desc .= "\n\n"._('You should go to the module admin page to fix these.');
		$notifications->add_error('freepbx', 'modules_disabled', $text, $desc, '?type=tool&display=modules');
	} else {
		$notifications->delete('freepbx', 'modules_disabled');
	}
	if ($cnt = count($modules_broken)) {
		$text = (($cnt > 1) ? sprintf(_('You have %s broken modules'), $cnt) : _('You have a broken module'));
		$desc = _('The following modules are disabled because they are broken:')."\n".implode(", ",array_keys($modules_broken));
		$desc .= "\n\n"._('You should go to the module admin page to fix these.');
		$notifications->add_critical('freepbx', 'modules_broken', $text, $desc, '?type=tool&display=modules', false);
	} else {
		$notifications->delete('freepbx', 'modules_broken');
	}
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

function dbug_write($txt,$check){
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

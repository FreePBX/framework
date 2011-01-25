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

//include all necessary classes TODO: include them dynamically as needed
$dirname = dirname(__FILE__);

require_once($dirname . '/libraries/components.class.php');
require_once($dirname . '/libraries/xml2Array.class.php');
require_once($dirname . '/libraries/cronmanager.class.php');
require_once($dirname . '/libraries/moduleHook.class.php');
require_once($dirname . '/libraries/notifications.class.php');
require_once($dirname . '/libraries/ampuser.class.php');
require_once($dirname . '/libraries/components.class.php');
require_once($dirname . '/libraries/modulelist.class.php');
require_once($dirname . '/libraries/freepbx_conf.class.php');
require_once($dirname . '/libraries/featurecodes.class.php');

//include other files
require_once($dirname . '/libraries/utility.functions.php');
require_once($dirname . '/libraries/module.functions.php');
require_once($dirname . '/libraries/usage_registry.functions.php');
require_once($dirname . '/libraries/php-upgrade.functions.php');
require_once($dirname . '/libraries/sql.functions.php');

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
			// earlier revisions of 1.4 and dahdi loaded but still running as zap, so if ZapScan is present, we assume
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
				return array('engine'=>'asterisk', 'version' => '1.8', 'additional' => $matches[1], 'raw' => $verinfo);
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
	
	// retrieve_conf html output
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
    unset($output);
		exec('killall -HUP op_server.pl 2>&1', $output, $exit_val);
		if ($exit_val != 0) {
			$desc = _('Could not reload the FOP operator panel server using killall -HUP op_server.pl. Configuration changes may not be reflected in the panel display.');
			$notify->add_error('freepbx','reload_fop', _('Could not reload FOP server'), $desc);
      // send the error output to dbug log if enabled.
      dbug($output);
			
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


// draw list for users and devices with paging
// $skip has been deprecated, used to be used to page-enate
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
 * ensure that any form that includes this calls the setDestinations() javascript function on submit.
 * ie: if the form name is "edit", and drawselects has been called with $i=2 then use onsubmit="setDestinations(edit,2)"
 * $table specifies if the destinations will be drawn in a new <tr> and <td>
 * 
 */   
function drawselects($goto,$i,$show_custom=false, $table=true, $nodest_msg='') {
	global $tabindex, $active_modules, $drawselect_destinations, $drawselects_module_hash; 
	$html=$destmod=$errorclass=$errorstyle='';
  if ($nodest_msg == '') {
	  $nodest_msg = '== '._('choose one').' ==';
  }

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
		//sort destination alphabetically		
		ksort($drawselect_destinations);
		ksort($drawselects_module_hash);
	}
	//set variables as arrays for the rare (impossible?) case where there are none
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
	  if($destmod==''){//if we haven't found a match, display error dest
		  $destmod='Error';
		  $drawselect_destinations['Error'][]=array('destination'=>$goto, 'description'=>'Bad Dest: '.$goto, 'class'=>'drawselect_error');
		  $drawselects_module_hash['Error']='error';
	  }
  }	

	//draw "parent" select box
	$style=' style="'.(($destmod=='Error')?'background-color:red;':'background-color:white;').'"';
	$html.='<select name="goto'.$i.'" class="destdropdown" '.$style.' tabindex="'.++$tabindex.'">';
	$html.='<option value="" style="background-color:white;">'.$nodest_msg.'</option>';
	foreach($drawselects_module_hash as $mod => $disc){
		/* We bind to the hosting module's domain. If we find the translation there we use it, if not
		 * we try the default 'amp' domain. If still no luck, we will try the _() which is the current
		 * module's display since some old translation code may have stored it locally but should migrate */
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
	$tabindexhtml=' tabindex="'.++$tabindex.'"';//keep out of the foreach so that we don't increment it
	foreach($drawselect_destinations as $cat=>$destination){
		$style=(($cat==$destmod)?'':'display:none;');
		if($cat=='Error'){$style.=' '.$errorstyle;}//add error style
		$style=' style="'.(($cat=='Error')?'background-color:red;':$style).'"';
		$html.='<select name="'.str_replace(' ','_',$cat).$i.'" '.$tabindexhtml.$style.' class="destdropdown2">';
		foreach($destination as $dest){
			$selected=($goto==$dest['destination'])?'SELECTED ':' ';
		// This is ugly, but I can't think of another way to do localization for this child object
		    if(isset( $dest['category']) && dgettext('amp',"Terminate Call") == $dest['category']) {
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

function get_headers_assoc($url) {
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


// Dragged this in from page.modules.php, so it can be used by install_amp. 
function runModuleSQL($moddir,$type){
	trigger_error("runModuleSQL() is depreciated - please use _module_runscripts(), or preferably module_install() or module_enable() instead", E_USER_WARNING);
	_module_runscripts($moddir, $type);
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
function redirect_standard( /* Note. Read the next line. Variable No of Params */ ) {
	$args = func_get_Args();

        foreach (array_merge(array('type','display'),$args) as $arg) {
                if (isset($_REQUEST[$arg])) {
                        $urlopts[] = $arg.'='.urlencode($_REQUEST[$arg]);
                }
        }
        $url = $_SERVER['PHP_SELF'].'?'.implode('&',$urlopts);
        redirect($url);
}

function redirect_standard_continue( /* Note. Read the next line. Varaible No of Params */ ) {
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
//'module' => the display for the section this should be listed under defaults to module display (can be used to group subsets within one module)
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

// TODO: should this be moved to modules.functions.php or usage_registry.functions.php
//

/**
 * Search through all active modules for a function that ends in $func.
 * Pass it $opts and return whatever is returned in to an array with the
 * retuning module name as the key
 * Takes:
 * @func variable	the function name that we are searching for. The module name
 * 					will be appened to this
 * @opts mixed		a variable or array that will be passed to the function being 
 * 					called , if its found
 *
 */
function mod_func_iterator($func, $opts = '') {
	global $active_modules;
	$res = array();
	
	foreach ($active_modules as $active => $mod) {
		$funct = $mod['rawname'] . '_' . $func;
		if (function_exists($funct)) {
			$res[$mod['rawname']] = $funct($opts);
		}
	}
	
	return $res;
}

?>

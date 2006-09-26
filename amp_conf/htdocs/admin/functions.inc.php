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

require_once('featurecodes.class.php');
require_once('components.class.php');

function parse_amportal_conf($filename) {
	$file = file($filename);
	if (is_array($file)) {
		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9]+)=([a-zA-Z0-9 .&-@=_<>\"\']+)\s*$/",$line,$matches)) {
				$conf[ $matches[1] ] = $matches[2];
			}
		}
	} else {
		die("<h1>Missing or unreadable config file ($filename)...cannot continue</h1>");
	}
	
	if ( !isset($conf["AMPDBENGINE"]) || ($conf["AMPDBENGINE"] == "")) {
		$conf["AMPDBENGINE"] = "mysql";
	}
	
	if ( !isset($conf["AMPDBNAME"]) || ($conf["AMPDBNAME"] == "")) {
		$conf["AMPDBNAME"] = "asterisk";
	}

/*			
	if (($amp_conf["AMPDBENGINE"] == "sqlite") && (!isset($amp_conf["AMPDBENGINE"])))
		$amp_conf["AMPDBFILE"] = "/var/lib/freepbx/freepbx.sqlite";
*/
	
	return $conf;
}

function parse_asterisk_conf($filename) {
	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([a-zA-Z0-9]+)\s* => \s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	return $conf;
}

function getAmpAdminUsers() {
	global $db;

	$sql = "SELECT username FROM ampusers WHERE sections='*'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	return $results;
}

function getAmpUser($username) {
	global $db;
	
	$sql = "SELECT username, password, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = '".$username."'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	
	if (count($results) > 0) {
		$user = array();
		$user["username"] = $results[0][0];
		$user["password"] = $results[0][1];
		$user["extension_low"] = $results[0][2];
		$user["extension_high"] = $results[0][3];
		$user["deptname"] = $results[0][4];
		$user["sections"] = explode(";",$results[0][5]);
		return $user;
	} else {
		return false;
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
			$this->_password = $user["password"];
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

// returns true if extension is within allowed range
function checkRange($extension){
	$low = isset($_SESSION["AMP_user"]->_extension_low)?$_SESSION["AMP_user"]->_extension_low:'';
	$high = isset($_SESSION["AMP_user"]->_extension_high)?$_SESSION["AMP_user"]->_extension_high:'';
	
	if ((($extension >= $low) && ($extension <= $high)) || ($low == '' && $high == ''))
		return true;
	else
		return false;
}

// returns true if department string matches dept for this user
function checkDept($dept){
	$deptname = isset($_SESSION["AMP_user"])?$_SESSION["AMP_user"]->_deptname:null;
	
	if ( ($dept == null) || ($dept == $deptname) )
		return true;
	else
		return false;
}

/* look for all modules in modules dir.
** returns array:
** array['module']['displayName']
** array['module']['version']
** array['module']['type']
** array['module']['status']
** array['module']['items'][array(items)]
** Use find_modules() to return only specific type or status
*/
function find_allmodules() {
	global $db;
	global $amp_conf;
	
	if (!is_dir($amp_conf['AMPWEBROOT'].'/admin/modules'))
	{
	    mkdir( $amp_conf['AMPWEBROOT'].'/admin/modules' );
	    return;
	}
	
	$dir = opendir($amp_conf['AMPWEBROOT'].'/admin/modules');
	$data = "<xml>";
	//loop through each module directory, ensure there is a module.ini file
	while ($file = readdir($dir)) {
		if (($file != ".") && ($file != "..") && ($file != "CVS") && ($file != ".svn") && is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file) && is_file($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file.'/module.xml')) {
			//open module.xml and read contents
			if(is_file($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file.'/module.xml')){
				$data .=file_get_contents($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file.'/module.xml');
				
			}
		}
	}
	$data .= "</xml>";
	$parser = new xml2ModuleArray($data);
	$xmlarray = $parser->parseModulesXML($data);
	
	// determine details about this module from database
	// modulename should match the directory name
	$sql = "SELECT * FROM modules";
	$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	if (is_array($results)) {
		foreach($results as $result) {
				/* 
				set status key based on results
				-1=broken (in table, not not on filesystem)
				0 or null=not installed
				1=disabled
				2=enabled
				3=enabled and needs upgrade
				*/
				if(isset($xmlarray[ $result['modulename'] ] ) && is_array($xmlarray[ $result['modulename'] ])) {
					if ($result['enabled'] != 0) {
						// check if file and registered versions are the same
						// version_compare returns 0 if no difference
						if (version_compare($result['version'],$xmlarray[ $result['modulename'] ]["version"]) === 0)
							$xmlarray[ $result['modulename'] ]["status"] = 2;
						else 
							$xmlarray[ $result['modulename'] ]["status"] = 3;
					} else {
						$xmlarray[ $result['modulename'] ]["status"] = 1;
					}
				} else {
					$xmlarray[ $result['modulename'] ]["status"] = -1;
				}
					
		}
	}

	//echo "<pre>"; print_r($xmlarray); echo "</pre>";
	return $xmlarray;
}

/* finds modules of the specified status and type
** $status can be 0 (not installed), 1 (disabled), 2 (enabled)
** $type can be 'setup' or 'tool'
**
** returns array:
** array['module']['displayName']
** array['module']['version']
** array['module']['type']
** array['module']['status']
** array['module']['items'][array(items)]
*/
function find_modules($status) {
	$modules = find_allmodules();
	if (isset($modules) && is_array($modules)) {	
		foreach(array_keys($modules) as $key) {
			//remove modules not matching status
			if(isset($modules[$key]['status']) && $modules[$key]['status'] == $status ){
				$return_modules[$key] = $modules[$key];
			}
		}
		return $return_modules;
	} else {
		return false;
	}
}


// This returns the version of a module
function modules_getversion($modname) {
	global $db;

	$sql = "SELECT version FROM modules WHERE modulename = '$modname'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if (isset($results['version'])) 
		return $results['version'];
	else
		return null;
}

// I bet you can't guess what this one does.
function modules_setversion($modname, $vers) {
	global $db;

	return sql("UPDATE modules SET version='$vers' WHERE modulename = '$modname'");
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
		die($results->getDebugInfo());
	}
	return $results;
}

// sql text formatting -- couldn't see that one was available already
function sql_formattext($txt) {
	if (isset($txt)) {
		$fmt = str_replace("'", "''", $txt);
		$fmt = "'" . $fmt . "'";
	} else {
		$fmt = 'null';
	}

	return $fmt;
}

//tell application we need to reload asterisk
function needreload() {
	global $db;
	$sql = "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage()); 
	}
}

//get the version number
function getversion() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'version'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

// draw list for users and devices with paging
function drawListMenu($results, $skip, $type, $dispnum, $extdisplay, $description) {
	$perpage=20;
	
	$skipped = 0;
	$index = 0;
	if ($skip == "") $skip = 0;
 	echo "<li><a id=\"".($extdisplay=='' ? 'current':'')."\" href=\"config.php?type=".$type."&display=".$dispnum."\">"._("Add")." ".$description."</a></li>";

	if (isset($results)) {
		foreach ($results AS $key=>$result) {
			if ($index >= $perpage) {
				$shownext= 1;
				break;
			}
			
			if ($skipped<$skip && $skip!= 0) {
				$skipped= $skipped + 1;
				continue;
			}
			
			$index= $index + 1;	
			echo "<li><a id=\"".($extdisplay==$result[0] ? 'current':'')."\" href=\"config.php?type=".$type."&display=".$dispnum."&extdisplay={$result[0]}&skip={$skip}\">{$result[1]} <{$result[0]}></a></li>";
		}
	}
	 
	if ($index >= $perpage) {
		 print "<li><center>";
	}
	 
	if ($skip) {
		 $prevskip= $skip - $perpage;
		 if ($prevskip<0) $prevskip= 0;
		 $prevtag_pre= "<a href='?type=".$type."&display=".$dispnum."&skip=$prevskip'>[PREVIOUS]</a>";
		 print "$prevtag_pre";
	}
	
	if (isset($shownext)) {
		$nextskip= $skip + $index;
		if ($prevtag_pre) $prevtag .= " | ";
		print "$prevtag <a href='?type=".$type."&display=".$dispnum."&skip=$nextskip'>[NEXT]</a>";
	}
	elseif ($skip) {
		print "$prevtag";
	}
	 
	 print "</center></li>";
}

// this function simply makes a connection to the asterisk manager, and should be called by modules that require it (ie: dbput/dbget)
function checkAstMan() {
	require_once('common/php-asmanager.php');
	global $amp_conf;
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		return $astman->disconnect();
	} else {
		echo "<h3>Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]."</h3>This module requires access to the Asterisk Manager.  Please ensure Asterisk is running and access to the manager is available.</div>";
		exit;
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
			} else if (preg_match("/^\s*(\d+)\s*=>\s*dup,(.*)\s*([;#].*)?/",$line,$matches)) {
				// "mailbox=>dup,name"
				// duplace name line
				$vmconf[$section][ $matches[1] ]["dups"][] = $matches[2];
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
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
	if ($iteration == 0) {
		$section = null;
	}
	
	$output = array();
		
	// if the file does not, copy if from the template.
	// TODO: is this logical?
	// TODO: don't use hardcoded path...? 
	if (!file_exists($filename)) {
		if (!copy( "/etc/asterisk/voicemail.conf.template", $filename )){
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
				
			} else if (preg_match("/^(\s*)(\d+)(\s*)=>(\s*)dup,(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// "mailbox=>dup,name"
				// duplace name line
				// leave it as-is (for now)
				//DEBUG echo "\ndup mailbox";
				$output[] = $line;
			} else if (preg_match("/^(\s*)#include(\s+)(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// include another file
				//DEBUG echo "\ninclude ".$matches[3]."<blockquote>";
				
				// make sure we have something as a comment
				if (!isset($matches[4])) {
					$matches[4] = "";
				}
				
				if ($matches[3][0] == "/") {
					// absolute path
					$include_filename = $matches[3];
				} else {
					// relative path
					$include_filename =  dirname($filename)."/".$matches[3];
				}
				
				$output[] = $matches[1]."#include".$matches[2].$matches[3].$matches[4];
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


// $goto is the current goto destination setting
// $i is the destination set number (used when drawing multiple destination sets in a single form ie: digital receptionist)
// esnure that any form that includes this calls the setDestinations() javascript function on submit.
// ie: if the form name is "edit", and drawselects has been called with $i=2 then use onsubmit="setDestinations(edit,2)"
function drawselects($goto,$i) {  
	
	/* --- MODULES BEGIN --- */
	global $active_modules;
	
	// This is purely to remove a warning. 
	if (!isset($selectHtml)) { $selectHtml=''; }
	$selectHtml .= '<tr><td colspan=2>'; 
	
	//check for module-specific destination functions
	foreach ($active_modules as $mod => $displayname) {
		$funct = strtolower($mod.'_destinations');
	
		//if the modulename_destinations() function exits, run it and display selections for it
		if (function_exists($funct)) {
			$options = "";
			$destArray = $funct(); //returns an array with 'destination' and 'description'.
			$checked = false;
			if (isset($destArray)) {
				//loop through each option returned by the module
				foreach ($destArray as $dest) {
					// check to see if the currently selected goto matches one these destinations
					if ($dest['destination'] == $goto)
						$checked = true;  //there is a match, so we select the radio for this group

					// create an select option for each destination 
					$options .= '<option value="'.$dest['destination'].'" '.(strpos($goto,$dest['destination']) === false ? '' : 'SELECTED').'>'.($dest['description'] ? $dest['description'] : $dest['destination']);
				}
				
				// make a unique id to be used for the HTML id
				// This allows us to have multiple drawselect() sets on the page without
				// conflicting with each other
				$radioid = uniqid("drawselect");
				
				$selectHtml .=	'<input type="radio" id="'.$radioid.'" name="goto_indicate'.$i.'" value="'.$mod.'" onclick="javascript:this.form.goto'.$i.'.value=\''.$mod.'\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) this.form.goto'.$i.'.value=\''.$mod.'\';" '.($checked? 'CHECKED=CHECKED' : '').' /> '._($displayname['displayName']).': ';
				if ($checked) { $goto = $mod; }
				$selectHtml .=	'<select name="'.$mod.$i.'" onfocus="document.getElementById(\''.$radioid.'\').checked = true;">';
				$selectHtml .= $options;	
				$selectHtml .=	"</select><br>\n";
			}
			
		}
	}
	/* --- MODULES END --- */
	
	//display a custom goto field
	$radioid = uniqid("drawselect");
	$selectHtml .= '<input type="radio" id="'.$radioid.'" name="goto_indicate'.$i.'" value="custom" onclick="javascript:this.form.goto'.$i.'.value=\'custom\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) this.form.goto'.$i.'.value=\'custom\';" '.(strpos($goto,'custom') === false ? '' : 'CHECKED=CHECKED').' />';
	$selectHtml .= '<a href="#" class="info"> '._("Custom App<span><br>ADVANCED USERS ONLY<br><br>Uses Goto() to send caller to a custom context.<br><br>The context name <b>MUST</b> contain the word 'custom' and should be in the format custom-context , extension , priority. Example entry:<br><br><b>custom-myapp,s,1</b><br><br>The <b>[custom-myapp]</b> context would need to be created and included in extensions_custom.conf</span>").'</a>:';
	$selectHtml .= '<input type="text" size="15" name="custom'.$i.'" value="'.(strpos($goto,'custom') === false ? '' : $goto).'" onfocus="document.getElementById(\''.$radioid.'\').checked = true;" />';
	$selectHtml .= "\n<input type='hidden' name='goto$i' value='$goto'>";

	//close off our row
	$selectHtml .= '</td></tr>';
	
	return $selectHtml;
}


/* below are legacy functions required to allow pre 2.0 modules to function (ie: interact with 'extensions' table) */

	//add to extensions table - used in callgroups.php
	function legacy_extensions_add($addarray) {
		global $db;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('".$addarray[0]."', '".$addarray[1]."', '".$addarray[2]."', '".$addarray[3]."', '".$addarray[4]."', '".$addarray[5]."' , '".$addarray[6]."')";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage().$sql);
		}
		return $result;
	}
	
	//delete extension from extensions table
	function legacy_extensions_del($context,$exten) {
		global $db;
		$sql = "DELETE FROM extensions WHERE context = '".$context."' AND `extension` = '".$exten."'";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		return $result;
	}
	
	
	//get args for specified exten and priority - primarily used to grab goto destination
	function legacy_args_get($exten,$priority,$context) {
		global $db;
		$sql = "SELECT args FROM extensions WHERE extension = '".$exten."' AND priority = '".$priority."' AND context = '".$context."'";
		list($args) = $db->getRow($sql);
		return $args;
	}

/* end legacy functions */

/* Usage
Grab some XML data, either from a file, URL, etc. however you want. Assume storage in $strYourXML;

$objXML = new xml2Array();
$arrOutput = $objXML->parse($strYourXML);
print_r($arrOutput); //print it out, or do whatever!

*/

class xml2Array {
	
	var $arrOutput = array();
	var $resParser;
	var $strXmlData;
	
	function parse($strInputXML) {
	
			$this->resParser = xml_parser_create ();
			xml_set_object($this->resParser,$this);
			xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
			
			xml_set_character_data_handler($this->resParser, "tagData");
		
			$this->strXmlData = xml_parse($this->resParser,$strInputXML );
			if(!$this->strXmlData) {
				die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($this->resParser)),
			xml_get_current_line_number($this->resParser)));
			}
							
			xml_parser_free($this->resParser);
			
			return $this->arrOutput;
	}
	function tagOpen($parser, $name, $attrs) {
		$tag=array("name"=>$name,"attrs"=>$attrs); 
		array_push($this->arrOutput,$tag);
	}
	
	function tagData($parser, $tagData) {
		if(trim($tagData)) {
			if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
				$this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
			} 
			else {
				$this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
			}
		}
	}
	
	function tagClosed($parser, $name) {
		$this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
		array_pop($this->arrOutput);
	}
}

/*
	Return a much more manageable assoc array with module data.
*/
class xml2ModuleArray extends xml2Array {
	function parseModulesXML($strInputXML) {
		$arrOutput = $this->parse($strInputXML);
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
		// loop through all active modules
		foreach($active_modules as $this_module) {
				// look for requested hooks for $module
				// ie: findme_hook_extensions()
				$funct = $this_module['rawname'] . '_hook_' . $target_module;
				if( function_exists( $funct ) ) {
					// execute the function, appending the 
					// html output to that of other hooking modules
					if ($hookReturn = $funct($viewing_itemid,$target_menuid))
						$this->hookHtml .= $hookReturn;
					// remember who installed hooks
					// we need to know this for processing form vars
					$this->arrHooks[] = $this_module['rawname'];
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

function execSQL( $file )
{
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
	global $amp_conf;
	$db_engine = $amp_conf["AMPDBENGINE"];

	$data='';
	
	// if there is an sql file, run it
	// don't forget about our 2 sql syntaxes
	if (($db_engine  == "mysql") || ($db_engine == "pgsql")) {
		if (is_file($amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.sql")) {
			execSQL( $amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.sql" );
		}
	}
	elseif ($db_engine  == "sqlite"){
		if (is_file($amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.sqlite")) {
			execSQL( $amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.sqlite" );
		}
	}
	else{
		// what to do here? 
		// in general this should fail in earliers stages...
	}
	
	// if there is a php file, run it
	if (is_file($amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.php")) {
		include($amp_conf["AMPWEBROOT"]."/admin/modules/{$moddir}/{$type}.php");
	}
	return true;
}

/*
// just for testing hooks, i'll delete it later
function queues_hook_core($viewing_itemid, $target_menuid) {
	switch ($target_menuid) {
		case 'did':
			//get the current setting for this display (if any)
			$alertinfo = $viewing_itemid;
        	return '
				<tr>
					<td><a href="#" class="info">'._("Alert Info").'<span>'._('ALERT_INFO can be used for distinctive ring with SIP devices.').'</span></a>:</td>
					<td><input type="text" name="alertinfo" size="10" value="'.(($alertinfo) ? $alertinfo : "") .'"></td>
				</tr>
			';
		break;
		default:
			return false;
		break;
	}
}

function queues_hookProcess_core($viewing_itemid, $request) {
	switch ($request['action']) {
		case 'edtIncoming':
			echo "<h1>HI</h1>";
        	return '
				<tr>
					<td><a href="#" class="info">'._("Alert Info").'<span>'._('ALERT_INFO can be used for distinctive ring with SIP devices.').'</span></a>:</td>
					<td><input type="text" name="alertinfo" size="10" value="'.(($alertinfo) ? $alertinfo : "") .'"></td>
				</tr>
			';
		break;
		default:
			return false;
		break;
	}
}
*/

?>

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

function parse_amportal_conf($filename) {
	$file = file($filename);
	if (is_array($file)) {
		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_<>\"\']+)\s*$/",$line,$matches)) {
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
	
	if ( !isset($conf["AMPENGINE"]) || ($conf["AMPENGINE"] == "")) {
		$conf["AMPENGINE"] = "asterisk";
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
	
	$sql = "SELECT username, password, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = '".addslashes($username)."'";
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

function engine_getinfo() {
	global $amp_conf;
	global $astman;

	switch ($amp_conf['AMPENGINE']) {
		case 'asterisk':
			if ($astman) {
				//get version
				$response = $astman->send_request('Command', array('Command'=>'show version'));
				$verinfo = $response['data'];
			} else {
				// could not connect to asterisk manager
				return false;
			}
			
			if (preg_match('/Asterisk SVN.+/', $verinfo)) {
				return array('engine'=>'asterisk', 'version' => '99', 'additional' => '99');
			}
			if (preg_match('/Asterisk (\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
				return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[4]);
			}
		break;
	}
	return false;
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

function check_reload_needed() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
	$row = $db->getRow($sql);
	if(DB::IsError($row)) {
		die($row->getMessage());
	}
	return ($row[0] == 'true');
}

//get the version number
function getversion() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'version'";
	$results = $db->getRow($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results[0];
}

// draw list for users and devices with paging
function drawListMenu($results, $skip, $type, $dispnum, $extdisplay, $description) {
	$perpage=20;
	
	$skipped = 0;
	$index = 0;
	if ($skip == "") $skip = 0;
	echo "<ul>\n";
 	echo "\t<li><a ".($extdisplay=='' ? 'class="current"':'')." href=\"config.php?type=".$type."&display=".$dispnum."\">"._("Add")." ".$description."</a></li>\n";

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
			echo "\t<li><a".($extdisplay==$result[0] ? ' class="current"':''). " href=\"config.php?type=".$type."&amp;display=".$dispnum."&amp;extdisplay={$result[0]}&amp;skip={$skip}\">{$result[1]} &lt;{$result[0]}&gt;</a></li>\n";
		}
	}
	 
	if ($skip) {
		 $prevskip= $skip - $perpage;
		 if ($prevskip<0) $prevskip= 0;
		 $prevtag_pre= "<a href='?type=".$type."&amp;display=".$dispnum."&amp;skip=$prevskip'>" .
			_("[PREVIOUS]") ."</a>";
		 print "\t<li><center>";
		 print "$prevtag_pre";
		 print "</center></li>\n";
	}
	
	if (isset($shownext)) {
		$nextskip= $skip + $index;
		if ($prevtag_pre) $prevtag .= " | ";
		print "\t<li><center>";
		print "$prevtag <a href='?type=".$type."&amp;display=".$dispnum."&amp;skip=$nextskip'>" . 
			_("[NEXT]") . "</a>";
		print "</center></li>\n";
	}
	echo "</ul>\n";
}

// this function simply makes a connection to the asterisk manager, and should be called by modules that require it (ie: dbput/dbget)
function checkAstMan() {
	global $amp_conf;
	global $astman;

	if ($astman) {
//		TODO old code was,
// 		return $astman->disconnect();
//		is this correct...?
		return true;
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
				
				$selectHtml .=	'<input type="radio" id="'.$radioid.'" name="goto_indicate'.$i.'" value="'.$mod.'" '.
								'onclick="javascript:this.form.goto'.$i.'.value=\''.$mod.'\';" '.
								'onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) this.form.goto'.$i.'.value=\''.$mod.'\';" '.
								($checked? 'CHECKED=CHECKED' : '').' /> ';
				$selectHtml .= '<label for="'.$radioid.'">'._($displayname['displayname']).':</label> ';
				if ($checked) { $goto = $mod; }
				$selectHtml .=	'<select name="'.$mod.$i.'" onfocus="document.getElementById(\''.$radioid.'\').checked = true; this.form.goto'.$i.'.value=\''.$mod.'\';">';
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
		$sql = "DELETE FROM extensions WHERE context = '".addslashes($context)."' AND `extension` = '".addslashes($exten)."'";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		return $result;
	}
	
	
	//get args for specified exten and priority - primarily used to grab goto destination
	function legacy_args_get($exten,$priority,$context) {
		global $db;
		$sql = "SELECT args FROM extensions WHERE extension = '".addslashes($exten)."' AND priority = '".addslashes($priority)."' AND context = '".addslashes($context)."'";
		list($args) = $db->getRow($sql);
		return $args;
	}

/* end legacy functions */

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
				$this->arrOutput[count($this->arrOutput)-1]['tagData'] .= "\n".$tagData;
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
	
	function recursive_parseLevel($items, &$attrs, $path = "") {
		$array = array();
		foreach (array_keys($items) as $idx) {
			$items[$idx]['name'] = strtolower($items[$idx]['name']);
			
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

// get_headers() for php4 (built in for php5)
if (!function_exists('get_headers')) {
	function get_headers($url ) {
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
	trigger_error("runModuleSQL() is depreciated - please use _module_runscripts(), or preferably module_install() or module_enable() instead", E_USER_WARNING);
	_module_runscripts($moddir, $type);
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
 
/** Get the latest module.xml file for this freePBX version. 
 * Caches in the database for 5 mintues.
 * If $module is specified, only returns the data for that module
 */
function module_getonlinexml($module = false) { // was getModuleXml()
	global $amp_conf;
	//this should be in an upgrade file ... putting here for now.
	sql('CREATE TABLE IF NOT EXISTS module_xml (time INT NOT NULL , data BLOB NOT NULL) TYPE = MYISAM ;');
	
	$result = sql('SELECT * FROM module_xml','getRow',DB_FETCHMODE_ASSOC);
	// if the epoch in the db is more than 2 hours old, or the xml is less than 100 bytes, then regrab xml
	// Changed to 5 minutes while not in release. Change back for released version.
	//
	// used for debug, time set to 0 to always fall through
	// if((time() - $result['time']) > 0 || strlen($result['data']) < 100 ) {
	if((time() - $result['time']) > 300 || strlen($result['data']) < 100 ) {
		$version = getversion();
		// we need to know the freepbx major version we have running (ie: 2.1.2 is 2.1)
		preg_match('/(\d+\.\d+)/',$version,$matches);
		//echo "the result is ".$matches[1];
		if (isset($amp_conf["AMPMODULEXML"])) {
			$fn = $amp_conf["AMPMODULEXML"]."modules-".$matches[1].".xml";
			// echo "(From amportal.conf)"; //debug
		} else {
		$fn = "http://mirror.freepbx.org/modules-".$matches[1].".xml";
			// echo "(From default)"; //debug
		}
		//$fn = "/usr/src/freepbx-modules/modules.xml";
		$data = file_get_contents($fn);
		// remove the old xml
		sql('DELETE FROM module_xml');
		// update the db with the new xml
		$data4sql = addslashes($data);
		sql('INSERT INTO module_xml (time,data) VALUES ('.time().',"'.$data4sql.'")');
	} else {
//		echo "using cache";
		$data = $result['data'];
	}
	//echo time() - $result['time'];
	$parser = new xml2ModuleArray($data);
	$xmlarray = $parser->parseAdvanced($data);
	//$modules = $xmlarray['XML']['MODULE'];
	
	//echo "<hr>Raw XML Data<pre>"; print_r(htmlentities($data)); echo "</pre>";
	//echo "<hr>XML2ARRAY<pre>"; print_r($xmlarray); echo "</pre>";
	
	
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

/** Looks through the modules directory and modules database and returns all available
 * information about one or all modules
 * @param string  (optional) The module name to query, or false for all module
 * @param mixed   (optional) The status(es) to show, using MODULE_STATUS_* constants. Can
 *                either be one value, or an array of values.
 */
function module_getinfo($module = false, $status = false) {
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
		// get info on all modules
		$dir = opendir($amp_conf['AMPWEBROOT'].'/admin/modules');
		while ($file = readdir($dir)) {
			if (($file != ".") && ($file != "..") && ($file != "CVS") && 
			    ($file != ".svn") && ($file != "_cache") && 
				is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file)) {
				
				$xml = _module_readxml($file);
				if (!is_null($xml)) {
					$modules[$file] = $xml;
					// if status is anything else, it will be updated below when we read the db
					$modules[$file]['status'] = MODULE_STATUS_NOTINSTALLED;
				}
			}
		}
		
		// query to get everything
		$sql = 'SELECT * FROM modules';
	}
	// determine details about this module from database
	// modulename should match the directory name
	
	$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	if (is_array($results)) {
		foreach($results as $row) {
			if (isset($modules[ $row['modulename'] ])) {
				if ($row['enabled'] != 0) {
					
					// check if file and registered versions are the same
					// version_compare returns 0 if no difference
					if (version_compare($row['version'], $modules[ $row['modulename'] ]['version']) == 0) {
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
	
	if ($status !== false) {
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
	}
	
	return $modules;
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
						if (preg_match('/^(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d(\.\d)*)$/i', $value, $matches)) {
							// matches[1] = operator, [2] = version
							$ver = getversion();
							$operator = (!empty($matches[1]) ? $matches[1] : 'ge'); // default to >=
							if (version_compare($matches[2], $ver, $operator) ) {
								$errors[] = _module_comparison_error_message('FreePBX', $matches[2], $ver, $operator);
							}
						}
					break;
					case 'module':
						if (preg_match('/^([a-z0-9_]+)(\s+(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d(\.\d)*))?$/i', $value, $matches)) {
							// matches[1] = modulename, [3]=comparison operator, [4] = version
							$modules = module_getinfo($matches[1]);
							if (isset($modules[$matches[1]])) {
								switch ($modules[$matches[1]]['status'] ) {
									case MODULE_STATUS_ENABLED:
										if (!empty($matches[4])) {
											// also doing version checking
											$operator = (!empty($matches[3]) ? $matches[3] : 'ge'); // default to >=
											if (version_compare($matches[4], $modules[$matches[1]]['dbversion'], $operator) ) {
												$errors[] = _module_comparison_error_message($matches[1].' module', $matches[4], $modules[$matches[1]]['dbversion'], $operator);
											}
										}
									break;
									case MODULE_STATUS_BROKEN:
										$errors[] = sprintf(_('Module %s is required, but yours is broken. You should reinstall '.
										                      'it and try again.'), $matches[1]);
									break;
									case MODULE_STATUS_DISABLED:
										$errors[] = sprintf(_('Module %s is required, but yours is disabled.'), $matches[1]);
									break;
									case MODULE_STATUS_NEEDUPGRADE:
										$errors[] = sprintf(_('Module %s is required, but yours is disabled because it needs to '.
										                      'be upgraded. Please upgrade %s first, and then try again.'), 
															$matches[1], $matches[1]);
									break;
									default:
									case MODULE_STATUS_NOTINSTALLED:
										$errors[] = sprintf(_('Module %s is required, yours is not installed.'), $matches[1]);
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
						
						if (preg_match('/^([a-z0-9_]+)(\s+(lt|le|gt|ge|==|=|eq|!=|ne)?\s*(\d(\.\d)*))?$/i', $value, $matches)) {
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
		return array(_("Module is already enabled"));
	}
	
	// doesn't make sense to skip this on $force - eg, we can't enable a non-installed or broken module
	if ($modules[$modulename]['status'] != MODULE_STATUS_DISABLED) {
		return array(_("Module cannot be enabled"));
	}
	
	if (!$force) { 
		if (($errors = module_checkdepends($modules[$modulename])) !== true) {
			return $errors;
		}
	}
	
	// disabled (but doesn't needupgrade or need install), and meets dependencies
	_module_setenabled($modulename, true);
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
function module_download($modulename, $force = false, $progress_callback = null) { // was fetchModule 
	global $amp_conf;
	
	// size of download blocks to fread()
	// basically, this controls how often progress_callback is called
	$download_chunk_size = 12*1024;
	
	// invoke progress callback
	if (function_exists($progress_callback)) {
		$progress_callback('getinfo', array('module'=>$modulename));
	}
			
	$res = module_getonlinexml($modulename);
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
			
			exec("tar zxf ".escapeshellarg($filename)." --directory=".escapeshellarg($amp_conf['AMPWEBROOT'].'/admin/modules/'), $output, $exitcode);
			if ($exitcode != 0) {
				return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['AMPWEBROOT'].'/admin/modules/'));
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
	
	if (isset($amp_conf['AMPMODULESVN'])) {
		$url = $amp_conf['AMPMODULESVN'].$res['location'];
		// echo "(From amportal.conf)"; // debug
	} else {
		$url = "http://mirror.freepbx.org/modules/".$res['location'];
		// echo "(From default)"; // debug
	}
	
	if (!($fp = @fopen($filename,"w"))) {
		return array(sprintf(_("Error opening %s for writing"), $filename));
	}
	
	$headers = get_headers($url);
	
	$totalread = 0;
	// invoke progress_callback
	if (function_exists($progress_callback)) {
		$progress_callback('downloading', array('module'=>$modulename, 'read'=>$totalread, 'total'=>$headers['content-length']));
	}
	
	if (!$dp = @fopen($url,'r')) {
		return array(sprintf(_("Error opening %s for reading"), $url));
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

	exec("tar zxf ".escapeshellarg($filename)." --directory=".escapeshellarg($amp_conf['AMPWEBROOT'].'/admin/modules/'), $output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['AMPWEBROOT'].'/admin/modules/'));
	}
	
	// invoke progress_callback
	if (function_exists($progress_callback)) {
		$progress_callback('done', array('module'=>$modulename));
	}

	return true;
}

/** Installs or upgrades a module from it's directory
 * Checks dependencies, and enables
 * @param string   The name of the module to install
 * @param bool     If true, skips status and dependency checks
 * @return mixed   True if succesful, array of error messages if not succesful
 */
function module_install($modulename, $force = false) {
	$modules = module_getinfo($modulename);
	if (($modules[$modulename]['status'] == MODULE_STATUS_NOTINSTALLED) || 
	    ($modules[$modulename]['status'] == MODULE_STATUS_NEEDUPGRADE)) {
	}
	global $db, $amp_conf;
	
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
		return array(_("This module is broken and cannot be installed. You should try to download it again."));
	}
	
	if (!$force) {
	
		if (!in_array($modules[$modulename]['status'], array(MODULE_STATUS_NOTINSTALLED, MODULE_STATUS_NEEDUPGRADE))) {
			return array(_("This module is already installed."));
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
		switch ($amp_conf["AMPDBENGINE"]) {
			case "sqlite":
				// to support sqlite2, we are not using autoincrement. we need to find the 
				// max ID available, and then insert it
				$sql = "SELECT max(id) FROM modules;";
				$results = $db->getRow($sql);
				$new_id = $results[0];
				$new_id ++;
				$sql = "INSERT INTO modules (id,modulename, version,enabled) values ('".$new_id."','".addslashes($modules[$modulename]['rawname'])."','".addslashes($modules[$modulename]['version'])."','0' );";
				break;
			
			default:
				$sql = "INSERT INTO modules (modulename, version, enabled) values ('".addslashes($modules[$modulename]['rawname'])."','".addslashes($modules[$modulename]['version'])."', 1);";
			break;
		}
	} else {
		// just need to update the version
		$sql = "UPDATE modules SET version='".addslashes($modules[$modulename]['version'])."' WHERE modulename = '".addslashes($modules[$modulename]['rawname'])."'";
	}
	
	// run query
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		return array(sprintf(_("Error updating database. Command was: %s; error was: %s "), $sql, $results->getMessage()));
	}
	
	// module is now installed & enabled
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
	
	$sql = "DELETE FROM modules WHERE modulename = '".addslashes($modulename)."'";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		return array(_("Error updating database: ").$results->getMessage());
	}
	
	if (!_module_runscripts($modulename, 'uninstall')) {
		return array(_("Failed to run un-installation scripts"));
	}
	
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
		die("Security problem, denying delete");
	}
	exec("rm -r ".escapeshellarg($dir),$output, $exitcode);
	if ($exitcode != 0) {
		return array(sprintf(_("Error deleting directory %s (code %d)"), $dir, $exitcode));
	}
	
	return true;
}


/** Internal use only */
function _module_setenabled($modulename, $enabled) {
	global $db;
	$sql = 'UPDATE modules SET enabled = '.($enabled ? '1' : '0').' WHERE modulename = "'.addslashes($modulename).'"';
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

function _module_readxml($modulename) {
	global $amp_conf;
	$dir = $amp_conf['AMPWEBROOT'].'/admin/modules/'.$modulename;
	if (is_dir($dir) && file_exists($dir.'/module.xml')) {
		$data = file_get_contents($dir.'/module.xml');
		//$parser = new xml2ModuleArray($data);
		//$xmlarray = $parser->parseModulesXML($data);
		$parser = new xml2Array($data);
		$xmlarray = $parser->data;
		if (isset($xmlarray['module'])) {
			// add a couple fields first
			$xmlarray['module']['displayname'] = $xmlarray['module']['name'];
			if (isset($xmlarray['module']['menuitems'])) {
				// set the legacy "items" array
				$xmlarray['module']['items'] = $xmlarray['module']['menuitems'];
				
				foreach ($xmlarray['module']['menuitems'] as $item=>$displayname) {
					$path = '/module/menuitems/'.$item;
					
					if (isset($parser->attributes[$path]['category'])) {
						$category = $parser->attributes[$path]['category'];
					} else if (isset($xmlarray['module']['category'])) {
						$category = $xmlarray['module']['category'];
					} else {
						$category = 'Basic';
					}
					
					if (isset($parser->attributes[$path.'/type'])) {
						$type = $parser->attributes[$path.'/type'];
					} else if (isset($xmlarray['module']['type'])) {
						$type = $xmlarray['module']['type'];
					} else {
						$type = 'setup';
					}
					
					$xmlarray['module']['itemsbycat'][$type][$category][$item] = $displayname;
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

	$sql = "SELECT version FROM modules WHERE modulename = '".addslashes($modname)."'";
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
			if ($db_engine  == "sqlite") {
				$sqlfilename = "install.sqlite";
			} else {
				$sqlfilename = "install.sql";
			}
			
			if (is_file($moduledir.'/'.$sqlfilename)) {
				execSQL($moduledir.'/'.$sqlfilename);
			}
			
			// then run .php scripts
			_modules_doinclude($moduledir.'/install.php', $modulename);
		break;
		case 'uninstall':
			// run uninstall .php scripts first
			_modules_doinclude($moduledir.'/uninstall.php', $modulename);
			
			if ($db_engine  == "sqlite") {
				$sqlfilename = "uninstall.sqlite";
			} else {
				$sqlfilename = "uninstall.sql";
			}
			
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
		include($filename);
	}
}
	

/*
function installModule($modname,$modversion) 
{
	global $db;
	global $amp_conf;
	
	switch ($amp_conf["AMPDBENGINE"])
	{
		case "sqlite":
			// to support sqlite2, we are not using autoincrement. we need to find the 
			// max ID available, and then insert it
			$sql = "SELECT max(id) FROM modules;";
			$results = $db->getRow($sql);
			$new_id = $results[0];
			$new_id ++;
			$sql = "INSERT INTO modules (id,modulename, version,enabled) values ('{$new_id}','{$modname}','{$modversion}','0' );";
			break;
		
		default:
			$sql = "INSERT INTO modules (modulename, version) values ('{$modname}','{$modversion}');";
		break;
	}

	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

function uninstallModule($modname) {
	global $db;
	$sql = "DELETE FROM modules WHERE modulename = '{$modname}'";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

/** downloads a module, and extracts it into the module dir
 * /
function module_fetch($name) { // was fetchModule
	global $amp_conf;
	$res = module_getonlinexml($modulename);
	if (!isset($res)) {
		echo "<div class=\"error\">"._("Unaware of module")." {$name}</div>";
		return false;
	}
	$file = basename($res['location']);
	$filename = $amp_conf['AMPWEBROOT']."/admin/modules/_cache/".$file;
	if(file_exists($filename)) {
		// We might already have it! Let's check the MD5.
		$filedata = "";
		$fh = @fopen($filename, "r");
		while (!feof($fh)) {
			$filedata .= fread($fh, 8192);
		}
		if (isset($res['md5sum']) && $res['md5sum'] == md5 ($filedata)) {
			// Note, if there's no MD5 information, it will redownload
			// every time. Otherwise theres no way to avoid a corrupt
			// download
			
			return verifyAndInstall($filename);
		} else {
			unlink($filename);
		}
	}
	if (isset($amp_conf['AMPMODULESVN'])) {
		$url = $amp_conf['AMPMODULESVN'].$res['location'];
		// echo "(From amportal.conf)"; // debug
	} else {
	$url = "http://mirror.freepbx.org/modules/".$res['location'];
		// echo "(From default)"; // debug
	}
	$fp = @fopen($filename,"w");
	$filedata = file_get_contents($url);
	fwrite($fp,$filedata);
	fclose($fp);
	if (is_readable($filename) !== TRUE ) {
		echo "<div class=\"error\">"._("Unable to save")." {$filename} - Check file/directory permissions</div>";
		return false;
	}
	// Check the MD5 info against what's in the module's XML
	if (!isset($res['md5sum']) || empty($res['md5sum'])) {
		echo "<div class=\"error\">"._("Unable to Locate Integrity information for")." {$filename} - "._("Continuing Anyway")."</div>";
	} elseif ($res['md5sum'] != md5 ($filedata)) {
		echo "<div class=\"error\">"._("File Integrity FAILED for")." {$filename} - "._("Aborting")."</div>";
		unlink($filename);
		return false;
	}
	// verifyAndInstall does the untar, and will do the signed-package check.
	return verifyAndInstall($filename);

}

function upgradeModule($module, $allmods = NULL) {
	if($allmods === NULL)
		$allmods = find_allmodules();
	// the install.php can set this to false if the upgrade fails.
	$success = true;
	if(is_file("modules/$module/install.php"))
		include "modules/$module/install.php";
	if ($success) {
		sql('UPDATE modules SET version = "'.$allmods[$module]['version'].'" WHERE modulename = "'.$module.'"');
		needreload();
	}
}

function rmModule($module) {
	global $amp_conf;
	if($module != 'core') {
		if (is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$module) && strstr($module, '.') === FALSE ) {
			exec('/bin/rm -rf '.$amp_conf['AMPWEBROOT'].'/admin/modules/'.$module);
		}
	} else {
		echo "<script language=\"Javascript\">alert('"._("You cannot delete the Core module")."');</script>";
	}
}

*/


function freepbx_log($section, $level, $message) {
        global $db;
        global $debug; // This is used by retrieve_conf
        global $amp_conf;
        
        if (!isset($amp_conf['AMPDISABLELOG']) || ($amp_conf['AMPDISABLELOG'] != 'true')) {
            $sth = $db->prepare("INSERT INTO freepbx_log (time, section, level, message) VALUES (NOW(),?,?,?)");
            $db->execute($sth, array($section, $level, $message));
        }
        if (isset($debug) && ($debug != false))
                print "[DEBUG-$section] ($level) $message\n";
}
?>

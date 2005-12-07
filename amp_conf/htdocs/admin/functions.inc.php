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


function parse_amportal_conf($filename) {
	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\%-]*)\"?\s*([;#].*)?/",$line,$matches)) {
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
	$low = $_SESSION["AMP_user"]->_extension_low;
	$high = $_SESSION["AMP_user"]->_extension_high;
	if ((($extension >= $low) && ($extension <= $high)) || (empty($low) && empty($high)))
		return true;
	else
		return false;
}


/* look for all modules in modules dir.
** returns array:
** array['module']['displayName']
** array['module']['version']
** array['module']['status']
** array['module']['items'][array(items)]
*/

function find_allmodules() {
	global $db;
	global $amp_conf;

	$dir = opendir($amp_conf['AMPWEBROOT'].'/admin/modules');
	//loop through each module directory, ensure there is a module.ini file
	while ($file = readdir($dir)) {
		if (($file != ".") && ($file != "..") && ($file != "CVS") && is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file) && is_file($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file.'/module.ini')) {
			//open module.ini and read contents
			$inifile = file($amp_conf['AMPWEBROOT'].'/admin/modules/'.$file.'/module.ini');
			foreach ($inifile as $line) {
				// parse the module display name and version from module.ini
				if (preg_match("/^\s*([a-zA-Z0-9]+)=([a-zA-Z0-9 .&-]+)\s*$/",$line,$matches)) { 
					if (trim($matches[1]) == "name")
						$mod[ $file ]['displayName'] = $matches[2];
					else if (trim($matches[1]) == "version")
						$mod[ $file ]['version'] = $matches[2];
					else 
						$mod[ $file ]['items'][ $matches[1] ] = $matches[2];
				}
				// determine details about this module from database
				// modulename should match the directory name
				$sql = "SELECT * FROM modules WHERE modulename = '".$file."'";
				$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
				if(DB::IsError($results)) {
					die($results->getMessage());
				}
				//set status key based on results (0=not installed, 1=disabled, 2=enabled)
				if ($results) {
					if ($results['enabled'] != 0)
						$mod[ $file ]["status"] = 2;
					else
						$mod[ $file ]["status"] = 1;
				} else {
					$mod[ $file ]["status"] = 0;
				}
			}
		}
	}
	return $mod;
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
function drawListMenu($results, $skip, $dispnum, $extdisplay, $description) {
	$perpage=20;
	
	$skipped = 0;
	$index = 0;
	if ($skip == "") $skip = 0;
 	echo "<li><a id=\"".($extdisplay=='' ? 'current':'')."\" href=\"config.php?display=".$dispnum."\">"._("Add")." ".$description."</a></li>";

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
	 
	  echo "<li><a id=\"".($extdisplay==$result[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]} <{$result[0]}></a></li>";
	 
	 }
	}
	 
	 if ($index >= $perpage) {
	 
	 print "<li><center>";
	 
	 }
	 
	 if ($skip) {
	 
		 $prevskip= $skip - $perpage;
		 if ($prevskip<0) $prevskip= 0;
		 $prevtag_pre= "<a href='?display=".$dispnum."&skip=$prevskip'>[PREVIOUS]</a>";
		 print "$prevtag_pre";
		 }
		 if ($shownext) {
	 
			 $nextskip= $skip + $index;
			 if ($prevtag_pre) $prevtag .= " | ";
			 print "$prevtag <a href='?display=".$dispnum."&skip=$nextskip'>[NEXT]</a>";
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

function getVoicemail() {
	$vmconf = null;
	$section = null;
	
	// yes, this is hardcoded.. is this a bad thing?
	parse_voicemailconf("/etc/asterisk/voicemail.conf", $vmconf, $section);
	
	return $vmconf;
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
	
	if (file_exists($filename)) {
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
		
		if ($iteration == 0) {
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
}

function saveVoicemail($vmconf) {
	// yes, this is hardcoded.. is this a bad thing?
	write_voicemailconf("/etc/asterisk/voicemail.conf", $vmconf, $section);
}


// $goto is the current goto destination setting
// $i is the destination set number (used when drawing multiple destination sets in a single form ie: digital receptionist)
function drawselects($goto,$i) {  
	
	/* --- MODULES BEGIN --- */
	global $active_modules;
	
	$selectHtml .= '<tr><td colspan=2><input type="hidden" name="goto'.$i.'" value="">';
	
	//check for module-specific destination functions
	foreach ($active_modules as $mod) {
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
			}
			
			$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="'.$mod.'" onclick="javascript:this.form.goto'.$i.'.value=\''.$mod.'\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) this.form.goto'.$i.'.value=\''.$mod.'\';" '.($checked? 'CHECKED=CHECKED' : '').' /> '._($mod).': ';
			$selectHtml .=	'<select name="'.$mod.$i.'"/>';
			$selectHtml .= $options;	
			$selectHtml .=	'</select><br>';
		}
	}
	/* --- MODULES END --- */
	
	//display a custom goto field
	$selectHtml .= '<input type="radio" name="goto_indicate'.$i.'" value="custom" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'custom\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'custom\';" '.(strpos($goto,'custom') === false ? '' : 'CHECKED=CHECKED').' />';
	$selectHtml .= '<a href="#" class="info"> '._("Custom App<span><br>ADVANCED USERS ONLY<br><br>Uses Goto() to send caller to a custom context.<br><br>The context name <b>MUST</b> contain the word 'custom' and should be in the format custom-context , extension , priority. Example entry:<br><br><b>custom-myapp,s,1</b><br><br>The <b>[custom-myapp]</b> context would need to be created and included in extensions_custom.conf</span>").'</a>:';
	$selectHtml .= '<input type="text" size="15" name="custom_args'.$i.'" value="'.(strpos($goto,'custom') === false ? '' : $goto).'" />';
	
	//close off our row
	$selectHtml .= '</td></tr>';
	
	return $selectHtml;
}

?>
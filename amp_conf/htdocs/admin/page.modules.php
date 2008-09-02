<?php /* $Id$ */

/** Controls if online module and install/uninstall options are available.
 * This is meant for when using external packaging systems (eg, deb or rpm) to manage
 * modules. Package mantainers should set AMPEXTERNPACKAGES to true in /etc/amportal.conf.
 * Optionally, the other way is to remove the below lines, and instead just define 
 * EXTERNAL_PACKAGE_MANAGEMENT as 1. This prevents changing the setting from amportal.conf.
 */
if (!isset($amp_conf['AMPEXTERNPACKAGES']) || ($amp_conf['AMPEXTERNPACKAGES'] != 'true')) {
	define('EXTERNAL_PACKAGE_MANAGEMENT', 0);
} else {
	define('EXTERNAL_PACKAGE_MANAGEMENT', 1);
}


$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
// can't go online if external management is on
$online = (isset($_REQUEST['online']) && !EXTERNAL_PACKAGE_MANAGEMENT) ? $_REQUEST['online'] : false;
// fix php errors from undefined variable. Not sure if we can just change the reference below to use
// online since it changes values so just setting to what we decided it is here.
$_REQUEST['online'] = $online ? 1 : 0;
$moduleaction = isset($_REQUEST['moduleaction'])?$_REQUEST['moduleaction']:false;
/*
	moduleaction is an array with the key as the module name, and possible values:
	
	downloadinstall - download and install (used when a module is not locally installed)
	upgrade - download and install (used when a module is locally installed)
	install - install/upgrade locally available module
	enable - enable local module
	disable - disable local module
	uninstall - uninstall local module
*/

$freepbx_version = get_framework_version();
$freepbx_version = $freepbx_version ? $freepbx_version : getversion();
$freepbx_help_url = "http://www.freepbx.org/freepbx-help-system?freepbx_version=".urlencode($freepbx_version);

function pageReload(){
return "";
	//return "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=".rand()."'</script>";
}


if (!$quietmode) {
	?>
	<script type="text/javascript" src="common/tabber-minimized.js"></script>
	<script type="text/javascript">
	function toggleInfoPane(pane) {
		var style = document.getElementById(pane).style;
		if (style.display == 'none' || style.display == '') {
			style.display = 'block';
		} else {
			style.display = 'none';
		}
	}

	function check_upgrade_all() {
		var re = /^moduleaction\[([a-z0-9_\-]+)\]$/;
		for(i=0; i<document.modulesGUI.elements.length; i++) {
			if (document.modulesGUI.elements[i].value == 'upgrade') {
				if (match = document.modulesGUI.elements[i].name.match(re)) {
					// check the box
					document.modulesGUI.elements[i].checked = true;
					// expand info pane
					document.getElementById('infopane_'+match[1]).style.display = 'block';
				}
			}
		}
	}

	function check_download_all() {
		var re = /^moduleaction\[([a-z0-9_\-]+)\]$/;
		for(i=0; i<document.modulesGUI.elements.length; i++) {
			if (document.modulesGUI.elements[i].value == 'downloadinstall') {
				if (match = document.modulesGUI.elements[i].name.match(re)) {
					// check the box
					document.modulesGUI.elements[i].checked = true;
					// expand info pane
					document.getElementById('infopane_'+match[1]).style.display = 'block';
				}
			}
		}
	}


	function showhide_upgrades() {
		var upgradesonly = document.getElementById('show_upgradable_only').checked;
		
		var module_re = /^module_([a-z0-9_]+)$/;   // regex to match a module element id
		var cat_re = /^category_([a-zA-Z0-9_]+)$/; // regex to match a category element id

		var elements = document.getElementById('modulelist').getElementsByTagName('li');

		// loop through all modules, check if there is an upgrade_<module> radio box 
		for(i=0; i<elements.length; i++) {
			if (match = elements[i].id.match(module_re)) {
				if (!document.getElementById('upgrade_'+match[1])) {
					// not upgradable
					document.getElementById('module_'+match[1]).style.display = upgradesonly ? 'none' : 'block';
				}
			}
		}
		
		
		
		// hide category headings that don't have any visible modules
		
		var elements = document.getElementById('modulelist').getElementsByTagName('div');
		// loop through category items
		for(i=0; i<elements.length; i++) {
			if (elements[i].id.match(cat_re)) {
				var subelements = elements[i].getElementsByTagName('li');
				var display = false;
				for(j=0; j<subelements.length; j++) {
					// loop through children <li>'s, find names that are module element id's 
					if (subelements[j].id.match(module_re) && subelements[j].style.display != 'none') {
						// if at least one is visible, we're displaying this element
						display = true;
						break; // no need to go further
					}
				}
				
				document.getElementById(elements[i].id).style.display = display ? 'block' : 'none';
			}
		}
		
	}

	function process_module_actions(actions) {
		freepbx_modal_show('moduleBox');
		urlStr = "config.php?type=tool&amp;display=modules&amp;extdisplay=process&amp;quietmode=1";
		for (var i in actions) {
			urlStr += "&amp;moduleaction["+i+"]="+actions[i];
		}
		$('#moduleBox').html('<iframe src="'+urlStr+'"></iframe>');
	}
	function close_module_actions(goback) {
		//freepbx_modal_close('moduleBox');
		freepbx_modal_hide('moduleBox');
		if (goback) {
			location.href = 'config.php?display=modules&amp;type=tool&amp;online=<?php echo ($_REQUEST['online']?1:0); ?>';
		}
	}

	</script>
	<?php

	echo "<h2>" . _("Module Administration") . "</h2>";
} else {
	// $quietmode==true
	?>
	<html><head>
		<link href="common/mainstyle.css" rel="stylesheet" type="text/css" />
	</head><body>
	<?php
}


$modules_local = module_getinfo(false,false,true);

if ($online) {
	$modules_online = module_getonlinexml(false);
	
	// $module_getonlinexml_error is a global set by module_getonlinexml()
	if ($module_getonlinexml_error) {
		echo "<div class=\"warning\"><p>".sprintf(_("Warning: Cannot connect to online repository (%s). Online modules are not available."), "mirror.freepbx.org")."</p></div><br />";
		$online = false;
		unset($modules_online);
	} else if (!is_array($modules_online)) {
		echo "<div class=\"warning\"><p>".sprintf(_("Warning: Error retrieving updates from online repository (%s). Online modules are not available."), "mirror.freepbx.org")."</p></div><br />";
		$online = false;
		unset($modules_online);
	} else {
		// combine online and local modules
		$modules = $modules_online;
		foreach (array_keys($modules) as $name) {
			if (isset($modules_local[$name])) {
				// combine in any other values in _local that aren't in _online
				$modules[$name] += $modules_local[$name];
				
				// explicitly override these values with the _local ones
				// - should never come from _online anyways, but this is just to be sure
				$modules[$name]['status'] = $modules_local[$name]['status'];
				$modules[$name]['dbversion'] = isset($modules_local[$name]['dbversion'])?$modules_local[$name]['dbversion']:'';
			} else {
				// not local, so it's not installed
				$modules[$name]['status'] = MODULE_STATUS_NOTINSTALLED;
			}
		}
		// add any remaining local-only modules
		$modules += $modules_local;
		
		// use online categories
		foreach (array_keys($modules) as $modname) {
			if (isset($modules_online[$modname]['category'])) {
				$modules[$modname]['category'] = $modules_online[$modname]['category'];
			}
		}
	}
}

if (!isset($modules)) {
	$modules = & $modules_local;
}


function category_sort_callback($a, $b) {
	// sort by category..
	$catcomp = strcmp($a['category'], $b['category']);
	if ($catcomp == 0) {
		// .. then by name
		return strcmp($a['name'], $b['name']);
	} else
		return $catcomp;
}

/** preps a string to use as an HTML id element
 */
function prep_id($name) {
	return preg_replace("/[^a-z0-9-]/i", "_", $name);
}

/** Progress callback used by module_download() 
 */
function download_progress($action, $params) {
	switch ($action) {
		case 'untar':
			echo '<script type="text/javascript">
			        var txt = document.createTextNode("'._('Untarring..').'");
			        var br = document.createElement(\'br\');
			        document.getElementById(\'moduleprogress\').appendChild(br); 
					document.getElementById(\'moduleprogress\').appendChild(txt); 
			     </script>';
			flush();
		break;
		case 'downloading':
			if ($params['total']==0) {
				$progress = $params['read'].' of '.$params['total'].' (0%)';
			} else {
				$progress = $params['read'].' of '.$params['total'].' ('.round($params['read']/$params['total']*100).'%)';
			}
			echo '<script type="text/javascript">
			        document.getElementById(\'downloadprogress_'.$params['module'].'\').innerHTML = \''.$progress.'\';
			      </script>';
			flush();
		break;
		case 'done';
			echo '<script type="text/javascript">
			        var txt = document.createTextNode("'._('Done.').'");
					var br = document.createElement(\'br\');
			        document.getElementById(\'moduleprogress\').appendChild(txt); 
					document.getElementById(\'moduleprogress\').appendChild(br); 
			     </script>';
			flush();
		break;
	}
}

/* enable_option($module_name, $option)
   This function will return false if the particular option, which is a module xml tag,
	 is set to 'no'. It also provides for some hardcoded overrides on critical modules to
	 keep people from editing the xml themselves and then breaking their the system.
*/
function enable_option($module_name, $option) {
	global $modules;

	$enable=true;
	$override = array('core'      => array('candisable' => 'no',
	                                       'canuninstall' => 'no',
					                              ),
	                  'framework' => array('candisable' => 'no',
	                                       'canuninstall' => 'no',
																			  ),
	                 );

	if (isset($modules[$module_name][$option]) && strtolower(trim($modules[$module_name][$option])) == 'no') {
		$enable=false;
	}
	if (isset($override[$module_name][$option]) && strtolower(trim($override[$module_name][$option])) == 'no') {
		$enable=false;
	}

	return $enable;
}

/* Replace '#nnn', 'bug nnn', 'ticket nnn' type ticket numbers in changelog with a link, taken from Greg's drupal filter
*/
function trac_replace_ticket($match) {
  $baseurl = 'http://freepbx.org/trac/ticket/';
  return '<a target="tractickets" href="'.$baseurl.$match[1].'" title="ticket '.$match[1].'">'.$match[0].'</a>';
}

/* Replace 'rnnn' changeset references to a link, taken from Greg's drupal filter
*/
function trac_replace_changeset($match) {
  $baseurl = 'http://freepbx.org/trac/changeset/';
  return '<a target="tractickets" href="'.$baseurl.$match[1].'" title="changeset '.$match[1].'">'.$match[0].'</a>';
}                                                                                                                                              

//--------------------------------------------------------------------------------------------------------
switch ($extdisplay) {  // process, confirm, or nothing
	case 'process':
		echo "<div id=\"moduleBoxContents\">";
		echo "<h4>"._("Please wait while module actions are performed")."</h4>\n";
		
		echo "<div id=\"moduleprogress\">";

		// stop output buffering, and send output
		@ ob_end_flush();

		flush();
		foreach ($moduleaction as $modulename => $action) {	
			$didsomething = true; // set to false in default clause of switch() below..
			
			switch ($action) {
				case 'upgrade':
				case 'downloadinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo sprintf(_('Downloading %s'), $modulename).' <span id="downloadprogress_'.$modulename.'"></span>';
						if (is_array($errors = module_download($modulename, false, 'download_progress'))) {
							echo '<span class="error">'.sprintf(_("Error(s) downloading %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
						
							if (is_array($errors = module_install($modulename))) {
								echo '<span class="error">'.sprintf(_("Error(s) installing %s"),$modulename).': ';
								echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
								echo '</span>';
							} else {
								echo '<span class="success">'.sprintf(_("%s installed successfully"),$modulename).'</span>';
							}
						}
					}
				break;
				case 'install':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_install($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) installing %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s installed successfully"),$modulename).'</span>';
						}
					}
				break;
				case 'enable':
					if (is_array($errors = module_enable($modulename))) {
						echo '<span class="error">'.sprintf(_("Error(s) enabling %s"),$modulename).': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.sprintf(_("%s enabled successfully"),$modulename).'</span>';
					}
				break;
				case 'disable':
					if (is_array($errors = module_disable($modulename))) {
						echo '<span class="error">'.sprintf(_("Error(s) disabling %s"),$modulename).': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.sprintf(_("%s disabled successfully"),$modulename).'</span>';
					}
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_uninstall($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) uninstalling %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s uninstalled successfully"),$modulename).'</span>';
						}
					}
				break;
				default:
					// just so we don't send an <hr> and flush()
					$didsomething = false;
			}
			
			if ($didsomething) {
				echo "<hr /><br />";
				flush();
			}
		}
		echo "</div>";
		if ($quietmode) {
			echo "\t<a href=\"#\" onclick=\"parent.close_module_actions(true);\" />"._("Return")."</a>";
		} else {
			echo "\t<input type=\"button\" value=\""._("Return")."\" onclick=\"location.href = 'config.php?display=modules&amp;type=tool&amp;online=".($_REQUEST['online']?1:0)."';\" />";
		echo "</div>";
		}
	break;
	case 'confirm':
		ksort($moduleaction);
		
		echo "<form name=\"modulesGUI\" action=\"config.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
		echo "<input type=\"hidden\" name=\"online\" value=\"".$online."\" />";
		echo "<input type=\"hidden\" name=\"extdisplay\" value=\"process\" />";
		
		echo "\t<script type=\"text/javascript\"> var moduleActions = new Array(); </script>\n";
		
		$actionstext = array();
		$errorstext = array();
		foreach ($moduleaction as $module => $action) {	
			$text = false;
			$skipaction = false;

			// make sure name is set. This is a problem for broken modules
			if (!isset($modules[$module]['name'])) {
				$modules[$module]['name'] = $module;
			}
			
			switch ($action) {
				case 'upgrade':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_checkdepends($modules_online[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be upgraded: %s Please try again after the dependencies have been installed."),  
							                        $modules[$module]['name'],
							                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							$actionstext[] = sprintf(_("%s %s will be upgraded to online version %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules_online[$module]['version']);
						}
					}
				break;
				case 'downloadinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_checkdepends($modules_online[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be installed: %s Please try again after the dependencies have been installed."),  
							                        $modules[$module]['name'],
							                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							$actionstext[] =  sprintf(_("%s %s will be downloaded and installed"), $modules[$module]['name'], $modules_online[$module]['version']);
						}
					}
				break;
				case 'install':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_checkdepends($modules[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf((($modules[$module]['status'] == MODULE_STATUS_NEEDUPGRADE) ?  _("%s cannot be upgraded: %s Please try again after the dependencies have been installed.") : _("%s cannot be installed: %s Please try again after the dependencies have been installed.") ),  
							                        $modules[$module]['name'],
							                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							if ($modules[$module]['status'] == MODULE_STATUS_NEEDUPGRADE) {
								$actionstext[] =  sprintf(_("%s %s will be upgraded to %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules[$module]['version']);
							} else {
								$actionstext[] =  sprintf(_("%s %s will be installed and enabled"), $modules[$module]['name'], $modules[$module]['version']);
							}
						}
					}
				break;
				case 'enable':
					if (is_array($errors = module_checkdepends($modules[$module]))) {
						$skipaction = true;
						$errorstext[] = sprintf(_("%s cannot be enabled: %s Please try again after the dependencies have been installed."),  
						                        $modules[$module]['name'],
						                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
					} else {
						$actionstext[] =  sprintf(_("%s %s will be enabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
					}
				break;
				case 'disable':
					if (is_array($errors = module_reversedepends($modules[$module]))) {
						$skipaction = true;
						$errorstext[] = sprintf(_("%s cannot be disabled because the following modules depend on it: %s Please disable those modules first then try again."),  
						                        $modules[$module]['name'],
						                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
					} else {
						$actionstext[] =  sprintf(_("%s %s will be disabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
					}
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_reversedepends($modules[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be uninstalled because the following modules depend on it: %s Please disable those modules first then try again."),  
							                        $modules[$module]['name'],
							                        '<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							$actionstext[] =  sprintf(_("%s %s will be uninstalled"), $modules[$module]['name'], $modules[$module]['dbversion']);
						}
					}
				break;
			}

			// If error above we skip this action so we can proceed with the others
			//
			if (!$skipaction) { //TODO
				echo "\t<script type=\"text/javascript\"> moduleActions['".$module."'] = '".$action."'; </script>\n";
			}
		}
		
		// Write out the erros, if there are additional actions that can be accomplished list those next with the choice to
		// process which will ignore the ones with errors but process the rest.
		//
		if (count($errorstext) > 0) {
			echo "<h4>"._("Errors with selection:")."</h4>\n";
			echo "<ul>\n";
			foreach ($errorstext as $text) {
				echo "\t<li>".$text."</li>\n";
			}
			echo "</ul>";
		} 
		if (count($actionstext) > 0) {
			if (count($errorstext) > 0) {
				echo "<h4>"._("You may confirm the remaining selection and then try the again for the listed issues once the required dependencies have been met:")."</h4>\n";
			} else {
				echo "<h4>"._("Please confirm the following actions:")."</h4>\n";
			}
			echo "<ul>\n";
			foreach ($actionstext as $text) {
				echo "\t<li>".$text."</li>\n";
			}
			echo "</ul>";
			echo "\t<input type=\"button\" value=\"Confirm\" name=\"process\" onclick=\"process_module_actions(moduleActions);\" />";
		} else {
			echo "<h4>"._("No actions to perform")."</h4>\n";
			echo "<p>"._("Please select at least one action to perform by clicking on the module, and selecting an action on the \"Action\" tab.")."</p>";
		}
		echo "\t<input type=\"button\" value=\""._("Cancel")."\" onclick=\"location.href = 'config.php?display=modules&amp;type=tool&amp;online=".($_REQUEST['online']?1:0)."';\" />";
		echo "</form>";
		
	break;
	case 'upload':
		// display links
		echo "<a href='config.php?display=modules&amp;type=tool'>"._("Manage local modules")."</a>\n";
		if (!EXTERNAL_PACKAGE_MANAGEMENT) {
			echo " | <a class='info' href='config.php?display=modules&amp;type=tool&amp;online=1'>"._("Check for updates online")."<span>"._("Checking for updates will transmit your FreePBX and Asterisk version numbers along with a unique but random identifier. This is used to provide proper update information and track version usage to focus development and maintenance efforts. No private information is transmitted.")."</span></a>\n";
		}
				
		if (isset($_FILES['uploadmod']) && !empty($_FILES['uploadmod']['name'])) {
			// display upload link, only if they did upload something
			echo " | <a href='config.php?display=modules&amp;type=tool&amp;extdisplay=upload'>"._("Upload module")."</a><br />\n";
			
			$res = module_handleupload($_FILES['uploadmod']);
			if (is_array($res)) {
				
				echo '<div class="error"><p>';
				echo sprintf(_('The following error(s) occurred processing the uploaded file: %s'), 
				     '<ul><li>'.implode('</li><li>',$res).'</li></ul>');
				echo sprintf(_('You should fix the problem or select another file and %s.'), 
				     "<a href='config.php?display=modules&amp;type=tool'>"._("try again")."</a>");
				echo "</p></div>\n";
			} else {
				
				echo "<p>".sprintf(_("Module uploaded successfully. You need to enable the module using %s to make it available."),
				     "<a href='config.php?display=modules&amp;type=tool'>"._("local module administration")."</a>")
					 ."</p>\n";
			}
			
		} else {
			echo "<p>"._('You can upload a tar gzip file containing a FreePBX module from your local system. If a module with the same name already exists, it will be overwritten.')."</p>\n";
		
			echo "<form name=\"modulesGUI-upload\" action=\"config.php\" method=\"post\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
			echo "<input type=\"hidden\" name=\"extdisplay\" value=\"upload\" />";
			
			echo "<input type=\"file\" name=\"uploadmod\" /> ";
			echo "&nbsp;&nbsp; <input type=\"submit\" value=\"Upload\" />";
			echo "</form>";
		}
		
	break;
	case 'online':
	default:
		
		uasort($modules, 'category_sort_callback');
		
		if ($online) {
			//echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=local'>"._("Terminate Connection to Online Module Repository")."</a><br />\n";
			//echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=online&amp;refresh=true'>"._("Force Refresh of Local Module Cache")."</a>\n";
			
			// Check for annoucements such as security advisories, required updates, etc.
			//
			$announcements = module_get_annoucements();
			if (isset($announcements) && !empty($announcements)) {
				echo "<div class='announcements'>$announcements</div>";
			}
			
			if (!EXTERNAL_PACKAGE_MANAGEMENT) {
				echo "<a href='config.php?display=modules&amp;type=tool&amp;online=0'>"._("Manage local modules")."</a>\n";
				echo "<input type=\"checkbox\" id=\"show_upgradable_only\" onclick=\"showhide_upgrades();\" /><label for=\"show_upgradable_only\">"._("Show only upgradable")."</label>";
			}
		} else {
			if (!EXTERNAL_PACKAGE_MANAGEMENT) {
				echo "<a class='info' href='config.php?display=modules&amp;type=tool&amp;online=1'>"._("Check for updates online")."<span>"._("Checking for updates will transmit your FreePBX and Asterisk version numbers along with a unique but random identifier. This is used to provide proper update information and track version usage to focus development and maintenance efforts. No private information is transmitted.")."</span></a>\n";
			}
			echo " | <a href='config.php?display=modules&amp;type=tool&amp;extdisplay=upload'>"._("Upload module")."</a><br />\n";
		}

		echo "<form name=\"modulesGUI\" action=\"config.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
		echo "<input type=\"hidden\" name=\"online\" value=\"".$online."\" />";
		echo "<input type=\"hidden\" name=\"extdisplay\" value=\"confirm\" />";
		
		echo "<div class=\"modulebuttons\">";
		if ($online) {
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_download_all();\">"._("Download all")."</a>";
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_upgrade_all();\">"._("Upgrade all")."</a>";
		}
		echo "\t<input type=\"reset\" value=\""._("Reset")."\" />";
		echo "\t<input type=\"submit\" value=\""._("Process")."\" name=\"process\" />";
		echo "</div>";

		echo "<div id=\"modulelist\">\n";

		echo "\t<div id=\"modulelist-header\">";
		echo "\t\t<span class=\"modulename\">"._("Module")."</span>\n";
		echo "\t\t<span class=\"moduletype\">"._("Type")."</span>\n";
		echo "\t\t<span class=\"moduleversion\">"._("Version")."</span>\n";
		echo "\t\t<span class=\"clear\">&nbsp;</span>\n";
		echo "\t</div>";

		$category = false;
		$numdisplayed = 0;
		$fd = $amp_conf['ASTETCDIR'].'/freepbx_module_admin.conf';
		if (file_exists($fd)) {
			$module_filter = parse_ini_file($fd);
		} else {
			$module_filter = array();
		}
		foreach (array_keys($modules) as $name) {
			if (isset($module_filter[$name]) && strtolower(trim($module_filter[$name])) == 'hidden') {
				continue;
			}
			$numdisplayed++;
			
			if ($category !== $modules[$name]['category']) {
				// show category header
				
				if ($category !== false) {
					// not the first one, so end the previous blocks
					echo "\t</ul></div>\n";
				}
				
				// start a new category header, and associated html blocks
				$category = $modules[$name]['category'];
				echo "\t<div class=\"category\" id=\"category_".prep_id($category)."\"><h3>"._($category)."</h3>\n";
				echo "\t<ul>";
			}
			
			echo "\t\t<li id=\"module_".prep_id($name)."\">\n";
			
			// ---- module header 
			echo "\t\t<div class=\"moduleheader\" onclick=\"toggleInfoPane('infopane_".prep_id($name)."');\" >\n";
			echo "\t\t\t<span class=\"modulename\"><a href=\"javascript:void(null);\">".(!empty($modules[$name]['name']) ? $modules[$name]['name'] : $name)."</a></span>\n";
			echo "\t\t\t<span class=\"moduletype\">".$modules[$name]['type']."</span>\n";
			echo "\t\t\t<span class=\"moduleversion\">".(isset($modules[$name]['dbversion'])?$modules[$name]['dbversion']:'&nbsp;')."</span>\n";
			
			echo "\t\t\t<span class=\"modulestatus\">";
			switch ($modules[$name]['status']) {
				case MODULE_STATUS_NOTINSTALLED:
					if (isset($modules_local[$name])) {
						echo '<span class="notinstalled">'._('Not Installed (Locally available)').'</span>';
					} else {
						echo '<span class="notinstalled">'.sprintf(_('Not Installed (Available online: %s)'), $modules_online[$name]['version']).'</span>';
					}
				break;
				case MODULE_STATUS_DISABLED:
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare_freepbx($modules_local[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							echo '<span class="alert">'.sprintf(_('Disabled; Online upgrade available (%s)'),$modules_online[$name]['version']).'</span>';
						} else if ($vercomp > 0) {
							echo sprintf(_('Disabled; Newer than online version (%s)'), $modules_online[$name]['version']);
						} else {
							echo _('Disabled; up to date');
						}
					} else {
						echo 'Disabled';
					}
				break;
				case MODULE_STATUS_NEEDUPGRADE:
					echo '<span class="alert">'.sprintf(_('Disabled; Pending upgrade to %s'),$modules_local[$name]['version']).'</span>';
				break;
				case MODULE_STATUS_BROKEN:
					echo '<span class="alert">'._('Broken').'</span>';
				break;
				default:
					// check for online upgrade
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare_freepbx($modules_local[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							echo '<span class="alert">'.sprintf(_('Online upgrade available (%s)'), $modules_online[$name]['version']).'</span>';
						} else if ($vercomp > 0) {
							echo sprintf(_('Newer than online version (%s)'),$modules_online[$name]['version']);
						} else {
							echo _('Enabled and up to date');
						}
					} else if (isset($modules_online)) {
						// we're connected to online, but didn't find this module
						echo _('Enabled; Not available online');
					} else {
						echo _('Enabled');
					}
				break;
			}
			echo "</span>\n";
			
			
			echo "\t\t\t<span class=\"clear\">&nbsp;</span>\n";
			echo "\t\t</div>\n";
			
			// ---- end of module header
			
			
			// ---- drop-down tab box thingy:
			
			echo "\t\t<div class=\"moduleinfopane\" id=\"infopane_".prep_id($name)."\" >\n";
			echo "\t\t\t<div class=\"tabber\">\n";
			
			if (isset($modules_online[$name]['attention']) && !empty($modules_online[$name]['attention'])) {
				echo "\t\t\t\t<div class=\"tabbertab\" title=\""._("Attention")."\">\n";
				echo nl2br($modules_online[$name]['attention']);
				echo "\t\t\t\t</div>\n";
			}
			
			echo "\t\t\t\t<div class=\"tabbertab actiontab\" title=\""._("Action")."\">\n";
			
			echo '<input type="radio" checked="CHECKED" id="noaction_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="0" /> '.
				 '<label for="noaction_'.prep_id($name).'">'._('No Action').'</label> <br />';	
			switch ($modules[$name]['status']) {
			
				case MODULE_STATUS_NOTINSTALLED:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (isset($modules_local[$name])) {
							echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
								 '<label for="install_'.prep_id($name).'">'._('Install').'</label> <br />';
						} else {
							echo '<input type="radio" id="upgrade_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="downloadinstall" /> '.
								 '<label for="upgrade_'.prep_id($name).'">'._('Download and Install').'</label> <br />';
						}
					}
				break;
				case MODULE_STATUS_DISABLED:
					echo '<input type="radio" id="enable_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="enable" /> '.
						 '<label for="enable_'.prep_id($name).'">'._('Enable').'</label> <br />';
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">'._('Uninstall').'</label> <br />';
						if (isset($modules_online[$name]['version'])) {
							$vercomp = version_compare_freepbx($modules_local[$name]['version'], $modules_online[$name]['version']);
							if ($vercomp < 0) {
								echo '<input type="radio" id="upgrade_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="upgrade" /> '.
									 '<label for="upgrade_'.prep_id($name).'">'.sprintf(_('Download and Upgrade to %s, and Enable'),$modules_online[$name]['version']).'</label> <br />';
							}
						}
					}
				break;
				case MODULE_STATUS_NEEDUPGRADE:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
							 '<label for="install_'.prep_id($name).'">'.sprintf(_('Upgrade to %s and Enable'),$modules_local[$name]['version']).'</label> <br />';
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">'._('Uninstall').'</label> <br />';
					}
				break;
				case MODULE_STATUS_BROKEN:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
							 '<label for="install_'.prep_id($name).'">'._('Install').'</label> <br />';
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">'._('Uninstall').'</label> <br />';
					}
				break;
				default:
					// check for online upgrade
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare_freepbx($modules_local[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							if (!EXTERNAL_PACKAGE_MANAGEMENT) {
								echo '<input type="radio" id="upgrade_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="upgrade" /> '.
									 '<label for="upgrade_'.prep_id($name).'">'.sprintf(_('Download and Upgrade to %s'), $modules_online[$name]['version']).'</label> <br />';
							}
						}
					}
					if (enable_option($name,'candisable')) {
						echo '<input type="radio" id="disable_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="disable" /> '.
						   '<label for="disable_'.prep_id($name).'">'._('Disable').'</label> <br />';
					}
					if (!EXTERNAL_PACKAGE_MANAGEMENT && enable_option($name,'canuninstall')) {
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">'._('Uninstall').'</label> <br />';
					}
				break;
			}
			echo "\t\t\t\t</div>\n";
			
			echo "\t\t\t\t<div class=\"tabbertab\" title=\""._("Description")."\">\n";
			if (isset($modules[$name]['description']) && !empty($modules[$name]['description'])) {
				echo "<h5>".sprintf(_("Description for version %s"),$modules[$name]['version'])."</h5>";
				echo nl2br($modules[$name]['description']);
			} else {
				echo _("No description is available.");
			}
			if (isset($modules[$name]['info']) && !empty($modules[$name]['info'])) {
				echo '<p>'._('More info').': <a href="'.$modules[$name]['info'].'" target="_new">'.$modules[$name]['info'].'</a></p>';
			} else {
				echo '<p>'._('More info').': <a href="'."$freepbx_help_url&freepbx_module=".urlencode($name).'" target="help">Get help for '.$modules[$name]['name'].'</a></p>';
			}
			echo "\t\t\t\t</div>\n";
			
			if (isset($modules[$name]['changelog']) && !empty($modules[$name]['changelog'])) {
				echo "\t\t\t\t<div class=\"tabbertab\" title=\""._("Changelog")."\">\n";
				echo "<h5>".sprintf(_("Change Log for version %s"), $modules[$name]['version'])."</h5>";

				// convert "1.x.x:" and "*1.x.x*" into bold, and do nl2br
				// TODO: need to fix this to convert 1.x.xbetax.x, 1.x.xalphax.x, 1.x.xrcx.x, 1.x.xRCx.x formats as well
				//
				$changelog = nl2br($modules[$name]['changelog']);
				$changelog = preg_replace('/(\d+(\.\d+|\.\d+beta\d+|\.\d+alpha\d+|\.\d+rc\d+|\.\d+RC\d+)+):/', '<strong>$0</strong>', $changelog);
				$changelog = preg_replace('/\*(\d+(\.\d+|\.\d+beta\d+|\.\d+alpha\d+|\.\d+rc\d+|\.\d+RC\d+)+)\*/', '<strong>$1:</strong>', $changelog);

				// convert '#xxx', 'ticket xxx', 'bug xxx' to ticket links and rxxx to changeset links in trac
				//
				$changelog = preg_replace_callback('/(?<!\w)(?:#|bug |ticket )([^&]\d{3,4})(?!\w)/i', 'trac_replace_ticket', $changelog);
				$changelog = preg_replace_callback('/(?<!\w)r(\d+)(?!\w)/', 'trac_replace_changeset', $changelog);
				$changelog = preg_replace_callback('/(?<!\w)\[(\d+)\](?!\w)/', 'trac_replace_changeset', $changelog);

				echo $changelog;
				echo "\t\t\t\t</div>\n";
			}
			
			if (isset($amp_conf['DEVEL']) && $amp_conf['DEVEL'] == 'true') {
				echo "\t\t\t\t<div class=\"tabbertab\" title=\""._("Debug")."\">\n";
				echo "\t\t\t\t<h5>".$name."</h5><pre>\n";
				print_r($modules_local[$name]);
				echo "</pre>";
				if (isset($modules_online)) {
					echo "\t\t\t\t<h5>Online info</h5><pre>\n";
					print_r($modules_online[$name]);
					echo "</pre>\n";
				}
					echo "\t\t\t\t<h5>combined</h5><pre>\n";
					print_r($modules[$name]);
					echo "</pre>\n";
				echo "\t\t\t\t</div>\n";
			}
			
			echo "\t\t\t</div>\n";
			echo "\t\t</div>\n";
			
			// ---- end of drop-down tab box 
			
			echo "\t\t</li>\n";
		}
		
		if ($numdisplayed == 0) {
			if (isset($modules_online) && count($modules_online) > 0) {
				echo _("All available modules are up-to-date and installed.");
			} else {
				echo _("No modules to display.");
			}
		}
		
		echo "\t</ul></div>\n";
		echo "</div>";

		echo "<div class=\"modulebuttons\">";
		if ($online) {
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_download_all();\">"._("Download all")."</a>";
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_upgrade_all();\">"._("Upgrade all")."</a>";
		}
		echo "\t<input type=\"reset\" value=\""._("Reset")."\" />";
		echo "\t<input type=\"submit\" value=\""._("Process")."\" name=\"process\" />";
		echo "</div>";

		echo "</form>";
	break;
}

if ($quietmode) {
	echo '</body></html>';
}

?>

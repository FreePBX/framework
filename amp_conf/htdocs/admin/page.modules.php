<?php /* $Id$ */

/** Controls if online module and install/uninstall options are available.
 * This is meant for when using external packaging systems (eg, deb or rpm) to manage
 * modules. Package mantainers should set this to 0 in their main freepbx package.
 */
define('EXTERNAL_PACKAGE_MANAGEMENT', 0);


$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
// can't go online if external management is on
$online = (isset($_REQUEST['online']) && !EXTERNAL_PACKAGE_MANAGEMENT) ? $_REQUEST['online'] : false;
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

function pageReload(){
return "";
	//return "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=".rand()."'</script>";
}



?>

<!-- <div class="rnav">
	<li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay="><?php echo _("Local Modules") ?></a></li>
	<li><a id="<?php echo ($extdisplay=='online' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay=online"><?php echo _("Online Modules") ?></a></li>
</div>
-->

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
	var re = /^moduleaction\[([a-z0-9_]+)\]$/;
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

</script>
<?php

echo "<h2>" . _("Module Administration") . "</h2>";


$modules_local = module_getinfo();

if ($online) {
	$modules_online = module_getonlinexml();
	$modules = $modules_local + $modules_online;
} else {
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
	return preg_replace("/[^a-z0-9]/i", "_", $name);
}

/** Progress callback used by module_download() 
 */
function download_progress($action, $params) {
	switch ($action) {
		case 'untar':
			echo '<script type="text/javascript">
			        var txt = document.createTextNode("Untarring..");
			        var br = document.createElement(\'br\');
			        document.getElementById(\'moduleprogress\').appendChild(br); 
					document.getElementById(\'moduleprogress\').appendChild(txt); 
			     </script>';
			flush();
		break;
		case 'downloading':
			$progress = $params['read'].' of '.$params['total'].' ('.round($params['read']/$params['total']*100).'%)';
			echo '<script type="text/javascript">
			        document.getElementById(\'downloadprogress_'.$params['module'].'\').innerHTML = \''.$progress.'\';
			      </script>';
			flush();
		break;
		case 'done';
			echo '<script type="text/javascript">
			        var txt = document.createTextNode("Done.");
					var br = document.createElement(\'br\');
			        document.getElementById(\'moduleprogress\').appendChild(txt); 
					document.getElementById(\'moduleprogress\').appendChild(br); 
			     </script>';
			flush();
		break;
	}
}

switch ($extdisplay) {  // process, confirm, or nothing
	case 'process':
		echo "<h4>"._("Please wait while module actions are performed")."</h4>\n";
		
		echo "<div id=\"moduleprogress\">";
		flush();
		foreach ($moduleaction as $modulename => $action) {	
			$didsomething = true; // set to false in default clause of switch() below..
			
			switch ($action) {
				case 'upgrade':
				case 'downloadinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo 'Downloading '.$modulename.' <span id="downloadprogress_'.$modulename.'"></span>';
						if (is_array($errors = module_download($modulename, false, 'download_progress'))) {
							echo '<span class="error">Error(s) downloading '.$modulename.': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
						
							if (is_array($errors = module_install($modulename))) {
								echo '<span class="error">Error(s) installing '.$modulename.': ';
								echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
								echo '</span>';
							} else {
								echo '<span class="success">'.$modulename.' installed successfully</span>';
							}
						}
					}
				break;
				case 'install':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_install($modulename))) {
							echo '<span class="error">Error(s) installing '.$modulename.': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.$modulename.' installed successfully</span>';
						}
					}
				break;
				case 'enable':
					if (is_array($errors = module_enable($modulename))) {
						echo '<span class="error">Error(s) enabling '.$modulename.': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.$modulename.' enabled successfully</span>';
					}
				break;
				case 'disable':
					if (is_array($errors = module_disable($modulename))) {
						echo '<span class="error">Error(s) disabling '.$modulename.': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.$modulename.' disabled successfully</span>';
					}
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = module_uninstall($modulename))) {
							echo '<span class="error">Error(s) uninstalling '.$modulename.': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.$modulename.' uninstalled successfully</span>';
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
	break;
	case 'confirm':
		ksort($moduleaction);
		
		echo "<form name=\"modulesGUI\" action=\"config.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
		echo "<input type=\"hidden\" name=\"online\" value=\"".$online."\" />";
		echo "<input type=\"hidden\" name=\"extdisplay\" value=\"process\" />";
		
		$actionstext = array();
		foreach ($moduleaction as $module => $action) {	
			$text = false;
			switch ($action) {
				case 'upgrade':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						$actionstext[] = sprintf(_("%s %s will be upgraded to online verison %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules_online[$module]['version']);
					}
				break;
				case 'downloadinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						$actionstext[] =  sprintf(_("%s %s will be downloaded and installed"), $modules[$module]['name'], $modules[$module]['version']);
					}
				break;
				case 'install':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if ($modules[$module]['status'] == MODULE_STATUS_NEEDUPGRADE) {
							$actionstext[] =  sprintf(_("%s %s will be upgraded to %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules[$module]['version']);
						} else {
							$actionstext[] =  sprintf(_("%s %s will be installed and enabled"), $modules[$module]['name'], $modules[$module]['version']);
						}
					}
				break;
				case 'enable':
					$actionstext[] =  sprintf(_("%s %s will be enabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
				break;
				case 'disable':
					$actionstext[] =  sprintf(_("%s %s will be disabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						$actionstext[] =  sprintf(_("%s %s will be uninstalled"), $modules[$module]['name'], $modules[$module]['dbversion']);
					}
				break;
			}
			echo "\t<input type=\"hidden\" name=\"moduleaction[".$module."]\" value=\"".$action."\" />\n";
		}
		
		if (count($actionstext) > 0) {
			echo "<h4>"._("Please confirm the following actions:")."</h4>\n";
			echo "<ul>\n";
			foreach ($actionstext as $text) {
				echo "\t<li>".$text."</li>\n";
			}
			echo "</ul>";
			
			echo "\t<input type=\"submit\" value=\"Confirm\" name=\"process\" />";
		} else {
			echo "<h4>"._("No actions to perform")."</h4>\n";
			echo "<p>"._("Please select at least one action to perform by clicking on the module, and selecting an action on the \"Action\" tab.")."</p>";
		}
		echo "\t<input type=\"button\" value=\"Cancel\" onclick=\"location.href = 'config.php?display=modules&amp;type=tool&amp;online=1';\" />";
		
		echo "</form>";
		
	break;
	case 'online':
	default:
		
		uasort($modules, 'category_sort_callback');
		
		if ($online) {
			//echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=local'>"._("Terminate Connection to Online Module Repository")."</a><br />\n";
			//echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=online&amp;refresh=true'>"._("Force Refresh of Local Module Cache")."</a>\n";
			
			if (isset($amp_conf['AMPMODULEMSG'])) {
				$announcements = @ file_get_contents($amp_conf['AMPMODULEMSG']."/version-$version.html");
			} else {
				$announcements = @ file_get_contents("http://mirror.freepbx.org/version-$version.html");
			}
			if (isset($announcements) && !empty($announcements)) {
				echo '<div class="announcements">$announcements</div>';
			}
		} else {
			if (!EXTERNAL_PACKAGE_MANAGEMENT) {
				echo "<a href='config.php?display=modules&amp;type=tool&amp;online=1'>"._("Check for updates online")."</a>\n";
			}
		}

		echo "<form name=\"modulesGUI\" action=\"config.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
		echo "<input type=\"hidden\" name=\"online\" value=\"".$online."\" />";
		echo "<input type=\"hidden\" name=\"extdisplay\" value=\"confirm\" />";
		
		echo "<div class=\"modulebuttons\">";
		if ($online) {
			//echo "\t<input type=\"button\" value=\"Upgrade all\" onClick=\"check_upgrade_all();\" />";
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_upgrade_all();\">Upgrade all</a>";
		}
		echo "\t<input type=\"reset\" value=\"Reset\" />";
		echo "\t<input type=\"submit\" value=\"Process\" name=\"process\" />";
		echo "</div>";

		echo "<div id=\"modulelist\">\n";

		echo "\t<div id=\"modulelist-header\">";
		echo "\t\t<span class=\"modulename\">Module</span>\n";
		echo "\t\t<span class=\"moduletype\">Type</span>\n";
		echo "\t\t<span class=\"moduleversion\">Version</span>\n";
		echo "\t\t<span class=\"clear\">&nbsp;</span>\n";
		echo "\t</div>";

		$category = false;
		foreach (array_keys($modules) as $name) {

			if ($category != $modules[$name]['category']) {
				// show category header
				
				if ($category !== false) {
					// not the first one, so end the previous blocks
					echo "\t</ul></div>\n";
				}
				
				// start a new category header, and associated html blocks
				$category = $modules[$name]['category'];
				echo "\t<div class=\"category\" id=\"category_".prep_id($category)."\"><h3>".$category."</h3>\n";
				echo "\t<ul>";
			}
			
			echo "\t\t<li id=\"module_".prep_id($name)."\">\n";
			
			// ---- module header 
			echo "\t\t<div class=\"moduleheader\" onclick=\"toggleInfoPane('infopane_".prep_id($name)."');\" >\n";
			echo "\t\t\t<span class=\"modulename\"><a href=\"javascript:void(null);\">".$modules[$name]['name']."</a></span>\n";
			echo "\t\t\t<span class=\"moduletype\">".$modules[$name]['type']."</span>\n";
			echo "\t\t\t<span class=\"moduleversion\">".$modules[$name]['dbversion']."</span>\n";
			
			echo "\t\t\t<span class=\"modulestatus\">";
			switch ($modules[$name]['status']) {
				case MODULE_STATUS_NOTINSTALLED:
					if (isset($modules_local[$name])) {
						echo '<span class="alert">Not Installed (Locally available)</span>';
					} else {
						echo 'Not Installed (Available online: '.$modules_online[$name]['version'].')';
					}
				break;
				case MODULE_STATUS_DISABLED:
					echo 'Disabled';
				break;
				case MODULE_STATUS_NEEDUPGRADE:
					echo '<span class="alert">Disabled; Pending upgrade to '.$modules[$name]['version'].'</span>';
				break;
				case MODULE_STATUS_BROKEN:
					echo '<span class="alert">Broken</span>';
				break;
				default:
					// check for online upgrade
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare($modules[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							echo '<span class="alert">Online upgrade available ('.$modules_online[$name]['version'].')</span>';
						} else if ($vercomp > 0) {
							echo 'Newer than online version ('.$modules_online[$name]['version'].')';
						} else {
							echo 'Enabled and up to date';
						}
					} else if (isset($modules_online)) {
						// we're connected to online, but didn't find this module
						echo 'Enabled; Not available online';
					} else {
						echo 'Enabled';
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
				echo "\t\t\t\t<div class=\"tabbertab\" title=\"Attention\">\n";
				echo nl2br($modules[$name]['attention']);
				echo "\t\t\t\t</div>\n";
			}
			
			echo "\t\t\t\t<div class=\"tabbertab actiontab\" title=\"Action\">\n";
			
			echo '<input type="radio" checked="CHECKED" id="noaction_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="0" /> '.
				 '<label for="noaction_'.prep_id($name).'">No Action</label> <br />';	
			switch ($modules[$name]['status']) {
			
				case MODULE_STATUS_NOTINSTALLED:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (isset($modules_local[$name])) {
							echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
								 '<label for="install_'.prep_id($name).'">Install</label> <br />';
						} else {
							echo '<input type="radio" id="upgrade_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="downloadinstall" /> '.
								 '<label for="upgrade_'.prep_id($name).'">Download and Install</label> <br />';
						}
					}
				break;
				case MODULE_STATUS_DISABLED:
					echo '<input type="radio" id="enable_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="enable" /> '.
						 '<label for="enable_'.prep_id($name).'">Enable</label> <br />';
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">Uninstall</label> <br />';
					}
				break;
				case MODULE_STATUS_NEEDUPGRADE:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
							 '<label for="install_'.prep_id($name).'">Upgrade to '.$modules_local[$name]['version'].' and Enable</label> <br />';
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">Uninstall</label> <br />';
					}
				break;
				case MODULE_STATUS_BROKEN:
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="install_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="install" /> '.
							 '<label for="install_'.prep_id($name).'">Install</label> <br />';
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">Uninstall</label> <br />';
					}
				break;
				default:
					// check for online upgrade
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare($modules[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							if (!EXTERNAL_PACKAGE_MANAGEMENT) {
								echo '<input type="radio" id="upgrade_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="upgrade" /> '.
									 '<label for="upgrade_'.prep_id($name).'">Download and Upgrade to '.$modules_online[$name]['version'].'</label> <br />';
							}
						}
					}
					echo '<input type="radio" id="disable_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="disable" /> '.
						 '<label for="disable_'.prep_id($name).'">Disable</label> <br />';
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo '<input type="radio" id="uninstall_'.prep_id($name).'" name="moduleaction['.prep_id($name).']" value="uninstall" /> '.
							 '<label for="uninstall_'.prep_id($name).'">Uninstall</label> <br />';
					}
				break;
			}
			echo "\t\t\t\t</div>\n";
			
			echo "\t\t\t\t<div class=\"tabbertab\" title=\"Description\">\n";
			if (isset($modules_online[$name]['description']) && !empty($modules_online[$name]['description'])) {
				echo "<h5>Description for version ".$modules_online[$name]['version']."</h5>";
				echo nl2br($modules_online[$name]['description']);
			} else if (isset($modules[$name]['description']) && !empty($modules[$name]['description'])) {
				echo nl2br($modules[$name]['description']);
			} else {
				echo "No description is available.";
			}
			if (isset($modules[$name]['info']) && !empty($modules[$name]['info'])) {
				echo '<p>More info: <a href="'.$modules[$name]['info'].'" target="_new">'.$modules[$name]['info'].'</a></p>';
			}
			echo "\t\t\t\t</div>\n";
			
			if (isset($modules_online[$name]['changelog']) && !empty($modules_online[$name]['changelog'])) {
				echo "\t\t\t\t<div class=\"tabbertab\" title=\"Changelog\">\n";
				echo "<h5>Change Log for version ".$modules_online[$name]['version']."</h5>";
				// convert "1.x.x:" and "*1.x.x*" into bold, and do nl2br
				$changelog = nl2br($modules_online[$name]['changelog']);
				$changelog = preg_replace('/(\d+(\.\d+)+):/', '<strong>$0</strong>', $changelog);
				$changelog = preg_replace('/\*(\d+(\.\d+)+)\*/', '<strong>$1:</strong>', $changelog);
				echo $changelog;
				echo "\t\t\t\t</div>\n";
			}
			
			echo "\t\t\t</div>\n";
			echo "\t\t</div>\n";
			
			// ---- end of drop-down tab box 
			
			echo "\t\t</li>\n";
		}
		echo "\t</ul></div>\n";
		echo "</div>";

		echo "<div class=\"modulebuttons\">";
		if ($online) {
			//echo "\t<input type=\"button\" value=\"Upgrade all\" onClick=\"check_upgrade_all();\" />";
			echo "\t<a href=\"javascript:void(null);\" onclick=\"check_upgrade_all();\">Upgrade all</a>";
		}
		echo "\t<input type=\"reset\" value=\"Reset\" />";
		echo "\t<input type=\"submit\" value=\"Process\" name=\"process\" />";
		echo "</div>";

		echo "</form>";
	break;
}

?>

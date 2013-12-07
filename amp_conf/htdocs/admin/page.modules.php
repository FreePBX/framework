<?php /* $Id$ */

/** Controls if online module and install/uninstall options are available.
 * This is meant for when using external packaging systems (eg, deb or rpm) to manage
 * modules. Package maintainers should set AMPEXTERNPACKAGES to true in /etc/amportal.conf.
 * Optionally, the other way is to remove the below lines, and instead just define 
 * EXTERNAL_PACKAGE_MANAGEMENT as 1. This prevents changing the setting from amportal.conf.
 */
if (!isset($amp_conf['AMPEXTERNPACKAGES']) || ($amp_conf['AMPEXTERNPACKAGES'] != 'true')) {
	define('EXTERNAL_PACKAGE_MANAGEMENT', 0);
} else {
	define('EXTERNAL_PACKAGE_MANAGEMENT', 1);
}

$modulef =& module_functions::create();

// Handle the ajax post back of an update online updates email array and status
//
if ($quietmode && !empty($_REQUEST['online_updates'])) {

	$online_updates = $_REQUEST['online_updates'];
	$update_email   = $_REQUEST['update_email'];
	$ci = new CI_Email();
	if (!$ci->valid_email($update_email) && $update_email) {
		$json_array['status'] = _("Invalid email address") . ' : ' . $update_email;
	} else {
		$cm =& cronmanager::create($db);
		$online_updates == 'yes' ?  $cm->enable_updates() : $cm->disable_updates();
		$cm->save_email($update_email);
		$json_array['status'] = true;
	}
	header("Content-type: application/json"); 
	echo json_encode($json_array);
	exit;
}

$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';

global $active_repos;
$loc_domain = 'amp';
if (isset($_REQUEST['check_online'])) {
  $online = 1;
  $active_repos = $_REQUEST['active_repos'];
  $modulef->set_active_repos($active_repos);
} else {
  $online = (isset($_REQUEST['online']) && $_REQUEST['online'] && !EXTERNAL_PACKAGE_MANAGEMENT) ? 1 : 0;
  $active_repos = $modulef->get_active_repos();
}

// fix php errors from undefined variable. Not sure if we can just change the reference below to use
// online since it changes values so just setting to what we decided it is here.

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
$displayvars = array();

$displayvars['freepbx_help_url'] = $freepbx_help_url;
$displayvars['online'] = $online;

if (!$quietmode) {
	$cm =& cronmanager::create($db);
	$displayvars['online_updates'] = $cm->updates_enabled() ? 'yes' : 'no';
	$update_email   = $cm->get_email();

	if (!$cm->updates_enabled()) {
		$displayvars['shield_class'] = 'updates_off';
	} else {
		$displayvars['shield_class'] = $update_email ? 'updates_full' : 'updates_partial';
	}

	$displayvars['update_blurb']   = htmlspecialchars(_("FreePBX allows you to automatically check for updates online. The updates will NOT be automatically installed. The email address you provdie is NEVER transmitted off of your PBX. The email is used by your local PBX to send notificaitons of updates that are available as well as IMPORTANT Security Notifications. It is STRONGYLY advised that you keep this enabled and keep updated of these important notificaions to avoid costly security issues."));
	$displayvars['ue'] = htmlspecialchars($update_email);
	//TODO: decide if warnings of any sort need to be given, or just list of repos active?
} else {
	// $quietmode==true
	?>
	<html><head></head><body>
	<?php
}

$modules_local = $modulef->getinfo(false,false,true);


if ($online) {
	$security_array = array();
	$security_issues_to_report = array();
	$modules_online = $modulef->getonlinexml(false, false, $security_array);
	
	// $module_getonlinexml_error is a global set by module_getonlinexml()
	if ($module_getonlinexml_error) {
		$displayvars['warning'] = sprintf(_("Warning: Cannot connect to online repository(s) (%s). Online modules are not available."), $amp_conf['MODULE_REPO']);		
		$online = 0;
		unset($modules_online);
	} else if (!is_array($modules_online)) {
		$displayvars['warning'] = sprintf(_("Warning: Error retrieving updates from online repository(s) (%s). Online modules are not available."), $amp_conf['MODULE_REPO']);
		$online = 0;
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


show_view('views/module_admin/header.php',$displayvars);

if (!isset($modules)) {
	$modules = & $modules_local;
}

//--------------------------------------------------------------------------------------------------------
switch ($extdisplay) {  // process, confirm, or nothing
	case 'process':
		echo "<div id=\"moduleBoxContents\">";
		echo "<h4>"._("Please wait while module actions are performed")."</h4>\n";
		echo "<div id=\"moduleprogress\">";

		// stop output buffering, and send output
		@ ob_flush();
		flush();
		foreach ($moduleaction as $modulename => $action) {	
			$didsomething = true; // set to false in default clause of switch() below..
			
			switch ($action) {
				case 'force_upgrade':
				case 'upgrade':
				case 'downloadinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						echo sprintf(_('Downloading %s'), $modulename).' <span id="downloadprogress_'.$modulename.'"></span>';
						if (is_array($errors = $modulef->download($modulename, false, 'download_progress'))) {
							echo '<span class="error">'.sprintf(_("Error(s) downloading %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							if (is_array($errors = $modulef->install($modulename))) {
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
						if (is_array($errors = $modulef->install($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) installing %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s installed successfully"),$modulename).'</span>';
						}
					}
				break;
				case 'enable':
					if (is_array($errors = $modulef->enable($modulename))) {
						echo '<span class="error">'.sprintf(_("Error(s) enabling %s"),$modulename).': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.sprintf(_("%s enabled successfully"),$modulename).'</span>';
					}
				break;
				case 'disable':
					if (is_array($errors = $modulef->disable($modulename))) {
						echo '<span class="error">'.sprintf(_("Error(s) disabling %s"),$modulename).': ';
						echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
						echo '</span>';
					} else {
						echo '<span class="success">'.sprintf(_("%s disabled successfully"),$modulename).'</span>';
					}
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->uninstall($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) uninstalling %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s uninstalled successfully"),$modulename).'</span>';
						}
					}
				break;
				case 'reinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->uninstall($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) uninstalling %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s uninstalled successfully"),$modulename).'</span>';
						}
						echo '<br/>';
						if (is_array($errors = $modulef->install($modulename))) {
							echo '<span class="error">'.sprintf(_("Error(s) installing %s"),$modulename).': ';
							echo '<ul><li>'.implode('</li><li>',$errors).'</li></ul>';
							echo '</span>';
						} else {
							echo '<span class="success">'.sprintf(_("%s installed successfully"),$modulename).'</span>';
						}
					}
				default:
					// just so we don't send an <hr> and flush()
					$didsomething = false;
			}
			
			if ($didsomething) {
				echo "<hr /><br />";
				@ ob_flush();
				flush();
			}
		}
		echo "</div>";
		if ($quietmode) {
			echo "\t<a href=\"#\" onclick=\"parent.close_module_actions(true);\" />"._("Return")."</a>";
		} else {
			echo "\t<input type=\"button\" value=\""._("Return")."\" onclick=\"location.href = 'config.php?display=modules&amp;type=$type&amp;online=".$online."';\" />";
		echo "</div>";
		}
	break;
	case 'confirm':
		ksort($moduleaction);
		/* if updating language packs, make sure they are the last thing to be done so that
		any modules currently being updated at the same time will be done so first and
		language pack updates for those modules will be included.
		*/
		if (isset($moduleaction['fw_langpacks'])) {
			$tmp = $moduleaction['fw_langpacks'];
			unset($moduleaction['fw_langpacks']);
			$moduleaction['fw_langpacks'] = $tmp;
			unset($tmp);
		}

		echo "<form name=\"modulesGUI\" action=\"config.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"display\" value=\"".$display."\" />";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\" />";
		echo "<input type=\"hidden\" name=\"online\" value=\"".$online."\" />";
		echo "<input type=\"hidden\" name=\"extdisplay\" value=\"process\" />";

		echo "\t<script type=\"text/javascript\"> var moduleActions = new Array(); </script>\n";

		$actionstext = array();
		$force_actionstext = array();
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
				case 'force_upgrade':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->checkdepends($modules_online[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be upgraded: %s Please try again after the dependencies have been installed."),  
							$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							switch ( version_compare_freepbx($modules[$module]['dbversion'], $modules_online[$module]['version'])) {
								case '-1':
									$actionstext[] = sprintf(_("%s %s will be upgraded to online version %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules_online[$module]['version']);
								break;
								case '0':
									$force_actionstext[] = sprintf(_("%s %s will be re-installed to online version %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules_online[$module]['version']);
								break;
								default:
									$force_actionstext[] = sprintf(_("%s %s will be downgraded to online version %s"), $modules[$module]['name'], $modules[$module]['dbversion'], $modules_online[$module]['version']);
							}
						}
					}
				break;
				case 'downloadinstall':
				if (!EXTERNAL_PACKAGE_MANAGEMENT) {
					if (is_array($errors = $modulef->checkdepends($modules_online[$module]))) {
						$skipaction = true;
						$errorstext[] = sprintf(_("%s cannot be installed: %s Please try again after the dependencies have been installed."),  
						$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
					} else {
						$actionstext[] =  sprintf(_("%s %s will be downloaded and installed"), $modules[$module]['name'], $modules_online[$module]['version']);
					}
				}
				break;
				case 'install':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->checkdepends($modules[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf((($modules[$module]['status'] == MODULE_STATUS_NEEDUPGRADE) ?  _("%s cannot be upgraded: %s Please try again after the dependencies have been installed.") : _("%s cannot be installed: %s Please try again after the dependencies have been installed.") ),  
							$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
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
					if (is_array($errors = $modulef->checkdepends($modules[$module]))) {
						$skipaction = true;
						$errorstext[] = sprintf(_("%s cannot be enabled: %s Please try again after the dependencies have been installed."),  
						$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
					} else {
						$actionstext[] =  sprintf(_("%s %s will be enabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
					}
				break;
				case 'disable':
					if (is_array($errors = $modulef->reversedepends($modules[$module]))) {
						$skipaction = true;
						$errorstext[] = sprintf(_("%s cannot be disabled because the following modules depend on it: %s Please disable those modules first then try again."),  
						$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
					} else {
						$actionstext[] =  sprintf(_("%s %s will be disabled"), $modules[$module]['name'], $modules[$module]['dbversion']);
					}
				break;
				case 'uninstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->reversedepends($modules[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be uninstalled because the following modules depend on it: %s Please disable those modules first then try again."),  
							$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							$actionstext[] =  sprintf(_("%s %s will be uninstalled"), $modules[$module]['name'], $modules[$module]['dbversion']);
						}
					}
				break;
				case 'reinstall':
					if (!EXTERNAL_PACKAGE_MANAGEMENT) {
						if (is_array($errors = $modulef->reversedepends($modules[$module]))) {
							$skipaction = true;
							$errorstext[] = sprintf(_("%s cannot be reinstalled because the following modules depend on it: %s Please disable those modules first then try again."),  
							$modules[$module]['name'],'<ul><li>'.implode('</li><li>',$errors).'</li></ul>');
						} else {
							$actionstext[] =  sprintf(_("%s %s will be reinstalled"), $modules[$module]['name'], $modules[$module]['dbversion']);
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

		// Write out the errors, if there are additional actions that can be accomplished list those next with the choice to
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
		if (count($actionstext) > 0 || count($force_actionstext) > 0) {
			if (count($errorstext) > 0) {
				echo "<h4>"._("You may confirm the remaining selection and then try the again for the listed issues once the required dependencies have been met:")."</h4>\n";
			} else {
				echo "<h4>"._("Please confirm the following actions:")."</h4>\n";
			}
			if (count($actionstext)) {
				echo "<h5>"._("Upgrades, installs, enables and disables:")."</h5>\n";
				echo "<ul>\n";
				foreach ($actionstext as $text) {
					echo "\t<li>".$text."</li>\n";
				}
				echo "</ul>";
			}
			if (count($force_actionstext)) {
				echo "<h5>"._("Forced downgrades and re-installs:")."</h5>\n";
				echo "<ul>\n";
				foreach ($force_actionstext as $text) {
					echo "\t<li>".$text."</li>\n";
				}
				echo "</ul>";
			}
			echo "\t<input type=\"button\" value=\""._("Confirm")."\" name=\"process\" onclick=\"process_module_actions(moduleActions);\" />";
		} else {
			echo "<h4>"._("No actions to perform")."</h4>\n";
			echo "<p>"._("Please select at least one action to perform by clicking on the module, and selecting an action on the \"Action\" tab.")."</p>";
		}
		
		echo "\t<input type=\"button\" value=\""._("Cancel")."\" onclick=\"location.href = 'config.php?display=modules&amp;type=$type&amp;online=$online';\" />";
		echo "</form>";
	break;
	case 'upload':
		// display links
		$displayvars = array();
		if (!EXTERNAL_PACKAGE_MANAGEMENT) {
			$disp_buttons[] = 'local';
			if (isset($_FILES['uploadmod']) && !empty($_FILES['uploadmod']['name'])) {
				// display upload button, only if they did upload something
				$disp_buttons[] = 'upload';
			}
			$displayvars['repo_select'] = displayRepoSelect($disp_buttons);
		} else {
			$displayvars['repo_select'] = array();
			echo "<a href='config.php?display=modules&amp;type=$type'>"._("Manage local modules")."</a>\n";
		}
		
		$displayvars['processed'] = false;
		if (isset($_REQUEST['upload']) && isset($_FILES['uploadmod']) && !empty($_FILES['uploadmod']['name'])) {
			$displayvars['res'] = $modulef->handleupload($_FILES['uploadmod']);
			$displayvars['processed'] = true;
		} elseif (isset($_REQUEST['download']) && !empty($_REQUEST['remotemod'])) {
			$displayvars['res'] = $modulef->handledownload($_REQUEST['remotemod']);
			$displayvars['processed'] = true;
		} elseif(isset($_REQUEST['remotemod'])) {
			$displayvars['res'][] = 'Nothing to download or upload';
			$displayvars['processed'] = true;
		}
		
		show_view('views/module_admin/upload.php',$displayvars);
	break;
	case 'online':
	default:

		uasort($modules, 'category_sort_callback');
		
		$repo_list = array();
		foreach($modules as &$module) {
			if(empty($module['repo'])) {
				$module['repo'] = 'Unknown';
			}
			if(!in_array($module['repo'],$repo_list) && $module['repo'] != 'local') {
				$repo_list[] = $module['repo'];
			}
		}
		$repo_list[] = 'local';
		
		if ($online) {
			// Check for announcements such as security advisories, required updates, etc.
			//
			$announcements = $modulef->get_annoucements();
			
			if (!EXTERNAL_PACKAGE_MANAGEMENT) {
				$displayvars['repo_select'] = displayRepoSelect(array(),false,$repo_list);
			}
		} else {
			if (!EXTERNAL_PACKAGE_MANAGEMENT) {
				$displayvars['repo_select'] = displayRepoSelect(array('upload'),true,$repo_list);
			}
		}
		
		$category = false;
		$numdisplayed = 0;
		$fd = $amp_conf['ASTETCDIR'].'/freepbx_module_admin.conf';
		if (file_exists($fd)) {
			$module_filter = parse_ini_file($fd);
		} else {
			$module_filter = array();
		}
		$module_display = array();
		$category = null;
		foreach (array_keys($modules) as $name) {
			if (!isset($modules[$name]['category'])) {
				$modules[$name]['category'] = _("Broken");
				$modules[$name]['name'] = $name;
			}
			if (isset($module_filter[$name]) && strtolower(trim($module_filter[$name])) == 'hidden') {
				continue;
			}

			/* OLD
			// Theory: module is not in the defined repos, and since it is not local (meaning we loaded it at some point) then we
			//         don't show it. Exception, if the status is BROKEN then we should show it because it was here once.
			//
			if ((!isset($active_repos[$modules[$name]['repo']]) || !$active_repos[$modules[$name]['repo']]) 
			&& $modules[$name]['status'] != MODULE_STATUS_BROKEN && !isset($modules_local[$name])) {
				continue;
			}
			*/

			// If versionupgrade module is present then allow it to skip modules that should not be presented
			// because an upgrade is in process. This can help assure only safe modules are present and
			// force the user to upgrade in the proper order.
			//
			if (function_exists('versionupgrade_allowed_modules') && !versionupgrade_allowed_modules($modules[$name])) {
			continue;
			}
			$numdisplayed++;
			
			if ($category !== $modules[$name]['category']) {
				$category = $modules[$name]['category'];
				$module_display[$category]['name'] = $modules[$name]['category'];
			}
			
			$loc_domain = $name;
			$name_text = modgettext::_($modules[$name]['name'], $loc_domain);
			
			$module_display[$category]['data'][$name] = $modules[$name];
			$salert = isset($modules[$name]['vulnerabilities']);
			$module_display[$category]['data'][$name]['mclass'] = $salert ? "modulevulnerable" : "moduleheader";
			
			if ($salert) {
				$module_display[$category]['data'][$name]['vulnerabilities'] = $modules[$name]['vulnerabilities'];
				foreach ($modules[$name]['vulnerabilities']['vul'] as $vul) {
					$security_issues_to_report[$vul] = true;
				}
			} else {
				$module_display[$category]['data'][$name]['vulnerabilities'] = array();
			}
			
			$module_display[$category]['data'][$name]['raw']['online'] = !empty($modules_online[$name]) ? $modules_online[$name] : array();
			$module_display[$category]['data'][$name]['raw']['local'] = !empty($modules_local[$name]) ? $modules_local[$name] : array();
			$module_display[$category]['data'][$name]['name'] = $name;
			$module_display[$category]['data'][$name]['pretty_name'] = !empty($name_text ) ? $name_text  : $name; 
			$module_display[$category]['data'][$name]['repo'] = $modules[$name]['repo'];
			$module_display[$category]['data'][$name]['dbversion'] = !empty($modules[$name]['dbversion']) ? $modules[$name]['dbversion'] : '';
			$module_display[$category]['data'][$name]['publisher'] = !empty($modules[$name]['publisher']) ? $modules[$name]['publisher'] : '';
			$module_display[$category]['data'][$name]['salert'] = $salert;
			
			if (!empty($modules_online[$name]['attention'])) {
				$module_display[$category]['data'][$name]['attention'] = nl2br(modgettext::_($modules[$name]['attention'], $loc_domain));
			}
			
			if (!empty($modules_online[$name]['changelog'])) {
				$changelog = nl2br($modules_online[$name]['changelog']);
				$changelog = preg_replace('/(\d+(\.\d+|\.\d+beta\d+|\.\d+alpha\d+|\.\d+rc\d+|\.\d+RC\d+)+):/', '<strong>$0</strong>', $changelog);
				$changelog = preg_replace('/\*(\d+(\.\d+|\.\d+beta\d+|\.\d+alpha\d+|\.\d+rc\d+|\.\d+RC\d+)+)\*/', '<strong>$1:</strong>', $changelog);

				// convert '#xxx', 'ticket xxx', 'bug xxx' to ticket links and rxxx to changeset links in trac
				//
				$changelog = preg_replace_callback('/(?<!\w)(?:#|bug |ticket )([^&]\d{3,4})(?!\w)/i', 'trac_replace_ticket', $changelog);
				$changelog = preg_replace_callback('/(?<!\w)r(\d+)(?!\w)/', 'trac_replace_changeset', $changelog);
				$changelog = preg_replace_callback('/(?<!\w)\[(\d+)\](?!\w)/', 'trac_replace_changeset', $changelog);
				
				$module_display[$category]['data'][$name]['changelog'] = $changelog;
			}
		}
		
		$displayvars['end_msg'] = (isset($modules_online) && empty($numdisplayed)) ? (count($modules_online) > 0 ? _("All available modules are up-to-date and installed.") : _("No modules to display.") ) : '';
		
		$displayvars['module_display'] = $module_display;
		$displayvars['devel'] = $amp_conf['DEVEL'];
		show_view('views/module_admin/main.php',$displayvars);
	break;
}
if ($quietmode) {
	echo '</body></html>';
} else {
	$displayvars = array("security_issues" => array());
	if (!empty($security_issues_to_report)) {
		foreach (array_keys($security_issues_to_report) as $id) {
			if (!is_array($security_array[$id]['related_urls']['url'])) {
				$security_array[$id]['related_urls']['url'] = array($security_array[$id]['related_urls']['url']);
			}
			$tickets = preg_replace_callback('/(?<!\w)(?:#|bug |ticket )([^&]\d{3,4})(?!\w)/i', 'trac_replace_ticket', $security_array[$id]['tickets']);
			$displayvars['security_issues'][$id] = $security_array[$id];
			$displayvars['security_issues']['tickets'] = $tickets;
			$displayvars['security_issues']['related_urls_text'] = count($security_array[$id]['related_urls']['url']) == 1 ? _("Related URL") : _("Related URLs");
			$displayvars['security_issues']['related_urls'] = $security_array[$id]['related_urls']['url'];
		}
	}
	show_view('views/module_admin/footer.php',$displayvars);
}

//-------------------------------------------------------------------------------------------
// Help functions
//

function category_sort_callback($a, $b) {
	if (!isset($a['category']) || !isset($b['category'])) {
		if (!isset($a['name']) || !isset($b['name'])) {
			return 0;
		} else {
			return strcmp($a['name'], $b['name']);
		}
	}
	// sort by category..
	$catcomp = strcmp($a['category'], $b['category']);
	if ($catcomp == 0) {
		// .. then by name
		return strcmp($a['name'], $b['name']);
	} elseif ($a['category'] == 'Basic') {
			return -1;
	} elseif ($b['category'] == 'Basic') {
		return 1;
	} else {
		return $catcomp;
	}
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
			@ ob_flush();
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
			@ ob_flush();
			flush();
		break;
		case 'done';
			echo '<script type="text/javascript">
			        var txt = document.createTextNode("'._('Done.').'");
					var br = document.createElement(\'br\');
			        document.getElementById(\'moduleprogress\').appendChild(txt); 
					document.getElementById(\'moduleprogress\').appendChild(br); 
			     </script>';
			@ ob_flush();
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

function pageReload(){
	return "";
	//return "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=".rand()."'</script>";
}

function displayRepoSelect($buttons,$online=false,$repo_list=array()) {
	global $display, $online, $tabindex;
	global $active_repos;

	$displayvars = array("display" => $display, "online" => $online, "tabindex" => $tabindex, "repo_list" => $repo_list, "active_repos" => $active_repos);
	$button_display = '';
	$href = "config.php?display=$display";
	$button_template = '<input type="button" value="%s" onclick="location.href=\'%s\';" />'."\n";

	foreach ($buttons as $button) {
		switch($button) {
			case 'local':
				$displayvars['button_display'] .= sprintf($button_template, _("Manage local modules"), $href);
			break;
			case 'upload':
				$displayvars['button_display'] .= sprintf($button_template, _("Upload modules"), $href.'&extdisplay=upload');
			break;
		}
	}

	$displayvars['tooltip']  = _("Choose the repositories that you want to check for new modules. Any updates available for modules you have on your system will be detected even if the repository is not checked. If you are installing a new system, you may want to start with the Basic repository and upload all modules, then go back and review the others.").' ';
	$displayvars['tooltip'] .= _(" The modules in the Extended repository are less common and may receive lower levels of support. The Unsupported repository has modules that are not supported by the FreePBX team but may receive some level of support by the authors.").' ';
	$displayvars['tooltip'] .= _("The Commercial repository is reserved for modules that are available for purchase and commercially supported.").' ';
	$displayvars['tooltip'] .= '<br /><br /><small><i>('._("Checking for updates will transmit your FreePBX, Distro, Asterisk and PHP version numbers along with a unique but random identifier. This is used to provide proper update information and track version usage to focus development and maintenance efforts. No private information is transmitted.").')</i></small>';

	return load_view('views/module_admin/reposelect.php',$displayvars);
}
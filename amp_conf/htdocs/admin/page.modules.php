<?php /* $Id$ */

$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
$refresh = isset($_REQUEST['refresh'])?$_REQUEST['refresh']:false;

$installed = find_allmodules();

function pageReload(){
return "";
	//return "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=".rand()."'</script>";
}

if (isset($_POST['submit']) && is_array($_POST['modules'])) { // if form has been submitted
	foreach ($_POST['modules'] as $module) {
		switch ($_POST['modaction']) {
			case "install":
				if (runModuleSQL($module,'install')) {
					installModule($module,$_POST[$module.'_version']);
					needreload();
				} else
					echo "<div class=\"error\">"._("Module install script failed to run")."</div>";
			break;
			case "uninstall":
				if (runModuleSQL($module,'uninstall')) {
					uninstallModule($module);
					needreload();
				} else
					echo "<div class=\"error\">"._("Module uninstall script failed to run")."</div>";
			break;
			case "enable":
				enableModule($module);
				needreload();
				echo pageReload();
			break;
			case "disable":
				disableModule($module);
				needreload();
				echo pageReload();
			break;
			case "delete":
				deleteModule($module);
				needreload();
				rmModule($module);
			break;
			case "download":
				fetchModule($module);
			break;
			case "upgrade":
				upgradeModule($module);
				needreload();
			break;
			case "installenable": // install and enable a module
				$boolInstall = true;
				// only run install if it's not installed
				if ($installed[$module]['status'] == 0) {
					// set to false on failed install
					$boolInstall = runModuleSQL($module,'install');
					if ($boolInstall) {
						installModule($module,$_POST[$module.'_version']);
						enableModule($module);
						echo pageReload();
					} else {
						echo "<div class=\"error\">{$module}: "._("Module install script failed to run")."</div>";
					}
				} else { // it's already installed, so just enable it
					enableModule($module);
					echo pageReload();
				}
				needreload();
			break;
			case "downloadinstall": // download, install and enable
				fetchModule($module);
				if (runModuleSQL($module,'install')) 
					installModule($module,$_POST[$module.'_version']);
				else
					echo "<div class=\"error\">"._("Module install script failed to run")."</div>";
				enableModule($module);
				needreload();
			break;
			case "downloadupdate": //download and update
				fetchModule($module);
				upgradeModule($module);
				needreload();
			break;
			case "uninstalldelete": //uninstall and delete
				if (runModuleSQL($module,'uninstall'))
					uninstallModule($module);
				else
					echo "<div class=\"error\">"._("Module uninstall script failed to run")."</div>";
				deleteModule($module);
				rmModule($module);
				needreload();
			break;
		}
	}
}
?>

</div>
<!-- <div class="rnav">
	<li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay="><?php echo _("Local Modules") ?></a></li>
	<li><a id="<?php echo ($extdisplay=='online' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay=online"><?php echo _("Online Modules") ?></a></li>
</div>
-->
<div class="content">

<?php
$modules_local = module_getinfo();

$modules_online = module_getonlinexml();

$modules = $modules_local + $modules_online;

function category_sort_callback($a, $b) {
	// sort by category..
	$catcomp = strcmp($a['category'], $b['category']);
	if ($catcomp == 0) {
		// .. then by name
		return strcmp($a['name'], $b['name']);
	} else
		return $catcomp;
}
uasort($modules, 'category_sort_callback');

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
		echo "\t<div class=\"category\" id=\"category_".$category."\"><h3>".$category."</h3>\n";
		echo "\t<ul>";
	}
	
	echo "\t\t<li>\n";
	echo "\t\t<span class=\"modulename\">".$modules[$name]['name']."</span>\n";
	echo "\t\t<span class=\"moduletype\">".$modules[$name]['type']."</span>\n";
	echo "\t\t<span class=\"moduleversion\">".$modules[$name]['dbversion']."</span>\n";
	
	echo "\t\t<span class=\"modulestatus\">";
	switch ($modules[$name]['status']) {
		case MODULE_STATUS_NOTINSTALLED:
			if (isset($modules_local[$name])) {
				echo '<span class="alert">Not Installed (Locally available)</span>';
			} else {
				echo '<span class="alert">Not Installed (Available online: '.$modules_online[$name]['version'].')</span>';
			}
		break;
		case MODULE_STATUS_DISABLED:
			echo '<span class="alert">Disabled</span>';
		break;
		case MODULE_STATUS_NEEDUPGRADE:
			echo '<span class="alert">Awaiting upgrade ('.$modules[$name]['version'].' ready)</span>';
		break;
		case MODULE_STATUS_BROKEN:
			echo '<span class="alert">Broken</span>';
		break;
		default:
			if (isset($modules_online[$name]['version'])) {
				$vercomp = version_compare($modules[$name]['version'], $modules_online[$name]['version']);
				if ($vercomp < 0) {
					echo '<span class="alert">Online upgrade available ('.$modules_online[$name]['version'].')</span>';
				} else if ($vercomp > 0) {
					echo 'Newer than online version ('.$modules_online[$name]['version'].')';
				}
			}
		break;
	}
	echo "</span>\n";
	
	echo "\t\t<span class=\"clear\">&nbsp;</span>\n";
	
	
	echo "\t\t</li>\n";
}
echo "\t</ul></div>\n";
echo "</div>";



echo '<pre>';







exit;

switch($extdisplay) {
	case "online": 
		echo "<h2>";
		echo _("Module Administration (online)");
		echo "</h2>";
		echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=local'>"._("Terminate Connection to Online Module Repository")."</a><br />\n";
		echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=online&amp;refresh=true'>"._("Force Refresh of Local Module Cache")."</a>\n";
		// If 'refresh' is set to 'true' then truncate the modules_xml table so it doesn't try to 
		// use the cached XML file.
		if ($refresh !== false)
			sql("truncate module_xml;");
		// Check for a warning or text message at mirror.freepbx.org/version-$version.html
		$version = getversion();
		$version = $version[0][0];

		if (isset($amp_conf['AMPMODULEMSG'])) {
			$announcements = file_get_contents($amp_conf['AMPMODULEMSG']."/version-$version.html");
		} else {
			$announcements = file_get_contents("http://mirror.freepbx.org/version-$version.html");
		}

		print "$announcements";

		// determine which modules we have installed already
		$installed = find_allmodules();
		// determine what modules are available
		$online = module_getonlinexml();
		$dispMods = new displayModules($installed,$online);
		echo $dispMods->drawModules();
	break;
	default:
		echo "<h2>";
		echo _("Module Administration");
		echo "</h2>";
		echo "<a href='config.php?display=modules&amp;type=tool&amp;extdisplay=online'>"._("Connect to Online Module Repository")."</a>\n";
		$installed = find_allmodules();
		$dispMods = new displayModules($installed);
		echo $dispMods->drawModules();

	break;
}
?>

<?php

/* BEGIN FUNCTIONS */

/* displays table of modules provided in the passed array
 * If displaying online modules, pass that array as the second arg 
*/

class displayModules {
	var $html;
	//constructor
	function displayModules($installed,$online=false) {
		// So, we have an array with several:
	/*
		[phpinfo] => Array
			(
				[displayName] => PHP Info
				[version] => 1.0
				[type] => tool
				[category] => Basic
				[info] => http://www.freepbx.org/wikiPage
				[items] => Array
					(
						[PHPINFO] => PHP Info
						[PHPINFO2] => PHP Info2
					)
	
				[requirements] => Array
					(
						[FILE] => /usr/sbin/asterisk
						[MODULE] => core
					)
	
			)
	*/
	
		// if we are displaying online modules, determine which are installed
		if($online) {
			
			$online = $this->sortModules($online);
			foreach(array_keys($online) as $arrkey) {
			
				if (!is_array($installed))
				{
				    continue;
				}
				    
				// Determine if module is already local
				if(array_key_exists($arrkey,$installed)) {
					//check if online version is newer
					$newversion = $online[$arrkey]['version'];
					$oldversion = isset($installed[$arrkey]['version'])?$installed[$arrkey]['version']:'0';
					// version_compare returns 1 if new > old
					if (version_compare($newversion,$oldversion) == 1) {
						$modsOnlineUpdate[] = $online[$arrkey];
					} else {
						// we are not displaying this array .. it's just here for kicks
						$modsOnlineInstalled[] = $online[$arrkey];
					}
				} else {
					$modsOnlineOnly[] = $online[$arrkey];
				}
				
				//$this->html .= $this->tableHtml($online[$arrkey],$status,$action);
			}
			
			/* 
			 *  Available Module Updates
			 */
			if(isset($modsOnlineUpdate) && is_array($modsOnlineUpdate)) {
				$color = "orange";
				$this->options = "
					<select name=\"modaction\">
						<option value=\"downloadupdate\">"._("Download and Update selected")."
						<option value=\"download\">"._("Download selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Available Module Updates (online)"), $modsOnlineUpdate, $color, $options);
			}
			
			/* 
			 *  Online Modules
			 */			
			if(isset($modsOnlineOnly) && is_array($modsOnlineOnly)) {
				$color = "white";
				$this->options = "
					<select name=\"modaction\">
						<option value=\"downloadinstall\">"._("Download and Install selected")."
						<option value=\"download\">"._("Download selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Modules Available (online)"), $modsOnlineOnly, $color, $options);
			}			
			
		}
			
		$installed = $this->sortModules($installed);
		if (isset($installed) && is_array($installed)) {
			foreach($installed as $mod) {
				//create seperate arrays based on module status
				if ($mod['status'] == 0) {
					$modsNotinstalled[] = $mod;
				} else if($mod['status'] == 1){
					$modsDisabled[] = $mod;
				} else if($mod['status'] == 2){
					$modsEnabled[] = $mod;
				} else if($mod['status'] == 3){
					$modsUpdate[] = $mod;
				} else if($mod['status'] == -1){
					$modsBroken[] = $mod;
				}

				//$this->html .= $this->tableHtml($mod,$status,$color);
			}
			
			// draw a form and list for each module status
			/* 
			 *  Modules Needing Update
			 */
			if(isset($modsUpdate) && is_array($modsUpdate)) {
				$color = "#CCFF00";
				$options = "
					<select name=\"modaction\">
						<option value=\"upgrade\">"._("Upgrade Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Enabled Modules Requiring Upgrade"), $modsUpdate, $color, $options);
			}
			
			/* 
			 *  Enabled Modules
			 */			
			if(isset($modsEnabled) && is_array($modsEnabled)) {
				$color = "white";
				$this->options = "
					<select name=\"modaction\">
						<option value=\"disable\">"._("Disable Selected")."
						<option value=\"uninstall\">"._("Uninstall Selected")."
						<option value=\"uninstalldelete\">"._("Uninstall and Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Enabled Modules"), $modsEnabled, $color, $options);
			}
			
			/* 
			 *  Disabled Modules
			 */			
			if(isset($modsDisabled) && is_array($modsDisabled)) {
				$color = "white";
				$this->options = "
					<select name=\"modaction\">
						<option value=\"enable\">"._("Enable Selected")."
						<option value=\"uninstall\">"._("Uninstall Selected")."
						<option value=\"uninstalldelete\">"._("Uninstall and Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Disabled Modules"), $modsDisabled, $color, $options);
			}

			/* 
			 *  Local Modules Not Installed
			 */			
			if(isset($modsNotinstalled) && is_array($modsNotinstalled)) {
				$color = "white";
				$this->options = "
					<select name=\"modaction\">
						<option value=\"installenable\">"._("Enable Selected")."
						<option value=\"delete\">"._("Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->buildTable(_("Not Installed Local Modules"), $modsNotinstalled, $color, $options);
			}
			
			if(isset($modsBroken) && is_array($modsBroken)) {
				$color = "#FFFFFF";
					//$rows .= $this->tableHtmlBroken($mod,$color);
				$this->options = "
					<select name=\"modaction\">
						<option value=\"delete\">"._("Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				// BROKEN modname instead of rawname ?? 
				$this->html .= $this->buildTable(_("Not Installed Local Modules"), $modsBroken, $color, $options);
			}
			
		}
		
	}
	
	//sorts the modules by category
	function sortModules($array) {
		if (isset($array) && is_array($array)) {
			foreach($array as $key => $mod) {
				// sort the list in category / displayName order
				// this is the only way i know how to do this...surely there is another way?
				
				// fields for sort
				$displayName = isset($mod['displayName']) ? $mod['displayName'] : 'unknown';
				$category = isset($mod['category']) ? $mod['category'] : 'unknown';	
				// we want to sort on this so make it first in the new array
				$newallmods[$key]['asort'] = $category.$displayName;
			
				// copy the rest of the array
				$newallmods[$key]['modname'] = $key;
				$newallmods[$key]['displayName'] = $displayName;
				$newallmods[$key]['category'] = $category;
				$newallmods[$key]['rawname'] = isset($mod['rawname'])?$mod['rawname'] : null;
				$newallmods[$key]['info'] = isset($mod['info'])?$mod['info'] : null;
				$newallmods[$key]['location'] = isset($mod['location']) ? $mod['location'] : null ;
				$newallmods[$key]['version'] = isset($mod['version']) ? $mod['version'] : 'unknown';
				$newallmods[$key]['type'] = isset($mod['type']) ? $mod['type'] : 'unknown';
				$newallmods[$key]['status'] = isset($mod['status']) ? $mod['status'] : 0;
				
				asort($newallmods);	
			}
			return $newallmods;
		}
	}
	
	// tableRow($color, $mod['rawname'], $mod['version'], $mod['type'], $mod['categrory'], $mod['displayName'], $mod['info']
	function tableRow($color, $rawname, $version, $type, $category, $displayname, $infourl) {
		$out = '<tr bgcolor="'.$color.'">';
		$out .= '<td><input type="checkbox" name="modules[]" value="'.$rawname.'">'.
		        '<input type="hidden" name="'.$rawname.'_version" value="'.$version.'"></td>';
		$out .= '<td><a target="_BLANK" href="'.$infourl.'">'.$displayname.' ('.$rawname.')</a></td>';
		$out .= '<td>'.$version.'</td>';
		$out .= '<td>'.$type.'</td>';
		$out .= '</tr>';
		return $out;
		return <<< End_of_Html
			
			<tr bgcolor={$color}>
				<td>
					<input type="checkbox" name="modules[]" value="{$arrRow['rawname']}">
					<input type="hidden" name="{$arrRow['rawname']}_version" value="{$arrRow['version']}">
				</td>
				<td><a target=_BLANK href={$arrRow['info']}>{$arrRow['displayName']} ({$arrRow['rawname']})</a></td>
				<td>{$arrRow['version']}</td>
				<td>{$arrRow['type']}</td>
				<td>{$arrRow['category']}</td>
			</tr>
			
End_of_Html;
	}

	function tableHtmlBroken($arrRow,$color) {
		return <<< End_of_Html
			
			<tr bgcolor={$color}>
				<td>
					<input type="checkbox" name="modules[]" value="{$arrRow['modname']}">
					<input type="hidden" name="{$arrRow['modname']}_version" value="{$arrRow['version']}">
				</td>
				<td><a target=_BLANK href={$arrRow['info']}>{$arrRow['displayName']} ({$arrRow['modname']})</a></td>
				<td>{$arrRow['version']}</td>
				<td>{$arrRow['type']}</td>
				<td>{$arrRow['category']}</td>
			</tr>
			
End_of_Html;
	}
	
	function formStart($title = "") {
		$uri = preg_replace("/&refresh=true/", "//", $_SERVER['REQUEST_URI']);
		return "
			<h4>{$title}</h4>
			<form method=\"POST\" action=\"{$uri}\">
			<table border=1><tr><th>&nbsp;</th><th>". _("Module")."</th><th>". _("Version")."</th><th>". _("Type") ."</th><th>". _("Category") ."</th></tr>
				";
	}

	function formEnd() {
		return "</table>{$this->options}</form><hr>";
	}
		
	function drawModules() {
		return $this->html;
	}
	
	
	function buildTable($title, $rows, $color, $options) {
		$out = $this->formStart($title);
		foreach($rows as $mod) {
			$out .= $this->tableRow($mod);
		}
		$out .= $this->formEnd();
		return $out;
	}
}

?>


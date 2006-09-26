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
		$online = getModuleXml();
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
				$rows = "";
				foreach($modsOnlineUpdate as $mod) {
					$color = "orange";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"downloadupdate\">"._("Download and Update selected")."
						<option value=\"download\">"._("Download selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Available Module Updates (online)"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
			}
			
			/* 
			 *  Online Modules
			 */			
			if(isset($modsOnlineOnly) && is_array($modsOnlineOnly)) {
				$rows = "";
				foreach($modsOnlineOnly as $mod) {
					$color = "white";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"downloadinstall\">"._("Download and Install selected")."
						<option value=\"download\">"._("Download selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Modules Available (online)"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
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
				$rows = "";
				foreach($modsUpdate as $mod) {		
					$color = "#CCFF00";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"upgrade\">"._("Upgrade Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Enabled Modules Requiring Upgrade"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
			}
			
			/* 
			 *  Enabled Modules
			 */			
			if(isset($modsEnabled) && is_array($modsEnabled)) {
				$rows = "";
				foreach($modsEnabled as $mod) {
					$color = "white";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"disable\">"._("Disable Selected")."
						<option value=\"uninstall\">"._("Uninstall Selected")."
						<option value=\"uninstalldelete\">"._("Uninstall and Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Enabled Modules"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
			}
			
			/* 
			 *  Disabled Modules
			 */			
			if(isset($modsDisabled) && is_array($modsDisabled)) {
				$rows = "";
				foreach($modsDisabled as $mod) {
					$color = "white";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"enable\">"._("Enable Selected")."
						<option value=\"uninstall\">"._("Uninstall Selected")."
						<option value=\"uninstalldelete\">"._("Uninstall and Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Disabled Modules"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
			}

			/* 
			 *  Local Modules Not Installed
			 */			
			if(isset($modsNotinstalled) && is_array($modsNotinstalled)) {
				$rows = "";
				foreach($modsNotinstalled as $mod) {
					$color = "white";
					$rows .= $this->tableHtml($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"installenable\">"._("Enable Selected")."
						<option value=\"delete\">"._("Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_("Not Installed Local Modules"));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
			}
			
			if(isset($modsBroken) && is_array($modsBroken)) {
				$rows = "";
				foreach($modsBroken as $mod) {
					$color = "#FFFFFF";
					$rows .= $this->tableHtmlBroken($mod,$color);
				}
				$this->options = "
					<select name=\"modaction\">
						<option value=\"delete\">"._("Delete Selected")."
					</select>
					<input type=\"submit\" name=\"submit\" value=\""._("Submit")."\">
					";
				// build the table
				$this->html .= $this->formStart(_('Broken'));
				$this->html .= $rows;
				$this->html .= $this->formEnd($mod['status']);
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
	
	function tableHtml($arrRow,$color) {
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
}

function getModuleXml() {
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
		$version = $version[0][0];
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
		$data4sql = (get_magic_quotes_gpc() ? $data : addslashes($data));
		sql('INSERT INTO module_xml (time,data) VALUES ('.time().',"'.$data4sql.'")');
	} else {
//		echo "using cache";
		$data = $result['data'];
	}
	//echo time() - $result['time'];
	$parser = new xml2ModuleArray($data);
	$xmlarray = $parser->parseModulesXML($data);
	//$modules = $xmlarray['XML']['MODULE'];
	
	//echo "<hr>Raw XML Data<pre>"; print_r(htmlentities($data)); echo "</pre>";
	//echo "<hr>XML2ARRAY<pre>"; print_r($xmlarray); echo "</pre>";
	
	return $xmlarray;
}

// runModuleSQL moved to functions.inc.php

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

function enableModule($modname) {
	global $db;
	$sql = "UPDATE modules SET enabled = 1 WHERE modulename = '{$modname}'";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

function disableModule($modname) {
	global $db;
	$sql = "UPDATE modules SET enabled = 0 WHERE modulename = '{$modname}'";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

function deleteModule($modname) {
	global $db;
	$sql = "DELETE FROM modules WHERE modulename = '{$modname}' LIMIT 1";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
}

//downloads a module, and extracts it into the module dir
function fetchModule($name) {
	global $amp_conf;
	$res = getThisModule($name);
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

function getThisModule($modname) {
	$xmlinfo = getModuleXml();
	foreach($xmlinfo as $key => $mod) {
		if (isset($mod['rawname']) && $mod['rawname'] == $modname) 
			return $mod;
	}
}

function verifyAndInstall($filename) {
	global $amp_conf;
	system("tar zxf {$filename} --directory={$amp_conf['AMPWEBROOT']}/admin/modules/");
	return true;
}
?>


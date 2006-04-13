<?php /* $Id$ */

$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';

if (isset($_POST['submit'])) { // if form has been submitted
	switch ($_POST['modaction']) {
		case "install":
			if (runModuleSQL($_POST['modname'],$_POST['modaction'])) 
				installModule($_POST['modname'],$_POST['modversion']);
			else
				echo "<div class=\"error\">"._("Module install script failed to run")."</div>";
		break;
		case "uninstall":
			if (runModuleSQL($_POST['modname'],$_POST['modaction']))
				uninstallModule($_POST['modname']);
			else
				echo "<div class=\"error\">"._("Module uninstall script failed to run")."</div>";
		break;
		case "enable":
			enableModule($_POST['modname']);
			echo "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=1'</script>";
		break;
		case "disable":
			disableModule($_POST['modname']);
			echo "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&foo=2'</script>";
		break;
		case "delete":
			deleteModule($_POST['modname']);
		break;
		case "download":
			fetchModule($_POST['rawname']);
		break;
		case "upgrade":
			upgradeModule($_POST['modname']);
		break;
		case "rmmod":
			rmModule($_POST['modname']);
		break;
	}
}
?>

</div>
<!-- <div class="rnav">
	<li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay="><?php echo _("Local Modules") ?></a></li>
	<li><a id="<?php echo ($extdisplay=='online' ? 'current':'') ?>" href="config.php?display=modules&type=tool&extdisplay=online"><?php echo _("Online Modules") ?></a></li>
</div>
--!>
<div class="content">


<?php
switch($extdisplay) {
	case "online": 
		echo "<h2>";
		echo _("Online Modules");
		echo "</h2>";
		echo "<h3><a href='config.php?display=modules&type=tool&extdisplay=local'>"._("Local Modules")."</a></h3>\n";
		// determine which modules we have installed already
		$installed = find_allmodules();
		// determine what modules are available
		$online = getModuleXml();
		$dispMods = new displayModules($installed,$online);
		echo $dispMods->drawModules();
	break;
	default:
		echo "<h2>";
		echo _("Local Module Administration");
		echo "</h2>";
		echo "<h3><a href='config.php?display=modules&type=tool&extdisplay=online'>"._("Online Modules")."</a></h3>\n";
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
				// Determine if module is already local
				if(array_key_exists($arrkey,$installed)) {
					//check if online version is newer
					$newversion = $online[$arrkey]['version'];
					$oldversion = $installed[$arrkey]['version'];
					// version_compare returns 1 if new > old
					if (version_compare($newversion,$oldversion) == 1) {
						$status = "Local (update available)";
						$action = "
						<form action={$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']} method=post>
							<input type=hidden name=modaction value=download>
							<input type=hidden name=rawname value={$online[$arrkey]['rawname']}>
							<input type=submit name=submit value=Download>
						</form>
						";
					} else {
						$status = "Local (up to date)";
						$action = "";
					}
				} else {
					$status = "Online";
					$action = "
					<form action={$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']} method=post>
						<input type=hidden name=modaction value=download>
						<input type=hidden name=rawname value={$online[$arrkey]['rawname']}>
						<input type=submit name=submit value=Download>
					</form>
					";
				}
				
				$this->html .= $this->tableHtml($online[$arrkey],$status,$action);
			}
			
		} else {	//local modules
			
			$installed = $this->sortModules($installed);
			foreach($installed as $key => $mod) {
				
				//dynamicatlly create a form based on status
				if ($mod['status'] == 0) {
					$status = _("Not Installed");
					//install form
					$action = "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modversion\" value=\"{$mod['version']}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"install\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Install")."\">";
					$action .= "</form>";
					$action .= "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modversion\" value=\"{$mod['version']}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"rmmod\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Remove")."\">";
					$action .= "</form>";
				} else if($mod['status'] == 1){
					$status = _("Disabled");
					//enable form
					$action = "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"enable\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Enable")."\">";
					$action .= "</form>";
					//uninstall form
					$action .= "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"uninstall\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Uninstall")."\">";
					$action .= "</form>";
					
				} else if($mod['status'] == 2){
					$status = _("Enabled");
					//disable form
					$action = "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"disable\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Disable")."\">";
					$action .= "</form>";
				} else if($mod['status'] == 3){
					$status = _("Enabled (needs update)");
					//disable form
					$action = "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"disable\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Disable")."\">";
					$action .= "</form>";
					//upgrade form
					$action .= "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modversion\" value=\"{$mod['version']}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"upgrade\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Update")."\">";
					$action .= "</form>";
				} else if($mod['status'] == -1){
					$status = _("Broken");
					//disable form
					$action = "<form method=\"POST\" action=\"{$_SERVER['REQUEST_URI']}\" style=display:inline>";
					$action .= "<input type=\"hidden\" name=\"modname\" value=\"{$key}\">";
					$action .= "<input type=\"hidden\" name=\"modaction\" value=\"delete\">";
					$action .= "<input type=\"submit\" name=\"submit\" value=\""._("Delete")."\">";
					$action .= "</form>";
				}
				$this->html .= $this->tableHtml($mod,$status,$action);
			}
		}
			
	}
	
	//sorts the modules by category
	function sortModules($array) {
		foreach($array as $key => $mod) {
				// sort the list in category / displayName order
				// this is the only way i know how to do this...surely there is another way?
				
				// fields for sort
				$displayName = isset($mod['displayName']) ? $mod['displayName'] : 'unknown';
				$category = isset($mod['category']) ? $mod['category'] : 'unknown';	
				// we want to sort on this so make it first in the new array
				$newallmods[$key]['asort'] = $category.$displayName;
			
				// copy the rest of the array
				$newallmods[$key]['displayName'] = $displayName;
				$newallmods[$key]['category'] = $category;
				$newallmods[$key]['rawname'] = $mod['rawname'];
				$newallmods[$key]['info'] = $mod['info'];
				$newallmods[$key]['location'] = $mod['location'];
				$newallmods[$key]['version'] = isset($mod['version']) ? $mod['version'] : 'unknown';
				$newallmods[$key]['type'] = isset($mod['type']) ? $mod['type'] : 'unknown';
				$newallmods[$key]['status'] = isset($mod['status']) ? $mod['status'] : 0;
				
				asort($newallmods);	
			}
			return $newallmods;
	}
	
	function tableHtml($arrRow,$status,$action) {
		return <<< End_of_Html
			
			<tr>
				<td><a target=_BLANK href={$arrRow['info']}>{$arrRow['displayName']} ({$arrRow['rawname']})</a></td>
				<td>{$arrRow['version']}</td>
				<td>{$arrRow['type']}</td>
				<td>{$arrRow['category']}</td>
				<td>{$status}</td>
				<td>{$action}</td>
			</tr>
			
End_of_Html;
	}
	
	function drawModules() {
		$table = "<table border=1><tr><th>". _("Module")."</th><th>". _("Version")."</th><th>". _("Type") ."</th><th>". _("Category") ."</th><th>". _("Status") ."</th><th>". _("Action") ."</th></tr>";
		$table .= $this->html;
		$table .= "</table>";
		return $table;
	}
}

function getModuleXml() {
	//this should be in an upgrade file ... putting here for now.
	sql('CREATE TABLE IF NOT EXISTS module_xml (time INT NOT NULL , data BLOB NOT NULL) TYPE = MYISAM ;');
	
	$result = sql('SELECT * FROM module_xml','getRow',DB_FETCHMODE_ASSOC);
	// if the epoch in the db is more than 2 hours old, then regrab xml
	if((time() - $result['time']) > 14400) {
		$version = getversion();
		$version = $version[0][0];
		// we need to know the freepbx major version we have running (ie: 2.1.2 is 2.1)
		preg_match('/(\d+\.\d+)/',$version,$matches);
		//echo "the result is ".$matches[1];
		$fn = "http://amportal.sourceforge.net/modules-".$matches[1].".xml";
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

// executes the SQL found in a module install.sql or uninstall.sql
function runModuleSQL($moddir,$type){
	global $db;
	global $amp_conf;
	$data='';
	$retval = false;
	// if there is an sql file, run it
	if (is_file("modules/{$moddir}/{$type}.sql")) {
		// run sql script
		$fd = fopen("modules/{$moddir}/{$type}.sql","r");
		while (!feof($fd)) {
			$data .= fread($fd, 1024);
		}
		fclose($fd);

		preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);
		
		foreach ($matches[1] as $sql) {
				$result = $db->query($sql); 
				if(DB::IsError($result)) {     
					return false;
				}
		}
		$retval = true;
	}
	
	// if there is a php file, run it
	if (is_file("modules/{$moddir}/{$type}.php")) {
		include("modules/{$moddir}/{$type}.php");
		$retval = true;
	}
	
	return true;
}

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
		if ($res['md5sum'] == md5 ($filedata)) {
			return verifyAndInstall($filename);
		} else {
			unlink($filename);
		}
	}
	$url = "https://svn.sourceforge.net/svnroot/amportal/modules/".$res['location'];
	$fp = @fopen($filename,"w");
	$filedata = file_get_contents($url);
	fwrite($fp,$filedata);
	fclose($fp);
	if (is_readable($filename) !== TRUE ) {
		echo "<div class=\"error\">"._("Unable to save")." {$filename} - Check file/directory permissions</div>";
		return false;
	}
	// Check the MD5 info against what's in the module's XML
	if (!isset($res['md5sum']) && !empty($res['md5sum'])) 
		echo "<div class=\"error\">"._("Unable to Check Integrity of")." {$filename}</div>";
	if ($res['md5sum'] =! md5 ($filedata)) {
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
	if (is_dir($amp_conf['AMPWEBROOT'].'/admin/modules/'.$module) && strstr($module, '.') === FALSE ) {
		exec('/bin/rm -rf '.$amp_conf['AMPWEBROOT'].'/admin/modules/'.$module);
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


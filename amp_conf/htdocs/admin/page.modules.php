<?php /* $Id$ */

// executes the SQL found in a module install.sql or uninstall.sql
function runModuleSQL($moddir,$type){
	global $db;
	$data='';
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
		return true;
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
			echo "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."'</script>";
		break;
		case "disable":
			disableModule($_POST['modname']);
			echo "<script language=\"Javascript\">document.location='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."'</script>";
		break;
	}
}
?>

<h2><?php echo _("Module Administration")?></h2>

<table border="1" >
<tr>
	<th><?php echo _("Module")?></th><th><?php echo _("Category")?></th><th><?php echo _("Version")?></th><th><?php echo _("Type")?></th><th><?php echo _("Status")?></th><th><?php echo _("Action")?></th>
</tr>

<?php
$allmods = find_allmodules();
foreach($allmods as $key => $mod) {
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
	$newallmods[$key]['version'] = isset($mod['version']) ? $mod['version'] : 'unknown';
	$newallmods[$key]['type'] = isset($mod['type']) ? $mod['type'] : 'unknown';
	$newallmods[$key]['status'] = isset($mod['status']) ? $mod['status'] : 0;
	
	asort($newallmods);	
}
foreach($newallmods as $key => $mod) {
	
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
	}
	
	echo "<tr>";
	echo "<td>";
	echo _($mod['displayName']);
	echo "</td>";
	echo "<td>";
	echo $mod['category'];
	echo "</td>";
	echo "<td>";
	echo $mod['version'];
	echo "</td>";
	echo "<td>";
	echo _($mod['type']); 
	echo "</td>";
	echo "<td>";
	echo $status;
	echo "</td>";
	echo "<td>";
	echo $action;
	echo "</td>";
	echo "</tr>";
}

?>

</table>

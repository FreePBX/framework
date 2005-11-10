<?php /* $Id$ */

// executes the SQL found in a module install.sql or uninstall.sql
function runModuleSQL($modDir,$type){
	global $db;
	if (is_file("modules/{$moddir}/{$type}.sql")) {
		// run sql script
		$fd = fopen("modules/{$moddir}/{$type}.sql");
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

function installModule($modname,$modversion) {
	global $db;
	$sql = "INSERT INTO modules (modulename, version) values ('{$modname}','{$modversion}')";
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
		break;
		case "disable":
			disableModule($_POST['modname']);
		break;
	}
}
?>

<h2>Module Administration</h2>

<table border="1" width="100%">
<th>Module</th><th>Version</th><th>Status</th><th>Action</th>
<?php
foreach(find_allmodules() as $key => $mod) {
	
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
	echo $mod['displayName'];
	echo "</td>";
	echo "<td>";
	echo $mod['version'];
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

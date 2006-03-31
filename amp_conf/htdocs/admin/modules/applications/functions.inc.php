<?php 

require "applications.inc.php";
	
function applications_list($cmd) {

	$apps = applications_known();
	$count=0;
	// Run through them all
	foreach ($apps as $app) {
		// Has their command been changed?
		if ($appcmd = applications_getcmd($app['func'])) 
			$apps[$count]['appcmd'] = $appcmd;
		// Is it enabled?
		if (applications_enabled($app['func']))
			$apps[$count]['enabled'] = 'CHECKED';
		else
			$apps[$count]['enabled'] = '';
		$count++;
	}
	// Check to see if we want 'all' or just the ones that are enabled
	if ($cmd == "enabled") {
		foreach($apps as $app) {
			if (strlen($app['enabled']))  
				$ret[] = $app;
		}
		if (isset($ret)) 
			return $ret;
		else
			return null;
	} else 
		return $apps;
}


function applications_init() {
	global $db;

	// This could cause grief on a slow machine. It's run every time the applications page
	// is loaded... But it's the only way I can think of to make sure that the latest 
	// apps are in the database.

	$apps = applications_list("all");
	foreach ($apps as $app) {
		// If this does become an issue, do a select name from app, then join them all together?
		// I can't see it becoming too huge. There's not THAT many things you can do with a phone system 8)
		$res = $db->getRow("SELECT func from applications where func='{$app['func']}'");
		if (!isset($res[0])) 
			sql("INSERT INTO applications (func, name, appcmd, enabled) VALUES ('".$app['func']."','".$app['name']."', '".$app['appcmd']."', 'CHECKED')");
	}
}

function applications_get_config($engine) {
	// This generates the dialplan
	global $ext;  
	switch($engine) {
		case "asterisk":
			if(is_array($applicationslist = applications_list("enabled"))) {
				foreach($applicationslist as $item) {
					$fname = 'application_'.$item['func'];
					if (function_exists($fname)) {
						$fname($item['func']);
					} else {
						$ext->add('from-internal-additional', 'debug', '', new ext_noop("No func $fname"));
					}	
				}
			}
		break;
	}
}

function applications_enabled($app) {
	global $db;

	$res = $db->getRow("SELECT enabled FROM applications WHERE func='{$app}'");
	if (strlen($res[0]))
		return true;
	else
		return false;
}

function applications_getcmd($app) {
	global $db;

	$res = $db->getRow("SELECT appcmd FROM applications WHERE func='{$app}'");
	if (isset($res[0])) 
		return $res[0];
	else
		return null;
}

?>
	

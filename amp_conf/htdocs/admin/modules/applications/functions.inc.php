<?php 

	
function applications_list($cmd) {
	$apps = array (
	// Update this when new applications are created/added
	// Format of 'Default Command', 'Descriptive Name, 'Function Name'
		// Create a function 'application_($func}'
		array('appcmd'=>'#', 'name'=>'Directory',  'func'=>'app_directory'),
		// See application_app_directory for instructions
		array('appcmd'=>'*78', 'name'=>'DND Activate', 'func'=>'app_dnd_on'),
		// Note, you can't use 'app-dnd-off' - they have to be underscores.
		array('appcmd'=>'*79', 'name'=>'DND Deactivate', 'func'=>'app_dnd_off'),
		array('appcmd'=>'*98', 'name'=>'My Voicemail', 'func'=>'app_myvoicemail'),
		// This checks for extra numbers dialled after it
		array('appcmd'=>'*96', 'name'=>'Dial Voicemail', 'func'=>'app_dialvoicemail'),
//		array('appcmd'=>'*97', 'name'=>'Message Center', 'func'=>'app_voicemail'),
		// This one generates a seperate context.
		array('appcmd'=>'*69', 'name'=>'Call Trace', 'func'=>'app_calltrace'),
		array('appcmd'=>'*70', 'name'=>'Call Waiting Activate', 'func'=>'app_cwon'),
		array('appcmd'=>'*71', 'name'=>'Call Waiting Deactivate', 'func'=>'app_cwoff'),
		array('appcmd'=>'*72', 'name'=>'Call Forward Activate', 'func'=>'app_cfon'),
		array('appcmd'=>'*72', 'name'=>'Call Forward Prompting Activate', 'func'=>'app_cfon_any'),
		array('appcmd'=>'*73', 'name'=>'Call Forward Deactivate', 'func'=>'app_cfoff'),
		array('appcmd'=>'*90', 'name'=>'Call Forward Busy Activate', 'func'=>'app_cfbon'),
		array('appcmd'=>'*91', 'name'=>'Call Forward Busy Deactive', 'func'=>'app_cfboff')
	);

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
			require "applications.inc.php";
			if(is_array($applicationslist = applications_list("enabled"))) {
				foreach($applicationslist as $item) {
					$fname = 'application_'.$item['func'];
					if (function_exists($fname)) {
						$fname("genconf");
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
	

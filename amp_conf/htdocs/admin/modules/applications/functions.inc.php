<?php 

	
function applications_list($cmd) {
	$apps = array (
	// Update this when new applications are created/added
	// Format of 'Default Command', 'Descriptive Name, 'Function Name'
		// Create a function 'application_($func}'
		array('appcmd'=>'#', 'name'=>'Directory',  'func'=>'app_directory'),
		// See application_app-directory for instructions
		array('appcmd'=>'*78', 'name'=>'DND Activate', 'func'=>'app_dnd_on'),
		// Note, you can't use 'app-dnd-off' - they have to be underscores.
		array('appcmd'=>'*79', 'name'=>'DND Deactivate', 'func'=>'app_dnd_off'),
		array('appcmd'=>'*98', 'name'=>'My Voicemail', 'func'=>'app_myvoicemail'),
		array('appcmd'=>'*97', 'name'=>'Message Center', 'func'=>'app_voicemail')
	);

	$count=0;
	// Run through them all
	foreach ($apps as $app) {
		// Has their command been changed?
		if ($appcmd = applications_getcmd($app['name'])) 
			$apps[$count]['appcmd'] = $appcmd;
		// Is it enabled?
		if (applications_enabled($app['name']))
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
		$res = $db->getRow("SELECT name from applications where name='{$app['name']}'");
		if (!isset($res[0])) 
			sql("INSERT INTO applications (name, appcmd, enabled) VALUES ('".$app['name']."', '".$app['appcmd']."', '')");
	}
}

function applications_get_config($engine) {
	// This generates the dialplan
	global $ext;  
	switch($engine) {
		case "asterisk":
			if(is_array($applicationslist = applications_list("enabled"))) {
				foreach($applicationslist as $item) {
					if (function_exists('application_'.$item['name'])) {
						'application_'.$item['name']("genconf");
					}
				}
			}
		break;
	}
}

function applications_enabled($app) {
	global $db;

	$res = $db->getRow("SELECT enabled FROM applications WHERE name='{$app}'");
	if (strlen($res[0]))
		return true;
	else
		return false;
}

function applications_getcmd($app) {
	global $db;

	$res = $db->getRow("SELECT appcmd FROM applications WHERE name='{$app}'");
	if (isset($res[0])) 
		return $res[0];
	else
		return null;
}
	

// Create Applications here
function application_app_directory($cmd) {
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover
	if ($cmd == 'info') 
		return _("Access to the internal directory");
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		global $ext;
		
		// Change the following two lines
		$appname = "Directory"; // This is my name. Used for sql commands
		$id = "app-directory"; // The context to be included

		$cmd = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal', $appcontext); // Add the include from from-internal
		// Build the context
		$ext->add($id, $cmd, '', new ext_wait('1')); // $cmd,1,Wait(1)
		$ext->add($id, $cmd, '', new ext_agi('directory,${DIR-CONTEXT},ext-local,${DIRECTORY:0:1}${DIRECTORY_OPTS}o'));
		$ext->add($id, $cmd, '', new ext_playback('vm-goodbye')); // $cmd,n,Playback(vm-goodbye)
		$ext->add($id, $cmd, '', new ext_hangup());
	}
}


		
		
	




	
	
	
?>

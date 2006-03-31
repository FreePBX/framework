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
		// This checks for extra numbers dialled after it
		array('appcmd'=>'*96', 'name'=>'Dial Voicemail', 'func'=>'app_dialvoicemail'),
		array('appcmd'=>'*97', 'name'=>'Message Center', 'func'=>'app_voicemail'),
		// This generates its own macro
		array('appcmd'=>'*69', 'name'=>'Call Trace', 'func'=>'app_calltrace'),
		array('appcmd'=>'*70', 'name'=>'Call Waiting Activate', 'func'=>'app_cwon'),
		array('appcmd'=>'*71', 'name'=>'Call Waiting Deactivate', 'func'=>'app_cwoff'),
		array('appcmd'=>'*72', 'name'=>'Call Forward Activate', 'func'=>'app_cfon'),
		array('appcmd'=>'*73', 'name'=>'Call Forward Deactivate', 'func'=>'app_cfoff'),
		array('appcmd'=>'*90', 'name'=>'Call Forward Busy Activate', 'func'=>'app_cfbon'),
		array('appcmd'=>'*91', 'name'=>'Call Forward Busy Deactive', 'func'=>'app_cfboff')
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
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following two lines
	$appname = "Directory"; // This is my name. Used for sql commands
	$id = "app-directory"; // The context to be included
	$descr = "Access to the internal directory";

	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		

		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal
		// Build the context
		$ext->add($id, $c, '', new ext_wait('1')); // $cmd,1,Wait(1)
		$ext->add($id, $c, '', new ext_agi('directory,${DIR-CONTEXT},ext-local,${DIRECTORY:0:1}${DIRECTORY_OPTS}o'));
		$ext->add($id, $c, '', new ext_playback('vm-goodbye')); // $cmd,n,Playback(vm-goodbye)
		$ext->add($id, $c, '', new ext_hangup());
	}
}

function application_app_dnd_on($cmd) {
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following three lines
	$appname = "DND Activate"; // This is my name. Used for sql commands
	$id = "app-dnd-on"; // The context to be included
	$descr = "Turn on Do Not Disturb"; // The small help text to be used as a mouseover

	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

		// This is where you build the context
		$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
		$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
		$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
		$ext->add($id, $c, '', new ext_setvar('DB(DND/${CALLERID(number)})', 'YES')); // $cmd,n,Set(...=YES)
		$ext->add($id, $c, '', new ext_playback('do-not-disturb&activated')); // $cmd,n,Playback(...)
		$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	}
}
		
function application_app_dnd_off($cmd) {
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following three lines
	$appname = "DND Deactivate"; // This is my name. Used for sql commands. MUST MATCH NAME in app_list above!!
	$id = "app-dnd-off"; // The context to be included
	$descr = "Turn off Do Not Disturb"; // The small help text to be used as a mouseover

	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

		// This is where you build the context
		$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
		$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
		$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
		$ext->add($id, $c, '', new ext_dbdel('DND/${CALLERID(number)}')); // $cmd,n,DBdel(..)
		$ext->add($id, $c, '', new ext_playback('do-not-disturb&de-activated')); // $cmd,n,Playback(...)
		$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	}
}
		
	
function application_app_myvoicemail($cmd) {
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following three lines
	$appname = "My Voicemail"; // This is my name. Used for sql commands. MUST MATCH NAME in app_list above!!
	$id = "app-vmmain"; // The context to be included
	$descr = "Check your voicemail. Prompts for password"; // The small help text to be used as a mouseover

	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

		// This is where you build the context
		$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
		$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
		$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
		$ext->add($id, $c, '', new ext_macro('get-vmcontext','${CALLERID(num)}')); 
		$ext->add($id, $c, '', new ext_vmmain('${CALLERID(num)}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
		$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	}
}
		
	

function application_app_dialvoicemail($cmd) {
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following three lines
	$appname = "Dial Voicemail"; // This is my name. Used for sql commands. MUST MATCH NAME in app_list above!!
	$id = "app-dialvm"; // The context to be included
	$descr = "Check voicemail from another xtn by dialing this and then the extension you want to check"; 
	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

		// Note that with this one, it has paramters. So we have to add '_' to the start and '.' to the end
		// of $c
		$c = "_$c.";
		$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
		$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
		// How long is the command? We need to strip that off the front
		$clen = strlen($c)-2;
		$ext->add($id, $c, '', new ext_macro('get-vmcontext','${EXTEN:'.$clen.'}')); 
		$ext->add($id, $c, '', new ext_vmmain('${EXTEN:'.$clen.'}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
		$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	}
}
		
	
function application_app_calltrace($cmd) {
	global $ext;
	// It's called with 'info' or 'genconf'. 'info' should just return a short 
	// text string that is used for the mouseover

	// Change the following three lines
	$appname = "Call Trace"; // This is my name. Used for sql commands. MUST MATCH NAME in app_list above!!
	$id = "app-calltrace"; // The context to be included
	$descr = "Reads out the number of the last caller, and if available, gives you the option to call them back"; 
	if ($cmd == 'info') 
		return _($descr);
	if ($cmd == 'genconf') {
		// Here, we're generating the config file to go in extensions_additional.conf
		$c = applications_getcmd($appname);  // This gets the code to be used
		// Start creating the dialplan
		$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

		$ext->add($id, $c, '', new ext_goto('1', 's', 'app-calltrace-perform')); 

		// Now, we need to create the calltrace application
		$id = 'app-calltrace-perform';
		$c = 's';
		$ext->add($id, $c, '', new ext_macro('user-callerid')); 
		$ext->add($id, $c, '', new ext_answer());
		$ext->add($id, $c, '', new ext_wait('1'));
		$ext->add($id, $c, '', new ext_background('info-about-last-call&telephone-number'));
		$ext->add($id, $c, '', new ext_setvar('lastcaller', '${DB(CALLTRACE/${CALLERID(number)})}'));
		$ext->add($id, $c, '', new ext_gotoif('$[ "${lastcaller}" = "" ]', 'noinfo'));
		$ext->add($id, $c, 'ok', new ext_saydigits('${lastcaller}'));
		$ext->add($id, $c, '', new ext_setvar('TIMEOUT(digit)', '3'));
		$ext->add($id, $c, '', new ext_setvar('TIMEOUT(response)', '7'));
		$ext->add($id, $c, '', new ext_background('to-call-this-number&press-1'));
		$ext->add($id, $c, '', new ext_goto('fin'));
		$ext->add($id, $c, 'noinfo', new ext_playback('from-unknown-caller'));
		$ext->add($id, $c, '', new ext_macro('hangupcall')); 
		$ext->add($id, $c, 'fin', new ext_noop('Waiting for input'));
		$ext->add($id, '1', '', new ext_goto('1', '${lastcaller}', 'from-internal'));
		$ext->add($id, 'i', '', new ext_playback('vm-goodbye')); 
		$ext->add($id, 'i', '', new ext_macro('hangupcall')); 
		$ext->add($id, 't', '', new ext_playback('vm-goodbye')); 
		$ext->add($id, 't', '', new ext_macro('hangupcall')); 

		// How long is the command? We need to strip that off the front
		$clen = strlen($c)-2;
		$ext->add($id, $c, '', new ext_macro('get-vmcontext','${EXTEN:'.$clen.'}')); 
		$ext->add($id, $c, '', new ext_vmmain('${EXTEN:'.$clen.'}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
		$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	}
}

?>

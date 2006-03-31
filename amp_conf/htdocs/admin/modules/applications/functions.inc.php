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
			sql("INSERT INTO applications (func, name, appcmd, enabled) VALUES ('".$app['func']."','".$app['name']."', '".$app['appcmd']."', '')");
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
	

// Create Applications here
function application_app_directory() {
	global $ext;

	$id = "app-directory"; // The context to be included. This must be unique.

	// Here, we're generating the config file to go in extensions_additional.conf
	$c = applications_getcmd($appname);  // This gets the 'dial' code to be used
	// Start creating the dialplan
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal
	// Build the context
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,1,Wait(1)
	$ext->add($id, $c, '', new ext_agi('directory,${DIR-CONTEXT},ext-local,${DIRECTORY:0:1}${DIRECTORY_OPTS}o')); // AGI
	$ext->add($id, $c, '', new ext_playback('vm-goodbye')); // $cmd,n,Playback(vm-goodbye)
	$ext->add($id, $c, '', new ext_hangup()); // hangup
}

function application_app_dnd_on() {
	global $ext;

	$id = "app-dnd-on"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(DND/${CALLERID(number)})', 'YES')); // $cmd,n,Set(...=YES)
	$ext->add($id, $c, '', new ext_playback('do-not-disturb&activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
		
function application_app_dnd_off() {
	global $ext;

	$id = "app-dnd-off"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('DND/${CALLERID(number)}')); // $cmd,n,DBdel(..)
	$ext->add($id, $c, '', new ext_playback('do-not-disturb&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
	
function application_app_myvoicemail() {
	global $ext;

	$id = "app-vmmain"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_macro('get-vmcontext','${CALLERID(num)}')); 
	$ext->add($id, $c, '', new ext_vmmain('${CALLERID(num)}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_dialvoicemail() {
	global $ext;

	$id = "app-dialvm"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
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

function application_app_calltrace() {
	global $ext;

	$id = "app-calltrace"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_goto('1', 's', 'app-calltrace-perform')); 

	// Create the calltrace application, which we are doing a 'Goto' to above.
	// I just reset these for ease of copying and pasting. 
	$id = 'app-calltrace-perform';
	$c = 's';
	$ext->add($id, $c, '', new ext_macro('user-callerid')); 
	$ext->add($id, $c, '', new ext_answer());
	$ext->add($id, $c, '', new ext_wait('1'));
	$ext->add($id, $c, '', new ext_background('info-about-last-call&telephone-number'));
	$ext->add($id, $c, '', new ext_setvar('lastcaller', '${DB(CALLTRACE/${CALLERID(number)})}'));
	$ext->add($id, $c, '', new ext_gotoif('$[ "${lastcaller}" = "" ]', 'noinfo'));
	$ext->add($id, $c, '', new ext_saydigits('${lastcaller}'));
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

}


function application_app_cwon() {
	global $ext;

	$id = "app-cw-on"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(CW/${CALLERID(number)})', 'ENABLED')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}


function application_app_cwoff() {
	global $ext;

	$id = "app-cw-off"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CW/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfon_any() {
	global $ext;

	$id = "app-cf-on-any"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('please-enter-your&extension'));
	$ext->add($id, $c, '', new ext_read('fromext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('ent-target-attendant'));
	$ext->add($id, $c, '', new ext_read('toext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_setvar('DB(CF/${fromext})', '${toext}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-unconditional&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${fromext}'));
	$ext->add($id, $c, '', new ext_playback('is-set-to'));
	$ext->add($id, $c, '', new ext_saydigits('${toext}'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfoff($cmd) {
	global $ext;

	$id = "app-cf-off"; // The context to be included

	$c = applications_getcmd($appname);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CF/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

?>

<?php 

function applications_known() {
	return array (
        // Update this when new applications are created/added. The database is updated automatically when 
	// a new one is entered here.

        // Format is 'Default Command', 'Descriptive Name, 'Function Name'
                // Create a function 'application_($func}'
                array('appcmd'=>'#', 'name'=>'Directory',  'func'=>'app_directory'),
                // See application_app_directory for instructions
                array('appcmd'=>'*78', 'name'=>'DND Activate', 'func'=>'app_dnd_on'),
                // Note, you can't use 'app-dnd-off' - they have to be underscores.
                array('appcmd'=>'*79', 'name'=>'DND Deactivate', 'func'=>'app_dnd_off'),
                array('appcmd'=>'*98', 'name'=>'My Voicemail', 'func'=>'app_myvoicemail'),
                // This checks for extra numbers dialled after it
                array('appcmd'=>'*96', 'name'=>'Dial Voicemail', 'func'=>'app_dialvoicemail'),
//              array('appcmd'=>'*97', 'name'=>'Message Center', 'func'=>'app_voicemail'),
                // This one generates a seperate context.
                array('appcmd'=>'*69', 'name'=>'Call Trace', 'func'=>'app_calltrace'),
                array('appcmd'=>'*70', 'name'=>'Call Waiting Activate', 'func'=>'app_cwon'),
                array('appcmd'=>'*71', 'name'=>'Call Waiting Deactivate', 'func'=>'app_cwoff'),
                array('appcmd'=>'*72', 'name'=>'Call Forward All Activate', 'func'=>'app_cfon'),
                array('appcmd'=>'*72', 'name'=>'Call Forward All Prompting Activate', 'func'=>'app_cfon_any'),
                array('appcmd'=>'*73', 'name'=>'Call Forward All Deactivate', 'func'=>'app_cfoff'),
                array('appcmd'=>'*74', 'name'=>'Call Forward All Prompting Deativate', 'func'=>'app_cfoff_any'),
                array('appcmd'=>'*90', 'name'=>'Call Forward Busy Activate', 'func'=>'app_cfbon'),
                array('appcmd'=>'*90', 'name'=>'Call Forward Busy Prompting Activate', 'func'=>'app_cfbon_any'),
                array('appcmd'=>'*91', 'name'=>'Call Forward Busy Deactive', 'func'=>'app_cfboff'),
                array('appcmd'=>'*92', 'name'=>'Call Forward Busy Prompting Deactive', 'func'=>'app_cfboff_any'),
                array('appcmd'=>'*43', 'name'=>'Echo Test', 'func'=>'app_echo_test'),
        );
}

// Create Applications here
function application_app_directory($f) {
	global $ext;

	$id = "app-directory"; // The context to be included. This must be unique.

	// Here, we're generating the config file to go in extensions_additional.conf
	$c = applications_getcmd($f);  // This gets the 'dial' code to be used
	// Start creating the dialplan
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal
	// Build the context
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,1,Wait(1)
	$ext->add($id, $c, '', new ext_agi('directory,${DIR-CONTEXT},ext-local,${DIRECTORY:0:1}${DIRECTORY_OPTS}o')); // AGI
	$ext->add($id, $c, '', new ext_playback('vm-goodbye')); // $cmd,n,Playback(vm-goodbye)
	$ext->add($id, $c, '', new ext_hangup()); // hangup
}

function application_app_dnd_on($f) {
	global $ext;

	$id = "app-dnd-on"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(DND/${CALLERID(number)})', 'YES')); // $cmd,n,Set(...=YES)
	$ext->add($id, $c, '', new ext_playback('do-not-disturb&activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
		
function application_app_dnd_off($f) {
	global $ext;

	$id = "app-dnd-off"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('DND/${CALLERID(number)}')); // $cmd,n,DBdel(..)
	$ext->add($id, $c, '', new ext_playback('do-not-disturb&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
	
function application_app_myvoicemail($f) {
	global $ext;

	$id = "app-vmmain"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_macro('get-vmcontext','${CALLERID(num)}')); 
	$ext->add($id, $c, '', new ext_vmmain('${CALLERID(num)}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_dialvoicemail($f) {
	global $ext;

	$id = "app-dialvm"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
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

function application_app_calltrace($f) {
	global $ext;

	$id = "app-calltrace"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
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

function application_app_cwon($f) {
	global $ext;

	$id = "app-cw-on"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(CW/${CALLERID(number)})', 'ENABLED')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}


function application_app_cwoff($f) {
	global $ext;

	$id = "app-cw-off"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CW/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfon($f) {
	global $ext;

	$id = "app-cf-on"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$clen = strlen($c);
	$c = "_$c.";
	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(CF/${CALLERID(number)})', '${EXTEN:'.$clen.'}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-unconditional&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${CALLERID(number)}'));
	$ext->add($id, $c, '', new ext_playback('is-set-to'));
	$ext->add($id, $c, '', new ext_saydigits('${EXTEN:'.$clen.'}'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
function application_app_cfon_any($f) {
	global $ext;

	$id = "app-cf-on-any"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
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

function application_app_cfoff_any($f) {
	global $ext;

	$id = "app-cf-off-any"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('please-enter-your&extension'));
	$ext->add($id, $c, '', new ext_read('fromext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_dbdel('DB(CF/${fromext})')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-unconditional&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${fromext}'));
	$ext->add($id, $c, '', new ext_playback('cancelled'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfoff($f) {
	global $ext;

	$id = "app-cf-off"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CF/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-unconditional&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfbon($f) {
	global $ext;

	$id = "app-cf-busy-on"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$clen = strlen($c);
	$c = "_$c.";
	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(CFB/${CALLERID(number)})', '${EXTEN:'.$clen.'}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-on-busy&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${CALLERID(number)}'));
	$ext->add($id, $c, '', new ext_playback('is-set-to'));
	$ext->add($id, $c, '', new ext_saydigits('${EXTEN:'.$clen.'}'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
function application_app_cfbon_any($f) {
	global $ext;

	$id = "app-cf-busy-on-any"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('please-enter-your&extension'));
	$ext->add($id, $c, '', new ext_read('fromext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('ent-target-attendant'));
	$ext->add($id, $c, '', new ext_read('toext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_setvar('DB(CFB/${fromext})', '${toext}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-on-busy&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${fromext}'));
	$ext->add($id, $c, '', new ext_playback('is-set-to'));
	$ext->add($id, $c, '', new ext_saydigits('${toext}'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfboff_any($f) {
	global $ext;

	$id = "app-cf-busy-off-any"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_background('please-enter-your&extension'));
	$ext->add($id, $c, '', new ext_read('fromext', 'then-press-pound'));
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_dbdel('DB(CFB/${fromext})')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-on-busyl&for&extension'));
	$ext->add($id, $c, '', new ext_saydigits('${fromext}'));
	$ext->add($id, $c, '', new ext_playback('cancelled'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_cfboff($f) {
	global $ext;

	$id = "app-cf-busy-off"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CFB/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-fwd-on-busy&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function application_app_echo_test($f) {
	global $ext;

	$id = "app-echo-test"; // The context to be included

	$c = applications_getcmd($f);  // This gets the code to be used
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer()); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_playback('demo-echotest')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_echo()); 
	$ext->add($id, $c, '', new ext_playback('demo-echodone')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_hangup()); // $cmd,n,Macro(user-callerid)
}

?>

?>

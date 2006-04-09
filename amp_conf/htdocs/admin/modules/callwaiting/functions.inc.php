<?php

function callwaiting_get_config($engine) {
	$modulename = 'callwaiting';
	
	// This generates the dialplan
	global $ext;  
	switch($engine) {
		case "asterisk":
			if (is_array($featurelist = featurecodes_getModuleFeatures($modulename))) {
				foreach($featurelist as $item) {
					$featurename = $item['featurename'];
					$fname = $modulename.'_'.$featurename;
					if (function_exists($fname)) {
						$fcc = new featurecode($modulename, $featurename);
						$fc = $fcc->getCodeActive();
						unset($fcc);
						
						if ($fc != '')
							$fname($fc);
					} else {
						$ext->add('from-internal-additional', 'debug', '', new ext_noop($modulename.": No func $fname"));
						var_dump($item);
					}	
				}
			}
		break;
	}
}

function callwaiting_cwon($c) {
	global $ext;

	$id = "app-callwaiting-cwon"; // The context to be included

	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer('')); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_setvar('DB(CW/${CALLERID(number)})', 'ENABLED')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}


function callwaiting_cwoff($c) {
	global $ext;

	$id = "app-callwaiting-cwoff"; // The context to be included

	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer('')); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_dbdel('CW/${CALLERID(number)}')); 
	$ext->add($id, $c, '', new ext_playback('call-waiting&de-activated')); // $cmd,n,Playback(...)
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}
?>

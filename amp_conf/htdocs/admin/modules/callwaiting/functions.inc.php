<?php

// FOLLOWING FUNCTION WILL BE STRIPPED OUT TO A SEPERATE FILE ONCE RYAN
// CHANGES THE MODULE INSTALL PROCESS TO RUN A .PHP FILE AS WELL AS A .SQL FILE
if ($_REQUEST['testfeaturecodes'] == "yes") {
	callwaiting_install();
	?>
	<script language="javascript">
	alert("callwaiting_install ran!");
	</script>
	<?php
}
function callwaiting_install() {
	// Register FeatureCode - Activate
	$fcc = new featurecode('callwaiting', 'cwon');
	$fcc->setDescription('Call Waiting - Activate');
	$fcc->setDefault('*70');
	$fcc->update();
	unset($fcc);

	// Register FeatureCode - Deactivate
	$fcc = new featurecode('callwaiting', 'cwoff');
	$fcc->setDescription('Call Waiting - Deactivate');
	$fcc->setDefault('*71');
	$fcc->update();
	unset($fcc);	
}

function callwaiting_get_config($engine) {
	$modulename = 'callwaiting';
	
	// This generates the dialplan
	global $ext;  
	switch($engine) {
		case "asterisk":
			if (is_array($featurelist = featurecodes_getModuleFeatures('callwaiting'))) {
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
						$ext->add('from-internal-additional', 'debug', '', new ext_noop("No func $fname"));
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

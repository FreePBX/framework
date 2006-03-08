<?php /* $id$ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ivr_destinations() {
	//get the list of meetmes
	$results = ivr_list();
	
	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => $result[0].',s,1', 'description' => $result['1']);
		}
	}
	
	return $extens;
}

/* 	Generates dialplan for conferences
	We call this with retrieve_conf
*/
function ivr_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $conferences_conf;
	switch($engine) {
		case "asterisk":
			$ivrlist = ivr_list();
			if(is_array($ivrlist)) {
				foreach($ivrlist as $item) {
					$ivr = ivr_get_s(ltrim($item['0']));
					// add dialplan
					$ext->addInclude($item[0],'ext-local');
					$ext->addInclude($item[0],'app-messagecenter');
					$ext->addInclude($item[0],'app-directory');
					$ext->add($item[0], 'h', '', new ext_hangup(''));
					$ext->add($item[0], 'i', '', new ext_playback('invalid'));
					$ext->add($item[0], 'i', '', new ext_goto('7','s'));
					
					$ext->add($item[0], 's', '', new ext_gotoif('$["foo${DIALSTATUS}" = "foo"]','3'));
					$ext->add($item[0], 's', '', new ext_gotoif('$[${DIALSTATUS} = ANSWER]','5'));
					$ext->add($item[0], 's', '', new ext_answer(''));
					$ext->add($item[0], 's', '', new ext_wait('1'));
					$ext->add($item[0], 's', '', new ext_setvar('LOOPED','1'));
					$ext->add($item[0], 's', 'LOOP', new ext_gotoif('$[${LOOPED} > 2]','hang,1'));
					$ext->add($item[0], 's', '', new ext_setvar('DIR-CONTEXT',substr($ivr[5][4],12)));
					$ext->add($item[0], 's', '', new ext_digittimeout('3'));
					$ext->add($item[0], 's', '', new ext_responsetimeout('7'));
					$ext->add($item[0], 's', '', new ext_background($ivr[8][4]));
					
					$ext->add($item[0], 'hang', '', new ext_playback('vm-goodbye'));
					$ext->add($item[0], 'hang', '', new ext_hangup(''));
					
					$default_t=true;
					// Actually add the IVR commands now.
					foreach(ivr_get($item[0]) as $ivr_item) {
						if (preg_match("/[0-9*#]/", $ivr_item[1])) {
							$ext->add($item[0], $ivr_item[1],'', new ext_goto($ivr_item[4]));
						}
						// check for user timeout setting
						if ($ivr_item[1]=="t" && $ivr_item[2]=="1" && $ivr_item[3]=="Goto") {
							$ext->add($item[0], $ivr_item[1],'', new ext_goto($ivr_item[4]));
							$default_t = false;
						}
					}
					//apply default timeout if needed
					if($default_t) {
						$ext->add($item[0], 't', '', new ext_setvar('LOOPED','$[${LOOPED} + 1]'));
						$ext->add($item[0], 't', '', new ext_goto('LOOP'));				
					}
				}
			}
		break;
	}
}

//Return an array of commands for this IVR
function ivr_get($menu_id) {
	global $db;
	$sql = "SELECT * FROM extensions WHERE context = '".$menu_id."' ORDER BY extension";
	$aalines = $db->getAll($sql);
	if(DB::IsError($aalines)) {
		die('aalines: '.$aalines->getMessage());
	}
	return $aalines;
}

//get only 's' extension info about auto-attendant
function ivr_get_s($menu_id) {
	global $db;
	$sql = "SELECT * FROM extensions WHERE context = '".$menu_id."' AND extension = 's' ORDER BY extension";
	$aalines = $db->getAll($sql);
	if(DB::IsError($aalines)) {
		die('aalines: '.$aalines->getMessage());
	}
	return $aalines;
}

//get unique voice menu numbers - returns 2 dimensional array
function ivr_list() {
	global $db;
	$dept = str_replace(' ','_',$_SESSION["AMP_user"]->_deptname);
	if (empty($dept)) $dept='%';  //if we are not restricted to dept (ie: admin), then display all AA menus
	$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context,priority";
	$unique_aas = $db->getAll($sql);
	if(DB::IsError($unique_aas)) {
	   die('unique: '.$unique_aas->getMessage().'<hr>'.$sql);
	}
	return $unique_aas;
}

?>

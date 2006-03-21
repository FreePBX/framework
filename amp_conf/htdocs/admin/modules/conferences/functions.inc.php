<?php /* $Id$ */

// extend extensions class.
// This example is about as simple as it gets
class conferences_conf {
	// return the filename to write
	function get_filename() {
		return "meetme_additional.conf";
	}
	function addMeetme($room, $pin) {
		$this->_meetmes[$room] = $pin;
	}
	// return the output that goes in the file
	function generateConf() {
		$output = "";
		if (is_array($this->_meetmes)) {
			foreach (array_keys($this->_meetmes) as $meetme) {
				$output .= 'conf => '.$meetme."|".$this->_meetmes[$meetme]."\n";
			}
		}
		return $output;
	}
}

// returns a associative arrays with keys 'destination' and 'description'
function conferences_destinations() {
	//get the list of meetmes
	$results = conferences_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-meetme,'.$result['0'].',1', 'description' => $result['1']." <".$result['0'].">");
		}
	return $extens;
	} else {
	return null;
	}
}


/* 	Generates dialplan for conferences
	We call this with retrieve_conf
*/
function conferences_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $conferences_conf;
	switch($engine) {
		case "asterisk":
			$ext->addInclude('from-internal-additional','ext-meetme');
			$contextname = 'ext-meetme';
			if(is_array($conflist = conferences_list())) {
				foreach($conflist as $item) {
					$room = conferences_get(ltrim($item['0']));
					
					$roomnum = ltrim($item['0']);
					$roomoptions = $room['options'];
					$roomuserpin = $room['userpin'];
					$roomadminpin = $room['adminpin'];
					$roomjoinmsg = $room['joinmsg'];
					
					// entry point
					$ext->add($contextname, $roomnum, '', new ext_setvar('MEETME_ROOMNUM',$roomnum));
					$ext->add($contextname, $roomnum, '', new ext_gotoif('$[${DIALSTATUS} = ANSWER]',($roomuserpin == '' && $roomadminpin == '' ? 'USER' : 'READPIN')));			
					$ext->add($contextname, $roomnum, '', new ext_answer(''));
					$ext->add($contextname, $roomnum, '', new ext_wait(1));
					
					// Deal with PINs -- if exist
					if ($roomuserpin != '' || $roomadminpin != '') {
						$ext->add($contextname, $roomnum, 'READPIN', new ext_read('PIN','enter-conf-pin-number'));
						
						// userpin -- must do always, otherwise if there is just an adminpin
						// there would be no way to get to the conference !
						$ext->add($contextname, $roomnum, '', new ext_gotoif('$[foo${PIN} = foo'.$roomuserpin.']','USER'));

						// admin pin -- exists
						if ($roomadminpin != '') {
							$ext->add($contextname, $roomnum, '', new ext_gotoif('$[${PIN} = '.$roomadminpin.']','ADMIN'));
						}

						// pin invalid
						$ext->add($contextname, $roomnum, '', new ext_playback('conf-invalidpin'));
						$ext->add($contextname, $roomnum, '', new ext_goto('READPIN'));
						
						// admin mode -- only valid if there is an admin pin
						if ($roomadminpin != '') {
							$ext->add($contextname, $roomnum, 'ADMIN', new ext_setvar('MEETME_OPTS','aA'.$roomoptions));
							if ($roomjoinmsg != "") {  // play joining message if one defined
								$ext->add($contextname, $roomnum, '', new ext_playback($roomjoinmsg));
							}
							$ext->add($contextname, $roomnum, '', new ext_goto('STARTMEETME,1'));							
						}
					}
					
					// user mode
					$ext->add($contextname, $roomnum, 'USER', new ext_setvar('MEETME_OPTS',$roomoptions));
					if ($roomjoinmsg != "") {  // play joining message if one defined
						$ext->add($contextname, $roomnum, '', new ext_playback($roomjoinmsg));
					}
					$ext->add($contextname, $roomnum, '', new ext_goto('STARTMEETME,1'));
					
					// add meetme config
					$conferences_conf->addMeetme($room['exten'],$room['userpin']);
				}
			}

			// Start the conference
			$ext->add($contextname, 'STARTMEETME', '', new ext_meetme('${MEETME_ROOMNUM}','${MEETME_OPTS}','${PIN}'));
			$ext->add($contextname, 'STARTMEETME', '', new ext_hangup(''));
			
			// hangup for whole context
			$ext->add($contextname, 'h', '', new ext_hangup(''));			
		break;
	}
}

//get the existing meetme extensions
function conferences_list() {
	$results = sql("SELECT exten,description FROM meetme ORDER BY exten","getAll",DB_FETCHMODE_ASSOC);
	foreach($results as $result){
		// check to see if we are in-range for the current AMP User.
		if (isset($result['exten']) && checkRange($result['exten'])){
			// return this item's dialplan destination, and the description
			$extens[] = array($result['exten'],$result['description']);
		}
	}
	if (isset($extens)) {
		return $extens;
	} else {
		return null;
	}
}

function conferences_get($account){
	//get all the variables for the meetme
	$results = sql("SELECT exten,options,userpin,adminpin,description,joinmsg FROM meetme WHERE exten = '$account'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function conferences_del($account){
	$results = sql("DELETE FROM meetme WHERE exten = \"$account\"","query");
}

function conferences_add($account,$name,$userpin,$adminpin,$options,$joinmsg=null){
	$results = sql("INSERT INTO meetme (exten,description,userpin,adminpin,options,joinmsg) values (\"$account\",\"$name\",\"$userpin\",\"$adminpin\",\"$options\",\"$joinmsg\")");
}
?>

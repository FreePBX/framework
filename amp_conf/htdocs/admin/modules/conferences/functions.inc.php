<?php /* $Id */

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
			if(is_array($conflist = conferences_list())) {
				foreach($conflist as $item) {
					$room = conferences_get(ltrim($item['0']));
					// add dialplan
					//$ext->add('ext-meetme', ltrim($item['0']), '', new ext_macro('joinmeetme',"{$room['exten']},{$room['options']}"));
					$ext->add('ext-meetme', ltrim($item['0']), '', new ext_macro('joinmeetme',"{$room['exten']},{$room['options']},{$room['userpin']},{$room['adminpin']}"));
					
					// add meetme config
					$conferences_conf->addMeetme($room['exten'],$room['userpin']);
				}
			}
			// now add our macros
			//;arg1=roomnum, arg2=options, arg3=userpin, arg4=adminpin
			$ext->add('macro-joinmeetme', 's', '', new ext_gotoif('$[${DIALSTATUS} = ANSWER]','READ'));
			$ext->add('macro-joinmeetme', 's', '', new ext_answer(''));
			$ext->add('macro-joinmeetme', 's', '', new ext_wait(1));
			$ext->add('macro-joinmeetme', 's', 'READ', new ext_read('PIN','enter-conf-pin-number'));
			$ext->add('macro-joinmeetme', 's', '', new ext_gotoif('$[foo${PIN} = foo${ARG3}]','user,1'));
			$ext->add('macro-joinmeetme', 's', '', new ext_gotoif('$[${PIN} = ${ARG4}]','admin,1'));
			$ext->add('macro-joinmeetme', 's', '', new ext_playback('conf-invalidpin'));
			$ext->add('macro-joinmeetme', 's', '', new ext_goto('READ'));
			$ext->add('macro-joinmeetme', 'user', '', new ext_meetme('${ARG1}','${ARG2}','${ARG3}'));
			$ext->add('macro-joinmeetme', 'admin', '', new ext_meetme('${ARG1}','aA${ARG2}','${ARG4}'));
			$ext->add('macro-joinmeetme', 'h', '', new ext_hangup(''));			
		break;
	}
}

//get the existing meetme extensions
function conferences_list() {
	$results = sql("SELECT exten,description FROM meetme","getAll",DB_FETCHMODE_ASSOC);
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
	$results = sql("SELECT exten,options,userpin,adminpin,description FROM meetme WHERE exten = '$account'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function conferences_del($account){
	$results = sql("DELETE FROM meetme WHERE exten = \"$account\"","query");
}

function conferences_add($account,$name,$userpin,$adminpin,$options){
	$results = sql("INSERT INTO meetme (exten,description,userpin,adminpin,options) values (\"$account\",\"$name\",\"$userpin\",\"$adminpin\",\"$options\")");
}
?>

<?php /* $Id */

// returns a associative arrays with keys 'destination' and 'description'
function timeconditions_destinations() {
	//get the list of meetmes
	$results = timeconditions_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'timeconditions,'.$result['timeconditions_id'].',1', 'description' => $result['displayname']);
		}
		return $extens;
	} else {
		return null;
	}
}


/* 	Generates dialplan for conferences
	We call this with retrieve_conf
*/
function timeconditions_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $conferences_conf;
	switch($engine) {
		case "asterisk":
			$timelist = timeconditions_list();
			if(is_array($timelist)) {
				foreach($timelist as $item) {
					$thisitem = timeconditions_get(ltrim($item['timeconditions_id']));
					// add dialplan
					$ext->add('timeconditions', $item['timeconditions_id'], '', new ext_gotoiftime($item['time'],$item['truegoto']));
					$ext->add('timeconditions', $item['timeconditions_id'], '', new ext_goto($item['falsegoto']));
				}
			}
		break;
	}
}

//get the existing meetme extensions
function timeconditions_list() {
	$results = sql("SELECT * FROM timeconditions","getAll",DB_FETCHMODE_ASSOC);
	if(is_array($results)){
		foreach($results as $result){
			// check to see if we have a dept match for the current AMP User.
			if (checkDept($result['deptname'])){
				// return this item's dialplan destination, and the description
				$allowed[] = $result;
			}
		}
	}
	if (isset($allowed)) {
		return $allowed;
	} else { 
		return null;
	}
}

function timeconditions_get($id){
	//get all the variables for the meetme
	$results = sql("SELECT * FROM timeconditions WHERE timeconditions_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function timeconditions_del($id){
	$results = sql("DELETE FROM timeconditions WHERE timeconditions_id = \"$id\"","query");
}

function timeconditions_add($post){
	if(!timeconditions_chk($post))
		return false;
	extract($post);
	if(empty($displayname)) $displayname = "unnamed";
	$results = sql("INSERT INTO timeconditions (displayname,time,truegoto,falsegoto,deptname) values (\"$displayname\",\"$time\",\"${$goto0.'0'}\",\"${$goto1.'1'}\",\"$deptname\")");
}

function timeconditions_edit($id,$post){
	if(!timeconditions_chk($post))
		return false;
	extract($post);
	if(empty($displayname)) $displayname = "unnamed";
	$results = sql("UPDATE timeconditions SET displayname = \"$displayname\", time = \"$time\", truegoto = \"${$goto0.'0'}\", falsegoto = \"${$goto1.'1'}\", deptname = \"$deptname\" WHERE timeconditions_id = \"$id\"");
}

// ensures post vars is valid
function timeconditions_chk($post){
	return true;
}
?>

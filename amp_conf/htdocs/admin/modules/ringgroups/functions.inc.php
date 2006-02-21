<?php /* $Id$ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ringgroups_destinations() {
	//get the list of ringgroups
	$results = ringgroups_list();
	
	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				ringgroups_get(ltrim($result['0']), $strategy,  $grptime, $grppre, $grplist);
				$extens[] = array('destination' => 'ext-group,'.ltrim($result['0']).',1', 'description' => $grppre.' <'.ltrim($result['0']).'>');
		}
	}
	
	return $extens;
}

/* 	Generates dialplan for ringgroups
	We call this with retrieve_conf
*/
function ringgroups_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	switch($engine) {
		case "asterisk":
			$ext->addInclude('from-internal-additional','ext-group');
			$ringlist = ringgroups_list();
			if (is_array($ringlist)) {
				foreach($ringlist as $item) {
					$exten = ringgroups_get(ltrim($item['0']), $strategy,  $grptime, $grppre, $grplist);
					$ext->add('ext-group', ltrim($item['0']), '', new ext_macro('rg-group',"{$strategy},{$grptime},{$grppre},{$grplist}"));
	
					//get goto for this group - note priority 2
					$goto = legacy_args_get(ltrim($item['0']),2,'ext-group');
					// destination from database is backwards from what ext_goto expects
					$goto_context = strtok($goto,',');
					$goto_exten = strtok(',');
					$goto_pri = strtok(',');
					$ext->add('ext-group', ltrim($item['0']), '', new ext_goto($goto_pri,$goto_exten,$goto_context));
				}
			}
		break;
	}
}
/* 
This module needs to be updated to use it's own 
database table and NOT the 'extensions' table
*/

function ringgroups_add($account,$grplist,$grpstrategy,$grptime,$grppre,$goto) {
	global $db;
	
	//add call to ringgroup macro
	$addarray = array('ext-group',$account,'1','Macro','rg-group,'.$grpstrategy.','.$grptime.','.$grppre.','.$grplist,'','0');
	legacy_extensions_add($addarray);
	
	//add failover goto
	$addarray = array('ext-group',$account,'2','Goto',$goto,'jump','0');
	legacy_extensions_add($addarray);
}

function ringgroups_list() {
	global $db;
	$sql = "SELECT DISTINCT extension FROM extensions WHERE context = 'ext-group' ORDER BY extension";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0]);
		}
	}
	return $extens;
}

function ringgroups_get($grpexten, &$strategy, &$time, &$prefix, &$group) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE context = 'ext-group' AND extension = '".$grpexten."' AND priority = '1'";
	$res = $db->getAll($sql);
	if(DB::IsError($res)) {
	   die($res->getMessage());
	}
	if (preg_match("/^rg-group,(.*),(.*),(.*),(.*)$/", $res[0][0], $matches)) {
		$strategy = $matches[1];
		$time = $matches[2];
		$prefix = $matches[3];
		$group = $matches[4];
		return true;
	} 
	return false;
}
?>
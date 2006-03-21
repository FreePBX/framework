<?php /* $Id$ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ringgroups_destinations() {
	//get the list of ringgroups
	$results = ringgroups_list();
	
	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$thisgrp = ringgroups_get(ltrim($result['0']));
				$extens[] = array('destination' => 'ext-group,'.ltrim($result['0']).',1', 'description' => $thisgrp['grppre'].' <'.ltrim($result['0']).'>');
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
			$contextname = 'ext-group';
			$ringlist = ringgroups_list();
			if (is_array($ringlist)) {
				foreach($ringlist as $item) {
					$grpnum = ltrim($item['0']);
					$grp = ringgroups_get($grpnum);
					
					$strategy = $grp['strategy'];
					$grptime = $grp['grptime'];
					$grplist = $grp['grplist'];
					$postdest = $grp['postdest'];
					$grppre = $grp['grppre'];
					$annmsg = $grp['annmsg']; // This is now a number refercing system recordings
					
					$ext->add($contextname, $grpnum, '', new ext_macro('user-callerid'));
					// check for old prefix
					$ext->add($contextname, $grpnum, '', new ext_gotoif('$[${CALLERID(name):0:${LEN(${RGPREFIX})}} != ${RGPREFIX}]', 'NEWPREFIX'));
					// strip off old prefix
					$ext->add($contextname, $grpnum, '', new ext_setvar('CALLERID(name)','${CALLERID(name):${LEN(${RGPREFIX})}}'));
					// set new prefix
					$ext->add($contextname, $grpnum, 'NEWPREFIX', new ext_setvar('RGPREFIX',$grppre));
					// add prefix to callerid name
					$ext->add($contextname, $grpnum, '', new ext_setvar('CALLERID(name)','${RGPREFIX}${CALLERID(name)}'));
					// recording stuff
					$ext->add($contextname, $grpnum, '', new ext_setvar('RecordMethod','Group'));
					$ext->add($contextname, $grpnum, '', new ext_macro('record-enable','${MACRO_EXTEN},${RecordMethod}'));
					// group dial
					$ext->add($contextname, $grpnum, '', new ext_setvar('RingGroupMethod',$strategy));
					if (isset($annmsg) && $annmsg != '') {
						// I have idea what this is for - we haven't done a DIAL yet??
						// $ext->add($contextname, $grpnum, '', new ext_gotoif('$[${DIALSTATUS} = ANSWER]','DIALGRP'));
						$filename = recordings_get($annmsg);
						$ext->add($contextname, $grpnum, '', new ext_answer(''));
						$ext->add($contextname, $grpnum, '', new ext_wait(1));
						$ext->add($contextname, $grpnum, '', new ext_playback($filename[2]));
					}
					$ext->add($contextname, $grpnum, 'DIALGRP', new ext_macro('dial',$grptime.',${DIAL_OPTIONS},'.$grplist));
					$ext->add($contextname, $grpnum, '', new ext_setvar('RingGroupMethod',''));
					// where next?
					if ((isset($postdest) ? $postdest : '') != '')
						$ext->add($contextname, $grpnum, '', new ext_goto($postdest));
					else
						$ext->add($contextname, $grpnum, '', new ext_hangup(''));
				}
			}
		break;
	}
}

function ringgroups_add($grpnum,$strategy,$grptime,$grplist,$postdest,$grppre='',$annmsg='') {
	$results = sql("INSERT INTO ringgroups (grpnum, strategy, grptime, grppre, grplist, annmsg, postdest) VALUES (".$grpnum.", '".str_replace("'", "''", $strategy)."', ".str_replace("'", "''", $grptime).", '".str_replace("'", "''", $grppre)."', '".str_replace("'", "''", $grplist)."', '".str_replace("'", "''", $annmsg)."', '".str_replace("'", "''", $postdest)."')");
}

function ringgroups_del($grpnum) {
	$results = sql("DELETE FROM ringgroups WHERE grpnum = $grpnum","query");
}

function ringgroups_list() {
	$results = sql("SELECT grpnum FROM ringgroups ORDER BY grpnum","getAll",DB_FETCHMODE_ASSOC);
	foreach ($results as $result) {
		if (isset($result['grpnum']) && checkRange($result['grpnum'])) {
			$grps[] = array($result['grpnum']);
		}
	}
	if (isset($grps))
		return $grps;
	else
		return null;
}

function ringgroups_get($grpnum) {
	$results = sql("SELECT grpnum, strategy, grptime, grppre, grplist, annmsg, postdest FROM ringgroups WHERE grpnum = $grpnum","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}
?>

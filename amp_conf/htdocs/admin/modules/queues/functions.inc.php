<?php /* $id:$ */
// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function queues_destinations() {
	//get the list of all exisiting
	$results = queues_list();
	
	//return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-queues,'.$result['0'].',1', 'description' => $result['1'].' <'.$result['0'].'>');
		}
	}
	
	return $extens;
}

/* 	Generates dialplan for "queues" components (extensions & inbound routing)
	We call this with retrieve_conf
*/
function queues_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	switch($engine) {
		case "asterisk":
			/* queue extensions */
			$ext->addInclude('from-internal-additional','ext-queues');
			$qlist = queues_list();
			if (is_array($qlist)) {
				foreach($qlist as $item) {
					
					$exten = $item[0];
					$q = queues_get($exten);
					
					$ext->add('ext-queues', $exten, '', new ext_answer(''));
					$ext->add('ext-queues', $exten, '', new ext_setcidname($q['prefix'].'${CALLERIDNAME}'));
					$ext->add('ext-queues', $exten, '', new ext_setvar('MONITOR_FILENAME','/var/spool/asterisk/monitor/q${EXTEN}-${TIMESTAMP}-${UNIQUEID}'));
					if(isset($q['joinannounce']) && $q['joinannounce'] != "") {
						$filename = recordings_get($annmsg);
						$ext->add('ext-queues', $exten, '', new ext_playback($filename['filename']));
					}
					$ext->add('ext-queues', $exten, '', new ext_queue($exten,'t','',$q['agentannounce'],$q['maxwait']));
	
					// destination field in 'incoming' database is backwards from what ext_goto expects
					$goto_context = strtok($q['goto'],',');
					$goto_exten = strtok(',');
					$goto_pri = strtok(',');
					
					$ext->add('ext-queues', $exten, '', new ext_goto($goto_pri,$goto_exten,$goto_context));
					
					//dynamic agent login/logout
					$ext->add('ext-queues', $exten."*", '', new ext_macro('agent-add',$exten.",".$q['password']));
					$ext->add('ext-queues', $exten."**", '', new ext_macro('agent-del',$exten.",".$exten));
				}
			}
		break;
	}
}

function queues_timeString($seconds, $full = false) {
        if ($seconds == 0) {
                return "0 ".($full ? "seconds" : "s");
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        $days = floor($hours / 24);
        $hours = $hours % 24;

        if ($full) {
                return substr(
                                ($days ? $days." day".(($days == 1) ? "" : "s").", " : "").
                                ($hours ? $hours." hour".(($hours == 1) ? "" : "s").", " : "").
                                ($minutes ? $minutes." minute".(($minutes == 1) ? "" : "s").", " : "").
                                ($seconds ? $seconds." second".(($seconds == 1) ? "" : "s").", " : ""),
                               0, -2);
        } else {
                return substr(($days ? $days."d, " : "").($hours ? $hours."h, " : "").($minutes ? $minutes."m, " : "").($seconds ? $seconds."s, " : ""), 0, -2);
        }
}

/*
This module needs to be updated to use it's own database table and not the extensions table
*/

function queues_add($account,$name,$password,$prefix,$goto,$agentannounce,$members,$joinannounce,$maxwait) {
	global $db;
	
	//add to extensions table
	if (!empty($agentannounce) && $agentannounce != 'None')
		$agentannounce="$agentannounce";
	else
		$agentannounce="";

	$addarray = array('ext-queues',$account,'1','Answer',''.'','','0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account,'2','SetCIDName',$prefix.'${CALLERIDNAME}','','0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account,'3','SetVar','MONITOR_FILENAME=/var/spool/asterisk/monitor/q${EXTEN}-${TIMESTAMP}-${UNIQUEID}','','0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account,'4','Playback',$joinannounce,'','0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account,'5','Queue',$account.'|t||'.$agentannounce.'|'.$maxwait,$name,'0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account.'*','1','Macro','agent-add,'.$account.','.$password,'','0');
	legacy_extensions_add($addarray);
	$addarray = array('ext-queues',$account.'**','1','Macro','agent-del,'.$account,'','0');
	legacy_extensions_add($addarray);
	
	//failover goto
	$addarray = array('ext-queues',$account,'6','Goto',$goto,'jump','0');
	legacy_extensions_add($addarray);
	//setGoto($account,'ext-queues','6',$goto,0);
	
	
	// now add to queues table
	$fields = array(
		array($account,'account',$account),
		array($account,'maxlen',($_REQUEST['maxlen'])?$_REQUEST['maxlen']:'0'),
		array($account,'joinempty',($_REQUEST['joinempty'])?$_REQUEST['joinempty']:'yes'),
		array($account,'leavewhenempty',($_REQUEST['leavewhenempty'])?$_REQUEST['leavewhenempty']:'no'),
		array($account,'strategy',($_REQUEST['strategy'])?$_REQUEST['strategy']:'ringall'),
		array($account,'timeout',($_REQUEST['timeout'])?$_REQUEST['timeout']:'15'),
		array($account,'retry',($_REQUEST['retry'])?$_REQUEST['retry']:'5'),
		array($account,'wrapuptime',($_REQUEST['wrapuptime'])?$_REQUEST['wrapuptime']:'0'),
		//array($account,'agentannounce',($_REQUEST['agentannounce'])?$_REQUEST['agentannounce']:'None'),
		array($account,'announce-frequency',($_REQUEST['announcefreq'])?$_REQUEST['announcefreq']:'0'),
		array($account,'announce-holdtime',($_REQUEST['announceholdtime'])?$_REQUEST['announceholdtime']:'no'),
		array($account,'queue-youarenext',($_REQUEST['announceposition']=='no')?'':'queue-youarenext'),  //if no, play no sound
		array($account,'queue-thereare',($_REQUEST['announceposition']=='no')?'':'queue-thereare'),  //if no, play no sound
		array($account,'queue-callswaiting',($_REQUEST['announceposition']=='no')?'':'queue-callswaiting'),  //if no, play no sound
		array($account,'queue-thankyou',($_REQUEST['announcemenu']=='none')?'queue-thankyou':'custom/'.$_REQUEST['announcemenu']),  //if none, play default thankyou, else custom/aa
		array($account,'context',($_REQUEST['announcemenu']=='none')?'':$_REQUEST['announcemenu']),  //if not none, set context=aa
		array($account,'monitor-format',($_REQUEST['monitor-format'])?$_REQUEST['monitor-format']:''),
		array($account,'monitor-join','yes'),
		array($account,'music',($_REQUEST['music'])?$_REQUEST['music']:'default'));

	//there can be multiple members
	if (isset($members)) {
		foreach ($members as $member) {
			$fields[] = array($account,'member',$member);
		}
	}

    $compiled = $db->prepare('INSERT INTO queues (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$fields);
    if(DB::IsError($result)) {
        die($result->getMessage()."<br><br>error adding to queues table");	
    }
}

function queues_del($account) {
	global $db;
	//delete from extensions table
	legacy_extensions_del('ext-queues',$account);
	legacy_extensions_del('ext-queues',$account.'*');
	legacy_extensions_del('ext-queues',$account.'**');
	
	$sql = "DELETE FROM queues WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }

}

//get the existing queue extensions
function queues_list() {
	global $db;
	$sql = "SELECT extension,descr FROM extensions WHERE application = 'Queue' ORDER BY extension";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0],$result[1]);
		}
	}
	if (isset($extens)) {
		return $extens;
	} else {
		return null;
	}
}


function queues_get($account) {
	global $db;
	
    if ($account == "")
    {
	    return array();
    }
    
	//get all the variables for the queue
	$sql = "SELECT keyword,data FROM queues WHERE id = '$account'";
	$results = $db->getAssoc($sql);

	//okay, but there can be multiple member variables ... do another select for them
	$sql = "SELECT data FROM queues WHERE id = '$account' AND keyword = 'member'";
	$results['member'] = $db->getCol($sql);
	
	//queues.php looks for 'announcemenu', which is the same a context
	$results['announcemenu'] = 	$results['context'];
	
	//if 'queue-youarenext=queue-youarenext', then assume we want to announce position
	if($results['queue-youarenext'] == 'queue-youarenext') 
		$results['announce-position'] = 'yes';
	else
		$results['announce-position'] = 'no';
	
	//get CID Prefix
	$sql = "SELECT args FROM extensions WHERE extension = '$account' AND context = 'ext-queues' AND application = 'SetCIDName'";
	list($args) = $db->getRow($sql);
	$prefix = explode('$',$args); //in table like prefix${CALLERIDNAME}
	$results['prefix'] = $prefix[0];	
	
	//get max wait time from Queue command
	$sql = "SELECT args,descr FROM extensions WHERE extension = '$account' AND context = 'ext-queues' AND application = 'Queue'";
	list($args, $descr) = $db->getRow($sql);
	$maxwait = explode('|',$args);  //in table like queuenum|t|||maxwait
	$results['agentannounce'] = $maxwait[3];
	$results['maxwait'] = $maxwait[4];
	$results['name'] = $descr;
	
	$sql = "SELECT args FROM extensions WHERE extension = '$account' AND context = 'ext-queues' and application = 'Playback'";
	list($args) = $db->getRow($sql);
	$results['joinannounce'] = $args; 
	
	//get password from AddQueueMember command
	$sql = "SELECT args FROM extensions WHERE extension = '$account*' AND context = 'ext-queues'";
	list($args) = $db->getRow($sql);
	$password = explode(',',$args); //in table like agent-add,account,password
	$results['password'] = $password[2];
	
	//get the failover destination (desc=jump)
	$sql = "SELECT args FROM extensions WHERE extension = '".$account."' AND descr = 'jump'";
	list($args) = $db->getRow($sql);
	$results['goto'] = $args; 

	return $results;
}
?>

<?php /* $id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

function parse_amportal_conf($filename) {
	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([a-zA-Z0-9]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	return $conf;
}

function timeString($seconds, $full = false) {
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

function addAmpUser($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	global $db;
	$sql = "INSERT INTO ampusers (username, password, extension_low, extension_high, deptname, sections) VALUES (";
	$sql .= "'".$username."',";
	$sql .= "'".$password."',";
	$sql .= "'".$extension_low."',";
	$sql .= "'".$extension_high."',";
	$sql .= "'".$deptname."',";
	$sql .= "'".implode(";",$sections)."');";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage().'<hr>'.$sql);
	} 
}

function deleteAmpUser($username) {
	global $db;
	
	$sql = "DELETE FROM ampusers WHERE username = '".$username."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

function getAmpUsers() {
	global $db;
	
	$sql = "SELECT username FROM ampusers ORDER BY username";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	return $results;
}

function getAmpAdminUsers() {
	global $db;

	$sql = "SELECT username FROM ampusers WHERE sections='*'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	return $results;
}

function getAmpUser($username) {
	global $db;
	
	$sql = "SELECT username, password, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = '".$username."'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	
	if (count($results) > 0) {
		$user = array();
		$user["username"] = $results[0][0];
		$user["password"] = $results[0][1];
		$user["extension_low"] = $results[0][2];
		$user["extension_high"] = $results[0][3];
		$user["deptname"] = $results[0][4];
		$user["sections"] = explode(";",$results[0][5]);
		return $user;
	} else {
		return false;
	}
}

class ampuser {
	var $username;
	var $_password;
	var $_extension_high;
	var $_extension_low;
	var $_deptname;
	var $_sections;
	
	function ampuser($username) {
		$this->username = $username;
		if ($user = getAmpUser($username)) {
			$this->_password = $user["password"];
			$this->_extension_high = $user["extension_high"];
			$this->_extension_low = $user["extension_low"];
			$this->_deptname = $user["deptname"];
			$this->_sections = $user["sections"];
		} else {
			// user doesn't exist
			$this->_password = false;
			$this->_extension_high = "";
			$this->_extension_low = "";
			$this->_deptname = "";
			$this->_sections = array();
		}
	}
	
	/** Give this user full admin access
	*/
	function setAdmin() {
		$this->_extension_high = "";
		$this->_extension_low = "";
		$this->_deptname = "";
		$this->_sections = array("*");
	}
	
	function checkPassword($password) {
		// strict checking so false will never match
		return ($this->_password === $password);
	}
	
	function checkSection($section) {
		// if they have * then it means all sections
		return in_array("*", $this->_sections) || in_array($section, $this->_sections);
	}
}

// returns true if extension is within allowed range
function checkRange($extension){
	$low = $_SESSION["user"]->_extension_low;
	$high = $_SESSION["user"]->_extension_high;
	if ((($extension >= $low) && ($extension <= $high)) || (empty($low) && empty($high)))
		return true;
	else
		return false;
}


//get unique voice menu numbers - returns 2 dimensional array
function getaas() {
	global $db;
	$dept = str_replace(' ','_',$_SESSION["user"]->_deptname);
	if (empty($dept)) $dept='%';  //if we are not restricted to dept (ie: admin), then display all AA menus
	$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context";
	$unique_aas = $db->getAll($sql);
	if(DB::IsError($unique_aas)) {
	   die('unique: '.$unique_aas->getMessage().'<hr>'.$sql);
	}
	return $unique_aas;
}

// get the existing extensions
// the returned arrays contain [0]:extension [1]:CID [2]:technology
function getextens() {
	$sip = getSip();
	$iax = getIax();
	$zap= getZap();
	$results = array_merge($sip, $iax, $zap);
	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0],$result[1],$result[2]);
		}
	}
	if (isset($extens)) sort($extens);
	return $extens;
}

function getSip() {
	global $db;
	$sql = "SELECT id,data FROM sip WHERE keyword = 'callerid' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	//add value 'sip' to each array
	foreach ($results as $result) {
		$result[] = 'sip';
		$sip[] = $result;
	}
	return $sip;
}

function getIax() {
	global $db;
	$sql = "SELECT id,data FROM iax WHERE keyword = 'callerid' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	//add value 'iax' to each array
	foreach ($results as $result) {
		$result[] = 'iax';
		$iax[] = $result;
	}
	return $iax;
}

function getZap() {
	global $db;
	$sql = "SELECT id,data FROM zap WHERE keyword = 'callerid' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	//add value 'zap' to each array
	foreach ($results as $result) {
		$result[] = 'zap';
		$zap[] = $result;
	}
	return $zap;
}

//get the existing group extensions
function getgroups() {
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

//get the existing queue extensions
function getqueues() {
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
	return $extens;
}

//get the existing did extensions
function getdids() {
	global $db;
	$sql = "SELECT extension FROM extensions WHERE context = 'ext-did' and priority ='1' ORDER BY extension";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

//get goto in specified group
function getgroupgoto($grpexten) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$grpexten."' AND (args LIKE 'ext-local,%,%' OR args LIKE 'vm,%' OR args LIKE 'aa_%,%,%' OR args LIKE 'ext-group,%,%' OR args LIKE 'from-pstn,s,1' OR args LIKE '%custom%')";
	$thisGRPgoto = $db->getAll($sql);
	if(DB::IsError($thisGRPgoto)) {
	   die($thisGRPgoto->getMessage());
	}
	return $thisGRPgoto;
}


function getgroupinfo($grpexten, &$time, &$prefix, &$group) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$grpexten."' AND priority = '1'";
	$res = $db->getAll($sql);
	if(DB::IsError($res)) {
	   die($thisGRPprefix->getMessage());
	}
	if (preg_match("/^rg-group,(.*),(.*),(.*)$/", $res[0][0], $matches)) {
		$time = $matches[1];
		$prefix = $matches[2];
		$group = $matches[3];
		return true;
	} 
	return false;
}

//add to extensions table - used in callgroups.php
function addextensions($addarray) {
	global $db;
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('".$addarray[0]."', '".$addarray[1]."', '".$addarray[2]."', '".$addarray[3]."', '".$addarray[4]."', '".$addarray[5]."' , '".$addarray[6]."')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
	return $result;
}

//delete extension from extensions table
function delextensions($context,$exten) {
	global $db;
	$sql = "DELETE FROM extensions WHERE context = '".$context."' AND `extension` = '".$exten."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage());
    }
	return $result;
}

//tell application we need to reload asterisk
function needreload() {
	global $db;
	$sql = "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage()); 
	}
}

//get info about auto-attendant
function aainfo($menu_id) {
	global $db;
	//do another select for all parts in this aa_
//	$sql = "SELECT * FROM extensions WHERE context = '".$dept."aa_".$menu_num."' ORDER BY extension";
	$sql = "SELECT * FROM extensions WHERE context = '".$menu_id."' ORDER BY extension";
	$aalines = $db->getAll($sql);
	if(DB::IsError($aalines)) {
		die('aalines: '.$aalines->getMessage());
	}
	return $aalines;
}

//get the version number
function getversion() {
	global $db;
	$sql = "SELECT value FROM admin WHERE variable = 'version'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

function zapexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `zap` (`id` bigint(11) NOT NULL default '-1',`keyword`varchar(20) NOT NULL default '',`data`varchar(150) NOT NULL default '',`flags` int(1) NOT NULL default '0',PRIMARY KEY (`id`,`keyword`))";
	$results = $db->query($sql);
}

function addzap($account,$callerid) {
	zapexists();
	global $db;
	$zapfields = array(
	array($account,'account',$account),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:''),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:''),
	array($account,'callerid',$callerid),
	array($account,'signalling',($_REQUEST['signalling'])?$_REQUEST['signalling']:'fxo_ks'),
	array($account,'echocancel',($_REQUEST['echocancel'])?$_REQUEST['echocancel']:'yes'),
	array($account,'echocancelwhenbridged',($_REQUEST['echocancelwhenbridged'])?$_REQUEST['echocancelwhenbridged']:'no'),
	array($account,'echotraining',($_REQUEST['echotraining'])?$_REQUEST['echotraining']:'800'),
	//array($account,'group',($_REQUEST['group'])?$_REQUEST['group']:'31'), //Default<>0 which is the default zap trunk
	array($account,'busydetect',($_REQUEST['busydetect'])?$_REQUEST['busydetect']:'no'),
	array($account,'busycount',($_REQUEST['busycount'])?$_REQUEST['busycount']:'7'),
	array($account,'callprogress',($_REQUEST['callprogress'])?$_REQUEST['callprogress']:'no'),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'channel',($_REQUEST['channel'])?$_REQUEST['channel']:''));

	$compiled = $db->prepare('INSERT INTO zap (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$zapfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>error adding to ZAP table");	
	}	

	//add E<enten>=ZAP to global vars (appears in extensions_additional.conf)
	$sql = "INSERT INTO globals VALUES ('E$account', 'ZAP')"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage().$sql); 
	}

	//add ZAPCHAN_<exten>=<zapchannel> to global vars. Needed in dialparties.agi to decide channel number without hitting the database.
	$zapchannel=$_REQUEST['channel'];
	$sql = "INSERT INTO globals VALUES ('ZAPCHAN_$account', '$zapchannel')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage().$sql);
	}
	
	//add ECID<enten> to global vars if using outbound CID
	if ($_REQUEST['outcid'] != '') {
		$outcid = $_REQUEST['outcid'];
		$sql = "INSERT INTO globals VALUES ('ECID$account', '$outcid')"; 
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage().$sql); 
		}
	}
}

//create iax if it doesn't exist
function iaxexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `iax` (`id` bigint(11) NOT NULL default '-1',`keyword` varchar(20) NOT NULL default '',`data` varchar(150) NOT NULL default '',`flags` int(1) NOT NULL default '0',PRIMARY KEY  (`id`,`keyword`))";
	$results = $db->query($sql);
}

//add to iax table
function addiax($account,$callerid) {
	iaxexists();
	global $db;
	$iaxfields = array(array($account,'account',$account),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'notransfer',($_REQUEST['notransfer'])?$_REQUEST['notransfer']:''),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:''),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:''),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:''),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:''),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:''),
	array($account,'port',($_REQUEST['iaxport'])?$_REQUEST['iaxport']:''),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:''),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',$callerid));

	$compiled = $db->prepare('INSERT INTO iax (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$iaxfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>error adding to IAX table");	
	}	

	//add E<enten>=IAX2 to global vars (appears in extensions_additional.conf)
	$sql = "INSERT INTO globals VALUES ('E$account', 'IAX2')"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage().$sql); 
	}

	//add ECID<enten> to global vars if using outbound CID
	if ($_REQUEST['outcid'] != '') {
		$outcid = $_REQUEST['outcid'];
		$sql = "INSERT INTO globals VALUES ('ECID$account', '$outcid')"; 
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage().$sql); 
		}
	}
}

//create sip if it doesn't exist
function sipexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `sip` (`id` bigint(11) NOT NULL default '-1',`keyword` varchar(20) NOT NULL default '',`data` varchar(150) NOT NULL default '',`flags` int(1) NOT NULL default '0',PRIMARY KEY  (`id`,`keyword`))";
	$results = $db->query($sql);
}

//add to sip table
function addsip($account,$callerid) {
	sipexists();
	global $db;
	$sipfields = array(array($account,'account',$account),
	array($account,'accountcode',($_REQUEST['accountcode'])?$_REQUEST['accountcode']:''),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'canreinvite',($_REQUEST['canreinvite'])?$_REQUEST['canreinvite']:''),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:''),
	array($account,'dtmfmode',($_REQUEST['dtmfmode'])?$_REQUEST['dtmfmode']:''),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:''),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:''),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:''),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:''),
	array($account,'nat',($_REQUEST['nat'])?$_REQUEST['nat']:''),
	array($account,'port',($_REQUEST['port'])?$_REQUEST['port']:''),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:''),
	array($account,'callgroup',($_REQUEST['callgroup'])?$_REQUEST['callgroup']:''),
	array($account,'pickupgroup',($_REQUEST['pickupgroup'])?$_REQUEST['pickupgroup']:''),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',$callerid));

	$compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$sipfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>".'error adding to SIP table');	
	}
	    
	//add E<enten>=SIP to global vars (appears in extensions_additional.conf)
	$sql = "INSERT INTO globals VALUES ('E$account', 'SIP')"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage().$sql); 
	}
	
	//add ECID<enten> to global vars if using outbound CID
	if ($_REQUEST['outcid'] != '') {
		$outcid = $_REQUEST['outcid'];
		$sql = "INSERT INTO globals VALUES ('ECID$account', '$outcid')"; 
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage().$sql); 
		}
	}
}

function addaccount($account,$mailb) {
	extensionsexists();
	global $db;
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('ext-local', '".$account."', '1', 'Macro', 'exten-vm,".$mailb.",".$account."', NULL , '0')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage());
    }
    return $result;
}

//create extensions if it doesn't exist
function extensionsexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `extensions` (`context` varchar(20) NOT NULL default 'default',`extension` varchar(20) NOT NULL default '',`priority` int(2) NOT NULL default '1',`application` varchar(20) NOT NULL default '',`args` varchar(50) default NULL,`descr` text,`flags` int(1) NOT NULL default '0',PRIMARY KEY  (`context`,`extension`,`priority`))";
	$results = $db->query($sql);
}

//get all rows relating to selected account
function exteninfo($extdisplay) {
	global $db;
	$sql = "SELECT * FROM sip WHERE id = '$extdisplay'";
	$thisExten = $db->getAll($sql);
	if(DB::IsError($thisExten)) {
	   die($thisExten->getMessage());
	}
	if (count($thisExten) > 0) {
		$thisExten[] = array('$extdisplay','tech','sip','info');  //add this to the array - as it doesn't exist in the table
	} else {
	//if (count($thisExten) == 0) {  //if nothing was pulled from sip, then it must be iax
		$sql = "SELECT * FROM iax WHERE id = '$extdisplay'";
		$thisExten = $db->getAll($sql);
		if(DB::IsError($thisExten)) {
		   die($thisExten->getMessage());
		}
		if (count($thisExten) > 0) {
			$thisExten[] = array('$extdisplay','tech','iax2','info');  //add this to the array - as it doesn't exist in the table
		} else {
			$sql = "SELECT * FROM zap WHERE id = '$extdisplay'";
			$thisExten = $db->getAll($sql);
			if(DB::IsError($thisExten)) {
				die($thisExten->getMessage());
			}
			if (count($thisExten) > 0) {
				$thisExten[] = array('$extdisplay','tech','zap','info');
			}
		}
	}
	//get var containing external cid
	$sql = "SELECT * FROM globals WHERE variable = 'ECID$extdisplay'";
	$ecid = $db->getAll($sql);
	if(DB::IsError($ecid)) {
	   die($ecid->getMessage());
	}
	$thisExten[] = array('$extdisplay','1outcid',$ecid[0][1],'info');
	sort($thisExten);
	
	return $thisExten;
}


//Delete an extension (extensions.php)
function delExten($extdisplay) {
	global $db;
    $sql = "DELETE FROM sip WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
    $sql = "DELETE FROM iax WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
    $sql = "DELETE FROM zap WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
    $sql = "DELETE FROM globals WHERE variable = 'E$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
    $sql = "DELETE FROM globals WHERE variable = 'ECID$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
	$sql = "DELETE FROM globals WHERE variable = 'ZAPCHAN_$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }
}


//add trunk to outbound-trunks context
function addOutTrunk($trunknum) {
	extensionsexists();
	global $db;
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('outbound-trunks', '_\${DIAL_OUT_".$trunknum."}.', '1', 'Macro', 'dialout,".$trunknum.",\${EXTEN}', NULL , '0')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage()."<br><br>".$sql);
    }
    return $result;
}


//write the OUTIDS global variable (used in dialparties.agi)
function writeoutids() {
	global $db;
	$sql = "SELECT variable FROM globals WHERE variable LIKE 'OUT\\\_%'"; // we have to escape _ for mysql: normally a wildcard
	$unique_trunks = $db->getAll($sql);
	if(DB::IsError($unique_trunks)) {
	   die('unique: '.$unique_trunks->getMessage());
	}
	foreach ($unique_trunks as $unique_trunk) {
		$outid = strtok($unique_trunk[0],"_");
		$outid = strtok("_");
		$outids .= $outid ."/";
	}
	$sql = "UPDATE globals SET value = '$outids' WHERE variable = 'DIALOUTIDS'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

//get unique trunks
function gettrunks() {
	global $db;
	$sql = "SELECT * FROM globals WHERE variable LIKE 'OUT\\\_%' ORDER BY RIGHT( variable, LENGTH( variable ) - 4 )+0"; // we have to escape _ for mysql: normally a wildcard
	$unique_trunks = $db->getAll($sql);
	if(DB::IsError($unique_trunks)) {
	   die('unique: '.$unique_trunks->getMessage());
	}
	//if no trunks have ever been defined, then create the proper variables with the default zap trunk
	if (count($unique_trunks) == 0) {
		//If all trunks have been deleted from admin, dialoutids might still exist
		$sql = "DELETE FROM globals WHERE variable = 'DIALOUTIDS'";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		$glofields = array(array('OUT_1','ZAP/g0'),
							array('DIAL_OUT_1','9'),
							array('DIALOUTIDS','1'));
	    $compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
		$result = $db->executeMultiple($compiled,$glofields);
	    if(DB::IsError($result)) {
	        die($result->getMessage()."<br><br>".$sql);	
	    }
		$unique_trunks[] = array('OUT_1','ZAP/g0');
		addOutTrunk("1");
	}
	// asort($unique_trunks);
	return $unique_trunks;
}


//add trunk info to sip or iax table
function addSipOrIaxTrunk($config,$table,$channelid,$trunknum) {
	global $db;
	
	//echo "addSipOrIaxTrunk($config,$table,$channelid,$trunknum)";
	
	$confitem['account'] = $channelid;
	$gimmieabreak = nl2br($config);
	$lines = split('<br />',$gimmieabreak);
	foreach ($lines as $line) {
		$line = trim($line);
		if (count(split('=',$line)) > 1) {
			$tmp = split('=',$line);
			$key=trim($tmp[0]);
			$value=trim($tmp[1]);
			if (isset($confitem[$key]) && !empty($confitem[$key]))
				$confitem[$key].="&".$value;
			else
				$confitem[$key]=$value;
		}
	}
	foreach($confitem as $k=>$v) {
		$dbconfitem[]=array($k,$v);
	}
	$compiled = $db->prepare("INSERT INTO $table (id, keyword, data) values ('9999$trunknum',?,?)");
	$result = $db->executeMultiple($compiled,$dbconfitem);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>INSERT INTO $table (id, keyword, data) values ('9999$trunknum',?,?)");	
	}
}

function getTrunkTech($trunknum) {
	global $db;
	
	$sql = "SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'";
	if (!$results = $db->getAll($sql)) {
		return false;
	}
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tech = "custom";
	} else {
		$tech = strtolower( strtok($results[0][0],'/') ); // the technology.  ie: ZAP/g0 is ZAP
		
		if ($tech == "iax2") $tech = "iax"; // same thing, here
	}
	return $tech;
}



function addTrunkDialRules($trunknum, $rules) {
	global $db;
	
	foreach ($rules as $rule) {
		$values = array();
		
		if (false !== ($pos = strpos($rule,"|"))) {
			// we have a | meaning to not dial the numbers before it
			// (ie, 1613|NXXXXXX should use the pattern _1613NXXXXXX but only pass NXXXXXX, not the leading 1613)
			
			$exten = "EXTEN:".$pos; // chop off leading digit
			$prefix = "";
			
			$rule = str_replace("|","",$rule); // remove all |'s
			
		} else if (false !== ($pos = strpos($rule,"+"))) {
			// we have a + meaning to add the numbers before it
			// (ie, 1613+NXXXXXX should use the pattern _NXXXXXX but pass it as 1613NXXXXXX)
			
			$prefix = substr($rule,0,$pos); // get the prefixed digits
			$exten = "EXTEN"; // pass as is
			
			$rule = substr($rule, $pos+1); // only match pattern after the +
		} else {
			// we pass the full dialed number as-is
			$exten = "EXTEN"; 
			$prefix = "";
		}
		
		if (!preg_match("/^[0-9*]+$/",$rule)) { 
			// note # is not here, as asterisk doesn't recoginize it as a normal digit, thus it requires _ pattern matching
			
			// it's not strictly digits, so it must have patterns, so prepend a _
			$rule = "_".$rule;
		}
		
		$values[] = array('1', 'Dial', '${OUT_'.$trunknum.'}/${OUTPREFIX_'.$trunknum.'}'.$prefix.'${'.$exten.'}');
		$values[] = array('2', 'Congestion', '');
		$values[] = array('102', 'NoOp', 'outdial-'.$trunknum.' dial failed');
		
		$sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
		$sql .= "('outdial-".$trunknum."', ";
		$sql .= "'".$rule."', ";
		// priority, application, args:
		$sql .= "?, ?, ?)";
		
		$compiled = $db->prepare($sql);
		$result = $db->executeMultiple($compiled,$values);
		if(DB::IsError($result)) {
			//var_dump($result);
			die($result->getMessage());
		}
		
	}
	
	// catch-all extension
	$sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
	$sql .= "('outdial-".$trunknum."-catchall', ";
	$sql .= "'_.', ";
	// priority, application, args:
	$sql .= "'1', ";
	$sql .= "'Dial', ";
	$sql .= "'\${OUT_".$trunknum."}/\${OUTPREFIX_".$trunknum."}\${EXTEN}');";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}

	// include catch-all in main context
	$sql = "INSERT INTO extensions (context, extension, priority, application, flags) VALUES ";
	$sql .= "('outdial-".$trunknum."', ";
	$sql .= "'include', ";
	$sql .= "'1', ";
	$sql .= "'outdial-".$trunknum."-catchall', ";
	$sql .= "'2');";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

function deleteTrunkDialRules($trunknum) {
	global $db;
	
	$sql = "DELETE FROM extensions WHERE context = 'outdial-".$trunknum."'";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}

	// the "catch-all" extension
	$sql = "DELETE FROM extensions WHERE context = 'outdial-".$trunknum."-catchall'";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

function getTrunkDialRules($trunknum) {
	global $db;
	$sql = "SELECT extension, args FROM extensions WHERE context = 'outdial-".$trunknum."' AND application = 'Dial' ORDER BY extension ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$rules = array();
	foreach ($results as $row) {
		if ($row[0][0] == "_") {
			// remove leading _
			$rule = substr($row[0],1);
		} else {
			$rule = $row[0];
		}
		
		if (preg_match("/(\d*){EXTEN:(\d+)}/", $row[1], $matches)) {
			// this has a digit offset, we need to insert a |
			$rule = substr($rule,0,$matches[2])."|".substr($rule,$matches[2]);
		} else if (preg_match("/(\d){EXTEN}/", $row[1], $matches)) {
			// this has a prefix, insert a +
			$rule = substr($rule,0,strlen($matches[1]))."+".substr($rule,strlen($matches[1]));
		}
		
		$rules[] = $rule;
	}
	return array_unique($rules);
	
}

// just used internally by addTrunk() and editTrunk()
function backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	global $db;
	
	if (!$dialoutprefix) $dialoutprefix = ""; // can't be NULL
	
	//echo  "backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	
	// change iax to "iax2" (only spot we actually store iax2, since its used by Dial()..)
	$techtemp = ((strtolower($tech) == "iax") ? "iax2" : $tech);
	$outval = (($techtemp == "custom") ? "AMP:".$channelid : strtoupper($techtemp).'/'.$channelid);
	
	$glofields = array(
			array('OUT_'.$trunknum, $outval),
			array('OUTPREFIX_'.$trunknum, $dialoutprefix),
			array('OUTMAXCHANS_'.$trunknum, $maxchans),
			array('OUTCID_'.$trunknum, $outcid),
			);
			
	unset($techtemp); 
	
	$compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
	$result = $db->executeMultiple($compiled,$glofields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>".$sql);
	}
	
	writeoutids();
	
	//addOutTrunk($trunknum); don't need to add to outbound-routes anymore
	
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			addSipOrIaxTrunk($peerdetails,'iax',$channelid,$trunknum);
			if ($usercontext != ""){
				addSipOrIaxTrunk($userconfig,'iax',$usercontext,'9'.$trunknum);
			}
			if ($register != ""){
				addTrunkRegister($trunknum,'iax',$register);
			}
		break;
		case "sip":
			addSipOrIaxTrunk($peerdetails,'sip',$channelid,$trunknum);
			if ($usercontext != ""){
				addSipOrIaxTrunk($userconfig,'sip',$usercontext,'9'.$trunknum);
			}
			if ($register != ""){
				addTrunkRegister($trunknum,'sip',$register);
			}
		break;
	}
	
}

// we're adding ,don't require a $trunknum
function addTrunk($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	global $db;
	
	// find the next available ID
	$trunknum = 1;
	foreach(gettrunks() as $trunk) {
		if ($trunknum == ltrim($trunk[0],"OUT_")) { 
			$trunknum++;
		}
	}
	
	backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
	
	return $trunknum;
}

function deleteTrunk($trunknum, $tech = null) {
	global $db;
	
	if ($tech === null) { // in EditTrunk, we get this info anyways
		$tech = getTrunkTech($trunknum);
	}

	//delete from globals table
	//$sql = "DELETE FROM globals WHERE variable LIKE '%OUT_$trunknum' OR  variable LIKE '%OUTCID_$trunknum'";
	$sql = "DELETE FROM globals WHERE variable LIKE '%OUT_$trunknum' OR variable IN ('OUTCID_$trunknum','OUTMAXCHANS_$trunknum','OUTPREFIX_$trunknum')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	//write outids
	writeoutids();

	//delete from extensions table
	//delextensions('outbound-trunks','_${DIAL_OUT_'.$trunknum.'}.');
	//DIALRULES deleteTrunkRules($trunknum);
	
	//and conditionally, from iax or sip
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			$sql = "DELETE FROM iax WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'";
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
		break;
		case "sip": 
			$sql = "DELETE FROM sip WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'";
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
		break;
	}
}

function editTrunk($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	//echo "editTrunk($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	$tech = getTrunkTech($trunknum);
	deleteTrunk($trunknum, $tech);
	backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
}

//get and print peer details (prefixed with 4 9's)
function getTrunkPeerDetails($trunknum) {
	global $db;
	
	$tech = getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$sql = "SELECT keyword,data FROM $tech WHERE id = '9999$trunknum' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	foreach ($results as $result) {
		if ($result[0] != 'account') {
			$confdetail .= $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

//get and print user config (prefixed with 5 9's)
function getTrunkUserConfig($trunknum) {
	global $db;
	
	$tech = getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$sql = "SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	foreach ($results as $result) {
		if ($result[0] != 'account') {
			$confdetail .= $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

//get trunk user context (prefixed with 5 9's)
function getTrunkUserContext($trunknum) {
	global $db;
	
	$tech = getTrunkTech($trunknum);
	if ($tech == "zap") return ""; // zap has no account
	
	$sql = "SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	foreach ($results as $result) {
		if ($result[0] == 'account') {
			$account = $result[1];
		}
	}
	return $account;
}

function getTrunkTrunkName($trunknum) {
	global $db;
	
	$sql = "SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'";
	if (!$results = $db->getAll($sql)) {
		return false;
	}
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tname = ltrim($results[0][0],"AMP:");
	} else {
	strtok($results[0][0],'/');
		$tname = strtolower( strtok('/') ); // the text _after_ technology.  ie: ZAP/g0 is g0
	}
	return $tname;
}

//get trunk account register string
function getTrunkRegister($trunknum) {
	global $db;
	$tech = getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no register
	
	$sql = "SELECT keyword,data FROM $tech WHERE id = '9999999$trunknum'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	foreach ($results as $result) {
			$register = $result[1];
	}
	return $register;
}

function addTrunkRegister($trunknum,$tech,$reg) {
	global $db;
	$sql = "INSERT INTO $tech (id, keyword, data) values ('9999999$trunknum','register','$reg')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

//get unique outbound route names
function getroutenames() {
	global $db;
	$sql = "SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";
	// we SUBSTRING() to remove "outrt-"
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	if (count($results) == 0) {
		// see if they're still using the old dialprefix method
		$sql = "SELECT variable,value FROM globals WHERE variable LIKE 'DIAL\\\_OUT\\\_%'";
		// we SUBSTRING() to remove "outrt-"
		$results = $db->getAll($sql);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}
		
		if (count($results) > 0) {
			// yes, they are using old method, let's update
			
			// get the default trunk
			$sql = "SELECT value FROM globals WHERE variable = 'OUT'";
			$results_def = $db->getAll($sql);
			if(DB::IsError($results_def)) {
				die($results_def->getMessage());
			}
			
			if (preg_match("/{OUT_(\d+)}/", $results_def[0][0], $matches)) {
				$def_trunk = $matches[1];
			} else {
				$def_trunk = "";
			}
			
			$default_patterns = array(	// default patterns that used to be in extensions.conf
						"NXXXXXX",
						"NXXNXXXXXX",
						"1800NXXXXXX",
						"1888NXXXXXX",
						"1877NXXXXXX",
						"1866NXXXXXX",
						"1NXXNXXXXXX",
						"011.",
						"911",
						"411",
						"311",
						);
			
			foreach ($results as $temp) {
				// temp[0] is "DIAL_OUT_1"
				// temp[1] is the dial prefix
				
				$trunknum = substr($temp[0],9);
				
				$name = "route".$trunknum;
				
				$trunks = array(1=>"OUT_".$trunknum); // only one trunk to use
				
				$patterns = array();
				foreach ($default_patterns as $pattern) {
					$patterns[] = $temp[1]."|".$pattern;
				}
				
				if ($trunknum == $def_trunk) {
					// this is the default trunk, add the patterns with no prefix
					$patterns = array_merge($patterns, $default_patterns);
				}
				
				// add this as a new route
				addroute($name, $patterns, $trunks,"new");
			}
			
			
			// delete old values
			$sql = "DELETE FROM globals WHERE (variable LIKE 'DIAL\\\_OUT\\\_%') OR (variable = 'OUT') ";
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
			
			// we need to re-generate extensions_additional.conf
			// i'm not sure how to do this from here
			
			// re-run our query
			$sql = "SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";
			// we SUBSTRING() to remove "outrt-"
			$results = $db->getAll($sql);
			if(DB::IsError($results)) {
				die($results->getMessage());
			}
		}
		
	} // else, it just means they have no routes.
	
	return $results;
}

//get unique outbound route patterns for a given context
function getroutepatterns($route) {
	global $db;
	$sql = "SELECT extension, args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk%' OR args LIKE'dialout-enum%') ORDER BY extension ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$patterns = array();
	foreach ($results as $row) {
		if ($row[0][0] == "_") {
			// remove leading _
			$pattern = substr($row[0],1);
		} else {
			$pattern = $row[0];
		}
		
		if (preg_match("/{EXTEN:(\d+)}/", $row[1], $matches)) {
			// this has a digit offset, we need to insert a |
			$pattern = substr($pattern,0,$matches[1])."|".substr($pattern,$matches[1]);
		}
		
		$patterns[] = $pattern;
	}
	return array_unique($patterns);
}

//get unique outbound route trunks for a given context
function getroutetrunks($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY priority ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$trunks = array();
	foreach ($results as $row) {
		if (preg_match('/^dialout-trunk,(\d+)/', $row[0], $matches)) {
			// check in_array -- even though we did distinct
			// we still might get ${EXTEN} and ${EXTEN:1} if they used | to split a pattern
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		} else if (preg_match('/^dialout-enum,(\d+)/', $row[0], $matches)) {
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		}
	}
	return $trunks;
}

//get password for this route
function getroutepassword($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY priority ";
	$results = $db->getOne($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	if (preg_match('/^.*,.*,.*,(\d+)/', $results, $matches)) {
		$password = $matches[1];
	} else {
		$password = "";
	}
	return $password;
	
}

//get outbound routes for a given trunk
function gettrunkroutes($trunknum) {
	global $db;
	
	$sql = "SELECT DISTINCT SUBSTRING(context,7), priority FROM extensions WHERE context LIKE 'outrt-%' AND (args LIKE 'dialout-trunk,".$trunknum.",%' OR args LIKE 'dialout-enum,".$trunknum.",%')ORDER BY context ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$routes = array();
	foreach ($results as $row) {
		$routes[$row[0]] = $row[1];
	}
	
	// array(routename=>priority)
	return $routes;
}

function addroute($name, $patterns, $trunks, $method, $pass) {
	global $db;

	$trunktech=array();

	//Retrieve each trunk tech for later lookup
	$sql="select * from globals WHERE variable LIKE 'OUT\\_%'";
        $result = $db->getAll($sql);
        if(DB::IsError($result)) {
		die($result->getMessage());
	}
	foreach($result as $tr) {
		$tech = strtok($tr[1], "/");
		$trunktech[$tr[0]]=$tech;
	}
	
 	if ($method=="new")
	{	
		$routepriority = getroutenames();
	 	$order=setroutepriorityvalue(count($routepriority));
	 	$name = sprintf ("%s-%s",$order,$name);
	}
	$trunks = array_values($trunks); // probably already done, but it's important for our dialplan

	
	foreach ($patterns as $pattern) {
		
		if (false !== ($pos = strpos($pattern,"|"))) {
			// we have a | meaning to not pass the digits on
			// (ie, 9|NXXXXXX should use the pattern _9NXXXXXX but only pass NXXXXXX, not the leading 9)
			
			$pattern = str_replace("|","",$pattern); // remove all |'s
			$exten = "EXTEN:".$pos; // chop off leading digit
		} else {
			// we pass the full dialed number as-is
			$exten = "EXTEN"; 
		}
		
		if (!preg_match("/^[0-9*]+$/",$pattern)) { 
			// note # is not here, as asterisk doesn't recoginize it as a normal digit, thus it requires _ pattern matching
			
			// it's not strictly digits, so it must have patterns, so prepend a _
			$pattern = "_".$pattern;
		}
		
		foreach ($trunks as $priority => $trunk) {
			$priority += 1; // since arrays are 0-based, but we want priorities to start at 1
			
			$sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
			$sql .= "('outrt-".$name."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$priority."', ";
			$sql .= "'Macro', ";
			if ($trunktech[$trunk] == "ENUM")
				$sql .= "'dialout-enum,".substr($trunk,4).",\${".$exten."},".$pass."'"; // cut off OUT_ from $trunk
			else
				$sql .= "'dialout-trunk,".substr($trunk,4).",\${".$exten."},".$pass."'"; // cut off OUT_ from $trunk
			$sql .= ")";
			
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
		}
		
		$priority += 1;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
		$sql .= "('outrt-".$name."', ";
		$sql .= "'".$pattern."', ";
		$sql .= "'".$priority."', ";
		$sql .= "'Macro', ";
		$sql .= "'outisbusy', ";
		$sql .= "'No available circuits')";
		
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
	}

	
	// add an include=>outrt-$name  to [outbound-allroutes]:
	
	// we have to find the first available priority.. priority doesn't really matter for the include, but
	// there is a unique index on (context,extension,priority) so if we don't do this we can't put more than
	// one route in the outbound-allroutes context.
	$sql = "SELECT priority FROM extensions WHERE context = 'outbound-allroutes' AND extension = 'include'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	$priorities = array();
	foreach ($results as $row) {
		$priorities[] = $row[0];
	}
	for ($priority = 1; in_array($priority, $priorities); $priority++);
	
	// $priority should now be the lowest available number
	
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
	$sql .= "('outbound-allroutes', ";
	$sql .= "'include', ";
	$sql .= "'".$priority."', ";
	$sql .= "'outrt-".$name."', ";
	$sql .= "'', ";
	$sql .= "'', ";
	$sql .= "'2')";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($priority.$result->getMessage());
	}
	
}

function deleteroute($name) {
	global $db;
	$sql = "DELETE FROM extensions WHERE context = 'outrt-".$name."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	$sql = "DELETE FROM extensions WHERE context = 'outbound-allroutes' AND application = 'outrt-".$name."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	return $result;
}

function renameRoute($oldname, $newname) {
	global $db;

	$route_prefix=substr($oldname,0,4);
	$newname=$route_prefix.$newname;
	$sql = "SELECT context FROM extensions WHERE context = 'outrt-".$newname."'";
	$results = $db->getAll($sql);
	if (count($results) > 0) {
		// there's already a route with this name
		return false;
	}
	
	$sql = "UPDATE extensions SET context = 'outrt-".$newname."' WHERE context = 'outrt-".$oldname."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
        $mypriority=sprintf("%d",$route_prefix);	
	$sql = "UPDATE extensions SET application = 'outrt-".$newname."', priority = '$mypriority' WHERE context = 'outbound-allroutes' AND application = 'outrt-".$oldname."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	return true;
}

function editroute($name, $patterns, $trunks, $pass) {
	deleteroute($name);
	addroute($name, $patterns, $trunks,"edit", $pass);
}

function getroute($route) {
	global $db;
 	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND args LIKE 'dialout-trunk,%' ORDER BY priority ";
 	$results = $db->getAll($sql);
 	if(DB::IsError($results)) {
 		die($results->getMessage());
 	}
 	
 	$trunks = array();
 	foreach ($results as $row) {
 		if (preg_match('/^dialout-trunk,(\d+)/', $row[0], $matches)) {
 			// check in_array -- even though we did distinct
 			// we still might get ${EXTEN} and ${EXTEN:1} if they used | to split a pattern
 			if (!in_array("OUT_".$matches[1], $trunks)) {
 				$trunks[] = "OUT_".$matches[1];
 			}
 		}
 	}
 	return $trunks;
}
function setroutepriorityvalue2($key)
{
	$my_lookup=array();
	$x=0;
	for ($j=97;$j<100;$j++)
	{
		for ($i=97;$i<123;$i++)
		{
			$my_lookup[$x++] = sprintf("%c%c",$j,$i);
		}
	}
echo "my key is $key $my_lookup[$key]";
	return ($my_lookup[$key]);
}
function setroutepriorityvalue($key)
{
	$key=$key+1;
	if ($key<10)
		$prefix = sprintf("00%d",$key);
	else if ((9<$key)&&($key<100))
		$prefix = sprintf("0%d",$key);
	else if ($key>100)
		$prefix = sprintf("%d",$key);
	return ($prefix);
}
function setroutepriority($routepriority, $reporoutedirection, $reporoutekey)
{
	global $db;
	$counter=-1;
	foreach ($routepriority as $tresult) 
	{
		$counter++;
		if (($counter==($reporoutekey-1)) && ($reporoutedirection=="up")) {
			// swap this one with the one before (move up)
			$temproute = $routepriority[$counter];
			$routepriority[ $counter ] = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $temproute;
			
		} else if (($counter==($reporoutekey)) && ($reporoutedirection=="down")) {
			// swap this one with the one after (move down)
			$temproute = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $routepriority[ $counter ];
			$routepriority[ $counter ] = $temproute;
		}
	}
	unset($temptrunk);
	$routepriority = array_values($routepriority); // resequence our numbers
	$counter=0;
	foreach ($routepriority as $tresult) 
	{
		$order=setroutepriorityvalue($counter++);
		$sql = sprintf("Update extensions set context='outrt-%s-%s' WHERE context='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage()); 
		}
	}
	// Delete and readd the outbound-allroutes entries
	$sql = "delete from  extensions WHERE context='outbound-allroutes'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        	die($result->getMessage().$sql);
	}
	$sql = "SELECT DISTINCT context FROM extensions WHERE context like 'outrt-%' ORDER BY context";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}

	$priority_loops=1;	
	foreach ($results as $row) {
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
		$sql .= "('outbound-allroutes', ";
		$sql .= "'include', ";
		$sql .= "'".$priority_loops++."', ";
		$sql .= "'".$row[0]."', ";
		$sql .= "'', ";
		$sql .= "'', ";
		$sql .= "'2')";
	
		//$sql = sprintf("Update extensions set application='outrt-%s-%s' WHERE context='outbound-allroutes' and  application='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage(). $sql); 
 		}
	}
	$routepriority = getroutenames();
	return ($routepriority);
}

 


function parse_conf($filename, &$conf, &$section) {
	if (is_null($conf)) {
		$conf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$conf[$section][ $matches[1] ] = $matches[2];
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
				}
				
				parse_conf($filename, $conf, $section);
			}
		}
	}
}

function readDialRulesFile() {
	global $localPrefixFile; // probably not the best way
	
	parse_conf($localPrefixFile, &$conf, &$section);
	
	return $conf;
}

function getDialRules($trunknum) {
	$conf = readDialRulesFile();
	if (isset($conf["trunk-".$trunknum])) {
		return $conf["trunk-".$trunknum];
	}
	return false;
}

function writeDialRulesFile($conf) {
	global $localPrefixFile; // probably not the best way
	
	$fd = fopen($localPrefixFile,"w");
	foreach ($conf as $section=>$values) {
		fwrite($fd, "[".$section."]\n");
		foreach ($values as $key=>$value) {
			fwrite($fd, $key."=".$value."\n");
		}
		fwrite($fd, "\n");
	}
	fclose($fd);
}

function addDialRules($trunknum, $dialrules) {
	$values = array();
	$i = 1;
	foreach ($dialrules as $rule) {
		$values["rule".$i++] = $rule;
	}
	
	$conf = readDialRulesFile();
	
	// rewrite for this trunk
	$conf["trunk-".$trunknum] = $values;
	
	writeDialRulesFile($conf);
}

function deleteDialRules($trunknum) {
	$conf = readDialRulesFile();
	
	// remove rules for this trunk
	unset($conf["trunk-".$trunknum]);
	
	writeDialRulesFile($conf);
}

function addqueue($account,$name,$password,$prefix,$goto,$agentannounce,$members) {
	global $db;
	
	//add to extensions table
	if ($agentannounce != 'None')
		$agentannounce="custom/$agentannounce";
	else
		$agentannounce="";

	$addarray = array('ext-queues',$account,'1','Answer',''.'','','0');
	addextensions($addarray);
	$addarray = array('ext-queues',$account,'2','SetCIDName',$prefix.'${CALLERIDNAME}','','0');
	addextensions($addarray);
	$addarray = array('ext-queues',$account,'3','SetVar','MONITOR_FILENAME=/var/spool/asterisk/monitor/q${EXTEN}-${TIMESTAMP}-${UNIQUEID}','','0');
	addextensions($addarray);
	$addarray = array('ext-queues',$account,'4','Queue',$account.'|t||'.$agentannounce.'|'.$_REQUEST['maxwait'],$name,'0');
	addextensions($addarray);
	$addarray = array('ext-queues',$account.'*','1','Macro','agent-add,'.$account.','.$password,'','0');
	addextensions($addarray);
	$addarray = array('ext-queues',$account.'**','1','Macro','agent-del,'.$account,'','0');
	addextensions($addarray);
	
	//start at priority 5. if changing this priority, also change in getqueueinfo()
	setGoto($account,'ext-queues','5',$goto,0);
	
	
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

function delqueue($account) {
	global $db;
	//delete from extensions table
	delextensions('ext-queues',$account);
	delextensions('ext-queues',$account.'*');
	delextensions('ext-queues',$account.'**');
	
	$sql = "DELETE FROM queues WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
    }

}

function getqueueinfo($account) {
	global $db;
	
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
	
	//get password from AddQueueMember command
	$sql = "SELECT args FROM extensions WHERE extension = '$account*' AND context = 'ext-queues'";
	list($args) = $db->getRow($sql);
	$password = explode(',',$args); //in table like agent-add,account,password
	$results['password'] = $password[2];
	
	//get the failover destination (at priority 5)
	$sql = "SELECT args FROM extensions WHERE extension = '".$account."' AND priority = '5'";
	list($args) = $db->getRow($sql);
	$results['goto'] = $args; 

	return $results;
}

// $formName is the name of the form we are drawing in
// $goto is the current goto destination setting
// $i is the destination set number (used when drawing multiple destination sets in a single form ie: digital receptionist)
function drawselects($formName,$goto,$i) {  

	//query for exisiting aa_N contexts
	$unique_aas = getaas();
	//get unique extensions
	$extens = getextens();
	//get unique ring groups
	$gresults = getgroups();
	//get unique queues
	$queues = getqueues();

	//get voicemail
	$uservm = getVoicemail();
	$vmcontexts = array_keys($uservm);
	foreach ($extens as $thisext) {
		$extnum = $thisext[0];
		// search vm contexts for this extensions mailbox
		foreach ($vmcontexts as $vmcontext) {
			if(isset($uservm[$vmcontext][$extnum])){
				$vmname = $uservm[$vmcontext][$extnum]['name'];
				$vmboxes[] = array($extnum, '"' . $vmname . '" <' . $extnum . '>');
			}
		}
	}
	
	$selectHtml = '	<tr><td colspan=2><input type="hidden" name="goto'.$i.'" value="">';				
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="ivr" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'ivr\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'ivr\';" '.(strpos($goto,'aa_') === false ? '' : 'CHECKED=CHECKED').' /> '._("Digital Receptionist").': ';
	$selectHtml .=	'<select name="ivr'.$i.'"/>';

	if (isset($unique_aas)) {
		foreach ($unique_aas as $unique_aa) {
			$menu_id = $unique_aa[0];
			$menu_name = $unique_aa[1];
			$selectHtml .= '<option value="'.$menu_id.'" '.(strpos($goto,$menu_id) === false ? '' : 'SELECTED').'>'.($menu_name ? $menu_name : 'Menu ID'.$menu_id);
		}
	}

	$selectHtml .=	'</select><br>';
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="extension" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'extension\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'extension\';" '.(strpos($goto,'ext-local') === false ? '' : 'CHECKED=CHECKED').'/> '._("Extension").': ';
	$selectHtml .=	'<select name="extension'.$i.'"/>';
	
	if (isset($extens)) {
		foreach ($extens as $exten) {
			$selectHtml .= '<option value="'.$exten[0].'" '.(strpos($goto,$exten[0]) === false ? '' : 'SELECTED').'>'.$exten[1];
		}
	}
			
	$selectHtml .=	'</select><br>';
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="voicemail" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'voicemail\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'voicemail\';" '.(strpos($goto,'vm') === false ? '' : 'CHECKED=CHECKED').' /> '._("Voicemail").': '; 
	$selectHtml .=	'<select name="voicemail'.$i.'"/>';
	
	if (isset($vmboxes)) {
		foreach ($vmboxes as $exten) {
			$selectHtml .= '<option value="'.$exten[0].'" '.(strpos($goto,$exten[0]) === false ? '' : 'SELECTED').'>'.$exten[1];
		}
	}
			
	$selectHtml .=	'</select><br>';
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="group" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'group\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'group\';" '.(strpos($goto,'ext-group') === false ? '' : 'CHECKED=CHECKED').' /> '._("Ring Group").': ';
	$selectHtml .=	'<select name="group'.$i.'"/>';
	
	if (isset($gresults)) {
		foreach ($gresults as $gresult) {
			$selectHtml .= '<option value="'.$gresult[0].'" '.(strpos($goto,$gresult[0]) === false ? '' : 'SELECTED').'>#'.$gresult[0];
		}
	}
				
	$selectHtml .=	'</select><br>';
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="queue" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'queue\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'queue\';" '.(strpos($goto,'ext-queues') === false ? '' : 'CHECKED=CHECKED').' /> '._("Queue").': ';
	$selectHtml .=	'<select name="queue'.$i.'"/>';
	
	if (isset($queues)) {
		foreach ($queues as $queue) {
			$selectHtml .= '<option value="'.$queue[0].'" '.(strpos($goto,$queue[0]) === false ? '' : 'SELECTED').'>'.$queue[0].':'.$queue[1];
		}
	}
				
	$selectHtml .=	'</select><br>';
	$selectHtml .=	'<input type="radio" name="goto_indicate'.$i.'" value="custom" onclick="javascript:document.'.$formName.'.goto'.$i.'.value=\'custom\';" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.'.$formName.'.goto'.$i.'.value=\'custom\';" '.(strpos($goto,'custom') === false ? '' : 'CHECKED=CHECKED').' />';
	$selectHtml .= '<a href="#" class="info"> '._("Custom App<span><br>ADVANCED USERS ONLY<br><br>Uses Goto() to send caller to a custom context.<br><br>The context name <b>MUST</b> contain the word 'custom' and should be in the format custom-context , extension , priority. Example entry:<br><br><b>custom-myapp,s,1</b><br><br>The <b>[custom-myapp]</b> context would need to be created and included in extensions_custom.conf</span>").'</a>:';
	$selectHtml .=	'<input type="text" size="15" name="custom_args'.$i.'" value="'.(strpos($goto,'custom') === false ? '' : $goto).'" />';
	$selectHtml .=	'<br></td></tr>';
	
	return $selectHtml;
}

function setGoto($account,$context,$priority,$goto,$i) {  //preforms logic for setting goto destinations
	if ($goto == 'extension') {
		$args = 'ext-local,'.$_REQUEST['extension'.$i].',1';
		$addarray = array($context,$account,$priority,'Goto',$args,'','0');
		addextensions($addarray);
	}
	elseif ($goto == 'voicemail') {
		$args = 'vm,'.$_REQUEST['voicemail'.$i];
		$addarray = array($context,$account,$priority,'Macro',$args,'','0');
		addextensions($addarray);
	}
	elseif ($goto == 'ivr') {
		$args = $_REQUEST['ivr'.$i].',s,1';
		$addarray = array($context,$account,$priority,'Goto',$args,'','0');
		addextensions($addarray);
	}
	elseif ($goto == 'group') {
		$args = 'ext-group,'.$_REQUEST['group'.$i].',1';
		$addarray = array($context,$account,$priority,'Goto',$args,'','0');
		addextensions($addarray);
	}
	elseif ($goto == 'custom') {
		$args = $_REQUEST['custom_args'.$i];
		$addarray = array($context,$account,$priority,'Goto',$args,'','0');
		addextensions($addarray);
	}
	elseif ($goto == 'queue') {
		$args = 'ext-queues,'.$_REQUEST['queue'.$i	].',1';
		$addarray = array($context,$account,$priority,'Goto',$args,'','0');
		addextensions($addarray);
	}
}

//get args for specified exten and priority - primarily used to grab goto destination
function getargs($exten,$priority,$context) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$exten."' AND priority = '".$priority."' AND context = '".$context."'";
	list($args) = $db->getRow($sql);
	return $args;
}

function addgroup($account,$grplist,$grptime,$grppre,$goto) {
	global $db;
	
	$addarray = array('ext-group',$account,'1','Macro','rg-group,'.$grptime.','.$grppre.','.$grplist,'','0');
	addextensions($addarray);
	
	setGoto($account,'ext-group','2',$goto,0);
}

/** Recursively read voicemail.conf (and any included files)
 * This function is called by getVoicemailConf()
 */
function parse_voicemailconf($filename, &$vmconf, &$section) {
	if (is_null($vmconf)) {
		$vmconf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*(\d+)\s*=>\s*(\d*),(.*),(.*),(.*),(.*)\s*([;#].*)?/",$line,$matches)) {
				// "mailbox=>password,name,email,pager,options"
				// this is a voicemail line	
				$vmconf[$section][ $matches[1] ] = array("mailbox"=>$matches[1],
									"pwd"=>$matches[2],
									"name"=>$matches[3],
									"email"=>$matches[4],
									"pager"=>$matches[5],
									"options"=>array(),
									);
								
				// parse options
				//output($matches);
				foreach (explode("|",$matches[6]) as $opt) {
					$temp = explode("=",$opt);
					//output($temp);
					if (isset($temp[1])) {
						list($key,$value) = $temp;
						$vmconf[$section][ $matches[1] ]["options"][$key] = $value;
					}
				}
			} else if (preg_match("/^\s*(\d+)\s*=>\s*dup,(.*)\s*([;#].*)?/",$line,$matches)) {
				// "mailbox=>dup,name"
				// duplace name line
				$vmconf[$section][ $matches[1] ]["dups"][] = $matches[2];
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
				}
				
				parse_voicemailconf($filename, $vmconf, $section);
				
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$vmconf[$section][ $matches[1] ] = $matches[2];
			}
		}
		fclose($fd);
	}
}

function getVoicemail() {
	$vmconf = null;
	$section = null;
	
	// yes, this is hardcoded.. is this a bad thing?
	parse_voicemailconf("/etc/asterisk/voicemail.conf", $vmconf, $section);
	
	return $vmconf;
}

/** Write the voicemail.conf file
 * This is called by saveVoicemail()
 * It's important to make a copy of $vmconf before passing it. Since this is a recursive function, has to
 * pass by reference. At the same time, it removes entries as it writes them to the file, so if you don't have
 * a copy, by the time it's done $vmconf will be empty.
*/
function write_voicemailconf($filename, &$vmconf, &$section, $iteration = 0) {
	if ($iteration == 0) {
		$section = null;
	}
	
	$output = array();
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^(\s*)(\d+)(\s*)=>(\s*)(\d+),(.*),(.*),(.*),(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// "mailbox=>password,name,email,pager,options"
				// this is a voicemail line
				//DEBUG echo "\nmailbox";
				
				// make sure we have something as a comment
				if (!isset($matches[10])) {
					$matches[10] = "";
				}
				
				// $matches[1] [3] and [4] are to preserve indents/whitespace, we add these back in
				
				if (isset($vmconf[$section][ $matches[2] ])) {	
					// we have this one loaded
					// repopulate from our version
					$temp = & $vmconf[$section][ $matches[2] ];
					
					$options = array();
					foreach ($temp["options"] as $key=>$value) {
						$options[] = $key."=".$value;
					}
					
					$output[] = $matches[1].$temp["mailbox"].$matches[3]."=>".$matches[4].$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options).$matches[10];
					
					// remove this one from $vmconf
					unset($vmconf[$section][ $matches[2] ]);
				} else {
					// we don't know about this mailbox, so it must be deleted
					// (and hopefully not JUST added since we did read_voiceamilconf)
					
					// do nothing
				}
				
			} else if (preg_match("/^(\s*)(\d+)(\s*)=>(\s*)dup,(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// "mailbox=>dup,name"
				// duplace name line
				// leave it as-is (for now)
				//DEBUG echo "\ndup mailbox";
				$output[] = $line;
			} else if (preg_match("/^(\s*)#include(\s+)(.*)(\s*[;#].*)?$/",$line,$matches)) {
				// include another file
				//DEBUG echo "\ninclude ".$matches[3]."<blockquote>";
				
				// make sure we have something as a comment
				if (!isset($matches[4])) {
					$matches[4] = "";
				}
				
				if ($matches[3][0] == "/") {
					// absolute path
					$include_filename = $matches[3];
				} else {
					// relative path
					$include_filename =  dirname($filename)."/".$matches[3];
				}
				
				$output[] = $matches[1]."#include".$matches[2].$matches[3].$matches[4];
				write_voicemailconf($include_filename, $vmconf, $section, $iteration+1);
				
				//DEBUG echo "</blockquote>";
				
			} else if (preg_match("/^(\s*)\[(.+)\](\s*[;#].*)?$/",$line,$matches)) {
				// section name
				//DEBUG echo "\nsection";
				
				// make sure we have something as a comment
				if (!isset($matches[3])) {
					$matches[3] = "";
				}
				
				// check if this is the first run (section is null)
				if ($section !== null) {
					// we need to add any new entries here, before the section changes
					//DEBUG echo "<blockquote><i>";
					//DEBUG var_dump($vmconf[$section]);
					if (isset($vmconf[$section])){  //need this, or we get an error if we unset the last items in this section - should probably automatically remove the section/context from voicemail.conf
						foreach ($vmconf[$section] as $key=>$value) {
							if (is_array($value)) {
								// mailbox line
								
								$temp = & $vmconf[$section][ $key ];
								
								$options = array();
								foreach ($temp["options"] as $key1=>$value) {
									$options[] = $key1."=".$value;
								}
								
								$output[] = $temp["mailbox"]." => ".$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options);
								
								// remove this one from $vmconf
								unset($vmconf[$section][ $key ]);
								
							} else {
								// option line
								
								$output[] = $key."=".$vmconf[$section][ $key ];
								
								// remove this one from $vmconf
								unset($vmconf[$section][ $key ]);
							}
						}
					} 
					//DEBUG echo "</i></blockquote>";
				}
				
				$section = strtolower($matches[2]);
				$output[] = $matches[1]."[".$section."]".$matches[3];
				$existing_sections[] = $section; //remember that this section exists

			} else if (preg_match("/^(\s*)([a-zA-Z0-9-_]+)(\s*)=(\s*)(.*?)(\s*[;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				//DEBUG echo "\noption line";
				
				
				// make sure we have something as a comment
				if (!isset($matches[6])) {
					$matches[6] = "";
				}
				
				if (isset($vmconf[$section][ $matches[2] ])) {
					$output[] = $matches[1].$matches[2].$matches[3]."=".$matches[4].$vmconf[$section][ $matches[2] ].$matches[6];
					
					// remove this one from $vmconf
					unset($vmconf[$section][ $matches[2] ]);
				} 
				// else it's been deleted, so we don't write it in
				
			} else {
				// unknown other line -- probably a comment or whitespace
				//DEBUG echo "\nother: ".$line;
				
				$output[] = str_replace(array("\n","\r"),"",$line); // str_replace so we don't double-space
			}
		}
		
		if ($iteration == 0) {
			// we need to add any new entries here, since it's the end of the file
			//DEBUG echo "END OF FILE!! <blockquote><i>";
			//DEBUG var_dump($vmconf);
			foreach (array_keys($vmconf) as $section) {
				if (!in_array($section,$existing_sections))  // If this is a new section, write the context label
					$output[] = "[".$section."]";
				foreach ($vmconf[$section] as $key=>$value) {
					if (is_array($value)) {
						// mailbox line
						
						$temp = & $vmconf[$section][ $key ];
						
						$options = array();
						foreach ($temp["options"] as $key=>$value) {
							$options[] = $key."=".$value;
						}
						
						$output[] = $temp["mailbox"]." => ".$temp["pwd"].",".$temp["name"].",".$temp["email"].",".$temp["pager"].",". implode("|",$options);
						
						// remove this one from $vmconf
						unset($vmconf[$section][ $key ]);
						
					} else {
						// option line
						
						$output[] = $key."=".$vmconf[$section][ $key ];
						
						// remove this one from $vmconf
						unset($vmconf[$section][$key ]);
					}
				}
			}
			//DEBUG echo "</i></blockquote>";
		}
		
		fclose($fd);
		
		//DEBUG echo "\n\nwriting ".$filename;
		//DEBUG echo "\n-----------\n";
		//DEBUG echo implode("\n",$output);
		//DEBUG echo "\n-----------\n";
		
		// write this file back out
		
		if ($fd = fopen($filename, "w")) {
			fwrite($fd, implode("\n",$output)."\n");
			fclose($fd);
		}
		
	}
}

function saveVoicemail($vmconf) {
	// yes, this is hardcoded.. is this a bad thing?
	write_voicemailconf("/etc/asterisk/voicemail.conf", $vmconf, $section);
}

function getsystemrecordings($path) {
	$i = 0;
	$arraycount = 0;
	
	if (is_dir($path)){
		if ($handle = opendir($path)){
			while (false !== ($file = readdir($handle))){ 
				if (($file != ".") && ($file != "..") && ($file != "CVS") && (strpos($file, "aa_") === FALSE)    ) 
				{
					$file_parts=explode(".",$file);
					$filearray[($i++)] = $file_parts[0];
				}
			}
		closedir($handle); 
		}
		   
	}
	if (isset($filearray)) sort($filearray);
	return ($filearray);
}

function getmusiccategory($path) {
	$i = 0;
	$arraycount = 0;
	
	if (is_dir($path)){
		if ($handle = opendir($path)){
			while (false !== ($file = readdir($handle))){ 
				if ( ($file != ".") && ($file != "..") && ($file != "CVS")  ) 
				{
					if (is_dir("$path/$file"))
						$filearray[($i++)] = "$file";
				}
			}
		closedir($handle); 
		}
	}
	if (isset($filearray)) sort($filearray);
	return ($filearray);
}

function rmdirr($dirname)
{
    // Sanity check
    if (!file_exists($dirname)) {
        return false;
    }
 
    // Simple delete for a file
    if (is_file($dirname)) {
        return unlink($dirname);
    }
 
    // Loop through the folder
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
 
        // Recurse
        rmdirr("$dirname/$entry");
    }
 
    // Clean up
    $dir->close();
    return rmdir($dirname);
}
function backuptableexists() {
        global $db;

        $sql ="CREATE TABLE IF NOT EXISTS `Backup` (";
                $sql.="`Name` varchar(50) default NULL,";
                $sql.="`Voicemail` varchar(50) default NULL,";
                $sql.="`Recordings` varchar(50) default NULL,";
                $sql.="`Configurations` varchar(50) default NULL,";
                $sql.="`CDR` varchar(55) default NULL,";
                $sql.="`FOP` varchar(50) default NULL,";
                $sql.="`Minutes` varchar(50) default NULL,";
                $sql.="`Hours` varchar(50) default NULL,";
                $sql.="`Days` varchar(50) default NULL,";
                $sql.="`Months` varchar(50) default NULL,";
                $sql.="`Weekdays` varchar(50) default NULL,";
                $sql.="`Command` varchar(200) default NULL,";
                $sql.="`Method` varchar(50) default NULL,";
                $sql.="`ID` int(11) NOT NULL auto_increment,";
                $sql.="PRIMARY KEY  (ID))";
        $results = $db->query($sql);
}
function setrecordingstatus($extension, $direction, $state) {
$amp_conf = parse_amportal_conf("/etc/amportal.conf");
        $fp = fsockopen("localhost", 5038, $errno, $errstr, 10);
        if (!$fp) {
                echo "Unable to connect to Asterisk Manager ($errno)<br />\n";
        } else {
                $buffer='';
                stream_set_timeout($fp, 5);
                $buffer = fgets($fp);
                if ($buffer!="Asterisk Call Manager/1.0\r\n")
                        echo "Asterisk Call Manager not responding<br />\n";
                else {
                        $out="Action: Login\r\nUsername: ".$amp_conf['AMPMGRUSER']."\r\nSecret: ".$amp_conf['AMPMGRPASS']."\r\n\r\n";
                        fwrite($fp,$out);
                        $buffer=fgets($fp);
                        if ($buffer!="Response: Success\r\n")
                                echo "Asterisk authentication failed:<br />$buffer<br />\n";
                        else {
                                $buffers=fgets($fp); // get rid of Message: Authentication accepted
				if ($direction=="In"){
					if ($state=="Always")
                                		$out="Action: Command\r\nCommand: database put RECORD-IN $extension ENABLED\r\n\r\n";
					else if ($state=="Never")
                                		$out="Action: Command\r\nCommand: database put RECORD-IN $extension DISABLED\r\n\r\n";
					else
                                		$out="Action: Command\r\nCommand: database del RECORD-IN $extension\r\n\r\n";
				}
				else if ($direction=="Out"){
					if ($state=="Always")
                                		$out="Action: Command\r\nCommand: database put RECORD-OUT $extension ENABLED\r\n\r\n";
					else if ($state=="Never")
                                		$out="Action: Command\r\nCommand: database put RECORD-OUT $extension DISABLED\r\n\r\n";
					else
                                		$out="Action: Command\r\nCommand: database del RECORD-OUT $extension\r\n\r\n";
				}
                                fwrite($fp,$out);
                                $buffer=fgets($fp); // get rid of a blank line
                                $buffer=fgets($fp);
                                if ($buffer!="Response: Follows\r\n")
                                        echo "Asterisk reload command not understood $buffer<br />\n";
                        }
                }

        }
        fclose($fp);
}
?>

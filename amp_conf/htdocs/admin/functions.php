<?
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

//get unique voice menu numbers - returns 2 dimensional array
function getaas() {
	global $db;
	$sql = "SELECT context FROM extensions WHERE extension = 's' AND priority = '1' AND context LIKE 'aa_%'";
	$unique_aas = $db->getAll($sql);
	if(DB::IsError($unique_aas)) {
	   die('unique: '.$unique_aas->getMessage());
	}
	return $unique_aas;
}

//get the existing extensions
function getextens() {
	$sip = getSip();
	$iax = getIax();
	$results = array_merge($sip, $iax);
	sort($results);
	return $results;
}

function getSip() {
	global $db;
	$sql = "SELECT id,data FROM sip WHERE keyword = 'callerid' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

function getIax() {
	global $db;
	$sql = "SELECT id,data FROM iax WHERE keyword = 'callerid' ORDER BY id";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

//get the existing group extensions
function getgroups() {
	global $db;
	$sql = "SELECT extension FROM extensions WHERE args = 'rg-group' ORDER BY extension";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	return $results;
}

//get extensions in specified group
function getgroupextens($grpexten) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$grpexten."' AND priority = '1'";
	$thisGRP = $db->getAll($sql);
	if(DB::IsError($thisGRP)) {
	   die($thisGRP->getMessage());
	}
	return $thisGRP;
}
//get ring time in specified group
function getgrouptime($grpexten) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$grpexten."' AND priority = '2'";
	$thisGRPtime = $db->getAll($sql);
	if(DB::IsError($thisGRPtime)) {
	   die($thisGRPtime->getMessage());
	}
	return $thisGRPtime;
}
//get goto in specified group
function getgroupgoto($grpexten) {
	global $db;
	$sql = "SELECT args FROM extensions WHERE extension = '".$grpexten."' AND priority = '4'";
	$thisGRPgoto = $db->getAll($sql);
	if(DB::IsError($thisGRPgoto)) {
	   die($thisGRPgoto->getMessage());
	}
	return $thisGRPgoto;
}

//add to extensions table - used in callgroups.php
function addextensions($addarray) {
	global $db;
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('".$addarray[0]."', '".$addarray[1]."', '".$addarray[2]."', '".$addarray[3]."', '".$addarray[4]."', '".$addarray[5]."' , '".$addarray[6]."')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage());
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
function aainfo($menu_num) {
	global $db;
	//do another select for all parts in this aa_
	$sql = "SELECT * FROM extensions WHERE context = 'aa_".$menu_num."' ORDER BY extension";
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

//create iax if it doesn't exist
function iaxexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `iax` (`id` int(11) NOT NULL default '-1',`keyword` varchar(20) NOT NULL default '',`data` varchar(50) NOT NULL default '',`flags` int(1) NOT NULL default '0',PRIMARY KEY  (`id`,`keyword`))";
	$results = $db->query($sql);
}

//add to iax table
function addiax($account,$callerid) {
	iaxexists();
	global $db;
    $iaxfields = array(array($account,'account',$account),
                    array($account,'secret',$_REQUEST['secret']),
                    array($account,'notransfer',$_REQUEST['notransfer']),
                    array($account,'context',$_REQUEST['context']),
                    array($account,'host',$_REQUEST['host']),
                    array($account,'type',$_REQUEST['type']),
                    array($account,'mailbox',$_REQUEST['mailbox']),
                    array($account,'username',$_REQUEST['username']),
					array($account,'port',$_REQUEST['iaxport']),
					array($account,'callerid',$callerid));

    $compiled = $db->prepare('INSERT INTO iax (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$iaxfields);
    if(DB::IsError($result)) {
        die($result->getMessage()."<br><br>".$sql);	
    }	

	//add E<enten>=IAX2 to global vars (appears in extensions_additional.conf)
	$sql = "INSERT INTO globals VALUES ('E$account', 'IAX2')"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage()); 
	}
}

//create sip if it doesn't exist
function sipexists() {
	global $db;
	$sql = "CREATE TABLE IF NOT EXISTS `sip` (`id` int(11) NOT NULL default '-1',`keyword` varchar(20) NOT NULL default '',`data` varchar(50) NOT NULL default '',`flags` int(1) NOT NULL default '0',PRIMARY KEY  (`id`,`keyword`))";
	$results = $db->query($sql);
}

//add to sip table
function addsip($account,$callerid) {
	sipexists();
	global $db;
    $sipfields = array(array($account,'account',$account),
	                    array($account,'secret',$_REQUEST['secret']),
	                    array($account,'canreinvite',$_REQUEST['canreinvite']),
	                    array($account,'context',$_REQUEST['context']),
	                    array($account,'dtmfmode',$_REQUEST['dtmfmode']),
	                    array($account,'host',$_REQUEST['host']),
	                    array($account,'type',$_REQUEST['type']),
	                    array($account,'mailbox',$_REQUEST['mailbox']),
	                    array($account,'username',$_REQUEST['username']),
						array($account,'nat',$_REQUEST['nat']),
						array($account,'port',$_REQUEST['port']),
						array($account,'callerid',$callerid));

	    $compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
		$result = $db->executeMultiple($compiled,$sipfields);
	    if(DB::IsError($result)) {
	        die($result->getMessage()."<br><br>".$sql);	
	    }
	    
	//add E<enten>=SIP to global vars (appears in extensions_additional.conf)
	$sql = "INSERT INTO globals VALUES ('E$account', 'SIP')"; 
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getMessage()); 
	}
}

function addaccount($account) {
	extensionsexists();
	global $db;
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('ext-local', '".$account."', '1', 'Macro', 'exten-vm,".$account.",".$account."', NULL , '0')";
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
	if (count($thisExten) == 0) {  //if nothing was pulled from sip, then it must be iax
		$sql = "SELECT * FROM iax WHERE id = '$extdisplay'";
		$thisExten = $db->getAll($sql);
		if(DB::IsError($thisExten)) {
		   die($thisExten->getMessage());
		}	
		$thisExten[] = array('$extdisplay','dtmfmode','iax2','info');  //add this to the array - as it doesn't exist in the table
	}
	return $thisExten;
}

//changes requested for SIP extension (extensions.php)
function editSip ($account,$callerid){
	global $db;
    $sipfields = array(array($account,$account,'account'),
                    array($_REQUEST['secret'],$account,'secret'),
                    array($_REQUEST['canreinvite'],$account,'canreinvite'),
                    array($_REQUEST['context'],$account,'context'),
                    array($_REQUEST['dtmfmode'],$account,'dtmfmode'),
                    array($_REQUEST['host'],$account,'host'),
                    array($_REQUEST['type'],$account,'type'),
                    array($_REQUEST['mailbox'],$account,'mailbox'),
                    array($_REQUEST['username'],$account,'username'),
					array($_REQUEST['nat'],$account,'nat'),
					array($_REQUEST['port'],$account,'port'),
					array($callerid,$account,'callerid'));

    $compiled = $db->prepare('UPDATE sip SET data = ? WHERE id = ? AND keyword = ? LIMIT 1');
    $result = $db->executeMultiple($compiled,$sipfields);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
    return $result;
}

//changes requested for IAX extension (extensions.php)
function editIax ($account,$callerid){
	global $db;
    $iaxfields = array(array($account,$account,'account'),
                    array($_REQUEST['secret'],$account,'secret'),
                    array($_REQUEST['notransfer'],$account,'notransfer'),
                    array($_REQUEST['context'],$account,'context'),
                    array($_REQUEST['host'],$account,'host'),
                    array($_REQUEST['type'],$account,'type'),
                    array($_REQUEST['mailbox'],$account,'mailbox'),
                    array($_REQUEST['username'],$account,'username'),
					array($_REQUEST['port'],$account,'port'),
					array($callerid,$account,'callerid'));

    $compiled = $db->prepare('UPDATE iax SET data = ? WHERE id = ? AND keyword = ? LIMIT 1');
    $result = $db->executeMultiple($compiled,$iaxfields);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
    return $result;
}

//Delete an extension (extensions.php)
function delExten($extdisplay) {
	global $db;
    $sql = "DELETE FROM sip WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
    $sql = "DELETE FROM iax WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
	$sql = "DELETE FROM globals WHERE variable = 'E$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
}
?>

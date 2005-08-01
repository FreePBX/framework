<?php /* $Id$ */
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

include 'common/php-asmanager.php';

function adddevice($id,$tech,$dial,$devicetype,$user,$description){
	global $db;
	global $amp_conf;
	//ensure this id is not already in use
	$devices = getdevices();
	foreach($devices as $device) {
		if ($device['id']==$id) {
			echo "<script>javascript:alert('"._("This device id is already in use")."');</script>";
			return false;
		}
	}
	//unless defined, $dial is TECH/id
	//zap is an exception
	if (empty($dial) && strtolower($tech) == "zap")
		$dial = "ZAP/".$_REQUEST['channel'];
	if (empty($dial))
		$dial = strtoupper($tech)."/".$id;
	
	
	//insert into devices table
	$sql="INSERT INTO devices (id,tech,dial,devicetype,user,description) values (\"$id\",\"$tech\",\"$dial\",\"$devicetype\",\"$user\",\"$description\")";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}
	
	//add details to astdb
	//TODO submitting the form will reset the logged in user for this device to default
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		$astman->database_put("DEVICE",$id."/dial",$dial);
		$astman->database_put("DEVICE",$id."/type",$devicetype);
		$astman->database_put("DEVICE",$id."/user",$user);
		$astman->database_put("AMPUSER",$user."/device",$id);
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//voicemail symlink
	exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
	exec("/bin/ln -s /var/spool/asterisk/voicemail/default/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
		
	//take care of sip/iax/zap config
	$funct = "add".strtolower($tech);
	if(function_exists($funct)){
		$funct($id);
	}
	
	//if the device is of type 'fixed', then we can use HINT
	//TODO is it possible to use ${variables} for a HINT extensions (ie: for adhoc devices)
	if(($devicetype == "fixed") && ($user != "none")) {
		addhint($user,$dial);
	}
}

function deldevice($account){
	global $db;
	global $amp_conf;
	
	//get all info about device
	$devinfo = getdeviceInfo($account);
	
	//delete from devices table
	$sql="DELETE FROM devices WHERE id = \"$account\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}
	
	//delete details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		$astman->database_del("DEVICE",$account."/dial");
		$astman->database_del("DEVICE",$account."/type");
		$astman->database_del("DEVICE",$account."/user");
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//voicemail symlink
	exec("rm -f /var/spool/asterisk/voicemail/device/".$account);
	
	//take care of sip/iax/zap config
	$funct = "del".strtolower($devinfo['tech']);
	if(function_exists($funct)){
		$funct($account);
	}
	
	//take care of any hint priority
	delhint($devinfo['user']);
}

function getdeviceInfo($account){
	global $db;
	//get all the variables for the meetme
	$sql = "SELECT * FROM devices WHERE id = '$account'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	
	//take care of sip/iax/zap config
	$funct = "get".strtolower($results['tech']);
	if(function_exists($funct)){
		$devtech = $funct($account);
		if (is_array($devtech)){
			$results = array_merge($results,$devtech);
		}
	}
	
	return $results;
}

//make sure we can connect to Asterisk Manager
checkAstMan();

$dispnum = 'devices'; //used for switch on config.php
//create vars from the request
extract($_REQUEST);

//if submitting form, update database
switch ($action) {
	case "add":
		adddevice($deviceid,$tech,$dial,$devicetype,$deviceuser,$description);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "del":
		deldevice($extdisplay);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "edit":  //just delete and re-add
		deldevice($extdisplay);
		adddevice($deviceid,$tech,$dial,$devicetype,$deviceuser,$description);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "resetall":  //form a url with this option to nuke the AMPUSER & DEVICE trees and start over.
		users2astdb();
		devices2astdb();
	break;
}
?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>"><?php echo _("Add Device")?></a><br></li>
<?php 
//get unique incoming routes
$devices = getdevices();

if (isset($devices)) {
	foreach ($devices as $device) {
		echo "<li><a id=\"".($extdisplay==$device['id'] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$device['id']}\">{$device['description']} <{$device['id']}></a></li>";
	}
}
?>
</div>


<div class="content">
<?php 
	if ($action == 'del') {
		echo '<br><h3>'.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else if(empty($tech) && empty($extdisplay)) {
?>
		<h2><?php echo _("Add a Device")?></h2>
		<h5><?php echo _("Select device technology:")?></h5>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=sip"><?php echo _("SIP")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=iax2"><?php echo _("IAX2")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=zap"><?php echo _("ZAP")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=custom"><?php echo _("Custom")?></a><br><br>
<?php
	} else {
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
?>
<?php if ($extdisplay) {	
	$deviceInfo=getdeviceInfo($extdisplay);
	extract($deviceInfo,EXTR_PREFIX_ALL,'devinfo');
	$tech = $devinfo_tech;
	if (is_array($deviceInfo)) extract($deviceInfo);
?>
		<h2><?php echo strtoupper($tech)." "._("Device")?>: <?php echo extdisplay; ?></h2>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Device")?> <?php echo $extdisplay ?></a></p>
<?php } else { ?>
		<h2><?php echo _("Add")." ".strtoupper($tech)." "._("Device")?></h2>
<?php } ?>
		<form name="addNew" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
		<input type="hidden" name="tech" value="<?php echo $tech ?>">
		<table>
		
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit Device') : _('Add Device')) ?><hr></h5></td></tr>

		<tr <?php echo ($extdisplay ? 'style="display:none"':'') ?>>
			<td>
				<a href="#" class="info"><?php echo _("Device ID")?><span><?php echo _('Give your device a unique integer ID.  The device will use this ID to authenicate to the system.')?></span></a>:
			</td>
			<td>
				<input type="text" name="deviceid" value="<?php echo $extdisplay ?>">
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Description")?><span><?php echo _("The caller id name for this device will be set to this description until it is logged into.")?><br></span></a>:
			</td><td>
				<input type="text" name="description" value="<?php echo $devinfo_description ?>"/>
			</td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Device Type")?><span><?php echo _('Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into by users.')?></span></a>:</td>
			<td>
				<select name="devicetype">
					<option value="fixed" <?php  echo ($devinfo_devicetype == 'fixed' ? 'SELECTED' : '')?>><?php echo _("Fixed")?>
					<option value="adhoc" <?php  echo ($devinfo_devicetype == 'adhoc' ? 'SELECTED' : '')?>><?php echo _("Adhoc")?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td><a href="#" class="info"><?php echo _("Default User")?><span><?php echo _('Fixed devices will always mapped to this user.  Adhoc devices will be mapped to this user by default.')?></span></a>:</td>
			<td>
				<select name="deviceuser">
					<option value="none" <?php echo ($devinfo_user == 'none' ? 'SELECTED' : '')?>><?php echo _("none")?>
			<?php 
				//get unique extensions
				$users = getextens();
				if (isset($users)) {
					foreach ($users as $auser) {
						echo '<option value="'.$auser[0].'" '.($user == $auser[0] ? 'SELECTED' : '').'>'.$auser[0];	
					}
				}
			?>
			</td>
		</tr>
		
		<tr>
			<td><br></td>
		</tr>
		
<?php
switch(strtolower($tech)) {
	case "zap":
		$basic = array(
			'channel' => '',
		);
		$advanced = array(
			'context' => 'from-internal',
			'signalling' => 'fxo_ks',
			'echocancel' => 'yes',
			'echocancelwhenbridged' => 'no',
			'echotraining' => '800',
			'busydetect' => 'no',
			'busycount' => '7',
			'callprogress' => 'no',
			'dial' => ''
		);
	break;
	case "iax2":
		$basic = array(
			'secret' => '',
		);
		$advanced = array(
			'notransfer' => 'yes',
			'context' => 'from-internal',
			'host' => 'dynamic',
			'type' => 'friend',
			'port' => '4569',
			'qualify' => 'no',
			'disallow' => '',
			'allow' => '',
			'dial' => ''
		);		
	break;
	case "sip":
		$basic = array(
			'secret' => '',
			'dtmfmode' => 'rfc2833'
		);
		$advanced = array(
			'canreinvite' => 'no',
			'context' => 'from-internal',
			'host' => 'dynamic',
			'type' => 'friend',
			'nat' => 'never',
			'port' => '5060',
			'qualify' => 'no',
			'callgroup' => '',
			'pickupgroup' => '',
			'disallow' => '',
			'allow' => '',
			'dial' => ''
		);
	break;
	case "custom":
		$basic = array(
			'dial' => '',
		);
		$advanced = array();
	break;
}

if($extdisplay) {
	foreach($basic as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
	}
	foreach($advanced as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
	}
} else {
	foreach($basic as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$value}\"/></td></tr>";
	}
	foreach($advanced as $key => $value) {
		echo "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>";
	}
}
?>
			
		<tr>
			<td colspan=2>
				<br><br><h6><input name="Submit" type="button" value="<?php echo _("Submit")?>" onclick="javascript:if(addNew.deviceid.value=='' || parseInt(addNew.deviceid.value)!=addNew.deviceid.value) {alert('<?php echo _("Please enter a device id.")?>')} else {addNew.submit();}"></h6>
			</td>
		</tr>
		</table>
		
		</form>
<?php 		
	} //end if action == delGRP
	

?>


<?php
//add to sip table
function addsip($account) {
	sipexists();
	global $db;
	global $currentFile;
	$sipfields = array(array($account,'account',$account),
	array($account,'accountcode',($_REQUEST['accountcode'])?$_REQUEST['accountcode']:''),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'canreinvite',($_REQUEST['canreinvite'])?$_REQUEST['canreinvite']:'no'),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'dtmfmode',($_REQUEST['dtmfmode'])?$_REQUEST['dtmfmode']:''),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:'dynamic'),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:'friend'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:$account),
	array($account,'nat',($_REQUEST['nat'])?$_REQUEST['nat']:'never'),
	array($account,'port',($_REQUEST['port'])?$_REQUEST['port']:'5060'),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:'no'),
	array($account,'callgroup',($_REQUEST['callgroup'])?$_REQUEST['callgroup']:''),
	array($account,'pickupgroup',($_REQUEST['pickupgroup'])?$_REQUEST['pickupgroup']:''),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'));

	$compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$sipfields);
	if(DB::IsError($result)) {
		die($result->getDebugInfo()."<br><br>".'error adding to SIP table');	
	}
		   

	//script to write sip conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);

}

function delsip($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM sip WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}

	//script to write sip conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function getsip($account) {
	global $db;
	$sql = "SELECT keyword,data FROM sip WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

//add to iax table
function addiax2($account) {
	global $db;
	global $currentFile;
	$iaxfields = array(array($account,'account',$account),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'notransfer',($_REQUEST['notransfer'])?$_REQUEST['notransfer']:'yes'),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:'dynamic'),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:'friend'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:$account),
	array($account,'port',($_REQUEST['iaxport'])?$_REQUEST['iaxport']:'4569'),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:'no'),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'));

	$compiled = $db->prepare('INSERT INTO iax (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$iaxfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>error adding to IAX table");	
	}	


	//script to write iax2 conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function deliax2($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM iax WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}
	
	//script to write iax2 conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function getiax2($account) {
	global $db;
	$sql = "SELECT keyword,data FROM iax WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function addzap($account) {
	zapexists();
	global $db;
	global $currentFile;
	$zapfields = array(
	array($account,'account',$account),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'),
	array($account,'signalling',($_REQUEST['signalling'])?$_REQUEST['signalling']:'fxo_ks'),
	array($account,'echocancel',($_REQUEST['echocancel'])?$_REQUEST['echocancel']:'yes'),
	array($account,'echocancelwhenbridged',($_REQUEST['echocancelwhenbridged'])?$_REQUEST['echocancelwhenbridged']:'no'),
	array($account,'echotraining',($_REQUEST['echotraining'])?$_REQUEST['echotraining']:'800'),
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


	//script to write zap conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_zap_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function delzap($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM zap WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}
	
	//script to write zap conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_zap_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function getzap($account) {
	global $db;
	$sql = "SELECT keyword,data FROM zap WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function addhint($account,$hint){
	global $db;
	global $currentFile;	
	//delete any existing hint for this extension
	delhint($account);
	
	//Add 'hint' priority if passed
	if ($hint != '') {
		$sql = "INSERT INTO extensions (context, extension, priority, application) VALUES ('ext-local', '".$account."', 'hint', '".$hint."')";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			echo $result->getMessage().$sql;
		}
	}
	$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	exec($wScript1);
}

function delhint($user) {
	global $currentFile;
	global $db;
	//delete from devices table
	$sql="DELETE FROM extensions WHERE extension = \"{$user}\" AND priority = \"hint\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}
	$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	exec($wScript1);
}

// this function rebuilds the astdb based on device table contents
// used on devices.php if action=resetall
function devices2astdb(){
	require_once('common/php-asmanager.php');
	checkAstMan();
	global $db;
	global $amp_conf;
	$sql = "SELECT * FROM devices";
	$devresults = $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($devresults)) {
		$devresults = null;
	}

	//add details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {	
		$astman->database_deltree("DEVICE");
		foreach($devresults as $dev) {
			extract($dev);	
			$astman->database_put("DEVICE",$id."/dial",$dial);
			$astman->database_put("DEVICE",$id."/type",$devicetype);
			$astman->database_put("DEVICE",$id."/user",$user);
			$astman->database_put("AMPUSER",$user."/device",$id);
			
			//voicemail symlink
			exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
			exec("/bin/ln -s /var/spool/asterisk/voicemail/default/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
		}
	} else {
		echo "Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"];
	}
	return $astman->disconnect();
}

// this function rebuilds the astdb based on users table contents
// used on devices.php if action=resetall
function users2astdb(){
	require_once('common/php-asmanager.php');
	checkAstMan();
	global $db;
	global $amp_conf;
	$sql = "SELECT * FROM users";
	$userresults = $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($userresults)) {
		$userresults = null;
	}
	
	//add details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		$astman->database_deltree("AMPUSER");
		foreach($userresults as $usr) {
			extract($usr);
			$astman->database_put("AMPUSER",$extension."/password",$password);
			$astman->database_put("AMPUSER",$extension."/ringtimer",$ringtimer);
			$astman->database_put("AMPUSER",$extension."/noanswer",$noasnwer);
			$astman->database_put("AMPUSER",$extension."/recording",$recording);
			$astman->database_put("AMPUSER",$extension."/outboundcid","\"".$outboundcid."\"");
			$astman->database_put("AMPUSER",$extension."/cidname","\"".$name."\"");
		}	
	} else {
		echo "Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"];
	}
	return $astman->disconnect();
}

?>

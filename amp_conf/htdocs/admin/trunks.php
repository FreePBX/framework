<?
// routing.php Copyright (C) 2004 Greg MacLellan (greg@mtechsolutions.ca)
// Asterisk Management Portal Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
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

//script to write conf file from mysql
$extenScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

//script to write sip conf file from mysql
$sipScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';

//script to write iax conf file from mysql
$iaxScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';

//script to write op_server.cfg file from mysql 
$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';

$display='6'; 
$extdisplay=$_REQUEST['extdisplay'];
$action = $_REQUEST['action'];
$tech = $_REQUEST['tech'];

$trunknum = ltrim($extdisplay,'OUT_');


// populate some global variables from the request string
$set_globals = array("outcid","maxchans","dialoutprefix","channelid","peerdetails","usercontext","userconfig","register");
foreach ($set_globals as $var) {
	if (isset($_REQUEST[$var])) {
		$$var = stripslashes( $_REQUEST[$var] );
	}
}


//if submitting form, update database
switch ($action) {
	case "addtrunk":
	echo "<br><br><br>";
	echo "addTrunk($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);";
		addTrunk($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
		exec($extenScript);
		exec($sipScript);
		exec($iaxScript);
		exec($wOpScript);
		needreload();
	break;
	case "edittrunk":
		editTrunk($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
		exec($extenScript);
		exec($sipScript);
		exec($iaxScript);
		exec($wOpScript);
		needreload();
	break;
	case "deltrunk":
		
		exec($extenScript);
		exec($sipScript);
		exec($iaxScript);
		exec($wOpScript);
		needreload();
		
		$extdisplay = ''; // resets back to main screen
	break;
}
	

	
//get all rows from globals
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
	die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${$global[0]} = htmlentities($global[1]);
}

?>
</div>

<div class="rnav">
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $display?>">Add Trunk</a><br></li>

<?
//get existing trunk info
$tresults = gettrunks();

foreach ($tresults as $tresult) {
    echo "<li><a id=\"".($extdisplay==$tresult[0] ? 'current':'')."\" href=\"config.php?display=".$display."&extdisplay={$tresult[0]}\">Trunk {$tresult[1]}</a></li>";
}

?>
</div>

<div class="content">

<?

if (!$tech && !$extdisplay) {
?>
	<h2>Add a Trunk</h2>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=ZAP">Add ZAP Trunk</a><br><br>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=IAX2">Add IAX2 Trunk</a><br><br>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=SIP">Add SIP Trunk</a><br><br>
<?
} else {
	if ($extdisplay) {
		//list($trunk_tech, $trunk_name) = explode("/",$tname);
		//if ($trunk_tech == "IAX2") $trunk_tech = "IAX"; // same thing
		$trunk_tech = getTrunkTech($trunknum);
	
		$outcid = ${"OUTCID_".$trunknum};
		$maxchans = ${"OUTMAXCHANS_".$trunknum};
		$dialoutprefix = ${"OUTPREFIX_".$trunknum};
		
		if (!isset($channelid)) {
			$channelid = getTrunkTrunkName($trunknum); 
		}
		
		// load from db
		if (!isset($peerdetails)) {	
			$peerdetails = getTrunkPeerDetails($trunknum);
		}
		
		if (!isset($usercontext)) {	
			$usercontext = getTrunkUserContext($trunknum); 
			
		}

		if (!isset($userconfig)) {	
			$userconfig = getTrunkUserConfig($trunknum);
		}
			
		if (!isset($register)) {	
			$register = getTrunkRegister($trunknum);
		}
		
		echo "<h2>Edit ".strtoupper($trunk_tech)." Trunk</h2>";
?>
		<p><a href="config.php?display=<?= $display ?>&extdisplay=<?= $extdisplay ?>&action=delroute">Delete Trunk <? echo strtoupper($trunk_tech)."/".$channelid; ?></a></p>
<?
	} else {
		// set defaults
		$outcid = "";
		$maxchans = "";
		$dialoutprefix = "";
		
		if ($tech == "zap") {
			$channelid = "g0";
		} else {
			$channelid = "";
		}
		
		// only for iax2/sip
		$peerdetails = "host=***provider ip address***\nusername=***userid***\nsecret=***password***\ntype=peer";
		$usercontext = "";
		$userconfig = "secret=***password***\ntype=user\ncontext=from-pstn";
		$register = "";
	
		echo "<h2>Add ".$tech." Trunk</h2>";
	} 
?>
	
		<form name="trunkEdit" action="config.php" method="get">
			<input type="hidden" name="display" value="<?echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?= $extdisplay ?>"/>
			<input type="hidden" name="action" value=""/>
			<input type="hidden" name="tech" value="<?echo $tech?>"/>
			<table>
			<tr>
				<td colspan="2">
					<h4>General Settings</h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Outbound Caller ID<span><br>Setting this option will override all clients' caller IDs for calls placed out this trunk<br><br>Format: <b>"caller name" &lt;#######&gt;</b><br><br>Leave this field blank to simply pass client caller IDs.<br><br></span></a>: 
				</td><td>
					<input type="text" size="20" name="outcid" value="<?= $outcid;?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Maximum channels<span>Controls the maximum number of channels (simultaneous calls) that can be used on this trunk, including both incoming and outgoing calls. Leave blank to specify no maximum.</span></a>: 
				</td><td>
					<input type="text" size="3" name="maxchans" value="<?= $maxchans; ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<br><h4>Outgoing Settings</h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Outbound Dial Prefix<span>The outbound dialing prefix is used to prefix a dialing string to outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line.<br><br>Most users should leave this option blank.</span></a>: 
				</td><td>
					<input type="text" size="8" name="dialoutprefix" value="<?= $dialoutprefix ?>"/>
				</td>
			</tr>
			
	<?
	switch ($trunk_tech) {
		case "zap":
		case "ZAP":
	?>
				<tr>
					<td>
						<a href=# class="info">Zap Identifier (trunk name)<span><br>ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).<br><br></span></a>: 
					</td><td>
						<input type="text" size="8" name="channelid" value="<?= $channelid ?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>
	<?
		break;
		default:
	?>
				<tr>
					<td>
						<a href=# class="info">Trunk Name<span><br>Give this trunk a unique name.  Example: myiaxtel<br><br></span></a>: 
					</td><td>
						<input type="text" size="14" name="channelid" value="<?= $channelid ?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info">PEER Details<span><br>Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="peerdetails"><?= $peerdetails ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<br><h4>Incoming Settings</h4>
					</td>
				</tr>
				<tr>
					<td>
						<a href=# class="info">USER Context<span><br>This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details.</span></a>: 
					</td><td>
						<input type="text" size="14" name="usercontext" value="<?= $usercontext  ?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info">USER Details<span><br>Modify the default USER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="userconfig"><?= $userconfig; ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<br><h4>Registration</h4>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info">Register String<span><br>Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com<br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="text" size="40" name="register" value="<?= $register ?>"/>
					</td>
				</tr>
	<?
		break;
	}
	?>
				
			<tr>
				<td colspan="2">
					<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkTrunk(trunkEdit, '<?= ($extdisplay ? "edittrunk" : "addtrunk") ?>')"></h6>
				</td>
			</tr>
			</table>
		</form>
<? 
}
?>

	
<? //Make sure the bottom border is low enuf
foreach ($tresults as $tresult) {
    echo "<br><br><br>";
}

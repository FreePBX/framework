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

//script to write conf file from mysql
$extenScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

//script to write sip conf file from mysql
$sipScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';

//script to write iax conf file from mysql
$iaxScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';

$display='6';
$extdisplay=$_REQUEST['extdisplay'];
$action = $_REQUEST['action'];

//if submitting form, update database
if ($action == 'edittrunk') {
	edittrunk();
	exec($extenScript);
	exec($sipScript);
	exec($iaxScript);
	needreload();
}

if ($action == 'delTrunk') {
	deltrunk();
	exec($extenScript);
	exec($sipScript);
	exec($iaxScript);
	needreload();
}

if ($action == 'addtrunk') {
	addtrunk();
	exec($extenScript);
	exec($sipScript);
	exec($iaxScript);
	needreload();
}

//get all rows from globals
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${$global[0]} = $global[1];	
}

?>
</div>

<div class="rnav">
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $display?>">Add Trunk</a><br></li>

<?
//get existing trunk info
$tresults = gettrunks();

foreach ($tresults as $tresult) {
    echo "<li><a id=\"".($extdisplay==$tresult[0] ? 'current':'')."\" href=\"config.php?display=".$display."&extdisplay={$tresult[0]}&tname={$tresult[1]}\">Trunk {$tresult[1]}</a></li>";
}

?>
</div>

<div class="content">

<?
switch($extdisplay) {
	default:

	//trunk name: tech/name
	$tname = $_REQUEST['tname'];
	if ($tname == ""){
		$tname = $_REQUEST['tech'].'/'.$_REQUEST['channelid']; 
	}
	//technology
	$tech = strtok($tname,"/");
	//trunk prefix number
	$tnumVar = 'DIAL_OUT_'.ltrim($extdisplay,'OUT_');
	
	if ($action == 'delTrunk') {
		echo '<br><h3>Trunk '.$tname.' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else {
	
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delTrunk';
?>
		<h2>Trunk <?echo $tname?></h2>
		<p><a href="<? echo $delURL ?>">Delete Trunk <? echo $tname; ?></a></p>
		<form name="trunkEdit" action="config.php" method="post">
			<input type="hidden" name="display" value="<?echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?echo $extdisplay?>"/>
			<input type="hidden" name="tname" value="<?echo $tname?>"/>
			<input type="hidden" name="tech" value="<?echo $tech?>"/>
			<input type="hidden" name="action" value="edittrunk"/>
			<table>
			<tr>
				<td colspan="2">
					<h4>General Settings</h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Dial Prefix<span><br>A Dial Prefix is a unique set of digits that will select this trunk.<br><br>For example, suppose the Dial Prefix is set to <b>123</b>.  To call 403-244-8089 on this trunk would require the caller to dial "<b>123</b> 403 244 8089<br><br></span></a> to access this trunk: 
				</td><td>
					<input type="text" size="8" name="dialprefix" value="<? echo $$tnumVar ?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Default Trunk<span><br>If chosen as the default, this trunk will be automatically selected for outbound numbers - eliminating the need to dial the <b>Dial Prefix</b><br><br>Note: There must always be a default trunk</span></a>: 
				</td><td>
					<input type="radio" name="defaulttrunk" value="yes" <? echo ($OUT == '${OUT_'.ltrim($extdisplay,'OUT_').'}' ? 'CHECKED=CHECKED':'') ?>/>Yes
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<br><h4>Outgoing Settings</h4>
				</td>
			</tr>
<?
if ($tech == "ZAP") {
?>
			<tr>
				<td>
					<a href=# class="info">Zap Identifier<span><br>ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).<br><br></span></a>: 
				</td><td>
					<input type="text" size="8" name="channelid" value="<? echo ltrim($tname,'ZAP/') ?>"/>
					<input type="hidden" size="14" name="usercontext" value="notneeded"/>
				</td>
			</tr>
<?
} else {
?>
			<tr>
				<td>
					<a href=# class="info">Trunk Name<span><br>Give this trunk a unique name.  Example: myiaxtel<br><br></span></a>: 
				</td><td>
					<input type="text" size="14" name="channelid" value="<? echo ltrim($tname,$tech.'/') ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<a href=# class="info">PEER Details<span><br>Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<textarea rows="10" cols="40" name="config"><?
					echo getTrunkDetails(ltrim($extdisplay,'OUT_'),strtolower(substr($tech,0,3)));
					?></textarea>
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
					<input type="text" size="14" name="usercontext" value="<? echo getTrunkAccount('9'.ltrim($extdisplay,'OUT_'),strtolower(substr($tech,0,3))); ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<a href=# class="info">USER Details<span><br>Modify the default USER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<textarea rows="10" cols="40" name="userconfig"><?
					echo getTrunkDetails('9'.ltrim($extdisplay,'OUT_'),strtolower(substr($tech,0,3)));
					?></textarea>
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
					<input type="text" size="40" name="register" value="<? echo getTrunkRegister(ltrim($extdisplay,'OUT_'),strtolower(substr($tech,0,3))); ?>"/>
				</td>
			</tr>
<?
}
?>
			<tr>
				<td colspan="2"><br>
					<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkTrunk(trunkEdit)"></h6>
				</td>
			</tr>
			</table>
		</form>
<?
	} //end if action == delTrunk
	
	break;
	case '':
	
?>
	<h2>Add a Trunk</h2>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']?>&extdisplay=addtrunk&tech=ZAP">Add ZAP Trunk</a><br><br>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']?>&extdisplay=addtrunk&tech=IAX2">Add IAX2 Trunk</a><br><br>
	<a href="<?echo $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']?>&extdisplay=addtrunk&tech=SIP">Add SIP Trunk</a><br><br>
<?
	break;
	case 'addtrunk':
	
	//we need a new unique OUT variable.  Just add one to the highest found for now.
	$newout = ltrim($tresult[0],'OUT_') + 1;
?>
		<h2>Add <?echo $_REQUEST['tech']?> Trunk</h2>

		<form name="trunkEdit" action="config.php" method="post">
			<input type="hidden" name="display" value="<?echo $display?>"/>
			<input type="hidden" name="extdisplay" value="OUT_<?echo $newout?>"/>
			<input type="hidden" name="tech" value="<?echo $_REQUEST['tech']?>"/>
			<input type="hidden" name="action" value="addtrunk"/>
			<table>
			<tr>
				<td colspan="2">
					<h4>General Settings</h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Dial Prefix<span><br>A Dial Prefix is a unique set of digits that will select this trunk.<br><br>For example, suppose the Dial Prefix is set to <b>123</b>.  To call 403-244-8089 on this trunk would require the caller to dial "<b>123</b> 403 244 2790"<br><br></span></a> to access this trunk: 
				</td><td>
					<input type="text" size="8" name="dialprefix" value=""/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info">Default Trunk<span><br>If chosen as the default, this trunk will be automatically selected for outbound numbers - eliminating the need to dial the <b>Dial Prefix</b><br><br>Note: There must always be a default trunk</span></a>: 
				</td><td>
					<input type="radio" name="defaulttrunk" value="yes"/>Yes
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<br><h4>Outgoing Settings</h4>
				</td>
			</tr>
<?
if ($_REQUEST['tech'] == 'ZAP'){
?>
			<tr>
				<td>
					<a href=# class="info">Zap Identifier<span><br>ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).<br><br></span></a>: 
				</td><td>
					<input type="text" size="8" name="channelid" value=""/>
					<input type="hidden" size="14" name="usercontext" value="notneeded"/>
				</td>
			</tr>
<?
} else {
?>
			<tr>
				<td>
					<a href=# class="info">Trunk Name<span><br>Give this trunk a unique name.  Example: myiaxtel<br><br></span></a>: 
				</td><td>
					<input type="text" size="14" name="channelid" value=""/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<a href=# class="info">PEER Details<span><br>Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<textarea rows="8" cols="40" name="config">host=***provider ip address***
username=***userid***
secret=***password***
type=peer</textarea>
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
					<input type="text" size="14" name="usercontext" value=""/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<a href=# class="info">USER Details<span><br>Modify the default USER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br><br></span></a>: 
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<textarea rows="8" cols="40" name="userconfig">secret=***password***
type=user
context=from-pstn</textarea> 
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
					<input type="text" size="40" name="register" value=""/>
				</td>
			</tr>
<?
}
?>
			<tr>
				<td colspan="2">
					<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkTrunk(trunkEdit)"></h6>
				</td>
			</tr>
			</table>
		</form>
<?php
	break;
}
?>
<? //Make sure the bottom border is low enuf
foreach ($tresults as $tresult) {
    echo "<br><br><br>";
}

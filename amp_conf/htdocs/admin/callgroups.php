<?php
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


//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	
$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];
$dispnum = 4; //used for switch on config.php

$goto = $_REQUEST['goto0'];
$account = $_REQUEST['account'];
$grplist = $_REQUEST['grplist'];
$grptime = $_REQUEST['grptime'];
$grppre = $_REQUEST['grppre'];
	
//add group
if ($action == 'addGRP') {
	
	addgroup($account,$grplist,$grptime,$grppre,$goto);
	
	exec($wScript1);
	needreload();
}

//del group
if ($action == 'delGRP') {
	delextensions('ext-group',ltrim($extdisplay,'GRP-'));
	
	exec($wScript1);
	needreload();
}

//edit group - just delete and then re-add the extension
if ($action == 'edtGRP') {

	delextensions('ext-group',$account);	
	addgroup($account,$grplist,$grptime,$grppre,$goto);

	exec($wScript1); 
	needreload();

}

?>
</div>

<div class="rnav">
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $dispnum?>">Add Ring Group</a><br></li>
<?
//get unique ring groups
$gresults = getgroups();

foreach ($gresults as $gresult) {
    echo "<li><a id=\"".($extdisplay=='GRP-'.$gresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=GRP-{$gresult[0]}\">Ring Group {$gresult[0]}</a></li>";
}
?>
</div>

<div class="content">
<?

		
		if ($action == 'delGRP') {
			echo '<br><h3>Group '.ltrim($extdisplay,'GRP-').' deleted!</h3><br><br><br><br><br><br><br><br>';
		} else {
			
	
			//get extensions in this group
			$thisGRP = getgroupextens(ltrim($extdisplay,'GRP-'));
			//get ringtime for this group
			$thisGRPtime = getgrouptime(ltrim($extdisplay,'GRP-'));
			//get prefix for this group
			$thisGRPprefix = getgroupprefix(ltrim($extdisplay,'GRP-'));


			$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delGRP';
	?>
			<h2>Ring Group: <? echo ltrim($extdisplay,'GRP-'); ?></h2>
<?		if ($extdisplay){ ?>
			<p><a href="<? echo $delURL ?>">Delete Group <? echo ltrim($extdisplay,'GRP-'); ?></a></p>
<?		} ?>
			<form name="editGRP" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="display" value="<?echo $dispnum?>">
			<input type="hidden" name="action" value="<? echo ($extdisplay ? 'edtGRP' : 'addGRP') ?>">
			<table>
			<tr><td colspan="2"><h5><? echo ($extdisplay ? 'Edit Ring Group' : 'Add Ring Group') ?><hr></h5></td></tr>
			<tr>
<?		if ($extdisplay){ ?>
				<input size="5" type="hidden" name="account" value="<? echo ltrim($extdisplay,'GRP-'); ?>">
<?		} else { ?>
				<td><a href="#" class="info">group number:<span>The number users will dial to ring extensions in this ring group</span></a></td>
				<td><input size="5" type="text" name="account" value="<? echo $gresult[0] + 1; ?>"></td>
<?		} ?>
			</tr>
			<tr>
				<td><a href="#" class="info">extension list:<span>Separate extensions with a | (pipe) character. Ex: 201|202|203<br><br>You can include an extension on a remote system, or an external number by including an asterisk (*) after the dial prefix for the appropriate trunk.  ex:  9*2448089 would dial 2448089 on the trunk accessible with dial prefix 9.<br><br></span></a></td>
				<td><input type="text" name="grplist" value="<? echo substr($thisGRP[0][0],6) ?>"></td>
			</tr>
			<tr>
				<td><a href="#" class="info">CID name prefix:<span>You can optionally prefix the Caller ID name when ringing extensions in this group. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.</span></a></td>
				<td><input size="4" type="text" name="grppre" value="<? echo substr($thisGRPprefix[0][0],4) ?>"></td>
			</tr><tr>
				<td>ring time (max 60 sec):</td>
				<td><input size="4" type="text" name="grptime" value="<? echo substr($thisGRPtime[0][0],10) ?>"></td>
			</tr>
			<tr><td colspan="2"><br><h5>Destination if no answer:<hr></h5></td></tr>

<?php 
//get goto for this group - note priority 5
$goto = getargs(ltrim($extdisplay,'GRP-'),5);
//draw goto selects
echo drawselects('editGRP',$goto,0);
?>
			
			<tr>
			<td colspan="2"><br><h6><input name="Submit" type="button" value="Submit Changes" onclick="checkGRP(editGRP);"></h6></td>		
			
			</tr>
			</table>
			</form>
<?		
		} //end if action == delGRP
		

?>

<? //Make sure the bottom border is low enuf
foreach ($gresults as $gresult) {
    echo "<br>";
}
?>





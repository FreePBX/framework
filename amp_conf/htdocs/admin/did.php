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
$dispnum = 7; //used for switch on config.php

$account = $_REQUEST['account'];	
$goto = $_REQUEST['goto0'];
	
//update db if submiting form
switch ($action) {
	case 'addDID':
	
		if ($goto == 'from-pstn') {
			$addarray = array('ext-did',$account,'1','Goto','from-pstn,s,1','','0');
			addextensions($addarray);
		} else {
			setGoto($account,'ext-did','1',$goto,0);
		}

		exec($wScript1);
		needreload();
	
	break;
	case 'delDID':

		delextensions('ext-did',ltrim($extdisplay,'DID-'));
		
		exec($wScript1);
		needreload();

	break;
	case 'edtDID':

		delextensions('ext-did',$account);
	
		if ($goto == 'from-pstn') {
			$addarray = array('ext-did',$account,'1','Goto','from-pstn,s,1','','0');
			addextensions($addarray);
		} else {
			setGoto($account,'ext-did','1',$goto,0);
		}

		exec($wScript1);
		needreload();

	break;
}

?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>">Add DID</a><br></li>
<?
//get unique Ring Groups
$dresults = getdids();

foreach ($dresults as $dresult) {
    echo "<li><a id=\"".($extdisplay=='DID-'.$dresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=DID-{$dresult[0]}\">DID # {$dresult[0]}</a></li>";
}
?>
</div>

<div class="content">
<?
	
	if ($action == 'delGRP') {
		echo '<br><h3>DID # '.ltrim($extdisplay,'DID-').' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else {
		
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delDID';
?>
		<h2>DID Route: <?php echo ltrim($extdisplay,'DID-'); ?></h2>
<?php if ($extdisplay) {	?>
		<p><a href="<?php echo $delURL ?>">Delete DID <?php echo ltrim($extdisplay,'DID-'); ?></a></p>
<?php } ?>
		<form name="editGRP" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtDID' : 'addDID') ?>">
		<input type="hidden" name="account" value="<?php echo ltrim($extdisplay,'DID-'); ?>">
		<table>
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? 'Edit DID' : 'Add DID') ?><hr></h5></td></tr>
		<tr>
			<td><a href="#" class="info">DID Number:<span>Define the expected DID digits if your trunk passes DID for incoming calls.</span></a></td>
			<td><input type="text" name="account" <?php echo ($extdisplay ? 'disabled="true"' : '') ?> value="<?php echo ltrim($extdisplay,'DID-') ?>"></td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr><td colspan="2"><h5>Set Destination<hr></h5></td></tr>
		
<?php 
//get the failover destination
$goto = getargs(ltrim($extdisplay,'DID-'),1);
//draw goto selects
echo drawselects('editGRP',$goto,0);
?>
		
		<tr><td colspan=2>
		<input type="radio" name="goto_indicate0" value="from-pstn" <?php echo strpos($goto,'from-pstn') === false ? '' : 'CHECKED=CHECKED';?> /> 
			Use 'Incoming Calls' settings<br>
			<br>				
		</td></tr>
		
		<tr>
		<td colspan="2"><br><h6><input name="Submit" type="button" value="Submit" onclick="checkDID(editGRP);"></h6></td>		
		
		</tr>
		</table>
		</form>
<?		
	} //end if action == delGRP
	

?>
<br><br><br><br><br><br><br><br><br>
<?php //Make sure the bottom border is low enuf
foreach ($dresults as $dresult) {
    echo "<br>";
}
?>





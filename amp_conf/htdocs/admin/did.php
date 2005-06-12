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
	
		$addarray = array('ext-did',$account,'1','SetVar','FROM_DID='.$account,'','0');
		addextensions($addarray);
			
		if ($goto == 'from-pstn') {
			$addarray = array('ext-did',$account,'2','Goto','from-pstn,s,1','','0');
			addextensions($addarray);
		} else {
			setGoto($account,'ext-did','2',$goto,0);
		}

		exec($wScript1);
		needreload();
	
	break;
	case 'delDID':

		delextensions('ext-did',substr($extdisplay,4));
		
		exec($wScript1);
		needreload();

	break;
	case 'edtDID':

		delextensions('ext-did',$account);
	
		$addarray = array('ext-did',$account,'1','SetVar','FROM_DID='.$account,'','0');
		addextensions($addarray);
			
		if ($goto == 'from-pstn') {
			$addarray = array('ext-did',$account,'2','Goto','from-pstn,s,1','','0');
			addextensions($addarray);
		} else {
			setGoto($account,'ext-did','2',$goto,0);
		}

		exec($wScript1);
		needreload();

	break;
}

?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>"><?php echo _("Add DID")?></a><br></li>
<?php 
//get unique Ring Groups
$dresults = getdids();

if (isset($dresults)) {
	foreach ($dresults as $dresult) {
		echo "<li><a id=\"".($extdisplay=='DID-'.$dresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=DID-" . urlencode($dresult[0]) . "\">DID # {$dresult[0]}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php 
	
	if ($action == 'delDID') {
		echo '<br><h3>DID # '.substr($extdisplay,4).' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else {
		
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delDID';
?>
		<h2><?php echo _("DID Route")?>: <?php echo substr($extdisplay,4); ?></h2>
<?php if ($extdisplay) {	?>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete DID")?> <?php echo substr($extdisplay,4); ?></a></p>
<?php } ?>
		<form name="editGRP" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtDID' : 'addDID') ?>">
<?php if ($extdisplay) {        ?>
		<input type="hidden" name="account" value="<?php echo substr($extdisplay,4); ?>">
<?php } ?>
		<table>
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit DID') : _('Add DID')) ?><hr></h5></td></tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("DID Number")?>:<span><?php echo _('Define the expected DID digits if your trunk passes DID for incoming calls. <br><br>Caller Id can be matched as well by appending "/" and the expected digits.<br><br><b>Examples:</b><br>123 - match DID "123"<br>s/100 - match CID "100"<br>1234/_256NXXXXXX - both')?></span></a></td>
			<td><input type="text" name="account" <?php echo ($extdisplay ? 'disabled="true"' : '') ?> value="<?php echo substr($extdisplay,4) ?>"></td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr><td colspan="2"><h5><?php echo _("Set Destination")?><hr></h5></td></tr>
		
<?php 
//get the failover destination
$goto = getargs(substr($extdisplay,4),2,'ext-did');
//draw goto selects
echo drawselects('editGRP',$goto,0);
?>
		
		<tr><td colspan=2>
		<input type="radio" name="goto_indicate0" value="from-pstn" <?php echo strpos($goto,'from-pstn') === false ? '' : 'CHECKED=CHECKED';?> /> 
			<?php echo _("Use 'Incoming Calls' settings")?><br>
			<br>				
		</td></tr>
		
		<tr>
		<td colspan="2"><br><h6><input name="Submit" type="button" value="Submit" onclick="checkDID(editGRP);"></h6></td>		
		
		</tr>
		</table>
		</form>
<?php 		
	} //end if action == delGRP
	

?>
<br><br><br><br><br><br><br><br><br>
<?php //Make sure the bottom border is low enuf
if (isset($dresults)) {
	foreach ($dresults as $dresult) {
		echo "<br><br>";
	}
}
?>





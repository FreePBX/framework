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

$dispnum = 'ringgroups'; //used for switch on config.php

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the extension we are currently displaying
isset($_REQUEST['extdisplay'])?$extdisplay=$_REQUEST['extdisplay']:$extdisplay='';
isset($_REQUEST['account'])?$account = $_REQUEST['account']:$account='';
isset($_REQUEST['grptime'])?$grptime = $_REQUEST['grptime']:$grptime='';
isset($_REQUEST['grppre'])?$grppre = $_REQUEST['grppre']:$grppre='';
isset($_REQUEST['strategy'])?$strategy = $_REQUEST['strategy']:$strategy='';
isset($_REQUEST['annmsg'])?$annmsg = $_REQUEST['annmsg']:$annmsg='';

if (isset($_REQUEST['goto0']) && isset($_REQUEST[$_REQUEST['goto0']."0"])) {
        $goto = $_REQUEST[$_REQUEST['goto0']."0"];
} else {
        $goto = '';
}


if (isset($_REQUEST["grplist"])) {
	$grplist = explode("\n",$_REQUEST["grplist"]);

	if (!$grplist) {
		$grplist = null;
	}
	
	foreach (array_keys($grplist) as $key) {
		//trim it
		$grplist[$key] = trim($grplist[$key]);
		
		// remove invalid chars
		$grplist[$key] = preg_replace("/[^0-9#*]/", "", $grplist[$key]);
		
		// remove blanks
		if ($grplist[$key] == "") unset($grplist[$key]);
	}
	
	// check for duplicates, and re-sequence
	$grplist = array_values(array_unique($grplist));
}

// do if we are submitting a form
if(isset($_POST['action'])){
	//check if the extension is within range for this user
	if (isset($account) && !checkRange($account)){
		echo "<script>javascript:alert('". _("Warning! Extension")." ".$account." "._("is not allowed for your account").".');</script>";
	} else {
		//add group
		if ($action == 'addGRP') {
			//ringgroups_add($account,implode("-",$grplist),$strategy,$grptime,$grppre,$goto);
			ringgroups_add($account,$strategy,$grptime,implode("-",$grplist),$goto,$grppre,$annmsg);
			needreload();
		}
		
		//del group
		if ($action == 'delGRP') {
			ringgroups_del($account);
			needreload();
		}
		
		//edit group - just delete and then re-add the extension
		if ($action == 'edtGRP') {
			ringgroups_del($account);	
			ringgroups_add($account,$strategy,$grptime,implode("-",$grplist),$goto,$grppre,$annmsg);
			needreload();
		}
	}
}
?>
</div>

<div class="rnav">
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add Ring Group")?></a></li>
<?php 
//get unique ring groups
$gresults = ringgroups_list();

if (isset($gresults)) {
	foreach ($gresults as $gresult) {
		echo "<li><a id=\"".($extdisplay=='GRP-'.$gresult[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($dispnum)."&extdisplay=".urlencode("GRP-".$gresult[0])."\">"._("Ring Group")." {$gresult[0]}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php 
if ($action == 'delGRP') {
	echo '<br><h3>'._("Ring Group").' '.$account.' '._("deleted").'!</h3><br><br><br><br><br><br><br><br>';
} else {
	if ($extdisplay) {
		// We need to populate grplist with the existing extension list.
		$thisgrp = ringgroups_get(ltrim($extdisplay,'GRP-'));
		$grpliststr = $thisgrp['grplist'];
		$grplist = explode("-", $grpliststr);
		$strategy = $thisgrp['strategy'];
		$grppre = $thisgrp['grppre'];
		$grptime = $thisgrp['grptime'];
		$goto = $thisgrp['postdest'];
		$annmsg = $thisgrp['annmsg'];
		unset($grpliststr);
		unset($thisgrp);
		
		$delButton = "
			<form name=delete action=\"{$_SERVER['PHP_SELF']}\" method=POST>
				<input type=\"hidden\" name=\"display\" value=\"{$dispnum}\">
				<input type=\"hidden\" name=\"account\" value=\"".ltrim($extdisplay,'GRP-')."\">
				<input type=\"hidden\" name=\"action\" value=\"delGRP\">
				<input type=submit value=\""._("Delete Group")."\">
			</form>";
			
		echo "<h2>"._("Ring Group").": ".ltrim($extdisplay,'GRP-')."</h2>";
		echo "<p>".$delButton."</p>";
	} else {
		$grplist = explode("-", '');;
		$strategy = '';
		$grppre = '';
		$grptime = '';
		$goto = '';
		$annmsg = '';

		echo "<h2>"._("Add Ring Group")."</h2>";
	}
	?>
			<form name="editGRP" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkGRP(editGRP);">
			<input type="hidden" name="display" value="<?php echo $dispnum?>">
			<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtGRP' : 'addGRP'); ?>">
			<table>
			<tr><td colspan="2"><h5><?php  echo ($extdisplay ? _("Edit Ring Group") : _("Add Ring Group")) ?><hr></h5></td></tr>
			<tr>
<?php
	if ($extdisplay) { 

?>
				<input size="5" type="hidden" name="account" value="<?php  echo ltrim($extdisplay,'GRP-'); ?>">
<?php 		} else { ?>
				<td><a href="#" class="info"><?php echo _("group number")?>:<span><?php echo _("The number users will dial to ring extensions in this ring group")?></span></a></td>
				<td><input size="5" type="text" name="account" value="<?php  echo $gresult[0] + 1; ?>"></td>
<?php 		} ?>
			</tr>
			<tr>
				<td> <a href="#" class="info"><?php echo _("ring strategy:")?>
				<span>
					<b><?php echo _("ringall")?></b>:  <?php echo _("ring all available channels until one answers (default)")?><br>
					<b><?php echo _("hunt")?></b>: <?php echo _("take turns ringing each available extension")?><br>
					<b><?php echo _("memoryhunt")?></b>: <?php echo _("ring first extension in the list, then ring the 1st and 2nd extension, then ring 1st 2nd and 3rd extension in the list.... etc.")?><br>
				</span>
				</a></td>
				<td>
					<select name="strategy"/>
					<?php
						$default = (isset($strategy) ? $strategy : 'ringall');
						$items = array('ringall','hunt','memoryhunt');
						foreach ($items as $item) {
							echo '<option value="'.$item.'" '.($default == $item ? 'SELECTED' : '').'>'._($item);
						}
					?>		
					</select>
				</td>
			</tr>
			<tr>
				<td valign="top"><a href="#" class="info"><?php echo _("extension list")?>:<span><br><?php echo _("List extensions to ring, one per line.<br><br>You can include an extension on a remote system, or an external number by suffixing a number with a pound (#).  ex:  2448089# would dial 2448089 on the appropriate trunk (see Outbound Routing).")?><br><br></span></a></td>
				<td valign="top">&nbsp;
<?php
		$rows = count($grplist)+1; 
		($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows);
?>
					<textarea id="grplist" cols="15" rows="<?php  echo $rows ?>" name="grplist"><?php echo implode("\n",$grplist);?></textarea><br>
					
					<input type="submit" style="font-size:10px;" value="<?php echo _("Clean & Remove duplicates")?>" />
				</td>
			</tr>
			<tr>
				<td><a href="#" class="info"><?php echo _("CID name prefix")?>:<span><?php echo _('You can optionally prefix the Caller ID name when ringing extensions in this group. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.')?></span></a></td>
				<td><input size="4" type="text" name="grppre" value="<?php  echo $grppre ?>"></td>
			</tr>


			<tr>
				<td><?php echo _("ring time (max 60 sec)")?>:</td>
				<td><input size="4" type="text" name="grptime" value="<?php  echo $grptime ?>"></td>
			</tr>
<?php if(function_exists('recordings_list')) { //only include if recordings is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("announcement:")?><span><?php echo _("Message to be played to the caller before dialing this group.<br><br>To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
		<td>
			<select name="annmsg"/>
			<?php
				$tresults = recordings_list();
				$default = (isset($annmsg) ? $annmsg : '');
				echo '<option value="">'._("None");
				if (isset($tresults[0])) {
					foreach ($tresults as $tresult) {
						$searchvalue="custom/$tresult";	
						echo '<option value='.$tresult[0].'"'.($searchvalue == $default ? ' SELECTED' : '').'>'.$tresult[1];
					}
				}
			?>		
			</select>		
		</td>
	</tr>
<?php }	else { ?>
	<tr>
		<td><a href="#" class="info"><?php echo _("announcement:")?><span><?php echo _("Message to be played to the caller before dialing this group.<br><br>You must install and enable the \"Systems Recordings\" Module to edit this option")?></span></a></td>
		<td>
			<?php
				$default = (isset($annmsg) ? $annmsg : '');
			?>
			<input type="hidden" name="annmsg" value="<?php echo $default; ?>"><?php echo ($default != '' ? $default : 'None'); ?>
		</td>
	</tr>
<?php } ?>
			
			<tr><td colspan="2"><br><h5><?php echo _("Destination if no answer")?>:<hr></h5></td></tr>

<?php 
//draw goto selects
echo drawselects($goto,0);
?>
			
			<tr>
			<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
			
			</tr>
			</table>
			</form>
<?php 		
		} //end if action == delGRP
		

?>
<script language="javascript">
<!--

function checkGRP(theForm) {
	// set up the Destination stuff
	setDestinations(theForm, 1);

	// form validation
	defaultEmptyOK = false;
	if (!isInteger(theForm.account.value)) {
		return warnInvalid(theForm.account, "Invalid Group Number specified");
	} else if (theForm.account.value.indexOf('0') == 0 && theForm.account.value.length > 1) {
		return warnInvalid(theForm.account, "Group numbers with more than one digit cannot begin with 0");
	}
	
	defaultEmptyOK = false;	
	if (isEmpty(theForm.grplist.value))
		return warnInvalid(theForm.grplist, "Please enter an extension list.");

	defaultEmptyOK = true;
	if (!isPrefix(theForm.grppre.value))
		return warnInvalid(theForm.grppre, "Invalid prefix. Valid characters: a-z A-Z 0-9 : _ -");
	
	defaultEmptyOK = false;
	if (!isInteger(theForm.grptime.value)) {
		return warnInvalid(theForm.grptime, "Invalid time specified");
	} else {
		var grptimeVal = theForm.grptime.value;
		if (grptimeVal < 1 || grptimeVal > 60)
			return warnInvalid(theForm.grptime, "Time must be between 1 and 60 seconds");
	}

	if (!validateDestinations(theForm, 1, true))
		return false;
		
	return true;
}
-->
</script>


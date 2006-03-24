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


isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the extension we are currently displaying
isset($_REQUEST['extdisplay'])?$extdisplay=$_REQUEST['extdisplay']:$extdisplay='';
$dispnum = "conferences"; //used for switch on config.php

//check if the extension is within range for this user
if (isset($account) && !checkRange($account)){
	echo "<script>javascript:alert('"._("Warning! Extension")." $account "._("is not allowed for your account.")."');</script>";
} else {
	
	//if submitting form, update database
	switch ($action) {
		case "add":
			conferences_add($_REQUEST['account'],$_REQUEST['name'],$_REQUEST['userpin'],$_REQUEST['adminpin'],$_REQUEST['options'],$_REQUEST['joinmsg']);
			needreload();
		break;
		case "delete":
			conferences_del($extdisplay);
			needreload();
		break;
		case "edit":  //just delete and re-add
			conferences_del($_REQUEST['account']);
			conferences_add($_REQUEST['account'],$_REQUEST['name'],$_REQUEST['userpin'],$_REQUEST['adminpin'],$_REQUEST['options'],$_REQUEST['joinmsg']);
			needreload();
		break;
	}
}

//get meetme rooms
//this function needs to be available to other modules (those that use goto destinations)
//therefore we put it in globalfunctions.php
$meetmes = conferences_list();
?>

</div>

<!-- right side menu -->
<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add Conference")?></a></li>
<?php
if (isset($meetmes)) {
	foreach ($meetmes as $meetme) {
		echo "<li><a id=\"".($extdisplay==$meetme[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($dispnum)."&extdisplay=".urlencode($meetme[0])."\">{$meetme[0]}:{$meetme[1]}</a></li>";
	}
}
?>
</div>


<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>'._("Conference").' '.$extdisplay.' '._("deleted").'!</h3><br><br><br><br><br><br><br><br>';
} else {
	if ($extdisplay){ 
		//get details for this meetme
		$thisMeetme = conferences_get($extdisplay);
		//create variables
		extract($thisMeetme);
	}

	$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
?>

	
<?php		if ($extdisplay){ ?>
	<h2><?php echo _("Conference:")." ". $extdisplay; ?></h2>
	<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Conference")?> <?php echo $extdisplay; ?></a></p>
<?php		} else { ?>
	<h2><?php echo _("Add Conference"); ?></h2>
<?php		}
?>
	<form autocomplete="off" name="editMM" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkConf();">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($extdisplay ? _("Edit Conference") : _("Add Conference")) ?><hr></h5></td></tr>
	<tr>
<?php		if ($extdisplay){ ?>
		<input type="hidden" name="account" value="<?php echo $extdisplay; ?>">
<?php		} else { ?>
		<td><a href="#" class="info"><?php echo _("conference number:")?><span><?php echo _("Use this number to dial into the conference.")?></span></a></td>
		<td><input type="text" name="account" value=""></td>
<?php		} ?>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("conference name:")?><span><?php echo _("Give this conference a brief name to help you identify it.")?></span></a></td>
		<td><input type="text" name="name" value="<?php echo (isset($description) ? $description : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("user PIN:")?><span><?php echo _("You can require callers to enter a password before they can enter this conference.<br><br>This setting is optional.<br><br>If either PIN is entered, the user will be prompted to enter a PIN.")?></span></a></td>
		<td><input size="8" type="text" name="userpin" value="<?php echo (isset($userpin) ? $userpin : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("admin PIN:")?><span><?php echo _("Enter a PIN number for the admin user.<br><br>This setting is optional unless the 'leader wait' option is in use, then this PIN will identify the leader.")?></span></a></td>
		<td><input size="8" type="text" name="adminpin" value="<?php echo (isset($adminpin) ? $adminpin : ''); ?>"></td>
	</tr>

	<?php
	$options = (isset($options) ? $options : "");
	?>
	<input type="hidden" name="options" value="<?php echo $options; ?>">
	
	<tr><td colspan="2"><br><h5><?php echo _("Conference Options")?><hr></h5></td></tr>
<?php if(function_exists('recordings_list')) { //only include if recordings is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("join message:")?><span><?php echo _("Message to be played to the caller before joining the conference.<br><br>To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
		<td>
			<select name="joinmsg"/>
			<?php
				$tresults = recordings_list();
				$default = (isset($joinmsg) ? $joinmsg : '');
				echo '<option value="">'._("None");
				if (isset($tresults[0])) {
					foreach ($tresults as $tresult) {
						echo '<option value="'.$tresult[0].'"'.($tresult[0] == $default ? ' SELECTED' : '').'>'.$tresult[1];
					}
				}
			?>		
			</select>		
		</td>
	</tr>
<?php }	else { ?>
	<tr>
		<td><a href="#" class="info"><?php echo _("join message:")?><span><?php echo _("Message to be played to the caller before joining the conference.<br><br>You must install and enable the \"Systems Recordings\" Module to edit this option")?></span></a></td>
		<td>
			<?php
				$default = (isset($joinmsg) ? $joinmsg : '');
			?>
			<input type="hidden" name="joinmsg" value="<?php echo $default; ?>"><?php echo ($default != '' ? $default : 'None'); ?>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td><a href="#" class="info"><?php echo _("leader wait:")?><span><?php echo _("wait until the conference leader (admin user) arrives before starting the conference")?></span></a></td>
		<td>
			<select name="opt#w">
			<?php
				$optselect = strpos($options, "w");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="w"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("quiet mode:")?><span><?php echo _("quiet mode (do not play enter/leave sounds)")?></span></a></td>
		<td>
			<select name="opt#q">
			<?php
				$optselect = strpos($options, "q");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="q"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("user count:")?><span><?php echo _("announce user(s) count on joining conference")?></span></a></td>
		<td>
			<select name="opt#c">
			<?php
				$optselect = strpos($options, "c");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="c"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("user join/leave:")?><span><?php echo _("announce user join/leave")?></span></a></td>
		<td>
			<select name="opt#i">
			<?php
				$optselect = strpos($options, "i");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="i"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("music on hold:")?><span><?php echo _("enable music on hold when the conference has a single caller")?></span></a></td>
		<td>
			<select name="opt#M">
			<?php
				$optselect = strpos($options, "M");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="M"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("allow menu:")?><span><?php echo _("present menu (user or admin) when '*' is received ('send' to menu)")?></span></a></td>
		<td>
			<select name="opt#s">
			<?php
				$optselect = strpos($options, "s");
				echo '<option value=""' . ($optselect === false ? ' SELECTED' : '') . '>'._("No") . '</option>';
				echo '<option value="s"'. ($optselect !== false ? ' SELECTED' : '') . '>'._("Yes"). '</option>';
			?>		
			</select>		
		</td>
	</tr>

	
	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
	</tr>
	</table>
<script language="javascript">
<!--

var theForm = document.editMM;

if (theForm.account.value == "") {
	theForm.account.focus();
} else {
	theForm.name.focus();
}

function checkConf()
{
	var msgInvalidConfNumb = "<?php echo _('Please enter a valid Conference Number'); ?>";
	var msgInvalidConfName = "<?php echo _('Please enter a valid Conference Name'); ?>";
	var msgNeedAdminPIN = "<?php echo _('You must set an admin PIN for the Conference Leader when selecting the leader wait option'); ?>";
	
	defaultEmptyOK = false;
	if (!isInteger(theForm.account.value))
		return warnInvalid(theForm.account, msgInvalidConfNumb);
		
	if (!isAlphanumeric(theForm.name.value))
		return warnInvalid(theForm.name, msgInvalidConfName);
		
	// update $options
	var theOptionsFld = theForm.options;
	theOptionsFld.value = "";
	for (var i = 0; i < theForm.elements.length; i++)
	{
		var theEle = theForm.elements[i];
		var theEleName = theEle.name;
		if (theEleName.indexOf("#") > 1)
		{
			var arr = theEleName.split("#");
			if (arr[0] == "opt")
				theOptionsFld.value += theEle.value;
		}
	}

	// not possible to have a 'leader' conference with no adminpin
	if (theForm.options.value.indexOf("w") > -1 && theForm.adminpin.value == "")
		return warnInvalid(theForm.adminpin, msgNeedAdminPIN);
		
	return true;
}

//-->
</script>
	</form>
<?php		
} //end if action == delGRP
?>

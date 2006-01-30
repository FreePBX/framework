<?php /* $Id */
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


$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];  //the extension we are currently displaying
$dispnum = "conferences"; //used for switch on config.php

//check if the extension is within range for this user
if (isset($account) && !checkRange($account)){
	echo "<script>javascript:alert('"._("Warning! Extension")." $account "._("is not allowed for your account.")."');</script>";
} else {
	
	//if submitting form, update database
	switch ($action) {
		case "add":
			conferences_add($_REQUEST['account'],$_REQUEST['name'],$_REQUEST['userpin'],$_REQUEST['adminpin'],$_REQUEST['options']);
			needreload();
		break;
		case "delete":
			conferences_del($extdisplay);
			needreload();
		break;
		case "edit":  //just delete and re-add
			conferences_del($_REQUEST['account']);
			conferences_add($_REQUEST['account'],$_REQUEST['name'],$_REQUEST['userpin'],$_REQUEST['adminpin'],$_REQUEST['options']);
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
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>"><?php echo _("Add Conference")?></a><br></li>
<?php
if (isset($meetmes)) {
	foreach ($meetmes as $meetme) {
		echo "<li><a id=\"".($extdisplay==$meetme[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$meetme[0]}\">{$meetme[0]}:{$meetme[1]}</a></li>";
	}
}
?>
</div>


<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>Conference '.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
} else {
	if ($extdisplay){ 
		//get details for this meetme
		$thisMeetme = conferences_get($extdisplay);
		//create variables
		extract($thisMeetme);
	}

	$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
?>

	<h2><?php echo _("Conference:")." ". $extdisplay; ?></h2>
<?php		if ($extdisplay){ ?>
	<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Conference")?> <?php echo $extdisplay; ?></a></p>
<?php		} ?>
	<form autocomplete="off" name="editMM" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($extdisplay ? _("Edit Conference") : _("Add Conference")) ?><hr></h5></td></tr>
	<tr>
<?php		if ($extdisplay){ ?>
		<input type="hidden" name="account" value="<?php echo $extdisplay; ?>">
<?php		} else { ?>
		<td><a href="#" class="info"><?php echo _("conference number:")?><span><?php echo _("Use this number to dial into the conference.<br><br>Conference admins will dial this conference number plus *<br><br>For example, if the conference number is 123:<br><br><b>123 = log in as user<br>123* = log in as admin</b>")?></span></a></td>
		<td><input type="text" name="account" value=""></td>
<?php		} ?>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("conference name:")?><span><?php echo _("Give this conference a brief name to help you identify it.")?></span></a></td>
		<td><input type="text" name="name" value="<?php echo (isset($description) ? $description : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("user PIN:")?><span><?php echo _("You can require callers to enter a password before they can enter this conference.<br><br>This setting is optional.")?></span></a></td>
		<td><input size="8" type="text" name="userpin" value="<?php echo (isset($userpin) ? $userpin : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("admin PIN:")?><span><?php echo _("Enter a PIN number the admin must enter after dialing <conference#>*")?></span></a></td>
		<td><input size="8" type="text" name="adminpin" value="<?php echo (isset($adminpin) ? $adminpin : ''); ?>"></td>
	</tr>

	<tr><td colspan="2"><br><h5><?php echo _("Conference Options")?><hr></h5></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("quiet mode:")?><span><?php echo _("quiet mode (do not play enter/leave sounds)")?></span></a></td>
		<td>
			<select name="options"/>
			<?php
				$default = (isset($options) ? $options : "");
				echo '<option value="" '.($options != "q" ? 'SELECTED' : '').'>'._("No");
				echo '<option value="q" '.($options == "q" ? 'SELECTED' : '').'>'._("Yes");
			?>		
			</select>		
		</td>
	</tr>

	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
	</tr>
	</table>
	</form>
<?php		
} //end if action == delGRP
?>

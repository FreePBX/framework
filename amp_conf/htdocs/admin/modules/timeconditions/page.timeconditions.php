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


isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the item we are currently displaying
isset($_REQUEST['itemid'])?$itemid=$_REQUEST['itemid']:$itemid='';
$dispnum = "timeconditions"; //used for switch on config.php

//if submitting form, update database
switch ($action) {
	case "add":
		timeconditions_add($_POST);
	break;
	case "delete":
		timeconditions_del($itemid);
	break;
	case "edit":  //just delete and re-add
		timeconditions_edit($itemid,$_POST);
	break;
}


//get list of time conditions
$timeconditions = timeconditions_list();
?>

</div> <!-- end content div so we can display rnav properly-->

<!-- right side menu -->
<div class="rnav">
    <li><a id="<?php echo ($itemid=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>"><?php echo _("Add Time Condition")?></a></li>
<?php
if (isset($timeconditions)) {
	foreach ($timeconditions as $timecond) {
		echo "<li><a id=\"".($itemid==$timecond['timeconditions_id'] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&itemid={$timecond['timeconditions_id']}\">{$timecond['displayname']}</a></li>";
	}
}
?>
</div>


<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>Time Condition '.$itemid.' deleted!</h3>';
} else {
	if ($itemid){ 
		//get details for this time condition
		$thisItem = timeconditions_get($itemid);
	}

	$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
?>

	<h2><?php echo _("Time Condition:")." ". $itemid; ?></h2>
<?php		if ($itemid){ ?>
	<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Time Condition")?> <?php echo $itemid; ?></a></p>
<?php		} ?>
	<form autocomplete="off" name="edit" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="setDestinations(edit,2)">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($itemid ? 'edit' : 'add') ?>">
	<input type="hidden" name="deptname" value="<?php echo $_SESSION["AMP_user"]->_deptname ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($itemid ? _("Edit Time Condition") : _("Add Time Condition")) ?><hr></h5></td></tr>

<?php		if ($itemid){ ?>
		<input type="hidden" name="account" value="<?php echo $itemid; ?>">
<?php		}?>

	<tr>
		<td><a href="#" class="info"><?php echo _("Time Condition name:")?><span><?php echo _("Give this Time Condition a brief name to help you identify it.")?></span></a></td>
		<td><input type="text" name="displayname" value="<?php echo (isset($thisItem['displayname']) ? $thisItem['displayname'] : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Time to match:")?><span><?php echo _("time range|days of week|days of month|months<br><br>you can use an * as a wildcard.<br><br>ex: <b>9:00-17:00|mon-fri|*|*</b>")?></span></a></td>
		<td><input type="text" name="time" value="<?php echo (isset($thisItem['time']) ? $thisItem['time'] : ''); ?>"></td>
	</tr>
	<tr><td colspan="2"><br><h5><?php echo _("Destination if time matches")?>:<hr></h5></td></tr>

<?php 
//draw goto selects
if (isset($thisItem)) {
	echo drawselects($thisItem['truegoto'],0);
} else { 
	echo drawselects(null, 0);
}
?>

	<tr><td colspan="2"><br><h5><?php echo _("Destination if time does not match")?>:<hr></h5></td></tr>

<?php 
//draw goto selects
if (isset($thisItem)) {
	echo drawselects($thisItem['falsegoto'],1);
} else { 
	echo drawselects(null, 1);
}
?>

	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
	</tr>
	</table>
	</form>
<?php		
} //end if action == delete
?>

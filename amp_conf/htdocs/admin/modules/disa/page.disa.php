<?php /* $Id */
//Copyright (C) 2006 Rob Thomas <xrobau@gmail.com>
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of version 2 the GNU General Public
//License as published by the Free Software Foundation.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.


$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$dispnum = "disa"; //used for switch on config.php

//if submitting form, update database
switch ($action) {
	case "add":
		disa_add($_POST);
		needreload();
	break;
	case "delete":
		$oldItem = disa_get($itemid);
		disa_del($itemid);
		needreload();
	break;
	case "edit":  //just delete and re-add
		disa_edit($itemid,$_POST);
		needreload();
	break;
}


$disas = disa_list();
?>

</div> <!-- end content div so we can display rnav properly-->

<!-- right side menu -->
<div class="rnav">
    <li><a id="<?php echo ($itemid=='' ? 'current':'std') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add")." DISA" ?></a></li>
<?php
if (isset($disas)) {
	foreach ($disas as $d) {
		echo "<li><a id=\"".($itemid==$d['disa_id'] ? 'current':'std')."\" href=\"config.php?display=".urlencode($dispnum)."&itemid=".urlencode($d['disa_id'])."\">{$d['displayname']}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>DISA '.$oldItem["displayname"].' '._("deleted").'!</h3>';
} else { 
	//get details for this time condition
	$thisItem = disa_get($itemid);
	$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
?>

	<h2><?php echo ($itemid ? "DISA: ".$thisItem["displayname"] : _("Add")." DISA"); ?></h2>
<?php		if ($itemid){ ?>
	<p><a href="<?php echo $delURL ?>"><?php echo _("Delete")." DISA"?> <?php echo $thisItem["displayname"]; ?></a></p>
<?php		} ?>
	<form autocomplete="off" name="edit" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return edit_onsubmit();">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($itemid ? 'edit' : 'add') ?>">
	<input type="hidden" name="deptname" value="<?php echo $_SESSION["AMP_user"]->_deptname ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($itemid ? _("Edit")." DISA" : _("Add")." DISA") ?><hr></h5></td></tr>

	<tr>
		<td><a href="#" class="info"><?php echo "DISA "._("name:")?><span><?php echo _("Give this DISA a brief name to help you identify it.")?></span></a></td>

		<td><input type="text" name="displayname" value="<?php echo (isset($thisItem['displayname']) ? $thisItem['displayname'] : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("PIN"); ?><span><?php echo _("The user will be prompted for this number, or use 'no-password' for no Authentication.")." "._("If you wish to have multiple PIN's, seperate them with commas"); ?></span></a></td>
		<td><input type="text" name="pin" value="<?php echo (isset($thisItem['pin']) ? $thisItem['pin'] : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Caller ID"); ?><span><?php echo _("(Optional) When using this DISA, the users CallerID will be set to this. Format is \"User Name\" <5551234>"); ?></span></a></td>
		<td><input type="text" name="cid" value="<?php echo (isset($thisItem['cid']) ? $thisItem['cid'] : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Context"); ?><span><?php echo _("(Experts Only) Sets the context that calls will originate from. Leave this as from-internal unless you know what you're doing."); ?></span></a></td>
		<td><input type="text" name="context" value="<?php echo (isset($thisItem['context']) ? $thisItem['context'] : 'from-internal'); ?>"></td>
	</tr>
        <tr>
                <td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>
        </tr>
        </table>

	</form>
<?php		
}
?>

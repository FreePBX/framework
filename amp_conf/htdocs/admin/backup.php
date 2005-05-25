<?php 
// backup.php Copyright (C) 2005 VerCom Systems, Inc. & Ron Hartmann (rhartmann@vercomsystems.com)
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
?>

<?php
include_once "schedule_functions.php";
$action = $_REQUEST['action'];
$display=13;

switch ($action) {
	case "addednew":
		$ALL_days=$_POST['all_days'];
		$ALL_months=$_POST['all_months'];
		$ALL_weekdays=$_POST['all_weekdays'];

		$backup_schedule=$_REQUEST['backup_schedule'];
		$name=(empty($_REQUEST['name'])?'backup':$_REQUEST['name']);
		$mins=$_REQUEST['mins'];
		$hours=$_REQUEST['hours'];
		$days=$_REQUEST['days'];
		$months=$_REQUEST['months'];
		$weekdays=$_REQUEST['weekdays'];
		
		$backup_options[]=$_REQUEST['bk_voicemail'];
		$backup_options[]=$_REQUEST['bk_sysrecordings'];
		$backup_options[]=$_REQUEST['bk_sysconfig'];
		$backup_options[]=$_REQUEST['bk_cdr'];
		$backup_options[]=$_REQUEST['bk_fop'];
	
		$Backup_Parms=Get_Backup_String($name,$backup_schedule, $ALL_days, $ALL_months, $ALL_weekdays, $mins, $hours, $days, $months, $weekdays);
		Save_Backup_Schedule($Backup_Parms, $backup_options);
	break;
	case "edited":
		$ID=$_REQUEST['backupid'];
		Delete_Backup_set($ID);
		$ALL_days=$_REQUEST['all_days'];
		$ALL_months=$_REQUEST['all_months'];
		$ALL_weekdays=$_REQUEST['all_weekdays'];

		$backup_schedule=$_REQUEST['backup_schedule'];
		$name=(empty($_REQUEST['name'])?'backup':$_REQUEST['name']);
		$mins=$_REQUEST['mins'];
		$hours=$_REQUEST['hours'];
		$days=$_REQUEST['days'];
		$months=$_REQUEST['months'];
		$weekdays=$_REQUEST['weekdays'];
		
		$backup_options[]=$_REQUEST['bk_voicemail'];
		$backup_options[]=$_REQUEST['bk_sysrecordings'];
		$backup_options[]=$_REQUEST['bk_sysconfig'];
		$backup_options[]=$_REQUEST['bk_cdr'];
		$backup_options[]=$_REQUEST['bk_fop'];
	
		$Backup_Parms=Get_Backup_String($name,$backup_schedule, $ALL_days, $ALL_months, $ALL_weekdays, $mins, $hours, $days, $months, $weekdays);
		Save_Backup_Schedule($Backup_Parms, $backup_options);
	break;
	case "delete":
		$ID=$_REQUEST['backupid'];
		Delete_Backup_set($ID);
	break;
	case "deletedataset":
		$dir=$_REQUEST['dir'];
		exec("/bin/rm -rf '$dir'");
	break;
	case "deletefileset":
		$dir=$_REQUEST['dir'];
		exec("/bin/rm -rf '$dir'");
	break;
	case "restored":
		$dir=$_REQUEST['dir'];
		$file=$_REQUEST['file'];
		$filetype=$_REQUEST['filetype'];
		$Message=Restore_Tar_Files($dir, $file, $filetype, $display);
		needreload();
	break;
}


?>
</div>
<div class="rnav">
    <li><a href="config.php?display=<?php echo $display?>&action=add"><?php echo _("Add Backup Schedule")?></a><br></li>
    <li><a href="config.php?display=<?php echo $display?>&action=restore"><?php echo _("Restore from Backup")?></a><br></li>

<?php 
//get unique account rows for navigation menu
$results = Get_Backup_Sets();

if (isset($results)) {
	foreach ($results as $result) {
		echo "<li><a id=\"".($extdisplay==$result[13] ? 'current':'')."\" href=\"config.php?display=".$display."&action=edit&backupid={$result[13]}&backupname={$result[0]}\">{$result[0]}</a></li>";
	}
}
?>
</div>


<div class="content">

<?php
if ($action == 'add')
{
	?>
	<h2><?php echo _("System Backup")?></h2>
	<form name="addbackup" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $display?>">
	<input type="hidden" name="action" value="addednew">
        <table>
	<?php Show_Backup_Options(); ?>
        </table>
    <h5>Run Schedule<hr></h5>
        <table>
	<?php show_schedule("yes",""); ?>
	<tr>
        <td colspan="5" align="center"><input name="Submit" type="submit" value="Submit Changes" ></td>
        </tr>
        </table>
	</form>
	<br><br><br><br><br>

<?php
}
else if ($action == 'edit')
{
	?>
	<h2><?php echo _("System Backup")?></h2>
	<p><a href="config.php?display=<?php echo $display ?>&action=delete&backupid=<?php echo $_REQUEST['backupid']; ?>"><?php echo _("Delete Backup Schedule")?> <?php echo $_REQUEST['backupname']; ?></a></p>
	<form name="addbackup" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $display?>">
	<input type="hidden" name="action" value="edited">
	<input type="hidden" name="backupid" value="<?php echo $_REQUEST['backupid']; ?>">
        <table>
	<?php Show_Backup_Options($_REQUEST['backupid']); ?>
        </table>
    <h5>Run Schedule<hr></h5>
        <table>
	<?php show_schedule("yes", "$_REQUEST[backupid]"); ?>
	<tr>
        <td colspan="5" align="center"><input name="Submit" type="submit" value="Submit Changes" ></td>
        </tr>
        </table>
	</form>
	<br><br><br><br><br>

<?php
}
else if ($action == 'restore')
{
?>
	<h2><?php echo _("System Restore")?></h2>
<?php
	if (!isset($_REQUEST['dir'])) {
		$dir = "/var/lib/asterisk/backups";
		if(!is_dir($dir)) mkdir($dir);
	} else {
		$dir = "$_REQUEST[dir]";
	}
	$file = "$_REQUEST[file]";

	Get_Tar_Files($dir, $display, $file);
	echo "<br><br><br><br><br><br><br><br><br><br><br><br>";
	
}
else
{
	if (isset($Message)){
	?>
		<h3><?php echo $Message ?></h3>
	<?php }
	else{
	?>
		<h2><?php echo _("System Backup") ?></h2>
	<?php }
?>

	

	<br><br><br><br><br><br>
	<br><br><br><br><br><br>
<?php
}
?>

<?php
// schedule_functions.php Copyright (C) 2005 VerCom Systems, Inc. & Ron Hartmann (rhartmann@vercomsystems.com)
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

function Get_Tar_Files($dir="", $display="", $file="")
{
        if (is_dir($dir))
        {
        	if (($file!=".") && ($file!="..") && ($file!="")){
                	echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to delete this File Set?','config.php?display=$display&action=deletedataset&dir=$dir')\">";
			echo _("DELETE ALL THE DATA IN THIS SET"); echo "<span>"; echo _("Delete this backup set and all data associated with this backup set..");echo "</span></a><br></li>";
                	echo "<br>";
		}
                if ($dh = opendir($dir)){
                        while (($file = readdir($dh)) !== false)
                        {
                                if (($file!=".") && ($file!="..") && ($dir=="/var/lib/asterisk/backups/"))
                                        echo "<li><a href=\"config.php?display=$display&action=restore&dir=$dir/$file\">$file</a><br></li>";
                                else if (($file!=".") && ($file!="..") )
                                        echo "<li><a href=\"config.php?display=$display&action=restore&dir=$dir/$file&file=$file\">$file</a><br></li>";
                        }
                        closedir($dh);
                }
        }
        else if (substr($dir, -6)=="tar.gz" ){
                echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to delete this File Set?','config.php?display=$display&action=deletefileset&dir=$dir&file=$file')\">";
		echo _("Delete File Set"); echo "<span>"; echo _("Delete this backup set."); echo "</span></a><br></li>";
                echo "<br>";
                $tar_string="tar tfz \"$dir\" | cut -d'/' -f4";
                exec($tar_string,$restore_files,$error);
                echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to Restore this Complete File Set\nDoing so will Permanently Over-Write all AMP and Asterisk Files\n You will Loose all Your CAll DETAIL RECORDS and YOUR VoiceMail that was recorded between the BACKUP DATE and NOW?','config.php?display=$display&action=restored&dir=$dir&filetype=ALL&file=$file')\">";
		echo _("Restore Entire Backup Set"); echo "<span>"; echo _("Restore your Complete Backup set overwriting all files."); echo "</span></a><br></li>";
                echo "<br>";
                if (array_search('voicemail.tar.gz',$restore_files)){
                        echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to Restore  this File Set\nDoing so will permently delete any new voicemail you have in your mailbox\n ansince this backup on $file ?','config.php?display=$display&action=restored&dir=$dir&filetype=VoiceMail&file=$file')\">";
			echo _("Restore VoiceMail Files");echo "<span>"; echo _("Restore your Voicemail files from this backup set.  NOTE! This will delete any voicemail currently in the voicemail boxes.");
			echo "</span></a><br></li>";
                        echo "<br>";
                }

                if (array_search('recordings.tar.gz',$restore_files)){
                        echo "<li><a class=\"info\" href=\"config.php?display=$display&action=restored&dir=$dir&filetype=Recordings&file=$file\">";
			echo _("Restore System Recordings Files"); echo "<span>"; echo _("Restore your system Voice Recordings including AutoAttendent files from this backup set.  NOTE! This will OVERWRITE any voicerecordings  currently on the system. It will NOT delete new files not currently in the backup set"); echo "</span></a><br></li>";
                        echo "<br>";
                }
                if (array_search('configurations.tar.gz',$restore_files)){
                        echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to Restore this File Set\nDoing so will Permanently Over-Write all AMP and Asterisk Files?','config.php?display=$display&action=restored&dir=$dir&filetype=Configurations&file=$file')\">";
			echo _("Restore System Configuration"); echo "<span>"; echo _("Restore your system configuration from this backup set.  NOTE! This will OVERWRITE any System changes you have made since this backup... ALL Itemes will be reset to what they were at the time of this backup set.."); echo "</span></a><br></li>";
                        echo "<br>";
                }
                if (array_search('fop.tar.gz',$restore_files)){
                        echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to Restore the Operator Panel Files\nDoing so will Permanently Over-Write all Operator Panel Files?','config.php?display=$display&action=restored&dir=$dir&filetype=FOP&file=$file')\">";
			echo _("Restore Operator Panel"); echo "<span>"; echo _("Restore the Operator Panel from this backup set.  NOTE! This will OVERWRITE any Operator Panel Changes you have made since this backup... ALL Itemes will be reset to what they were at the time of this backup set.."); echo "</span></a><br></li>";
                        echo "<br>";
                }
                if (array_search('cdr.tar.gz',$restore_files)){
                        echo "<li><a class=\"info\" href=\"javascript:decision('Are you sure you want to Restore the CALL DETAIL FILES \nDoing so will Permanently DELETE  all CALL RECORDS.?','config.php?display=$display&action=restored&dir=$dir&filetype=CDR&file=$file')\">";
			echo _("Restore Call Detail Report"); echo "<span>"; echo _("Restore the Call Detail Records from this backup set.  NOTE! This will DELETE ALL CALL RECORDS that have been saved since this backup set.."); echo "</span></a><br></li>";
                        echo "<br>";
                }
        }
        else{
                echo "<h2>"; echo _("ERROR its not a BACKUP SET file");echo "</h2>";
	}
}
function Restore_Tar_Files($dir="", $file="",$filetype="", $display="")
{
		$amp_conf = parse_amportal_conf("/etc/amportal.conf");
                $Message="Restore Failed";

                if($filetype=="ALL"){
                        $Message="Restored All Files in BackupSet";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        exec('/bin/rm -rf /var/spool/asterisk/voicemail');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/voicemail.tar.gz /tmp/ampbackups.$fileholder/recordings.tar.gz ";
                        $tar_cmd.="/tmp/ampbackups.$fileholder/configurations.tar.gz /tmp/ampbackups.$fileholder/fop.tar.gz /tmp/ampbackups.$fileholder/cdr.tar.gz  | tar -Pxvz";
                        exec($tar_cmd);
                        $tar_cmd="tar -Pxvz -f \"$dir\" /tmp/ampbackups.$fileholder/asterisk.sql /tmp/ampbackups.$fileholder/asteriskcdr.sql";
                        exec($tar_cmd);
                        $sql_cmd="mysql -u $amp_conf[AMPDBUSER] -p$amp_conf[AMPDBPASS] < /tmp/ampbackups.$fileholder/asterisk.sql";
                        exec($sql_cmd);
                        $sql_cmd="mysql -u $amp_conf[AMPDBUSER] -p$amp_conf[AMPDBPASS] < /tmp/ampbackups.$fileholder/asteriskcdr.sql";
                        exec($sql_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
                else if($filetype=="VoiceMail"){
                        $Message="Restored VoiceMail";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        exec('/bin/rm -rf /var/spool/asterisk/voicemail');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/voicemail.tar.gz | tar -Pxvz";
                        exec($tar_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
                else if($filetype=="Recordings"){
                        $Message="Restored System Recordings";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/recordings.tar.gz | tar -Pxvz";
                        exec($tar_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
                else if($filetype=="Configurations"){
                        $Message="Restored System Configuration";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/configurations.tar.gz | tar -Pxvz";
                        exec($tar_cmd);
                        $tar_cmd="tar -Pxvz -f \"$dir\" /tmp/ampbackups.$fileholder/asterisk.sql";
                        exec($tar_cmd);
                        $sql_cmd="mysql -u $amp_conf[AMPDBUSER] -p$amp_conf[AMPDBPASS] < /tmp/ampbackups.$fileholder/asterisk.sql";
                        exec($sql_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
                else if($filetype=="FOP"){
                        $Message="Restored Operator Panel";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/fop.tar.gz | tar -Pxvz";
                        exec($tar_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
                else if($filetype=="CDR"){
                        $Message="Restored CDR logs";
                        $fileholder=substr($file, 0,-7);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                        $tar_cmd="tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/cdr.tar.gz | tar -Pxvz";
                        exec($tar_cmd);
                        $tar_cmd="tar -Pxvz -f \"$dir\" /tmp/ampbackups.$fileholder/asteriskcdr.sql";
                        exec($tar_cmd);
                        $sql_cmd="mysql -u $amp_conf[AMPDBUSER] -p$amp_conf[AMPDBPASS] < /tmp/ampbackups.$fileholder/asteriskcdr.sql";
                        exec($sql_cmd);
                        exec('/bin/rm -rf /tmp/ampbackups.$fileholder');
                }
return ($Message);
}
function Get_Backup_Sets() {
        global $db;
        $sql = "SELECT * FROM Backup";
        $results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
        return $results;
}
function Delete_Backup_Set($ID="") {
        global $db;
	$sql = "DELETE FROM Backup  WHERE ID = '$ID'";
        $result = $db->query($sql);
        if(DB::IsError($result)) {
                die($result->getMessage());
        }
	$Cron_Script="/var/lib/asterisk/bin/retrieve_backup_cron_from_mysql.pl";
	exec($Cron_Script);
}
function Save_Backup_Schedule($Backup_Parms, $backup_options )
{
        global $db;
	if ($Backup_Parms[1]=="now")
	{
		$Cron_Script="/var/lib/asterisk/bin/ampbackup.pl '$Backup_Parms[0]' $backup_options[0] $backup_options[1] $backup_options[2] $backup_options[3] $backup_options[4]";
		//echo "$Cron_Script";
		exec($Cron_Script);
	}
	$sql = "INSERT INTO Backup (Name, Voicemail, Recordings, Configurations, CDR, FOP, Minutes, Hours, Days, Months,Weekdays, Command, Method ) VALUES (";
        $sql .= "'".$Backup_Parms[0]."',";
        $sql .= "'".$backup_options[0]."',";
        $sql .= "'".$backup_options[1]."',";
        $sql .= "'".$backup_options[2]."',";
        $sql .= "'".$backup_options[3]."',";
        $sql .= "'".$backup_options[4]."',";
        $sql .= "'".$Backup_Parms[2]."',";
        $sql .= "'".$Backup_Parms[3]."',";
        $sql .= "'".$Backup_Parms[4]."',";
        $sql .= "'".$Backup_Parms[5]."',";
        $sql .= "'".$Backup_Parms[6]."',";
        $sql .= "'".$Backup_Parms[7]."',";
        $sql .= "'".$Backup_Parms[1]."');";
        $result = $db->query($sql);
        if(DB::IsError($result)) {
                die($result->getMessage().'<hr>'.$sql);
        }
	$Cron_Script="/var/lib/asterisk/bin/retrieve_backup_cron_from_mysql.pl";
	exec($Cron_Script);
	
}
function Get_Backup_String($name, $backup_schedule, $ALL_days, $ALL_months, $ALL_weekdays, $mins="", $hours="", $days="", $months="", $weekdays="")
{
	if ($backup_schedule=="hourly")
		$Cron_String="0 * * * * /var/lib/asterisk/bin/ampbackup.pl";
	else if ($backup_schedule=="daily")
		$Cron_String="0 0 * * * /var/lib/asterisk/bin/ampbackup.pl";
	else if ($backup_schedule=="weekly")
		$Cron_String="0 0 * * 0 /var/lib/asterisk/bin/ampbackup.pl";
	else if ($backup_schedule=="monthly")
		$Cron_String="0 0 1 * * /var/lib/asterisk/bin/ampbackup.pl";
	else if ($backup_schedule=="yearly")
		$Cron_String="0 0 1 1 * /var/lib/asterisk/bin/ampbackup.pl";
	else if ($backup_schedule=="follow_schedule")
	{
		
		if (count($mins)<1)
        		$mins_string=":0:";
		else{
			foreach ($mins as $value)
        			$mins_string.=":$value:";
		}
		if (count($hours)<1)
        		$hours_string=":0:";
		else{
			foreach ($hours as $value)
		        	$hours_string.=":$value:";
		}
		if(($ALL_days=="1")||(count($days)<1))
			$days_string="*";
		else{
			foreach ($days as $value)
		        	$days_string.=":$value:";
		}
		if(($ALL_months=="1")||(count($months)<1))
			$months_string="*";
		else{
			foreach ($months as $value)
		        	$months_string.=":$value:";
		}
		if($ALL_weekdays=="1")
			$weekdays_string="*";
		else{
			foreach ($weekdays as $value)
		        	$weekdays_string.=":$value:";
		}

	       	$cron_mins_string=trim($mins_string,":");
		$cron_hours_string=trim($hours_string,":");
		$cron_days_string=trim($days_string,":");
		$cron_months_string=trim($months_string,":");
		$cron_weekdays_string=trim($weekdays_string,":");
		$Cron_String=str_replace("::", ",", "$cron_mins_string $cron_hours_string $cron_days_string $cron_months_string $cron_weekdays_string /var/lib/asterisk/bin/ampbackup.pl");
	}
	else if ($backup_schedule=="now")
		$Cron_String="0 0 0 0 0 /var/lib/asterisk/bin/ampbackup.pl";
	$Backup_String[]="$name";
	$Backup_String[]="$backup_schedule";
	$Backup_String[]="$mins_string";
	$Backup_String[]="$hours_string";
	$Backup_String[]="$days_string";
	$Backup_String[]="$months_string";
	$Backup_String[]="$weekdays_string";
	$Backup_String[]="$Cron_String";

	return ($Backup_String);
}
function Get_Backup_Times($BackupID)
{
        global $db;
        $sql = "SELECT Minutes, Hours, Days, Months, Weekdays, Method From Backup where ID=\"$BackupID\"";
        $results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
        return $results;
}
function Get_Backup_Options($BackupID)
{
        global $db;
        $sql = "SELECT Name, Voicemail, Recordings, Configurations, CDR, FOP FROM Backup where ID=\"$BackupID\"";
        $results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
        return $results;
}
function Show_Backup_Options($ID="")
{
	if ($ID==""){
		$name=""; $voicemail="no"; $sysrecordings="no"; $sysconfig="no"; $cdr="no"; $fop="no";}
	else{
		$backup_options=Get_Backup_Options($ID);
		foreach ($backup_options as $bk_options) 
			$name="$bk_options[0]";$voicemail="$bk_options[1]"; $sysrecordings="$bk_options[2]"; $sysconfig="$bk_options[3]"; $cdr="$bk_options[4]"; $fop="$bk_options[5]";
	}
	?>
        <tr>
                <td><a href="#" class="info"><?php echo _("Schedule Name:")?><span><?php echo _("Give this Backup Schedule a brief name to help you identify it.");?></span></a></td>
                <td><input type="text" name="name" value="<?php echo (isset($name) ? $name : ''); ?>"></td>
        </tr>

	<tr>
 		<td><a href="#" class="info"><?php echo _("VoiceMail");?><span><?php echo _("Backup the System VoiceMail Boxes... CAUTION: Could result in large file");?></span></a>: </td>
 		<?php if ($voicemail == "yes"){?>
 			<td><input type="radio" name="bk_voicemail" value="yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_voicemail" value="no"/> no</td>
 		<?php } else{ ?>
 			<td><input type="radio" name="bk_voicemail" value="yes" /> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_voicemail" value="no" checked=checked/> no</td>
 		<?php } ?>
 	</tr>
	<tr>
 		<td><a href="#" class="info"><?php echo _("System Recordings");?><span><?php echo _("Backup the System Recordings (AutoAttendent, Music On Hold, System Recordings)");?></span></a>: </td>
 		<?php if ($sysrecordings == "yes"){?>
 			<td><input type="radio" name="bk_sysrecordings" value="yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_sysrecordings" value="no"/> no</td>
 		<?php } else{ ?>
 			<td><input type="radio" name="bk_sysrecordings" value="yes" /> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_sysrecordings" value="no" checked=checked/> no</td>
 		<?php } ?>
 	</tr>
	<tr>
 		<td><a href="#" class="info"><?php echo _("System Configuration");?><span><?php echo _("Backup the System Configurations (Database, etc files, SQL Database, astdb)");?></span></a>: </td>
 		<?php if ($sysconfig == "yes"){?>
 			<td><input type="radio" name="bk_sysconfig" value="yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_sysconfig" value="no"/> no</td>
 		<?php } else{ ?>
 			<td><input type="radio" name="bk_sysconfig" value="yes" /> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_sysconfig" value="no" checked=checked/> no</td>
 		<?php } ?>
 	</tr>
	<tr>
 		<td><a href="#" class="info"><?php echo _("CDR");?><span><?php echo _("Backup the System Call Detail Reporting (HTML and Database)");?></span></a>: </td>
 		<?php if ($cdr == "yes"){?>
 			<td><input type="radio" name="bk_cdr" value="yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_cdr" value="no"/> no</td>
 		<?php } else{ ?>
 			<td><input type="radio" name="bk_cdr" value="yes" /> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_cdr" value="no" checked=checked/> no</td>
 		<?php } ?>
 	</tr>
	<tr>
 		<td><a href="#" class="info"><?php echo _("Operator Panel");?><span><?php echo _("Backup the Operator Panel (HTML and Database)");?></span></a>: </td>
 		<?php if ($fop == "yes"){?>
 			<td><input type="radio" name="bk_fop" value="yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_fop" value="no"/> no</td>
 		<?php } else{ ?>
 			<td><input type="radio" name="bk_fop" value="yes" /> yes &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="bk_fop" value="no" checked=checked/> no</td>
 		<?php } ?>
 	</tr>
	<?php
}
function Schedule_Show_Minutes($Minutes_Set="")
{
	echo "<br><br><table> <tr>";
	echo "<td valign=top><select multiple size=12 name=mins[]>";
	for ($minutes=0; $minutes<=59; $minutes++)
	{
/*		if (($minutes==12)||($minutes==24)||($minutes==36)||($minutes==48))
		{
			echo "</select></td>";
			echo "<td width=2 valign=top><select multiple size=12 name=mins[]>";
		}
*/		if (strstr($Minutes_Set,":$minutes:"))
			echo "<option value=\"$minutes\" selected>$minutes";
		else
			echo "<option value=\"$minutes\" >$minutes";
	}
	echo "</select></td>";
	echo "</tr></table></td>";
}
function Schedule_Show_Hours($Hours_Set="")
{
	echo "<br><br><table> <tr>";
	echo "<td valign=top><select multiple size=12 name=hours[]>";
	for ($hours=0; $hours<=23; $hours++)
	{
/*		if ($hours==12)
		{
			echo "</select></td>";
			echo "<td valign=top><select multiple size=12 name=hours[]>";
		}
*/		if (strstr($Hours_Set,":$hours:"))
			echo "<option value=\"$hours\" selected>$hours";
		else
			echo "<option value=\"$hours\" >$hours";
	}
	echo "</select></td>";
	echo "</tr></table></td>";
}

function Schedule_Show_Days($Days_Set="")
{
	if ($Days_Set==""){
	echo "<input type=radio name=all_days value=1 checked>"; echo _("All"); echo "<br>";
	echo "<input type=radio name=all_days value=0 >"; echo _("Selected"); echo "<br>";
	}
	else{
	echo "<input type=radio name=all_days value=1 >"; echo _("All"); echo "<br>";
	echo "<input type=radio name=all_days value=0 checked>"; echo _("Selected"); echo "<br>";
	}

	echo "<table> <tr>";
	echo "<td valign=top><select multiple size=12 name=days[]>";
	for ($days=1; $days<=31; $days++)
	{
/*		if (($days==13)||($days==25))
		{
			echo "</select></td>";
			echo "<td valign=top><select multiple size=12 name=days[]>";
		}
*/		if (strstr($Days_Set,":$days:"))
			echo "<option value=\"$days\" selected>$days";
		else
			echo "<option value=\"$days\" >$days";
	}
	echo "</select></td>";
	echo "</tr></table></td>";
}

function Schedule_Show_Months($Months_Set="")
{
	if ($Months_Set==""){
	echo "<input type=radio name=all_months value=1 checked>"; echo _("All"); echo "<br>";
	echo "<input type=radio name=all_months value=0 >"; echo _("Selected"); echo "<br>";
	}
	else{
	echo "<input type=radio name=all_months value=1 >"; echo _("All"); echo "<br>";
	echo "<input type=radio name=all_months value=0 checked>"; echo _("Selected"); echo "<br>";
	}
	echo "<table> <tr>";
	echo "<td valign=top><select multiple size=12 name=months[]>";
	echo (strstr($Months_Set,":1:") ? '<option value="1" selected>January':'<option value="1" >January');
	echo (strstr($Months_Set,":2:") ? '<option value="2" selected>February':'<option value="2" >February');
	echo (strstr($Months_Set,":3:") ? '<option value="3" selected>March':'<option value="3" >March');
	echo (strstr($Months_Set,":4:") ? '<option value="4" selected>April':'<option value="4" >April');
	echo (strstr($Months_Set,":5:") ? '<option value="5" selected>May':'<option value="5" >May');
	echo (strstr($Months_Set,":6:") ? '<option value="6" selected>June':'<option value="6" >June');
	echo (strstr($Months_Set,":7:") ? '<option value="7" selected>July':'<option value="7" >July');
	echo (strstr($Months_Set,":8:") ? '<option value="8" selected>August':'<option value="8" >August');
	echo (strstr($Months_Set,":9:") ? '<option value="9" selected>September':'<option value="9" >September');
	echo (strstr($Months_Set,":10:") ? '<option value="10" selected>October':'<option value="10" >October');
	echo (strstr($Months_Set,":11:") ? '<option value="11" selected>November':'<option value="11" >November');
	echo (strstr($Months_Set,":12:") ? '<option value="12" selected>December':'<option value="12" >December');

	echo "</select></td>";
	echo "</tr></table></td>";
}

function Schedule_Show_Weekdays($Weekdays_Set="")
{
	if ($Weekdays_Set==""){
	echo "<input type=radio name=all_weekdays value=1 checked>";echo _("All"); echo "<br>";
	echo "<input type=radio name=all_weekdays value=0 >";echo _("Selected"); echo "<br>";
	}
	else{
	echo "<input type=radio name=all_weekdays value=1 >";echo _("All"); echo "<br>";
	echo "<input type=radio name=all_weekdays value=0 checked>";echo _("Selected"); echo "<br>";
	}
	echo "<table> <tr>";
	echo "<td valign=top><select multiple size=12 name=weekdays[]>";
	echo (strstr($Weekdays_Set,":0:") ? '<option value="1" selected>Monday':'<option value="0" >Monday');
	echo (strstr($Weekdays_Set,":1:") ? '<option value="1" selected>Tuesday':'<option value="1" >Tuesday');
	echo (strstr($Weekdays_Set,":2:") ? '<option value="2" selected>Wednesday':'<option value="2" >Wednesday');
	echo (strstr($Weekdays_Set,":3:") ? '<option value="3" selected>Thursday':'<option value="3" >Thursday');
	echo (strstr($Weekdays_Set,":4:") ? '<option value="4" selected>Friday':'<option value="4" >Friday');
	echo (strstr($Weekdays_Set,":5:") ? '<option value="5" selected>Saturday':'<option value="5" >Saturday');
	echo (strstr($Weekdays_Set,":6:") ? '<option value="6" selected>Sunday':'<option value="6" >Sunday');

	echo "</select></td>";
	echo "</tr></table></td>";
}
function show_quickbar($Method="")
{
?>
	<tr bgcolor=#b7b7b7> <td colspan=6><?php echo _("Run Backup");?> 
	<select name=backup_schedule>
	<option value=follow_schedule <?php echo ($Method == "follow_schedule" ? "SELECTED" : "")?>><?php echo _("Follow Schedule Below");?>
	<option value=now <?php echo ($Method == "now" ? "SELECTED" : "")?>><?php echo _("Now");?>
	<option value=daily <?php echo ($Method == "daily" ? "SELECTED" : "")?>><?php echo _("Daily (at midnight)");?>
	<option value=weekly <?php echo ($Method == "weekly" ? "SELECTED" : "")?>><?php echo _("Weekly (on Sunday)");?>
	<option value=monthly <?php echo ($Method == "monthly" ? "SELECTED" : "")?>><?php echo _("Monthly (on the 1st)");?>
	<option value=yearly <?php echo ($Method == "yearly" ? "SELECTED" : "")?>><?php echo _("Yearly (on 1st Jan)");?>
	</select>
	</td></tr>
<?php
}
function show_schedule($quickbar="no", $BackupID="")
{
	if ($BackupID==""){
		$Minutes="";
		$Hours="";
		$Days="";
		$Months="";
		$WeekDays="";
		$Method="follow_schedule";
	}
	else{
		$backup_times=Get_Backup_Times($BackupID);
		foreach ($backup_times as $bk_times) 
			$Minutes="$bk_times[0]"; $Hours="$bk_times[1]"; $Days="$bk_times[2]"; $Months="$bk_times[3]"; $Weekdays="$bk_times[4]"; $Method="$bk_times[5]";
		
	}
	if ($quickbar=="yes")
		show_quickbar($Method);
	else
		echo "<tr bgcolor=#7f7f7f>";
	echo "<td><b>Minutes</b></td> <td><b>Hours</b></td> <td><b>Days</b></td> <td><b>Months</b></td><td><b>Weekdays</b></td> </tr> <tr bgcolor=#b7b7b7>";
	echo "<td valign=top>";
	Schedule_Show_Minutes($Minutes); 
	echo "<td valign=top>";
	Schedule_Show_Hours($Hours);
	echo "<td valign=top>";
	Schedule_Show_Days($Days); 
	echo "<td valign=top>";
	Schedule_Show_Months($Months);
	echo "<td valign=top>";
	Schedule_Show_Weekdays($Weekdays);
}


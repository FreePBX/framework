<?php 
/* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//Re-written by Rob Thomas <xrobau@gmail.com> 20060318.
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

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$notes = isset($_REQUEST['notes'])?$_REQUEST['notes']:'';
$rname = isset($_REQUEST['rname'])?$_REQUEST['rname']:'';
$usersnum = isset($_REQUEST['usersnum'])?$_REQUEST['usersnum']:'';

recordings_init();

echo "</div>\n";

switch ($action) {
	case "recorded":
		if (empty($usersnum)) {
			$dest = "unnumbered-";
		} else {
			$dest = "{$usersnum}-";
		}
		// Clean up the filename, take out any nasty characters
		$filename = escapeshellcmd(strtr($rname, '/ ', '__'));
		rename("/var/lib/asterisk/sounds/{$dest}ivrrecording.wav","/var/lib/asterisk/sounds/custom/{$filename}.wav");
		$isok = recordings_add($rname, "custom/{$filename}.wav");
		recording_sidebar(null, $usersnum);
		recording_addpage($usersnum);
		if ($isok) 
			echo '<div class="content"><h5>'._("System Recording").' "'.$rname.'" '._("Saved").'!</h5>';
		break;
	case "edit":
		recording_sidebar($id, $usersnum);	
		recording_editpage($id, $usersnum);
		break;
	case "edited":
		recordings_update($id, $rname, $notes);
		recording_sidebar($id, $usernsnum);
		recording_editpage($id, $usersnum);
		echo '<div class="content"><h5>'._("System Recording").' "'.$rname.'" '._("Updated").'!</h5></div>';
		break;
	case "delete";
		recordings_del($id);
	default:
		recording_sidebar($id, $usersnum);
		recording_addpage($usersnum);
		break;
}
	
function recording_addpage($usersnum) { ?>
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h3><?php echo _("Add Recording") ?></h3>
	<h5><?php echo _("Step 1: Record or upload")?></h5>
	<p> <?php if (!empty($usersnum)) {
		echo _("Using your phone,")."<a href=\"#\" class=\"info\">"._("dial *77")." <span>";
		echo _("Start speaking at the tone. Hangup when finished.")."</span></a>";
		echo _("and speak the message you wish to record.")."\n";
	} else { ?>
		<form name="xtnprompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="recordings">
		<?php
		echo _("If you wish to make and verify recordings from your phone, please enter your extension number here:"); ?>
		<input type="text" size="6" name="usersnum"> <input name="Submit" type="submit" value="Go">
		</form>
	<?php } ?>
	</p>
	<p>
	<form enctype="multipart/form-data" name="upload" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		<?php echo _('Alternatively, upload a recording in')?> <a href="#" class="info"><?php echo _(".wav format")?><span><?php echo _("The .wav file _must_ be 16 bit PCM Encoded at a sample rate of 8000Hz")?></span></a>:<br>
		<input type="hidden" name="display" value="recordings">
		<input type="hidden" name="action" value="recordings_start">
                <input type="hidden" name="usersnum" value="<?php echo $usersnum ?>">
		<input type="file" name="ivrfile"/>
		<input type="button" value="<?php echo _("Upload")?>" onclick="document.upload.submit(upload);alert('<?php echo _("Please wait until the page reloads.")?>');"/>
	</form>
	<?php

	if (isset($_FILES['ivrfile']['tmp_name']) && is_uploaded_file($_FILES['ivrfile']['tmp_name'])) {
		if (empty($usersnum)) {
			$dest = "unnumbered-";
		} else {
			$dest = $usersnum;
		}
		move_uploaded_file($_FILES['ivrfile']['tmp_name'], "/var/lib/asterisk/sounds/".$dest."ivrrecording.wav");
		echo "<h6>"._("Successfully uploaded")." ".$_FILES['ivrfile']['name']."</h6>";
	} ?>
	</p>
	<form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return rec_onsubmit();">
	<input type="hidden" name="action" value="recorded">
	<input type="hidden" name="display" value="recordings">
	<input type="hidden" name="usersnum" value="<?php echo $usersnum ?>">
	<?php
	if (!empty($usersnum)) { ?>
		<h5><?php echo _("Step 2: Verify")?></h5>
		<p> <?php echo _("After recording or uploading, <em>dial *99</em> to listen to your recording.")?> </p>
		<p> <?php echo _("If you wish to re-record your message, dial *77 again.")?></p>
		<h5><?php echo _("Step 3: Name")?> </h5> <?php
	} else { 
		echo "<h5>"._("Step 2: Name")."</h5>";
	} ?>
	<table style="text-align:right;">
		<tr valign="top">
			<td valign="top"><?php echo _("Name this Recording")?>: </td>
			<td style="text-align:left"><input type="text" name="rname"></td>
		</tr>
	</table>
	<h6><?php echo _("Click \"SAVE\" when you are satisfied with your recording")?>
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6> 
	<script language="javascript">
	<!--
	var theForm = document.prompt;
	function rec_onsubmit() {
	defaultEmptyOK = false;
	if (!isAlphanumeric(theForm.rname.value))
		return warnInvalid(theForm.rname, "Please enter a valid Name for this System Recording");
	}
	-->
	</script>
	</form>
	</div>
<?php
}

function recording_editpage($id, $num) { ?>
	
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h3><?php echo _("Edit Recording") ?></h3>
	<?php
	$this_recording = recordings_get($id);
	if (!$this_recording) {
		echo "<tr><td colspan=2><h2>Error reading Recording ID $id - Aborting</h2></td></tr></table>";
		return;
	}?>
	<?php 
	echo "<a href=config.php?display=recordings&amp;action=delete&amp;usersnum=".urlencode($num);
	echo "&amp;id=$id>Remove Recording</a> <i style='font-size: x-small'>(Note, does not delete file from computer)</i>";
	?>
	<form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="action" value="edited">
	<input type="hidden" name="display" value="recordings">
	<input type="hidden" name="usersnum" value="<?php echo $num ?>">
	<input type="hidden" name="id" value="<?php echo $id ?>">
	<table>
	<tr><td colspan=2><hr></td></tr>
	<tr>
		<td><a href="#" class="info">Change Name<span>This changes the short name, visible on the right, of this recording</span></a></td>
		<td><input type="text" name="rname" value="<?php echo $this_recording['displayname'] ?>"></td>
	</tr>
	<tr>
	    	<td><a href="#" class="info">Descriptive Name<span>This is displayed, as a hint, when selecting this recording in Queues, Digital Receptionist, etc</span></a></td>
	    	<td>&nbsp;<textarea name="notes" rows="3" cols="40"><?php echo $this_recording['description'] ?></textarea></td>
	</tr>
	</table>
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6>
	</form>
	</div>
<?php
}

function recording_sidebar($id, $num) {
?>
        <div class="rnav">
        <li><a id="<?php echo empty($id)?'current':'nul' ?>" href="config.php?display=recordings&amp;usersnum=<?php echo urlencode($num) ?>"><?php echo _("Add Recording")?></a></li>
<?php

        $tresults = recordings_list();
        if (isset($tresults)){
                foreach ($tresults as $tresult) {
                        echo "<li><a id=\"".($id==$tresult[0] ? 'current':'nul')."\" href=\"config.php?display=recordings";
                        echo "&amp;action=edit&amp;usersnum=".urlencode($num)."&amp;id={$tresult['0']}\">{$tresult['1']}</a></li>\n";
                }
        }
        echo "</div>\n";
}

function runModuleSQL($moddir,$type){
        global $db;
        $data='';
        if (is_file("modules/{$moddir}/{$type}.sql")) {
                // run sql script
                $fd = fopen("modules/{$moddir}/{$type}.sql","r");
                while (!feof($fd)) {
                        $data .= fread($fd, 1024);
                }
                fclose($fd);

                preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);

                foreach ($matches[1] as $sql) {
                                $result = $db->query($sql);
                                if(DB::IsError($result)) {
                                        return false;
                                }
                }
                return true;
        }
                return true;
}

?>

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
$rname = isset($_REQUEST['rname'])?$_REQUEST['rname']:'';
$usersnum = isset($_REQUEST['usersnum'])?$_REQUEST['usersnum']:'';

recordings_init();

echo "</div>\n";

switch ($action) {
	case "recorded":
		if (empty($usersnum)) {
			$dest = "unnumbered-";
		} else {
			$dest = $usersnum;
		}
		// Clean up the filename, take out any nasty characters
		$filename = escapeshellcmd(strtr($rname, '/ ', '__'));
		rename('/var/lib/asterisk/sounds/'.$dest.'ivrrecording.wav','/var/lib/asterisk/sounds/custom/'.$filename.'.wav');
		recording_add($rname, $filename.".wav");
		recording_sidebar(null, $usersnum);
		echo '<br><h3>'._("System Recording").' "'.$rname.'" '._("Saved").'!</h3>';
		recording_mainpage($usersnum);
		break;
	case "edit":
		recording_sidebar($id, $usersnum);	
		recording_editpage($id, $usersnum);
		break;
	default:
		recording_sidebar($id, $usersnum);
		recording_mainpage($usersnum);
		break;
}
	
function recording_mainpage($usersnum) { ?>
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h2><?php echo _("Add Recording") ?></h2>
	<h5><?php echo _("Step 1: Record or upload")?></h5>
	<p> <?php if (!empty($usersnum)) {
		echo _("Using your phone,")."<a href=\"#\" class=\"info\">"._("dial *77")." <span>";
		echo _("Start speaking at the tone. Hangup when finished.")."</span></a>";
		echo _("and speak the message you wish to record.")."\n";
	} else { ?>
		<form name="xtnprompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="recordings">
		<input type="hidden" name="action" value="<?php echo $action ?>">
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
                <input type="hidden" name="usersnum" value="<?php echo $usersnum;?>">
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
	<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
	<input type="hidden" name="display" value="recordings">
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
			<td style="text-align:left"><input type="text" name="rname" value="<?php echo $prompt ?>"></td>
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

function recording_editpage($id, $usersnum) { ?>
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h2><?php echo _("Edit Recording") ?></h2>
	<h5><?php echo _("Step 1: Record or upload")?></h5>
	</div>
<?php
}

function recording_sidebar($id, $num) {
?>
        <div class="rnav">
        <li><a id="nul" href="config.php?display=recordings"><?php echo _("Add Recording")?></a></li>
<?php

        $tresults = recordings_list();
        if (isset($tresults)){
                foreach ($tresults as $tresult) {
                        echo "<li><a id=\"".($id==$tresult[0] ? 'current':'nul')."\" href=\"config.php?display=recordings";
                        echo "&amp;action=edit&amp;usersnum=".urlencode($id)."&amp;id={$tresult['0']}\">{$tresult['1']}</a></li>\n";
                }
        }
        echo "</div>\n";
}

?>

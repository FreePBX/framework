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
?>

<?php
$action = $_REQUEST['action'];
$promptnum = $_REQUEST['promptnum'];
$prompt = $_REQUEST['recordingdisplay'];
$rname = $_REQUEST['rname'];
if ($promptnum == null) $promptnum = '1';
$display=12;


switch($action) {
	default:
?>
<h4>Your Current Extension</h4>
<form name="prompt" action="<?php echo $_REQUEST['PHP_SELF'] ?>" method="post">
        <input type="hidden" name="action" value="recordings_start">
        <input type="hidden" name="display" value="<?php echo $display?>">
        This Digital Receptionist wizard asks you to record and playback a greeting using your phone.<br><br>
        Please enter your current extension number:
        <input type="text" size="6" name="cidnum"><br>
        <h6><input name="Submit" type="submit" value="Continue"></h6><br><br><br><br><br><br>
</form>

<?php
        break;
	case 'recorded':
		$rname=strtr($rname," ", "_"); /* remove any spaces from the name to ensure a happy playground */
		//rename = move in php.  This ensures that someone trying to dial *99 will not hear old recordings.
		rename('/var/lib/asterisk/sounds/'.$_REQUEST['cidnum'].'ivrrecording.wav','/var/lib/asterisk/sounds/custom/'.$rname.'.wav');
		echo "Recording $prompt Saved";

	break;
	case 'delete':
		unlink('/var/lib/asterisk/sounds/custom/'.$prompt.'.wav');
		echo "Recording $prompt Deleted";
	break;
        case 'recordings_start':
?>

</div>
<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $display?>&action=recordings_start&cidnum=<?php echo $_REQUEST['cidnum'] ?>">Add Recording</a><br></li>

<?php
//get existing recordings info
$tresults = getsystemrecordings("/var/lib/asterisk/sounds/custom");

if (isset($tresults)){
	foreach ($tresults as $tresult) {
		echo "<li><a id=\"".($recordingdisplay==$tresult ? 'current':'')."\" href=\"config.php?display=".$display."&recordingdisplay={$tresult}&recording_action=edit&action=recordings_start&cidnum=$_REQUEST[cidnum]\">{$tresult}</a></li>";
	}
}

?>
</div>

<div class="content">
<h4>Recording: <?php echo $prompt ?></h4>
<?php
	//if we are trying to edit - let's be nice and give them the recording back
	if ($_REQUEST['recording_action'] == 'edit'){
?>
	<p><a href="config.php?display=<?php echo $display ?>&recordingdisplay=<?php echo $prompt ?>&action=delete">Delete Recording <?php echo $prompt; ?></a></p>
<?php
		//copy('/var/lib/asterisk/sounds/custom/'.$prompt.'.wav','/var/lib/asterisk/sounds/ivrrecording.wav');
		copy('/var/lib/asterisk/sounds/custom/'.$prompt.'.wav','/var/lib/asterisk/sounds/'.$_REQUEST['cidnum'].'ivrrecording.wav');

		echo '<h5>Dial *99 to listen to your current recording - click continue if you wish to re-use it.</h5>';
	}
?>
<h5>Step 1: Record</h5>
<p>
	Using your phone, <a href="#" class="info">dial *77<span>Start speaking at the tone. Hangup when finished.</span></a> and speak the message you wish to record.
</p>
<p>
	<form enctype="multipart/form-data" name="upload" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		Alternatively, upload a recording in <a href="#" class="info">.wav format<span>The .wav file _must_ have a sample rate of 8000Hz</span></a>:<br>
		<input type="hidden" name="display" value="<?php echo $display?>">
		<input type="hidden" name="promptnum" value="<?php echo $promptnum?>">
		<input type="hidden" name="action" value="recordings_start">
                <input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
		<input type="file" name="ivrfile"/>

		<input type="button" value="Upload" onclick="document.upload.submit(upload);alert('Please wait until the page reloads.');"/>
	</form>
<?php
if (is_uploaded_file($_FILES['ivrfile']['tmp_name'])) {
	move_uploaded_file($_FILES['ivrfile']['tmp_name'], "/var/lib/asterisk/sounds/".$_REQUEST['cidnum']."ivrrecording.wav");
	echo "<h6>Successfully uploaded ".$_FILES['ivrfile']['name']."</h6>";
}
?>
</p>
<form name="prompt" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
<input type="hidden" name="action" value="recorded">
<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
<input type="hidden" name="promptnum" value="<?php echo $promptnum?>">
<input type="hidden" name="display" value="<?php echo $display?>">
<h5>Step 2: Verify</h5>
<p>
	After recording or uploading, <em>dial *99</em> to listen to your recording.
</p>
<p>
	If you wish to re-record your message, dial *77 again.
</p>
<h5>Step 3: Name </h5>
<table style="text-align:right;">
<tr valign="top">
	<td valign="top">Name this Recording: </td>
	<td style="text-align:left"><input type="text" name="rname" value="<?php echo $prompt ?>"></td>
</tr>
</table>
<h6>Click "SAVE" when you are satisfied with your recording<input name="Submit" type="submit" value="Save"></h6>

</form>

<?php
	break;
?>


<?php
}
?>


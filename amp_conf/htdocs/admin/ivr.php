<?
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

<?
$action = $_REQUEST['action'];
$promptnum = $_REQUEST['promptnum'];
if ($promptnum == null) $promptnum = '1';

		//do another select for all parts in this aa_.  Will return nothing if this in new aa
		$aalines = aainfo($promptnum);
		$optioncount=0;
		
		//find relevant info in this context
		foreach ($aalines as $aaline) {
			$extension = $aaline[1];
			$application = $aaline[3];
			$args = explode(',',$aaline[4]);
			$argslen = count($args);
			if ($application == 'Macro' && $args[0] == 'exten-vm') {
					$optioncount++;
					$dropt = array('extension',$extension,$args[2]);
					$dropts[]= $dropt;
					//echo '<li>option '.$extension.' <b>dials extension #'.$args[2].'</b>';
			}
			elseif ($application == 'Macro' && $args[0] == 'vm') {
					$optioncount++;
					$dropt = array('voicemail',$extension,$args[1]);
					$dropts[]= $dropt;
					//echo '<li>option '.$extension.' <b>sends to voicemail box #'.$args[1].'</b>';
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'aa_') === false)) {
					$optioncount++;
					$dropt = array('ivr',$extension,substr($args[0],3));
					$dropts[]= $dropt;
					//echo '<li>option '.$extension.' <b>goes to Voice Menu #'.substr($args[0],3).'</b>';
					$menu_request[] = $args[0]; //we'll check to see if the aa_ target exists later
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-group') === false)) {
					$optioncount++;
					$dropt = array('group',$extension,$args[1]);
					$dropts[]= $dropt;
					//echo '<li>option '.$extension.' <b>dials group #'.$args[1].'</b>';
			}
			elseif ($application == 'Background') {
					$description = $aaline[5];
			}
			elseif ($application == 'DigitTimeout') {
					$mname = $aaline[5];
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'custom') === false)) {
					$optioncount++;
					$dropt = array('custom',$extension,$args[0].','.$args[1].','.$args[2]);
					$dropts[]= $dropt;
			}
		}
		
		
		
		
		
		
switch($action) {
	default:
?>

<h4>Record Menu #<? echo $promptnum ?>: <?echo $mname?></h4>
<?
	//if we are trying to edit - let's be nice and give them the recording back
	if ($_REQUEST['ivr_action'] == 'edit'){
		copy('/var/lib/asterisk/sounds/custom/aa_'.$promptnum.'.wav','/var/lib/asterisk/sounds/ivrrecording.wav');
		echo '<h5>Dial *99 to listen to your current recording - click continue if you wish to re-use it.</h5>';
	}
?>
<h5>Step 1: Record</h5>
<p>
	Using your phone, <a href="#" class="info">dial *77<span>Start speaking at the tone. Hangup when finished.</span></a> and record the message you wish to greet callers with.
</p>
<p>
	<form enctype="multipart/form-data" name="upload" action="<? echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		Alternatively, upload a recording in <a href="#" class="info">.wav format<span>The .wav file _must_ have a sample rate of 8000Hz</span></a>:<br>
		<input type="hidden" name="display" value="2">
		<input type="hidden" name="promptnum" value="<?echo $promptnum?>">
		<input type="file" name="ivrfile"/>
		<input type="button" value="Upload" onclick="document.upload.submit(upload);alert('Please wait until the page reloads.');"/>
	</form>
<?php
if (is_uploaded_file($_FILES['ivrfile']['tmp_name'])) {
	move_uploaded_file($_FILES['ivrfile']['tmp_name'], "/var/lib/asterisk/sounds/ivrrecording.wav");
	echo "<h6>Successfully uploaded ".$_FILES['ivrfile']['name']."</h6>";
}
?>
</p>
<form name="prompt" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
<input type="hidden" name="action" value="ivr_recorded">
<h5>Step 2: Verify</h5>
<p>
	After recording or uploading, <em>dial *99</em> to listen to your message.
</p>
<p>
	If you wish to re-record your message, dial *77 again.
</p>
<h5>Step 3: Name & Describe</h5>
<table style="text-align:right;">
<tr valign="top">
	<td valign="top">Name this menu: </td>
	<td style="text-align:left"><input type="text" name="mname" value="<? echo $mname ?>"></td>
</tr>
<tr>
	<td valign="top">Describe the menu: </td>
	<td>&nbsp;&nbsp;<textarea name="notes" rows="3" cols="50"><? echo $description ?></textarea></td>
</tr>
</table>
<h6>Click "Continue" when you are satisfied with your recording<input name="Submit" type="submit" value="Continue"></h6>

<h4>Consider including in your recording:</h4>
<p>
	<li>"If you know the extension you are trying to reach, dial it now."
	<li>"Dial # to access the company directory."
</p>
<p>
	Example:  Thank you for calling. Please press 1 for our locations, 2 for hours of operation, or 0 to speak with a representative. If you know the extension of the party you are calling, dial it now.  To access the company directory, press pound now.
</p>
</form>

















<?
	break;
	case 'ivr_recorded':
?>
<h4>Options for Menu #<? echo $promptnum ?>: <? echo $_REQUEST['mname']; ?></h4>
<form name="prompt" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
<input type="hidden" name="action" value="ivr_options_yes_num"/>
<input type="hidden" name="notes" value="<? echo $_REQUEST['notes'];?>">
<input type="hidden" name="mname" value="<? echo $_REQUEST['mname']; ?>">
<p>
Aside from local extensions and the pound key (#) for the directory, how many other options should callers be able to dial during the playback of Prompt #<? echo $promptnum ?>?<br>
<br>Number of options for Menu #<? echo $promptnum ?>: <? echo $_REQUEST['mname']; ?><input size="2" type="text" name="ivr_num_options" value="<? echo $optioncount ?>">
</p>
<h6><input name="Submit" type="submit" value="Continue"></h6>
</form>
















<?
	break;
	case 'ivr_options_yes_num':
	
	if (( $_REQUEST['ivr_num_options'] == '0' ) || ( $_REQUEST['ivr_num_options'] == '' )) {
?>
	<form name="prompt" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="action" value="ivr_options_set">
		<input type="hidden" name="notes" value="<? echo $_REQUEST['notes'];?>">
		<input type="hidden" name="mname" value="<? echo $_REQUEST['mname']; ?>">
		<input name="Submit" type="submit" value="Finished!  Click to save your changes.">
	</form><br><br><br><br><br><br>
<?
	} else {
		//query for exisiting aa_N contexts
		$unique_aas = getaas();
		
		//get unique extensions
		$extens = getextens();
		
		//get unique call groups
		$gresults = getgroups();
?>
<h4>Options for Menu #<? echo $promptnum ?>: <? echo $_REQUEST['mname']; ?></h4>
<p>
	Define the various options you expect your callers to dial after/during the playback of this recorded menu.
</p>
<p>
	"<b>Dialed Option #</b>" is the number you expect the caller to dial.<br>
	"<b>Action</b>" is the result of the caller dialing the option #.  This can send the caller to an internal extension, a voicemail box, or to another recorded menu. 
</p>
<p>
	If choose to send the caller to another menu, enter the number of an existing, or non-existing, menu.  You will be asked to record voice menus for numbers representing non-existing menus. You are currently working on menu #<? echo $promptnum ?>: <? echo $_REQUEST['mname']; ?>
</p>
<hr>
<p>	<form name="prompt" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="action" value="ivr_options_set"/>
	<input type="hidden" name="notes" value="<? echo $_REQUEST['notes'];?>">
	<input type="hidden" name="mname" value="<? echo $_REQUEST['mname']; ?>">
	<input type="hidden" name="ivr_num_options" value="<? echo $_REQUEST['ivr_num_options'] ?>">
	<table>
	<tr>
		<td><h4>Dialed Option #</h4></td>
		<td width="40px">&nbsp;</td>
		<td><h4>Action</h4></td>
	</tr>
<?
	for ($i = 0; $i < $_REQUEST['ivr_num_options']; $i++) { 
?>
	<tr>
		<td style="text-align:right;">
			<input size="2" type="text" name="ivr_option<? echo $i ?>" value="<? echo ($dropts[$i][1]=='')?$i+1:$dropts[$i][1] ?>">
		</td>
		<td></td>
		<td>
		
			<input type="radio" name="goto_indicate<? echo $i ?>" value="ivr" disabled="true" <?echo ($dropts[$i][0]=='ivr')?' checked=checked':''; ?>/> Digital Receptionist: 
			<input type="hidden" name="goto<? echo $i ?>" value="<? echo $dropts[$i][0] ?>">
			<select name="ivr<? echo $i ?>" onclick="javascript:document.prompt.goto_indicate<? echo $i ?>[0].checked=true;javascript:document.prompt.goto<? echo $i ?>.value='ivr';"/>
		<?
			$menu_num=1;
			foreach ($unique_aas as $unique_aa) {
				$menu_num = substr($unique_aa[0],3);
				$menu_name = $unique_aa[1];
				echo '<option value="'.$menu_num.'"';
				echo ($dropts[$i][2]==$menu_num)?' selected=selected':'';
				echo '>Menu #'.$menu_num.': '.$menu_name;
				$menu_num++;
			}
			for ($j = 0; $j < $_REQUEST['ivr_num_options']; $j++) { 
				$menu_num++;
				echo '<option value="'.$menu_num.'">A NEW Menu #'.$menu_num;
			}
		?>
			</select><br>
			<input type="radio" name="goto_indicate<? echo $i ?>" value="extension" disabled="true" <?echo ($dropts[$i][0]=='extension')?' checked=checked':''; ?> /> Extension: 
			<select name="extension<? echo $i ?>" onclick="javascript:document.prompt.goto_indicate<? echo $i ?>[1].checked=true;javascript:document.prompt.goto<? echo $i ?>.value='extension';"/>
		<?
			foreach ($extens as $exten) {
				echo '<option value="'.$exten[0].'"';
				echo ($dropts[$i][2]==$exten[0])?' selected=selected':'';
				echo '>Extension #'.$exten[0];
			}
		?>		
			</select><br>
			<input type="radio" name="goto_indicate<? echo $i ?>" value="voicemail" disabled="true" <?echo ($dropts[$i][0]=='voicemail')?' checked=checked':''; ?>/> Voicemail: 
			<select name="voicemail<? echo $i ?>" onclick="javascript:document.prompt.goto_indicate<? echo $i ?>[2].checked=true;javascript:document.prompt.goto<? echo $i ?>.value='voicemail';"/>
		<?
			foreach ($extens as $exten) {
				echo '<option value="'.$exten[0].'"';
				echo ($dropts[$i][2]==$exten[0])?' selected=selected':'';
				echo '>Voicemail #'.$exten[0];
			}
		?>		
			</select><br>
			<input type="radio" name="goto_indicate<? echo $i ?>" value="group" disabled="true" <?echo ($dropts[$i][0]=='group')?' checked=checked':''; ?>/> Call Group: 
			<select name="group<? echo $i ?>" onclick="javascript:document.prompt.goto_indicate<? echo $i ?>[3].checked=true;javascript:document.prompt.goto<? echo $i ?>.value='group';"/>
		<?
			foreach ($gresults as $gresult) {
				echo '<option value="'.$gresult[0].'"';
				echo ($dropts[$i][2]==$gresult[0])?' selected=selected':'';
				echo '>Group #'.$gresult[0];
			}
		?>			
			</select><br>
			<input type="radio" name="goto_indicate<? echo $i ?>" value="custom" disabled="true" <?echo ($dropts[$i][0]=='custom')?' checked=checked':''; ?>/> <a href="#" class="info">Custom Goto<span><br>ADVANCED USERS ONLY<br><br>Uses Goto() to send caller to a custom context.<br><br>The context name <b>MUST</b> contain the word "custom" and should be in the format custom-context , extension , priority. Example entry:<br><br><b>custom-myapp,s,1</b><br><br>The <b>[custom-myapp]</b> context would need to be created and included in extensions_custom.conf<b><b></span></a>: 
			<input type="text" size="20" name="custom<? echo $i ?>" value="<? echo $dropts[$i][2]; ?>" onclick="javascript:document.prompt.goto_indicate<? echo $i ?>[4].checked=true;javascript:document.prompt.goto<? echo $i ?>.value='custom';"/>
			</input><br>
	
		</td>
	</tr>
	
	<tr><td><br></td></tr>
<?
	}
?>
	</table>
	<h6>
	<input type="button" value="Continue" onClick="checkIVR(prompt,<?echo $_REQUEST['ivr_num_options']?>)"
	</h6>
	</form>
</p>
<?

	} // end else ( $_REQUEST['ivr_num_options'] == '0' )
?>













<?
	break;
	case 'ivr_options_set':
	
	//if we are editing an exisiting menu, delete the original before writing
	if ($_REQUEST['ivr_action'] == 'edit') {
		$_REQUEST['ivr_action'] = 'delete';
		$_REQUEST['ivract_target'] = $promptnum;
		$_REQUEST['map_display'] = 'no';
		include 'ivr_action.php';
	}
	
	$_REQUEST['map_display'] = 'yes';
	$_REQUEST['ivr_action'] = 'write';
	include 'ivr_action.php';
?>


<?
}
?>

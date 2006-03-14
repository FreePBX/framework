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
isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
isset($_REQUEST['menu_id'])?$menu_id = $_REQUEST['menu_id']:$menu_id='';
// individual AMP Users department prefix - has no effect if deptartment is empty
$dept = str_replace(' ','_',$_SESSION["AMP_user"]->_deptname);

$dircontext = $_SESSION["AMP_user"]->_deptname;  //we'll override this below if a directory has already been set in database
if (empty($dircontext))  
	$dircontext = 'default';						

if (empty($menu_id)) $menu_id = $dept.'aa_1';

		//do another select for all parts in this aa_.  Will return nothing if this in new aa
		$aalines = ivr_get($menu_id);
		$optioncount=0;
		
		//find relevant info in this context
		foreach ($aalines as $aaline) {
			$extension = $aaline[1];
			$application = $aaline[3];
			$args = explode(',',$aaline[4]);
			$argslen = count($args);
			if (($application == 'Macro' && $args[0] == 'exten-vm') || ($application == 'Goto' && $args[0] == 'ext-local'))  {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Macro' && $args[0] == 'vm') {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Goto' && !(strpos($args[0],$dept.'aa_') === false)) {
					$optioncount++;
					$dropts[]= $extension;
					//$menu_request[] = $args[0]; //we'll check to see if the aa_ target exists later
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-group') === false)) {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Background') {
					$description = $aaline[5];
			}
			elseif ($application == 'DigitTimeout') {
					$mname = $aaline[5];
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'custom') === false)) {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-queues') === false)) {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'timeconditions') === false)) {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-meetme') === false)) {
					$optioncount++;
					$dropts[]= $extension;
			}
			elseif ($application == 'SetVar') {  //directory context
					$dircontext = ltrim('=',strstr('=',$args));
			}
		}
		
		
		
		
		
		
switch($action) {
	default:
	// we prompt the user for the extension they are calling from
	// this reduces the possiblity of simultaneous actions of ivr recordings conflicting
?>
<h4><?php echo _("Your User/Extension")?></h4>
<form name="prompt" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return prompt_onsubmit();">
	<input type="hidden" name="action" value="ivr_start">
	<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
	<input type="hidden" name="ivr_action" value="<?php echo isset($_REQUEST['ivr_action'])?$_REQUEST['ivr_action']:''?>">
	<input type="hidden" name="display" value="ivr">
	<?php echo _("This Digital Receptionist wizard asks you to record and playback a greeting using your phone.")?><br><br>
	<?php echo _("Please enter your user/extension:")?> 
	<input type="text" size="6" name="cidnum"><br>
	<h6><input name="Submit" type="submit" value="<?php echo _("Continue")?>"></h6><br><br><br><br><br><br>
<script languague="javascript">
<!--
var theForm = document.prompt;
theForm.cidnum.focus();
function prompt_onsubmit() {
	defaultEmptyOK = false;
	if (!isInteger(theForm.cidnum.value))
		return warnInvalid(theForm.cidnum, "Please enter your user/extension number:");
		
	return true;
}
-->
</script>
</form>
	
	
	
	
	
	
	
	
	
	
<?php	
	break;
	case 'ivr_start':
?>

<h4><?php echo _("Record Menu")?>: <?php echo $mname?></h4>
<?php
	//if we are trying to edit - let's be nice and give them the recording back
	if (isset($_REQUEST['ivr_action']) && $_REQUEST['ivr_action'] == 'edit'){
		copy('/var/lib/asterisk/sounds/custom/'.$menu_id.'.wav','/var/lib/asterisk/sounds/'.$_REQUEST['cidnum'].'ivrrecording.wav');
		echo '<h5>'._("Dial *99 to listen to your current recording - click continue if you wish to re-use it.").'</h5>';
	}
?>
<h5><?php echo _("Step 1: Record")?></h5>
<p>
	<?php echo _("Using your phone,")?> <a href="#" class="info"><?php echo _("dial *77")?><span><?php echo _("Start speaking at the tone. Hangup when finished.")?></span></a> <?php echo _("and record the message you wish to greet callers with.")?>
</p>
<p>
	<form enctype="multipart/form-data" name="upload" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		<?php echo _("Alternatively, upload a recording in")?> <a href="#" class="info"><?php echo _(".wav format")?><span><?php echo _("The .wav file _must_ have a sample rate of 8000Hz")?></span></a>:<br>
		<input type="hidden" name="display" value="ivr">
		<input type="hidden" name="ivr_action" value="<?php echo isset($_REQUEST['ivr_action'])?$_REQUEST['ivr_action']:''?>">
		<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
		<input type="hidden" name="action" value="ivr_start">
		<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
		<input type="file" name="ivrfile"/>
		<input type="button" value="<?php echo _("Upload")?>" onclick="document.upload.submit(upload);alert('<?php echo _("Please wait until the page reloads.")?>');"/>
	</form>
<?php
if (isset($_FILES['ivrfile']['tmp_name']) && is_uploaded_file($_FILES['ivrfile']['tmp_name'])) {
	move_uploaded_file($_FILES['ivrfile']['tmp_name'], "/var/lib/asterisk/sounds/".$_REQUEST['cidnum']."ivrrecording.wav");
	echo "<h6>"._("Successfully uploaded")." ".$_FILES['ivrfile']['name']."</h6>";
}
?>
</p>
<form name="prompt" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return rec_onsubmit();">
<input type="hidden" name="action" value="ivr_recorded">
<input type="hidden" name="cidnum" value="<?php echo isset($_REQUEST['cidnum'])?$_REQUEST['cidnum']:'' ;?>">
<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
<input type="hidden" name="display" value="ivr">
<input type="hidden" name="ivr_action" value="<?php echo isset($_REQUEST['ivr_action'])?$_REQUEST['ivr_action']:'' ?>">
<h5><?php echo _("Step 2: Verify")?></h5>
<p>
	<?php echo _("After recording or uploading,")?> <em><?php echo _("dial *99")?></em> <?php echo _("to listen to your message.")?>
</p>
<p>
	<?php echo _("If you wish to re-record your message, dial *77 again.")?>
</p>
<h5><?php echo _("Step 3: Name & Describe")?></h5>
<table style="text-align:right;">
<tr valign="top">
	<td valign="top"><?php echo _("Name this menu:")?> </td>
	<td style="text-align:left"><input type="text" name="mname" value="<?php echo htmlspecialchars($mname) ?>"></td>
</tr>
<tr>
	<td valign="top"><?php echo _("Describe the menu:")?> </td>
	<td>&nbsp;&nbsp;<textarea name="notes" rows="3" cols="50"><?php echo htmlspecialchars($description); ?></textarea></td>
</tr>
</table>
<h6><?php echo _("Click \"Continue\" when you are satisfied with your recording")?><input name="Submit" type="submit" value="<?php echo _("Continue")?>"></h6>

<h4><?php echo _("Consider including in your recording:")?></h4>
<p>
	<li>"<?php echo _("If you know the extension you are trying to reach, dial it now.")?>"
	<li>"<?php echo _("Dial # to access the company directory.")?>"
</p>
<p>
	<?php echo _("Example:  Thank you for calling. Please press 1 for our locations, 2 for hours of operation, or 0 to speak with a representative. If you know the extension of the party you are calling, dial it now.  To access the company directory, press pound now.")?>
</p>

<script language="javascript">
<!--
var theForm = document.prompt;

function rec_onsubmit() {
	defaultEmptyOK = false;
	if (!isAlphanumeric(theForm.mname.value))
		return warnInvalid(theForm.mname, "Please enter a valid Name for this menu");
}

-->
</script>
</form>

















<?php 
	break;
	case 'ivr_recorded':
?>
<h4><?php echo _("Options for Menu:")?> <?php echo $_REQUEST['mname']; ?></h4>
<form name="prompt" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return numopts_onsubmit();">
<input type="hidden" name="action" value="ivr_options_yes_num"/>
<input type="hidden" name="notes" value="<?php echo $_REQUEST['notes'];?>">
<input type="hidden" name="mname" value="<?php echo $_REQUEST['mname']; ?>">
<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
<input type="hidden" name="ivr_action" value="<?php echo $_REQUEST['ivr_action']?>">
<input type="hidden" name="display" value="ivr">
<p><?php echo _("Callers to this Menu can press the pound key (#) to access the user directory.")?><br><br>
<?php echo _("Directory context to be used:")?> 
<select name="dir-context">
<?php
$uservm = getVoicemail();
$vmcontexts = array_keys($uservm);
echo 'ctx:'.$dircontext;
foreach ($vmcontexts as $vmcontext) {
	echo '<option value="'.$vmcontext.'" '.(strpos($dircontext,$vmcontext) === false ? '' : 'SELECTED').'>'.($vmcontext=='general' ? _("Entire Directory") : _($vmcontext));
}
?>
</select>
</p>
<p>
<?php echo _("Aside from local extensions and the pound key (#), how many other options should callers be able to dial during the playback of this menu prompt?")?><br>
<br><?php echo _("Number of options for Menu:")?> <?php echo $_REQUEST['mname']; ?><input size="2" type="text" name="ivr_num_options" value="<?php echo $optioncount ?>">
</p>
<h6><input name="Submit" type="submit" value="<?php echo _("Continue")?>"></h6>
<script languague="javascript">
<!--
var theForm = document.prompt;

function numopts_onsubmit() {
	defaultEmptyOK = false;
	if (!isInteger(theForm.ivr_num_options.value))
		return warnInvalid(theForm.ivr_num_options, "Please enter a valid Number of options");
		
	return true;
}

-->
</script>
</form>
















<?php 
	break;
	case 'ivr_options_yes_num':
	
	if (( $_REQUEST['ivr_num_options'] == '0' ) || ( $_REQUEST['ivr_num_options'] == '' )) {
?>
	<form name="prompt" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="ivr">
		<input type="hidden" name="action" value="ivr_options_set">
		<input type="hidden" name="notes" value="<?php echo $_REQUEST['notes'];?>">
		<input type="hidden" name="mname" value="<?php echo $_REQUEST['mname']; ?>">
		<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
		<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
		<input type="hidden" name="ivr_action" value="<?php echo $_REQUEST['ivr_action']?>">
		<input type="hidden" name="dir-context" value="<?php echo $_REQUEST['dir-context'];?>">
		<input name="Submit" type="submit" value="<?php echo _("Finished!  Click to save your changes.")?>">
	</form><br><br><br><br><br><br>
<?php 
	} else {
/* this stuff isn't required
		//query for exisiting aa_N contexts
		$unique_aas = ivr_list();
		
		//get unique extensions
		$extens = core_users_list();
		
		//get unique Ring Groups
		$gresults = getgroups();
*/
?>
<h4><?php echo _("Options for Menu:")." ".$_REQUEST['mname']; ?></h4>
<p>
	<?php echo _("Define the various options you expect your callers to dial after/during the playback of this recorded menu.")?>
</p>
<p>
	"<b><?php echo _("Dialed Option #")?></b>" <?php echo _("is the number you expect the caller to dial.")?><br>
	"<b><?php echo _("Action")?></b>" <?php echo _("is the result of the caller dialing the option #.  This can send the caller to an internal extension, a voicemail box, ring group, queue, or to another recorded menu.")?> 
</p>
<hr>
<p>	<form name="prompt" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkIVR(<?php echo $_REQUEST['ivr_num_options']?>);">
	<input type="hidden" name="display" value="ivr">
	<input type="hidden" name="action" value="ivr_options_set"/>
	<input type="hidden" name="notes" value="<?php echo $_REQUEST['notes'];?>">
	<input type="hidden" name="mname" value="<?php echo $_REQUEST['mname']; ?>">
	<input type="hidden" name="ivr_num_options" value="<?php echo $_REQUEST['ivr_num_options'] ?>">
	<input type="hidden" name="cidnum" value="<?php echo $_REQUEST['cidnum'];?>">
	<input type="hidden" name="menu_id" value="<?php echo $menu_id?>">
	<input type="hidden" name="ivr_action" value="<?php echo $_REQUEST['ivr_action']?>">
	<input type="hidden" name="dir-context" value="<?php echo $_REQUEST['dir-context'];?>">
	<table>
	<tr>
		<td><h4><?php echo _("Dialed Option #")?></h4></td>
		<td width="40px">&nbsp;</td>
		<td><h4><?php echo _("Action")?></h4></td>
	</tr>
<?php 
	for ($i = 0; $i < $_REQUEST['ivr_num_options']; $i++) { 
?>
	<tr>
		<td style="text-align:right;">
			<input size="2" type="text" name="ivr_option<?php echo $i ?>" value="<?php echo ($dropts[$i]=='')?$i+1:$dropts[$i] ?>">
		</td>
		<td></td>
		<td>
		
			<table>
			<?php 
			//get the failover destination at priority 1
			$sql = "SELECT args FROM extensions WHERE extension = '".$dropts[$i]."' AND priority = '1' AND context = '".$menu_id."'";
			list($goto) = $db->getRow($sql);
			//draw goto selects
			echo drawselects($goto,$i);
			//echo 'goto='.$goto;
			?>
			</table>

		</td>
	</tr>
	
	<tr><td><br></td></tr>
<?php 
	}
?>
	</table>
	<h6>
	<input type="submit" value="<?php echo _("Continue")?>">
	</h6>
<script language="javascript">
<!--
var theForm = document.prompt;

function checkIVR(ivr_num_options) {
	setDestinations(theForm,ivr_num_options);
	
	defaultEmptyOK = false;
	for (var i = 0; i < ivr_num_options; i++) {
		var theFldName = "ivr_option"+i;
		var theFldVal = theForm[theFldName].value;
		if ( (!isInteger(theFldVal) && theFldVal != "t" && theFldVal != "*") || (isWhitespace(theFldVal)) ) // && theForm[theFldName].value != "t" && theForm[theFldName].value != "*")
			return warnInvalid(theForm[theFldName], "Please enter the Option # using only digits or 't' or '*'");
	}
	
	if (!validateDestinations(theForm,ivr_num_options,true))
		return false;
	
	return true;
}
-->
</script>
	
	</form>
</p>
<?php 

	} // end else ( $_REQUEST['ivr_num_options'] == '0' )
?>













<?php 
	break;
	case 'ivr_options_set':
	
	//if we are editing an exisiting menu, delete the original before writing
	if ($_REQUEST['ivr_action'] == 'edit') {
		$_REQUEST['ivr_action'] = 'delete';
		$_REQUEST['map_display'] = 'no';
		include 'modules/ivr/ivr_action.php';
	}
	
	$_REQUEST['map_display'] = 'yes';
	$_REQUEST['ivr_action'] = 'write';
	include 'modules/ivr/ivr_action.php';
?>


<?php 
}
?>

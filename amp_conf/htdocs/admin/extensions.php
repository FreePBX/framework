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

// Note: if you run into database errors with a particular extension number, 
// build a url as follows to remove that extension (XXX) from all tables and voicemail.conf:
//             config.php?display=3&action=delete&extdisplay=XXX


//script to write sip conf file from mysql
$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';

//script to write iax conf file from mysql
$wIaxScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';

//script to write zap_additional.conf file from mysql
$wZapScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_zap_conf_from_mysql.pl';

//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

//script to write op_server.cfg file from mysql 
$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';

//script to write meetme_additional.conf file from mysql 
$wMeetScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_meetme_conf_from_mysql.pl';
	
$action = $_REQUEST['action'];
//$display=$_REQUEST['display'];
$extdisplay=$_REQUEST['extdisplay'];
$dispnum = 3; //used for switch on config.php

$account = $_REQUEST['account'];

//read in the voicemail.conf and set appropriate variables for display
$uservm = getVoicemail();
$vmcontexts = array_keys($uservm);
$vm=false;
foreach ($vmcontexts as $vmcontext) {
	if(isset($uservm[$vmcontext][$extdisplay])){
		//echo $extdisplay.' found in context '.$vmcontext.'<hr>';
		$incontext = $vmcontext;  //the context for the current extension
		$vmpwd = $uservm[$vmcontext][$extdisplay]['pwd'];
		$name = $uservm[$vmcontext][$extdisplay]['name'];
		$email = $uservm[$vmcontext][$extdisplay]['email'];
		$pager = $uservm[$vmcontext][$extdisplay]['pager'];
		//loop through all options
		if (is_array($uservm[$vmcontext][$extdisplay]['options'])) {
			$alloptions = array_keys($uservm[$vmcontext][$extdisplay]['options']);
			if (isset($alloptions)) {
				foreach ($alloptions as $option) {
					if ( ($option!="attach") && ($option!="envelope") && ($option!="saycid") && ($option!="delete") && ($option!="nextaftercmd") && ($option!='') )
						$options .= $option.'='.$uservm[$vmcontext][$extdisplay]['options'][$option].'|';
				}
				$options = rtrim($options,'|');
				// remove the = sign if there are no options set
				$options = rtrim($options,'=');
				
			}
			extract($uservm[$vmcontext][$extdisplay]['options'], EXTR_PREFIX_ALL, "vmops");
		}
		$vm=true;
	}
}

$vmcontext = $_SESSION["user"]->_deptname; //AMP Users can only add to their department's context
if (empty($vmcontext)) 
	$vmcontext = ($_REQUEST['vmcontext'] ? $_REQUEST['vmcontext'] : $incontext);
if (empty($vmcontext))
	$vmcontext = 'default';

//check if the extension is within range for this user
if (isset($account) && !checkRange($account)){
	echo "<script>javascript:alert('". _("Warning! Extension")." ".$account." "._("is not allowed for your account").".');</script>";
} else {
	
	//add extension
	if ($action == 'add') {
	
		$callerid = '"'.$_REQUEST['name'].'" '.'<'.$account.'>';
	
		// If IAX2, handle it differently 
		if ($_REQUEST['tech'] == 'iax2') {
			//add to iax table
			addiax($account,$callerid);	
		} else if ($_REQUEST['tech'] == 'sip') {
			//add to sip table
			addsip($account,$callerid);
		} else { //zap
			addzap($account,$callerid);
		}
		
	
		//write out conf files
		exec($wScript);
		exec($wIaxScript);
		exec($wZapScript);
		
		//write out op_server.cfg
		exec($wOpScript);
			
		//take care of voicemail.conf if using voicemail
		if ($_REQUEST['vm'] != 'disabled')
		{ 
			$vmoption = explode("=",$_REQUEST['attach']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['saycid']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['envelope']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['delete']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['nextaftercmd']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$uservm[$vmcontext][$account] = array(
										'mailbox' => $account, 
										'pwd' => $_REQUEST['vmpwd'],
										'name' => $_REQUEST['name'],
										'email' => $_REQUEST['email'],
										'pager' => $_REQUEST['pager'],
										'options' => $vmoptions);
			saveVoicemail($uservm);

		}
		
		//update ext-local context in extensions.conf
		$mailb = ($_REQUEST['mailbox'] == '') ? 'novm' : $_REQUEST['mailbox'];
		addaccount($account,$mailb);
		
		//write out extenstions_additional.conf
		exec($wScript1);
		
		//write out meetme_additional.conf
		exec($wMeetScript);
		
		//indicate 'need reload' link in footer.php 
		needreload();
		$account='';
		$email='';
		$pager='';
		$name='';
		
		
	} //end add
	
	//delete extension from database
	if ($action == 'delete') {
	
		//delete the extension info
		delExten($extdisplay);
		
		//write out conf files
		exec($wScript);
		exec($wIaxScript);
		exec($wZapScript);
		
		//write out op_server.cfg
		exec($wOpScript);
		
		//take care of voicemail.conf
		unset($uservm[$incontext][$extdisplay]);
		saveVoicemail($uservm);
		
		//update ext-local context in extensions.conf
		$result = delextensions('ext-local',$extdisplay);
	
		//write out new conf file
		exec($wScript1);
		
		//write out meetme_additional.conf
		exec($wMeetScript);
	
		//indicate 'need reload' link in header.php 
		needreload();
		
	} //end delete
	
	//edit database
	if ($action == 'advEdit') {
	
		$callerid = '"'.$_REQUEST['cidname'].'" '.'<'.$account.'>';
	
		//delete and re-add the account
			delExten($account);
		
			// If IAX2, handle it differently 
			if ($_REQUEST['tech'] == 'iax2') {
				//add to iax table
				addiax($account,$callerid);	
			} else if ($_REQUEST['tech'] == 'sip') {
				//add to sip table
				addsip($account,$callerid);
			} else {
				addzap($account,$callerid);
			}
		
		//write out conf files
		exec($wScript);
		exec($wIaxScript);
		exec($wZapScript);
		
		//write out op_server.cfg
		exec($wOpScript);
	
		//take care of voicemail.conf.  If vm has been disabled, then delete from voicemail.conf
		if ($_REQUEST['vm'] == 'disabled')
		{
			unset($uservm[$incontext][$account]);
		} else {
			unset($uservm[$incontext][$account]); // we remove it first because the context may have been changed

			// need to make sure that there are any options enetered in the text field
			if ($_REQUEST['options']!=''){
				$options = explode("|",$_REQUEST['options']);
				foreach($options as $option) {
					$vmoption = explode("=",$option);
					$vmoptions[$vmoption[0]] = $vmoption[1];
				}
			}
			$vmoption = explode("=",$_REQUEST['attach']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['saycid']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['envelope']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['delete']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$vmoption = explode("=",$_REQUEST['nextaftercmd']);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			$uservm[$vmcontext][$account] = array(
										'mailbox' => $account, 
										'pwd' => $_REQUEST['vmpwd'],
										'name' => $_REQUEST['name'],
										'email' => $_REQUEST['email'],
										'pager' => $_REQUEST['pager'],
										'options' => $vmoptions);
		}
		saveVoicemail($uservm);
		
		//update ext-local context in extensions.conf
		$mailb = ($_REQUEST['mailbox'] == '') ? 'novm' : $_REQUEST['mailbox'];
		$sql = "UPDATE `extensions` SET `args` = 'exten-vm,".$mailb.",".$account."' WHERE `context` = 'ext-local' AND `extension` = '".$account."' AND `priority` = '1' LIMIT 1 ;";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		exec($wScript1);
		
		//write out meetme_additional.conf
		exec($wMeetScript);
		
		//indicate 'need reload' link in header.php 
		needreload();
		// make sure that all the settings are accurately displayed based on the values passed in the last submit.
		$options=$options[0];
		if (is_array($vmoptions))
			extract($vmoptions, EXTR_PREFIX_ALL, "vmops");
		$vmpwd=$_REQUEST['vmpwd'];
		$email=$_REQUEST['email'];
		$pager=$_REQUEST['pager'];
		$name=$_REQUEST['name'];

		
	} //end edit
}

?>
</div>

<div class="rnav">
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>">Add Extension</a><br></li>
<?php 
//get unique account rows for navigation menu
$results = getextens();

if (isset($results)) {
	foreach ($results as $result) {
		echo "<li><a id=\"".($extdisplay==$result[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]}</a></li>";
	}
}
?>
</div>

<div class="content">

<?php 
switch($extdisplay) {
    default:
		
		if ($_REQUEST['action'] == 'delete') {
			echo '<br><h3>Extension '.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
		} else {
		
		//get all rows relating to selected account
		$thisExten = exteninfo($extdisplay);
		
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
	?>
	
		<h2>Extension: <?php  echo $extdisplay; ?> (
			<?php  
			foreach ($thisExten as $result) {
				if ($result[1] == 'tech') {
					echo '<span style="text-transform:uppercase;">'.$result[2].'</span>';
					$tech = $result[2];
				}
			}
			?>
		)</h2>
		<p><a href="<?php  echo $delURL ?>"><?php echo _("Delete Extension")." ".$extdisplay; ?></a></p>
		
		

		
		
		<form name="advEdit" action="<?php  $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="advEdit"/>
		<input type="hidden" name="tech" value="<?php echo $tech?>"/>
		<p>
			<table>
				<tr><td colspan=2  width=320><h5><?php echo _("Account Settings");?>:<hr></h5></td></tr>
			<?php  
			foreach ($thisExten as $result) {
				if ($result[1] != 'account' && $result[1] != 'tech') {
					if ($result[1] == '1outcid') {
						echo '<tr><td><a href="#" class="info">'._("outbound callerid:").' <span><br>'._("Overrides the caller id when dialing out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound callerid feature for this extension.").'<br><br></span></a></td>';
						echo '<td><input size="20" type="text" name="outcid" value="'.htmlentities($result[2]).'"/></td></tr>';
					} else if ($result[1] == 'callerid') {  //We don't allow user to change cid number, since the dialplan depends on it.  
						$cid = explode('"',$result[2]);
						echo '<tr><td width="135">'.$result[1].': </td><td><input size="14" type="text" name="cidname" value="'.htmlentities($cid[1]).'"/>'.htmlentities($cid[2]).'</td></tr>';
					} else {
						echo '<tr><td width="135">'.$result[1].': </td><td><input size="20" type="text" name="'.$result[1].'" value="'.htmlentities($result[2]).'"/></td></tr>';
					}
				}
			}
			?>
			<input type="hidden" name="account" value="<?php  echo $extdisplay ?>">
			<tr><td colspan=2>
				<h5><br><?php echo _("Voicemail & Directory:");?>&nbsp;&nbsp;&nbsp;&nbsp;
					<select name="vm" onchange="checkVoicemail(advEdit);">
						<option value="enabled" <?php  echo ($vm) ? 'selected' : '' ?>><?php echo _("Enabled");?></option> 
						<option value="disabled" <?php  echo ($vm) ? '' : 'selected' ?>><?php echo _("Disabled");?></option> 
					</select>
				<hr></h5>
			</td></tr>
			<tr><td colspan=2>
				<table id="voicemail" <?php  echo ($vm) ? '' : 'style="display:none;"' ?>>
				<tr>
					<td>
						<?php echo _("voicemail pwd:");?>
					</td>
					<td>
						<input size="20" type="password" name="vmpwd" value="<?php  echo $vmpwd; ?>" />
					</td>
				</tr>
				<tr>
					<td><?php echo _("full name:");?></td>
					<td><input size="20" type="text" name="name" value="<?php  echo $name; ?>"/></td>
				</tr>
				<tr>
					<td><?php echo _("email address:");?></td>
					<td><input size="20" type="text" name="email" value="<?php  echo $email; ?>"/></td>
				</tr>
				<tr>
					<td><?php echo _("pager email address:");?></td>
					<td><input size="20" type="text" name="pager" value="<?php  echo $pager; ?>"/></td>
				</tr>
				<tr>
 					<td><a href="#" class="info"><?php echo _("email attachment");?><span><?php echo _("Option to attach voicemails to email.");?></span></a>: </td>
 					<?php if ($vmops_attach == "yes"){?>
 					<td><input type="radio" name="attach" value="attach=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="attach" value="attach=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input type="radio" name="attach" value="attach=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="attach" value="attach=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 
				<tr>
 					<td><a href="#" class="info">Play CID<span>Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.</span></a>: </td>
 					<?php if ($vmops_saycid == "yes"){?>
 					<td><input type="radio" name="saycid" value="saycid=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="saycid" value="saycid=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input type="radio" name="saycid" value="saycid=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="saycid" value="saycid=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Envelope")?><span><?php echo _("Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message. This settng does not affect the operation of the envelope option in the advanced voicemail menu.")?></span></a>: </td>
 					<?php if ($vmops_envelope == "yes"){?>
 					<td><input type="radio" name="envelope" value="envelope=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="envelope" value="envelope=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input type="radio" name="envelope" value="envelope=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="envelope" value="envelope=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Next")?><span><?php echo _("If set to \"yes,\" after deleting or saving a voicemail message, the system will automatically play the next message, if no the user will have to press \"6\" to go to the next message")?></span></a>: </td>
 					<?php if ($vmops_nextaftercmd == "yes"){?>
 					<td><input type="radio" name="nextaftercmd" value="nextaftercmd=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="nextaftercmd" value="nextaftercmd=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input type="radio" name="nextaftercmd" value="nextaftercmd=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="nextaftercmd" value="nextaftercmd=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Delete Vmail")?><span><?php echo _("If set to \"yes\" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE email attachment SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER.")?>
</span></a>: </td>
 					<?php if ($vmops_delete == "yes"){?>
 					<td><input type="radio" name="delete" value="delete=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="delete" value="delete=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input type="radio" name="delete" value="delete=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="delete" value="delete=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
					<td><a href="#" class="info">vm options<span><?php echo _("Separate options with pipe ( | )")?><br><br>ie: review=yes|maxmessage=60</span></a>: </td>
					<td><input size="20" type="text" name="options" value="<?php  echo $options; ?>" /></td>
				</tr>
				<tr>
					<td><?php echo _("vm context:")?> </td>
					<td><input size="20" type="text" name="vmcontext" value="<?php  echo $vmcontext; ?>" /></td>
				</tr>
				</table>
			</td></tr>
			<tr>
				<td colspan=2>
					<br><h6><input name="Submit" type="button" value="<?php echo _("Submit Changes")?>" onclick="javascript:if(advEdit.vm.value=='enabled'&&advEdit.mailbox.value=='') advEdit.mailbox.value=advEdit.account.value+'@'+advEdit.vmcontext.value;checkForm(advEdit)"></h6>
				</td>
			</tr>
			</table>
		</p>
		</form>
<?php
		} //end if action=delete

    break;
    case '':
?>

    <form name="addNew" action="<?php  $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
        <input type="hidden" name="action" value="add">
        <h2><?php echo _("Add an Extension")?></h2>
        <p>
            <table>
			<tr><td colspan=2><h5><br><?php echo _("Account Settings")?>:<hr></h5></td></tr>
			<tr>
				<td width="135">
					<a href="#" class="info"><?php echo _("phone protocol")?><span><?php echo _("The technology your phone supports")?></span></a>: 
				</td>
				<td>&nbsp;
					<select name="tech" onchange="hideExtenFields(addNew)">
						<option value="sip">SIP</option> 
						<option value="iax2">IAX2</option>
						<option value="zap">ZAP</option>
					</select>
					<select name="dtmfmode" id="dtmfmode">
						<option value="rfc2833">rfc2833</option> 
						<option value="inband">inband</option> 
						<option value="info">info</option>  
					</select>
				</td>
			</tr>
            <tr>
                <td>
                    <a href="#" class="info"><?php echo _("extension number")?><span><?php echo _("This is the phone number for the new extension.<br><b>It must be unique.</b><br><br>This extension's USERNAME and MAILBOX are also the same as the extension number.")?><br></span></a>: 
                </td><td>
                    <input tabindex="1" size="5" type="text" name="account" value="<?php  echo ($result[0] == '' ? '200' : ($result[0] + 1))?>"/>
                </td>
            </tr>
			</table>
			<table id="secret" style="display:inline">
            <tr>
                <td width="135">
                    <a href="#" class="info"><?php echo _("extension password")?><span><?php echo _("The client (phone) uses this password to access the system.<br>This password can contain numbers and letters.<br>Ignored on Zap channels.")?><br></span></a>:
                </td><td>
                    <input tabindex="2" size="10" type="text" name="secret" value=""/>
                </td>
            </tr>
			</table>
			<table id="channel" style="display:none">
			<tr>
				<td width="135">
					<a href="#" class="info"><?php echo _("zap channel")?><span><?php echo _("The zap channel this extension is connected.<br>Ignored on SIP or IAX channels.")?><br></span></a>:
				</td><td>
					<input tabindex="2" size="4" type="text" name="channel" value=""/>
				</td>
			</tr>
			</table>
			<table>
            <tr>
                <td  width="135"><a href="#" class="info"><?php echo _("full name")?><span><?php echo _("User's full name. This is used for the Caller ID Name and for the Company Directory (if enabled below).")?></span></a>: </td>
                <td><input tabindex="3" type="text" name="name" value="<?php  echo $name; ?>"/></td>
            </tr>
			<tr><td colspan=2>
				<h5><br><br><?php echo _("Voicemail & Directory:")?>&nbsp;&nbsp;&nbsp;&nbsp;
					<select name="vm" onchange="checkVoicemail(addNew);">
						<option value="enabled"><?php echo _("Enabled");?></option> 
						<option value="disabled"><?php echo _("Disabled");?></option> 
					</select>
				<hr></h5>
			</td></tr>
			<tr><td colspan=2>
				<table id="voicemail">
				<tr>
					<td>
						<a href="#" class="info"><?php echo _("voicemail password")?><span><?php echo _("This is the password used to access the voicemail system.<br><br>This password can only contain numbers.<br><br>A user can change the password you enter here after logging into the voicemail system (*98) with a phone.")?><br><br></span></a>: 
					</td><td>
						<input tabindex="4" size="10" type="text" name="vmpwd" value=""/>
					</td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("email address")?><span><?php echo _("The email address that voicemails are sent to.")?></span></a>: </td>
					<td><input tabindex="5" type="text" name="email" value="<?php  echo $email; ?>"/></td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("pager email address")?><span><?echo _("Pager/mobile email address that short voicemail notifcations are sent to.")?></span></a>: </td>
					<td><input tabindex="6" type="text" name="pager" value="<?php  echo $pager; ?>"/></td>
				</tr>
				<tr>
 					<td><a href="#" class="info"><?php echo _("email attachment")?><span><?php echo _("Option to attach voicemails to email.")?></span></a>: </td>
 					<?php if ($vmops_attach == "yes"){?>
 					<td><input  tabindex="7" type="radio" name="attach" value="attach=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="8" type="radio" name="attach" value="attach=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  tabindex="7" type="radio" name="attach" value="attach=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="8" type="radio" name="attach" value="attach=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 
				<tr>
 					<td><a href="#" class="info"><?php echo _("Play CID")?><span><?php echo _("Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.")?></span></a>: </td>
 					<?php if ($vmops_saycid == "yes"){?>
 					<td><input  tabindex="9" type="radio" name="saycid" value="saycid=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="10" type="radio" name="saycid" value="saycid=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  tabindex="9" type="radio" name="saycid" value="saycid=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="10" type="radio" name="saycid" value="saycid=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Envelope")?><span><?php echo _("Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message. This settng does not affect the operation of the envelope option in the advanced voicemail menu.")?></span></a>: </td>
 					<?php if ($vmops_envelope == "yes"){?>
 					<td><input  tabindex="11" type="radio" name="envelope" value="envelope=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="12" type="radio" name="envelope" value="envelope=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  tabindex="11" type="radio" name="envelope" value="envelope=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="12" type="radio" name="envelope" value="envelope=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Next")?><span><?php echo _("If set to \"yes,\" after deleting or saving a voicemail message, the system will automatically play the next message, if no the user will have to press \"6\" to go to the next message")?></span></a>: </td>
 					<?php if ($vmops_nextaftercmd == "yes"){?>
 					<td><input  tabindex="13" type="radio" name="nextaftercmd" value="nextaftercmd=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="14" type="radio" name="nextaftercmd" value="nextaftercmd=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  tabindex="13" type="radio" name="nextaftercmd" value="nextaftercmd=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="14" type="radio" name="nextaftercmd" value="nextaftercmd=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Delete Vmail")?><span><?php echo _("If set to \"yes\" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER.")?>
</span></a>: </td>
 					<?php if ($vmops_delete == "yes"){?>
 					<td><input  tabindex="15" type="radio" name="delete" value="delete=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  tabindex="16" type="radio" name="delete" value="delete=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  tabindex="15" type="radio" name="delete" value="delete=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input tabindex="16" type="radio" name="delete" value="delete=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				</table>
			</td></tr>

			<input type="hidden" name="canreinvite" value="no"/>
			<input type="hidden" name="context" value="from-internal"/>
			<input type="hidden" name="host" value="dynamic"/>
			<input type="hidden" name="type" value="friend"/>
			<input type="hidden" name="nat" value="never"/>
			<input type="hidden" name="port" value="5060"/>
			<input type="hidden" name="mailbox" value=""/>
			<input type="hidden" name="username" value=""/>
			<input type="hidden" name="iaxport" value="4569"/>
			<input type="hidden" name="notransfer" value="yes"/>
			<input type="hidden" name="qualify" value="no"/>
			<input type="hidden" name="callgroup" value=""/>
			<input type="hidden" name="pickupgroup" value=""/>
			<input type="hidden" name="disallow" value=""/>
			<input type="hidden" name="allow" value=""/>
			<input type="hidden" name="vmcontext" value="<?php echo $vmcontext ?>"/>

            <tr>
                <td colspan=2>
                    <br><br><h6><input name="Submit" type="button" value="<?php echo _("Add Extension")?>" onclick="javascript:if(addNew.vm.value=='enabled') addNew.mailbox.value=addNew.account.value+'@'+addNew.vmcontext.value;checkForm(addNew)"></h6>
                </td>
            </tr>
            </table>
        </p>
    </form>

<?php
	break;
}
?>

<?php  //Make sure the bottom border is low enuf
if (isset($resuults)) {
	foreach ($results as $result) {
		echo "<br><br>";
	}
}
?>





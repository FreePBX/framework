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

//add extension
if ($action == 'add') {

    $account = $_REQUEST['account'];
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
    	include 'vm_conf.php';
	}
	
	//update ext-local context in extensions.conf
	addaccount($account);
    
    //write out extenstions_additional.conf
	exec($wScript1);
	
	//write out meetme_additional.conf
	exec($wMeetScript);
	
	//indicate 'need reload' link in footer.php 
	needreload();
	
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
    include 'vm_conf.php';
	
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

    $account = $_REQUEST['account'];
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
	
	
/*	// If IAX2, handle it differently 
	if ($_REQUEST['tech'] == 'iax2') {
		//edit iax table
		editIax($account,$callerid);
	} else {
		//ok, it's SIP
		editSip($account,$callerid);
	}
*/	
    //write out conf files
    exec($wScript);
    exec($wIaxScript);
	exec($wZapScript);
	
	//write out op_server.cfg
	exec($wOpScript);

    //take care of voicemail.conf.  If vm has been disabled, then delete from voicemail.conf
	if ($_REQUEST['vm'] == 'disabled')
	{
		$action = 'delete';
		include 'vm_conf.php';
		$action = 'advEdit';
	} else {
		include 'vm_conf.php';
	}
	
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
	
} //end edit

?>
</div>

<div class="rnav">
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>">Add Extension</a><br></li>
<?php 
//get unique account rows for navigation menu
$results = getextens();

foreach ($results as $result) {
    echo "<li><a id=\"".($extdisplay==$result[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]}</a></li>";
}
?>
</div>

<div class="content">

<?php 
switch($extdisplay) {
    default:
		
		if ($_REQUEST['action'] == 'delete') {
			echo '<br><h3>Extension '.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br><br><br><br><br>';
		} else {
		
		include 'vm_read.php'; //read vm config into uservm[][]
		
		
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
		<p><a href="<?php  echo $delURL ?>">Delete Extension <?php  echo $extdisplay; ?></a></p>
		
		

		
		
		<form name="advEdit" action="<?php  $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="advEdit"/>
		<input type="hidden" name="tech" value="<?php echo $tech?>"/>
		<p>
			<table>
				<tr><td colspan=2  width=320><h5>Account Settings:<hr></h5></td></tr>
			<?php  
			foreach ($thisExten as $result) {
				if ($result[1] != 'account' && $result[1] != 'tech') {
					if ($result[1] == '1outcid') {
						echo '<tr><td><a href="#" class="info">outbound callerid: <span><br>Overrides the caller id when dialling out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>"caller name" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound callerid feature for this extension.<br><br></span></a></td>';
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
				<h5><br>Voicemail & Directory:&nbsp;&nbsp;&nbsp;&nbsp;
					<select name="vm" onchange="checkVoicemail(advEdit);">
						<option value="enabled">Enabled</option> 
						<option value="disabled" <?php  echo ($vm) ? '' : 'selected' ?>>Disabled</option> 
					</select>
				<hr></h5>
			</td></tr>
			<tr><td colspan=2>
				<table id="voicemail" <?php  echo ($vm) ? '' : 'style="display:none;"' ?>>
				<tr>
					<td>
						voicemail pwd:
					</td>
					<td>
						<input size="20" type="password" name="vmpwd" value="<?php  echo $vmpwd; ?>" />
					</td>
				</tr>
				<tr>
					<td>full name:</td>
					<td><input size="20" type="text" name="name" value="<?php  echo $name; ?>"/></td>
				</tr>
				<tr>
					<td>email address:</td>
					<td><input size="20" type="text" name="email" value="<?php  echo $email; ?>"/></td>
				</tr>
				<tr>
					<td>pager email address:</td>
					<td><input size="20" type="text" name="pager" value="<?php  echo $pager; ?>"/></td>
				</tr>
				<tr>
					<td>vm options: </td>
					<td><input size="20" type="text" name="options" value="<?php  echo $options; ?>" /></td>
				</tr>
				</table>
			</td></tr>
			<tr>
				<td colspan=2>
					<br><h6><input name="Submit" type="button" value="Submit Changes" onclick="checkForm(advEdit)"></h6>
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
        <h2>Add an Extension</h2>
        <p>
            <table>
			<tr><td colspan=2><h5><br>Account Settings:<hr></h5></td></tr>
			<tr>
				<td width="135">
					<a href="#" class="info">phone protocol<span>The technology your phone supports</span></a>: 
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
                    <a href="#" class="info">extension number<span>This is the phone number for the new extension.<br><b>It must be unique.</b><br><br>This extension's USERNAME and MAILBOX are also the same as the extension number.<br></span></a>: 
                </td><td>
                    <input tabindex="1" size="5" type="text" name="account" value="<?php  echo ($result[0] == '' ? '200' : ($result[0] + 1))?>"/>
                </td>
            </tr>
			</table>
			<table id="secret" style="display:inline">
            <tr>
                <td width="135">
                    <a href="#" class="info">extension password<span>The client (phone) uses this password to access the system.<br>This password can contain numbers and letters.<br>Ignored on Zap channels.<br></span></a>:
                </td><td>
                    <input tabindex="2" size="10" type="text" name="secret" value=""/>
                </td>
            </tr>
			</table>
			<table id="channel" style="display:none">
			<tr>
				<td width="135">
					<a href="#" class="info">zap channel<span>The zap channel this extension is connected.<br>Ignored on SIP or IAX channels.<br></span></a>:
				</td><td>
					<input tabindex="2" size="4" type="text" name="channel" value=""/>
				</td>
			</tr>
			</table>
			<table>
            <tr>
                <td  width="135"><a href="#" class="info">full name<span>User's full name. This is used for the Caller ID Name and for the Company Directory (if enabled below).</span></a>: </td>
                <td><input tabindex="4" type="text" name="name" value="<?php  echo $name; ?>"/></td>
            </tr>
			<tr><td colspan=2>
				<h5><br><br>Voicemail & Directory:&nbsp;&nbsp;&nbsp;&nbsp;
					<select name="vm" onchange="checkVoicemail(addNew);">
						<option value="enabled">Enabled</option> 
						<option value="disabled">Disabled</option> 
					</select>
				<hr></h5>
			</td></tr>
			<tr><td colspan=2>
				<table id="voicemail">
				<tr>
					<td>
						<a href="#" class="info">voicemail password<span>This is the password used to access the voicemail system.<br><br>This password can only contain numbers.<br><br>A user can change the password you enter here after logging into the voicemail system (*98) with a phone.<br><br></span></a>: 
					</td><td>
						<input tabindex="3" size="10" type="text" name="vmpwd" value=""/>
					</td>
				</tr>
				<tr>
					<td><a href="#" class="info">email address<span>The email address that voicemails are sent to.</span></a>: </td>
					<td><input tabindex="5" type="text" name="email" value="<?php  echo $email; ?>"/></td>
				</tr>
				<tr>
					<td><a href="#" class="info">email attachment<span>Option to attach voicemails to email.</span></a>: </td>
					<td><input tabindex="7" type="radio" name="options" value="attach=yes" checked=checked/> yes &nbsp;&nbsp;&nbsp;&nbsp;<input tabindex="8" type="radio" name="options" value="attach=no"/> no</td>
				</tr>
				<tr>
					<td><a href="#" class="info">pager email address<span>Pager/mobile email address that short voicemail notifcations are sent to.</span></a>: </td>
					<td><input tabindex="6" type="text" name="pager" value="<?php  echo $pager; ?>"/></td>
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

            <tr>
                <td colspan=2>
                    <br><br><h6><input name="Submit" type="button" value="Add Extension" onclick="checkForm(addNew)"></h6>
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
foreach ($results as $result) {
    echo "<br><br>";
}
?>





<?php
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

//script to write sip conf file from mysql
$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';

//script to write iax conf file from mysql
$wIaxScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';

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

	// the dtmfmode request variable can now be IAX2.  If IAX2, handle it differently 
	if ($_REQUEST['dtmfmode'] == 'iax2') {
		//add to iax table
		addiax($account,$callerid);	
	} else {  #ok, it's SIP
	    //add to sip table
	    addsip($account,$callerid);
	}
	

    //write out conf files
    exec($wScript);
	exec($wIaxScript);
	
	//write out op_server.cfg
	exec($wOpScript);
	    
    //take care of voicemail.conf
    include 'vm_conf.php';
	
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
if ($action == 'bscEdit' || $action == 'advEdit') {

    $account = $_REQUEST['account'];
	
	if ($_REQUEST['callerid'] == '') {
		$callerid = '"'.$_REQUEST['name'].'" '.'<'.$account.'>';
	} else {
		$callerid = stripslashes($_REQUEST['callerid']);
	}

	// the dtmfmode request variable can now be IAX2.  If IAX2, handle it differently 
	if ($_REQUEST['dtmfmode'] == 'iax2') {
		//edit iax table
		editIax($account,$callerid);
	} else {
		//ok, it's SIP
		editSip($account,$callerid);
	}
	
    //write out conf files
    exec($wScript);
    exec($wIaxScript);
	
	//write out op_server.cfg
	exec($wOpScript);
	
    //take care of voicemail.conf
    include 'vm_conf.php';
	
	//update ext-local context in extensions.conf
	$sql = "UPDATE `extensions` SET `args` = 'exten-vm,".$_REQUEST['mailbox'].",".$account."' WHERE `context` = 'ext-local' AND `extension` = '".$account."' AND `priority` = '1' LIMIT 1 ;";
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
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $dispnum?>">Add Extension</a><br></li>
<?
//get unique account rows for navigation menu
$results = getextens();

foreach ($results as $result) {
    echo "<li><a id=\"".($extdisplay==$result[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]}</a></li>";
}
?>
</div>

<div class="content">

<?
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
	
		<h2>Extension: <? echo $extdisplay; ?></h2>
		<p><a href="<? echo $delURL ?>">Delete Extension <? echo $extdisplay; ?></a></p>
		
		
		<form name="bscEdit" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?echo $dispnum?>">
		<input type="hidden" name="action" value="bscEdit"/>
		<h4>Basic Edit:</h4>
		<p>
				<?
				foreach ($thisExten as $result) {
					${$result[1]} = $result[2];
				}
				?>
			<input tabindex="1" type="hidden" name="account" value="<? echo $account; ?>"/>
			<table>
			<tr>
				<td>extension password:</td>
				<td><input tabindex="2" size="10" type="text" name="secret" value="<? echo $secret; ?>"/></td>
			</tr>
			<tr>
				<td>voicemail password:</td>
				<td><input tabindex="3" size="10" type="password" name="vmpwd" value="<? echo $vmpwd; ?>"/></td>
			</tr>
			<tr>
				<td>full name:</td>
				<td><input tabindex="4" type="text" name="name" value="<? echo $name; ?>"/></td>
			</tr>
			<tr>
				<td>email address:</td>
				<td><input tabindex="5" type="text" name="email" value="<? echo $email; ?>"/></td>
			</tr>
			<tr>
				<td>pager email address:</td>
				<td><input tabindex="6" type="text" name="pager" value="<? echo $pager; ?>"/></td>
			</tr>
			<tr>
				<td>vm attachment: </td>
				<td><input tabindex="7" type="radio" name="options" value="attach=yes" <? echo (ereg('attach=yes',$options) ? 'checked=checked' : '')?>/> yes</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input  tabindex="8" type="radio" name="options" value="attach=no" <? echo (ereg('attach=no',$options) ? 'checked=checked' : '')?>/> no</td>
			</tr>
			<tr>
				<td>phone type: </td>
				<td>&nbsp;
					<select  name="dtmfmode" style="font-size:x-small">
						<option value="rfc2833" <? echo (ereg('rfc2833',$dtmfmode) ? 'selected=selected' : '')?>>SIP-rfc2833 (ie: UIP200)</option> 
						<option value="inband" <? echo (ereg('inband',$dtmfmode) ? 'selected=selected' : '')?>>SIP-inband (ie: HandyTone</option> 
						<option value="info" <? echo (ereg('info',$dtmfmode) ? 'selected=selected' : '')?>>SIP-info (ie: BudgeTone)</option> 
						<option value="iax2" <? echo (ereg('iax2',$dtmfmode) ? 'selected=selected' : '')?>>IAX2</option> 
					</select>
				</td>
			</tr>
			<input type="hidden" name="canreinvite" value="<? echo $canreinvite; ?>"/>
			<input type="hidden" name="context" value="<? echo $context; ?>"/>
			<input type="hidden" name="host" value="<? echo $host; ?>"/>
			<input type="hidden" name="type" value="<? echo $type; ?>"/>
			<input type="hidden" name="mailbox" value="<? echo $mailbox; ?>"/>
			<input type="hidden" name="username" value="<? echo $username; ?>"/>
			<input type="hidden" name="nat" value="<? echo ($nat==null) ? 'never' : $nat; ?>"/>
			<input type="hidden" name="port" value="<? echo ($port==null) ? '5060' : $port; ?>"/>
			<input type="hidden" name="iaxport" value="<? echo ($iaxport==null) ? '4569' : $iaxport; ?>"/>
			<input type="hidden" name="notransfer" value="<? echo ($notransfer==null) ? 'yes' : $notransfer; ?>"/>
			<input type="hidden" name="qualify" value="<? echo ($qualify==null) ? 'no' : $qualify; ?>"/>
			<input type="hidden" name="callerid" value="<? echo ($callerid==null) ? ' ' : htmlentities($callerid); ?>"/>
			<tr>
				<td>
					&nbsp;
				</td>
				<td>
					<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkForm(bscEdit)"></h6>
				</td>
			</tr>
			</table>
		</p>
		</form>
		
		
		<form name="advEdit" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?echo $dispnum?>">
		<input type="hidden" name="action" value="advEdit"/>
		<p>
			<table bgcolor=#EEEEEE>
				<tr><td colspan=2><h4>Advanced Edit:<br><br></h4></td></tr>
				<tr><td colspan=2><h5>Account Settings:<hr></h5></td></tr>
			<? 
			foreach ($thisExten as $result) {
				if ($result[1] != 'account') {
					echo '<tr><td>'.$result[1].': </td><td><input size="20" type="text" name="'.$result[1].'" value="'.htmlentities($result[2]).'"/></td></tr>';
				}
			}
			?>
			<input type="hidden" name="account" value="<? echo $account ?>">
			<tr><td colspan=2><h5><br>Voicemail & Directory Settings:<hr></h5></td></tr>
			<tr>
				<td>
					voicemail pwd:
				</td>
				<td>
					<input size="20" type="password" name="vmpwd" value="<? echo $vmpwd; ?>" />
				</td>
			</tr>
			<tr>
				<td>full name:</td>
				<td><input size="20" type="text" name="name" value="<? echo $name; ?>"/></td>
			</tr>
			<tr>
				<td>email address:</td>
				<td><input size="20" type="text" name="email" value="<? echo $email; ?>"/></td>
			</tr>
			<tr>
				<td>pager email address:</td>
				<td><input size="20" type="text" name="pager" value="<? echo $pager; ?>"/></td>
			</tr>
			<tr>
				<td>vm options: </td>
				<td><input size="20" type="text" name="options" value="<? echo $options; ?>" /></td>
			</tr>
			
			<tr>
				<td colspan=2>
					<br><h6><input name="Submit" type="button" value="Submit Advanced Edit" onclick="checkForm(advEdit)"></h6>
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

    <form name="addNew" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?echo $dispnum?>">
        <input type="hidden" name="action" value="add">
        <h2>Add an Extension</h2>
        <p>
            <table>
            <tr>
                <td>
                    <a href="#" class="info">extension number<span>This is the phone number for the new extension.<br><b>It must be unique.</b><br><br>This extension's USERNAME and MAILBOX are also the same as the extension number.<br></span></a>: 
                </td><td>
                    <input tabindex="1" size="5" type="text" name="account" value="<? echo ($result[0] == '' ? '200' : ($result[0] + 1))?>"/>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="#" class="info">extension password<span>The client (phone) uses this password to access the system.<br>This password can contain numbers and letters.<br></span></a>:
                </td><td>
                    <input tabindex="2" size="10" type="text" name="secret" value=""/>
                </td>
            </td>
            <tr>
                <td>
                    <a href="#" class="info">voicemail password<span>This is the password used to access the voicemail system.<br><br>This password can only contain numbers.<br><br>A user can change the password you enter here after logging into the voicemail system (*98) with a phone.<br><br>Note: If you leave this field blank, a voicemail account will NOT be created for this extension.<br></span></a>: 
                </td><td>
                    <input tabindex="3" size="10" type="text" name="vmpwd" value=""/>
                </td>
            </tr>
            <tr>
                <td><a href="#" class="info">full name<span>User's full name. This is used in the Company Directory.</span></a>: </td>
                <td><input tabindex="4" type="text" name="name" value="<? echo $name; ?>"/></td>
            </tr>
            <tr>
                <td><a href="#" class="info">email address<span>The email address that voicemails are sent to.</span></a>: </td>
                <td><input tabindex="5" type="text" name="email" value="<? echo $email; ?>"/></td>
            </tr>
            <tr>
                <td><a href="#" class="info">pager email address<span>Pager/mobile email address that short voicemail notications are sent to.</span></a>: </td>
                <td><input tabindex="6" type="text" name="pager" value="<? echo $pager; ?>"/></td>
            </tr>
            <tr>
                <td><a href="#" class="info">Attachment<span>Option to attach voicemails to email.</span></a>: </td>
                <td><input tabindex="7" type="radio" name="options" value="attach=yes" checked=checked/> yes</td>
            </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input tabindex="8" type="radio" name="options" value="attach=no"/> no</td>
        </tr>
		<tr>
			<td>phone type: </td>
			<td>&nbsp;
				<select name="dtmfmode" style="font-size:x-small">
					<option value="rfc2833">SIP-rfc2833 (ie: UIP200)</option> 
					<option value="inband">SIP-inband (ie: HandyTone)</option> 
					<option value="info">SIP-info (ie: BudgeTone)</option> 
					<option value="iax2">IAX2</option> 
				</select>
			</td>
		</tr>
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
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>
                    <h6><input name="Submit" type="button" value="Add Extension" onclick="checkForm(addNew)"></h6>
                </td>
            </tr>
            </table>
        </p>
    </form>

<?php
	break;
}
?>

<? //Make sure the bottom border is low enuf
foreach ($results as $result) {
    echo "<br><br>";
}
?>





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

    $sipfields = array(array($account,'account',$account),
                    array($account,'secret',$_REQUEST['secret']),
                    array($account,'canreinvite',$_REQUEST['canreinvite']),
                    array($account,'context',$_REQUEST['context']),
                    array($account,'dtmfmode',$_REQUEST['dtmfmode']),
                    array($account,'host',$_REQUEST['host']),
                    array($account,'type',$_REQUEST['type']),
                    array($account,'mailbox',$_REQUEST['mailbox']),
                    array($account,'username',$_REQUEST['username']),
			array($account,'nat',$_REQUEST['nat']),
			array($account,'port',$_REQUEST['port']),
			array($account,'callerid',$callerid));

    $compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
    $result = $db->executeMultiple($compiled,$sipfields);

    if(DB::IsError($result)) {
        die($result->getMessage()."<br><br>".$sql);
    }
    //write out conf file
    exec($wScript);

	//write out op_server.cfg
	exec($wOpScript);
	
	//write out meetme_additional.conf
	exec($wMeetScript);
    
    //take care of voicemail.conf
    include 'vm_conf.php';
	
	//update ext-local context in extensions.conf
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('ext-local', '".$account."', '1', 'Macro', 'exten-vm,".$account.",".$account."', NULL , '0')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage());
    }
	exec($wScript1);
	
	//indicate 'need reload' link in header.php 
	needreload();
	
} //end add

//delete extension from database
if ($action == 'delete') {

    $sql = "DELETE FROM sip WHERE id = '$extdisplay'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
    //write out conf file
    exec($wScript);
	
	//write out op_server.cfg
	exec($wOpScript);

	//write out meetme_additional.conf
	exec($wMeetScript);
	
    //take care of voicemail.conf
    include 'vm_conf.php';
	
	//update ext-local context in extensions.conf
	$result = delextensions('ext-local',$extdisplay);

	//write out new conf file
	exec($wScript1);

	//indicate 'need reload' link in header.php 
	needreload();
	
} //end delete

//edit database
if ($action == 'bscEdit' || $action == 'advEdit') {

    $account = $_REQUEST['account'];
	$callerid = '"'.$_REQUEST['name'].'" '.'<'.$account.'>';

    $sipfields = array(array($account,$account,'account'),
                    array($_REQUEST['secret'],$account,'secret'),
                    array($_REQUEST['canreinvite'],$account,'canreinvite'),
                    array($_REQUEST['context'],$account,'context'),
                    array($_REQUEST['dtmfmode'],$account,'dtmfmode'),
                    array($_REQUEST['host'],$account,'host'),
                    array($_REQUEST['type'],$account,'type'),
                    array($_REQUEST['mailbox'],$account,'mailbox'),
                    array($_REQUEST['username'],$account,'username'),
					array($_REQUEST['nat'],$account,'nat'),
					array($_REQUEST['port'],$account,'port'),
					array($callerid,$account,'callerid'));

    $compiled = $db->prepare('UPDATE sip SET data = ? WHERE id = ? AND keyword = ? LIMIT 1');
    $result = $db->executeMultiple($compiled,$sipfields);
    if(DB::IsError($result)) {
        die($result->getMessage());
    }
    //write out conf file
    exec($wScript);
	
	//write out op_server.cfg
	exec($wOpScript);

	//write out meetme_additional.conf
	exec($wMeetScript);
	
    //take care of voicemail.conf
    include 'vm_conf.php';
	
	//update ext-local context in extensions.conf
	$sql = "UPDATE `extensions` SET `args` = 'exten-vm,".$_REQUEST['mailbox'].",".$account."' WHERE `context` = 'ext-local' AND `extension` = '".$account."' AND `priority` = '1' LIMIT 1 ;";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        die($result->getMessage());
    }
	exec($wScript1);
	
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
		$sql = "SELECT * FROM sip WHERE id = '$extdisplay'";
		$thisExten = $db->getAll($sql);
		if(DB::IsError($thisExten)) {
		   die($thisExten->getMessage());
		}
		
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
	?>
	
		<h2>Extension: <? echo $extdisplay; ?></h2>
		<p><a href="<? echo $delURL ?>">Delete Extension <? echo $extdisplay; ?></a></p>
		
		
		<form name="bscEdit" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?echo $dispnum?>">
		<input type="hidden" name="action" value="bscEdit"/>
		<h4>Basic Edit:</h4>
		<p>
			<table>
			<tr>
				<td>extension number:</td>
				<?
				foreach ($thisExten as $result) {
					${$result[1]} = $result[2];
				}
				?>
				<td><input tabindex="1" size="10" type="text" name="account" value="<? echo $account; ?>"/></td>
			</tr>
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
				<td>phone dtmf mode: </td>
				<td>&nbsp;
					<select  name="dtmfmode" style="font-size:x-small">
						<option value="rfc2833" <? echo (ereg('rfc2833',$dtmfmode) ? 'selected=selected' : '')?>>rfc2833 (ie: UIP200)</option> 
						<option value="inband" <? echo (ereg('inband',$dtmfmode) ? 'selected=selected' : '')?>>inband (ie: HandyTone</option> 
						<option value="info" <? echo (ereg('info',$dtmfmode) ? 'selected=selected' : '')?>>info (ie: BudgeTone)</option> 
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
		<h4>Advanced Edit:<br></h4>
		<p>
			<table>
			<? 
			foreach ($thisExten as $result) {
				if ($result[1] != 'callerid') {
					echo '<tr><td>'.$result[1].': </td><td><input size="10" type="text" name="'.$result[1].'" value="'.$result[2].'"/></td></tr>';
				}
			}
			?>
			<tr>
				<td>
					voicemail pwd:
				</td>
				<td>
					<input size="10" type="password" name="vmpwd" value="<? echo $vmpwd; ?>" />
				</td>
			</tr>
			<tr>
				<td>full name:</td>
				<td><input type="text" name="name" value="<? echo $name; ?>"/></td>
			</tr>
			<tr>
				<td>email address:</td>
				<td><input type="text" name="email" value="<? echo $email; ?>"/></td>
			</tr>
			<tr>
				<td>pager email address:</td>
				<td><input type="text" name="pager" value="<? echo $pager; ?>"/></td>
			</tr>
			<tr>
				<td>options: </td>
				<td><input type="text" name="options" value="<? echo $options; ?>" /></td>
			</tr>
			
			<tr>
				<td>
					&nbsp;
				</td>
				<td>
					<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkForm(advEdit)"></h6>
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
                    <a href="#" class="info">extension number<span>This is the phone number for the new extension. <br>The client (phone) also uses this as the username to access the system. <br>It must be unique. </span></a>: 
                </td><td>
                    <input tabindex="1" size="5" type="text" name="account" value="<? echo ($result[0] == '' ? '200' : ($result[0] + 1))?>"/>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="#" class="info">extension password<span>The client (phone) uses this password to access the system.<br>This password can contain numbers and letters.<br>The extension's USERNAME and MAILBOX are also the same as the extension.</span></a>:
                </td><td>
                    <input tabindex="2" size="10" type="text" name="secret" value=""/>
                </td>
            </td>
            <tr>
                <td>
                    <a href="#" class="info">voicemail password<span>This is the password used to access the voicemail system.<br>This password can only contain numbers.<br>A user can change the password you enter here after logging into the voicemail system (*98) with a phone.</span></a>: 
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
			<td>phone dtmfmode: </td>
			<td>&nbsp;
				<select name="dtmfmode" style="font-size:x-small">
					<option value="rfc2833">rfc2833 (ie: UIP200)</option> 
					<option value="inband">inband (ie: HandyTone)</option> 
					<option value="info">info (ie: BudgeTone)</option> 
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
    echo "<br>";
}
?>





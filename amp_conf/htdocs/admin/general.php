<?
// Asterisk Management Portal (AMP)
// Copyright (C) 2004 Coalescent Systems Inc

//script to write conf file from mysql
$wScript = rtrim($_SERVER['PATH_TRANSLATED'],$currentFile).'retrieve_extensions_from_mysql.pl';


$action = $_REQUEST['action'];


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(array($_REQUEST['DIAL_OUT'],'DIAL_OUT'),
						array($_REQUEST['RINGTIMER'],'RINGTIMER'),
						array($_REQUEST['FAX_RX'],'FAX_RX'),
						array($_REQUEST['FAX_RX_EMAIL'],'FAX_RX_EMAIL'),
						array($_REQUEST['DIRECTORY'],'DIRECTORY'));

	$compiled = $db->prepare('UPDATE globals SET value = ? WHERE variable = ?');
	$result = $db->executeMultiple($compiled,$globalfields);
	if(DB::IsError($result)) {
		echo $action.'<br>';
		die($result->getMessage());
	}
	//write out conf file
	exec($wScript);
	
	//indicate 'need reload' link in header.php 
	needreload();
	
}
	
//get all rows relating to selected account
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${$global[0]} = $global[1];	
}

//get unique extensions
$extens = getextens();

?>

<form name="general" action="config.php" method="post">
<input type="hidden" name="display" value="5"/>
<input type="hidden" name="action" value="editglobals"/>

<h5>Dialing Options</h5>
<p>
	Dial this number to use an outside line (default is 9): 
	<input type="text" size="1" name="DIAL_OUT" value="<? echo $DIAL_OUT ?>"/>
</p>
<p>
	Number of seconds to ring phones before sending callers to voicemail: 
	<input type="text" size="2" name="RINGTIMER" value="<? echo $RINGTIMER?>"/>
</p>

<h5>Company Directory</h5>
<p>
	Find users in the <a href=# class="info">Company Directory<span><br>
	Callers who are greeted by a Digital Receptionist can dial pound (#) to access the Company Directory.<br><br>
	Internal extensions can dial *411 to access the Company Directory.</span></a> by: 
	<select name="DIRECTORY">
		<option value="first" <? echo ($DIRECTORY == 'first' ? 'SELECTED' : '')?>>first name
		<option value="last" <? echo ($DIRECTORY == 'last' ? 'SELECTED' : '')?>>last name
	</select> 
</p>

<h5>Fax Machine</h5>
<p>
	Extension of <a class="info" href="#">fax machine<span>Select 'system' to have the system receive and email faxes.<br>Selecting 'disabled' will result in incoming calls being answered more quickly.</span></a> for receiving faxes: 
	<!--<input type="text" size="8" name="FAX_RX" value="<? echo $FAX_RX?>"/>-->
	<select name="FAX_RX">
		<option value="disabled" <? echo ($FAX_RX == 'disabled' ? 'SELECTED' : '')?>>disabled
		<option value="system" <? echo ($FAX_RX == 'system' ? 'SELECTED' : '')?>>system
<?
	foreach ($extens as $exten) {
		echo '<option value="SIP/'.$exten[0].'" '.($FAX_RX == 'SIP/'.$exten[0] ? 'SELECTED' : '').'>Extension #'.$exten[0];
	}
?>			
	</select>
	
</p>
<p>
	<a class="info" href="#">Email address<span>Email address used if 'system' has been chosen for the fax extension above.</span></a> to have faxes emailed to:  
	<input type="text" size="20" name="FAX_RX_EMAIL" value="<? echo $FAX_RX_EMAIL?>"/>
</p>
<br>
<h6>
	<input name="Submit" type="button" value="Submit Changes" onclick="checkGeneral(general)">
</h6>
</form>

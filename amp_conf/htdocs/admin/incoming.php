<?
// Asterisk Management Portal (AMP)
// Copyright (C) 2004 Coalescent Systems Inc
?>

<?
//script to write conf file from mysql
$wScript = rtrim($_SERVER['PATH_TRANSLATED'],$currentFile).'retrieve_extensions_from_mysql.pl';


$action = $_REQUEST['action'];


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(array($_REQUEST['INCOMING'],'INCOMING'),
						array($_REQUEST['REGTIME'],'REGTIME'),
						array($_REQUEST['REGDAYS'],'REGDAYS'),
						array($_REQUEST['AFTER_INCOMING'],'AFTER_INCOMING'),
						array($_REQUEST['IN_OVERRIDE'],'IN_OVERRIDE'));

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

//query for exisiting aa_N contexts
$unique_aas = getaas();

//get unique extensions
$extens = getextens();

//get unique call groups
$gresults = getgroups();
?>

<form name="incoming" action="config.php" method="post">
<input type="hidden" name="display" value=""/>
<input type="hidden" name="action" value="editglobals"/>
<h5>Send <a href="#" class="info">Incoming Calls<span>Dial 7777 from an internal extension to simulate an incoming call.</span></a> from the <a href="#" class="info">PSTN<span>Public Switched Telephone Network (ie: the phone company)</span></a> to:</h5>
<p>
	regular hours:
	<a href="#" class="info"><b>times</b>
		<span>Enter a range, using 24-hour time format. For example, for 8:00am to 5:00pm, type:<br><br>&nbsp;&nbsp;&nbsp;&nbsp;<b>8:00-17:00</b><br><br>An asterisk (*) matches all hours.</span>
	</a>
	<input type="text" size="10" name="REGTIME" value="<? echo $REGTIME ?>"> 
	<a href="#" class="info"><b>days</b>
		<span>Enter a range, using 3 letter abbreviations. For example, for Monday to Friday, type:<br><br>&nbsp;&nbsp;&nbsp;&nbsp;<b>mon-fri</b><br><br>An asterisk (*) matches all days.</span>
	</a>
	<input type="text" size="8" name="REGDAYS" value="<? echo $REGDAYS ?>">:
</p>
<p> 
	<input type="radio" name="in_indicate" value="ivr" disabled="true" <? echo strpos($INCOMING,'aa_') === false ? '' : 'CHECKED=CHECKED';?>/> Digital Receptionist: 
	<input type="hidden" name="INCOMING" value="<? echo $INCOMING; ?>">
	<select name="INCOMING_IVR" onclick="javascript:document.incoming.in_indicate[0].checked=true;javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_IVR.options[document.incoming.INCOMING_IVR.options.selectedIndex].value;"/>
<?
	foreach ($unique_aas as $unique_aa) {
		$menu_num = substr($unique_aa[0],3);
		echo '<option value="aa_'.$menu_num.'" '.($INCOMING == 'aa_'.$menu_num ? 'SELECTED' : '').'>Voice Menu #'.$menu_num;
	}
?>
	</select><br>
	<input type="radio" name="in_indicate" value="extension" disabled="true" <? echo strpos($INCOMING,'EXT') === false ? '' : 'CHECKED=CHECKED';?>/> Extension: 
	<select name="INCOMING_EXTEN" onclick="javascript:document.incoming.in_indicate[1].checked=true;javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_EXTEN.options[document.incoming.INCOMING_EXTEN.options.selectedIndex].value;"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="EXT-'.$exten[0].'" '.($INCOMING == 'EXT-'.$exten[0] ? 'SELECTED' : '').'>#'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="in_indicate" value="group" disabled="true" <? echo strpos($INCOMING,'GR') === false ? '' : 'CHECKED=CHECKED';?>/> Call Group: 
	<select name="INCOMING_GRP" onclick="javascript:document.incoming.in_indicate[2].checked=true;javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_GRP.options[document.incoming.INCOMING_GRP.options.selectedIndex].value;"/>
<?
	foreach ($gresults as $gresult) {
		echo '<option value="GRP-'.$gresult[0].'" '.($INCOMING == 'GRP-'.$gresult[0] ? 'SELECTED' : '').'>#'.$gresult[0];
	}
?>			
	</select><br>
</p>

<p>
	after hours: 
</p>
<p> 
	<input type="radio" name="after_in_indicate" value="ivr" disabled="true" <? echo strpos($AFTER_INCOMING,'aa_') === false ? '' : 'CHECKED=CHECKED';?>/> Digital Receptionist: 
	<input type="hidden" name="AFTER_INCOMING" value="<? echo $AFTER_INCOMING; ?>">
	<select name="AFTER_INCOMING_IVR" onclick="javascript:document.incoming.after_in_indicate[0].checked=true;javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_IVR.options[document.incoming.AFTER_INCOMING_IVR.options.selectedIndex].value;"/>
<?
	foreach ($unique_aas as $unique_aa) {
		$menu_num = substr($unique_aa[0],3);
		echo '<option value="aa_'.$menu_num.'" '.($AFTER_INCOMING == 'aa_'.$menu_num ? 'SELECTED' : '').'>Voice Menu #'.$menu_num;
	}
?>
	</select><br>
	<input type="radio" name="after_in_indicate" value="extension" disabled="true" <? echo strpos($AFTER_INCOMING,'EXT') === false ? '' : 'CHECKED=CHECKED';?>/> Extension: 
	<select name="AFTER_INCOMING_EXTEN" onclick="javascript:document.incoming.after_in_indicate[1].checked=true;javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_EXTEN.options[document.incoming.AFTER_INCOMING_EXTEN.options.selectedIndex].value;"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="EXT-'.$exten[0].'" '.($AFTER_INCOMING == 'EXT-'.$exten[0] ? 'SELECTED' : '').'>#'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="after_in_indicate" value="group" disabled="true" <? echo strpos($AFTER_INCOMING,'GR') === false ? '' : 'CHECKED=CHECKED';?>/> Call Group: 
	<select name="AFTER_INCOMING_GRP" onclick="javascript:document.incoming.after_in_indicate[2].checked=true;javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_GRP.options[document.incoming.AFTER_INCOMING_GRP.options.selectedIndex].value;"/>
<?
	foreach ($gresults as $gresult) {
		echo '<option value="GRP-'.$gresult[0].'" '.($AFTER_INCOMING == 'GRP-'.$gresult[0] ? 'SELECTED' : '').'>#'.$gresult[0];
	}
?>			
	</select><br>
</p>

<h5>Override Incoming Calls Settings</h5>
<p>
	<input type="radio" name="IN_OVERRIDE" value="none" <? echo $IN_OVERRIDE == 'none' ? 'CHECKED=CHECKED' : '' ?>> no override (obey the above settings)<br>
	<input type="radio" name="IN_OVERRIDE" value="forcereghours"<? echo $IN_OVERRIDE == 'forcereghours' ? 'CHECKED=CHECKED' : '' ?>> <a href="#" class="info">force regular hours<span>Select this box if you would like to force the above regular hours setting to always take effect.<br><br>  This is useful for occasions when your office needs to remain open after-hours. (ie: open late on Thursday, or open all day on Sunday).</span></a><br>
	<input type="radio" name="IN_OVERRIDE" value="forceafthours"<? echo $IN_OVERRIDE == 'forceafthours' ? 'CHECKED=CHECKED' : '' ?>> <a href="#" class="info">force after hours<span>Select this box if you would like to force the above after hours setting to always take effect.<br><br>  This is useful for holidays that fall in the 'regular hours' range above (ie: a holiday Monday).</span></a>
</p>

<br>
<h6>
	<input name="Submit" type="button" value="Submit Changes" onclick="checkIncoming(incoming)">
</h6>
</form>

<br><br><br><br><br>

<?
// routing.php Copyright (C) 2004 Greg MacLellan (greg@mtechsolutions.ca)
// Asterisk Management Portal Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
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

//script to write conf file from mysql
$extenScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

//script to write sip conf file from mysql
$sipScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';

//script to write iax conf file from mysql
$iaxScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';

//script to write op_server.cfg file from mysql 
$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';

$display='8'; 
$extdisplay=$_REQUEST['extdisplay'];
$action = $_REQUEST['action'];


$dialpattern = array();
if (isset($_REQUEST["dialpattern"])) {
	//$dialpattern = $_REQUEST["dialpattern"];
	$dialpattern = explode("\n",$_REQUEST["dialpattern"]);

	if (!$dialpattern) {
		$dialpattern = array();
	}
	
	foreach (array_keys($dialpattern) as $key) {
		//trim it
		$dialpattern[$key] = trim($dialpattern[$key]);
		
		// remove blanks
		if ($dialpattern[$key] == "") unset($dialpattern[$key]);
		
		// remove leading underscores (we do that on backend)
		if ($dialpattern[$key][0] == "_") $dialpattern[$key] = substr($dialpattern[$key],1);
	}
	
	// check for duplicates, and re-sequence
	$dialpattern = array_values(array_unique($dialpattern));
}
	

$trunkpriority = array();
if (isset($_REQUEST["trunkpriority"])) {
	$trunkpriority = $_REQUEST["trunkpriority"];

	if (!$trunkpriority) {
		$trunkpriority = array();
	}
	
	// delete blank entries
	foreach (array_keys($trunkpriority) as $key) {
		if (empty($trunkpriority[$key])) unset($trunkpriority[$key]);
	}
	$trunkpriority = array_values($trunkpriority); // resequence our numbers
}

$routename = isset($_REQUEST["routename"]) ? $_REQUEST["routename"] : "";

//if submitting form, update database
switch ($action) {
	case "addroute":
		addRoute($routename, $dialpattern, $trunkpriority);
		exec($extenScript);
		needreload();
	break;
	case "editroute":
		editRoute($routename, $dialpattern, $trunkpriority);
		exec($extenScript);
		needreload();
	break;
	case "delroute":
		deleteRoute($extdisplay);
		exec($extenScript);
		needreload();
		
		$extdisplay = ''; // resets back to main screen
	break;
}
	

	
//get all rows from globals
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${$global[0]} = htmlentities($global[1]);	
}

?>
</div>

<div class="rnav">
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $display?>">Add Route</a><br></li>

<?
//get existing trunk info
$tresults = getroutenames();

foreach ($tresults as $tresult) {
    echo "<li><a id=\"".($extdisplay==$tresult[0] ? 'current':'')."\" href=\"config.php?display=".$display."&extdisplay={$tresult[0]}\">{$tresult[0]}</a></li>";
}

?>
</div>

<div class="content">

<?
if ($extdisplay) {
	
	// load from db
	
	if (!isset($_REQUEST["dialpattern"])) {
		$dialpattern = getroutepatterns($extdisplay);
	}
	
	if (!isset($_REQUEST["trunkpriority"])) {
		$trunkpriority = getroutetrunks($extdisplay);
	}
	
	echo "<h2>Edit Route</h2>";
} else {	
	echo "<h2>Add Route</h2>";
}

// build trunks associative array
foreach (gettrunks() as $temp) {
	$trunks[$temp[0]] = $temp[1];
}

if ($extdisplay) { // editing
?>
	<p><a href="config.php?display=<?= $display ?>&extdisplay=<?= $extdisplay ?>&action=delroute">Delete Route <? echo $extdisplay; ?></a></p>
<? } ?>

	<form name="routeEdit" action="config.php" method="get">
		<input type="hidden" name="display" value="<?echo $display?>"/>
		<input type="hidden" name="extdisplay" value="<?= $extdisplay ?>"/>
		<input type="hidden" name="action" value=""/>
		<table>
		<tr>
			<td>
				<a href=# class="info">Route Name<span><br>Name of this route. Shuold be used to describe what type of calls this route matches (for example, 'local' or 'longdistance').<br><br></span></a>: 
			</td>
<? if ($extdisplay) { // editing?>
			<td>
				<?= $extdisplay;?>
				<input type="hidden" name="routename" value="<?= $extdisplay;?>"/>
			</td>
<? } else { // new ?>
			<td>
				<input type="text" size="20" name="routename" value="<?= $routename;?>"/>
			</td>
<? } ?>
		</tr>
		<tr>
			<td colspan="2">
				<br>
				<a href=# class="info">Dial Patterns<span>A Dial Pattern is a unique set of digits that will select this trunk. Enter one dial pattern per line.<br><br><b>Rules:</b><br>
   <strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br>
   <strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit form 1-9<br>
   <strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br>
   <strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br>
   <strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters <br>
   <strong>|</strong>&nbsp;&nbsp;&nbsp; seperates a dialing prefix from the number (for example, 9|NXXXXXX would match when some dialed "95551234" but would only pass "5551234" to the trunks)
				</span></a><br><br>
			</td>
		</tr>
<? /* old code for using textboxes -- replaced by textarea code
$key = -1;
foreach ($dialpattern as $key=>$pattern) {
?>
		<tr>
			<td><?= $key ?>
			</td><td>
				<input type="text" size="20" name="dialpattern[<?= $key ?>]" value="<?= $dialpattern[$key] ?>"/>
			</td>
		</tr>
<?
} // foreach

$key += 1; // this will be the next key value
?>
		<tr>
			<td><?= $key ?>
			</td><td>
				<input type="text" size="20" name="dialpattern[<?= $key ?>]" value="<?= $dialpattern[$key] ?>"/>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<br><input type="submit" value="Add">
			</td>
		</tr>
<? */ ?>
		<tr>
			<td>
			</td><td>
				<textarea cols="20" rows="<? $rows = count($dialpattern)+1; echo (($rows < 5) ? 5 : $rows); ?>" name="dialpattern"><?=  implode("\n",$dialpattern);?></textarea>
			</td>
		</tr>
		<tr>
			<td>Insert:</td>
			<script language="javascript"><!--
			
			function insertCode() {
				code = document.getElementById('inscode').value;
				insert = '';
				switch(code) {
					case "local":
						insert = 'NXXXXXX\n';
					break;
					case "local10":
						insert = 'NXXXXXX\n'+
							'NXXNXXXXXX\n';
					break;
					case 'tollfree':
						insert = '1800NXXXXXX\n'+
							'1888NXXXXXX\n'+
							'1877NXXXXXX\n'+
							'1866NXXXXXX\n';
					break;
					case "ld":
						insert = '1NXXNXXXXXX\n';
					break;
					case "int":
						insert = '011.\n';
					break;
					case 'info':
						insert = '411\n'+
							'311\n';
					break;
					case 'emerg':
						insert = '911\n';
					break;
					
				}
				if (document.routeEdit.dialpattern.value[ document.routeEdit.dialpattern.value.length - 1 ] == "\n") {
					document.routeEdit.dialpattern.value = document.routeEdit.dialpattern.value + insert;
				} else {
					document.routeEdit.dialpattern.value = document.routeEdit.dialpattern.value + '\n' + insert;
				}
				
				// reset element
				document.getElementById('inscode').value = '';
			}
			
			--></script>
			<td>
				<select onChange="insertCode();" id="inscode">
					<option value="">Pick pre-defined patterns</option>
					<option value="local">Local 7 digit</option>
					<option value="local10">Local 7/10 digit</ption>
					<option value="tollfree">Toll-free</option>
					<option value="ld">Long-distance</option>
					<option value="int">International</option>
					<option value="info">Information</option>
					<option value="emerg">Emergency</option>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2">
			<br><br>
				<a href=# class="info">Trunk Sequence<span>The Trunk Sequence controls the order of trunks that will be used when the above Dial Patterns are matched. <br><br>For Dial Patterns that match long distance numbers, for example, you'd want to pick the cheapest routes for long distance (ie, VoIP trunks first) followed by more expensive routes (POTS lines).<br></span></a><br><br>
			</td>
		</tr>
<?
$key = -1;
foreach ($trunkpriority as $key=>$trunk) {
?>
		<tr>
			<td><?= $key; ?>
			</td>
			<td>
				<select id='trunkpri<?= $key ?>' name="trunkpriority[<?= $key ?>]">
				<option value=""></option>
				<?
				foreach ($trunks as $name=>$display) {
					echo "<option value=\"".$name."\" ".($name == $trunk ? "selected" : "").">".$display."</option>";
				}
				?>
				</select>
			</td>
		</tr>
<?
} // foreach

$key += 1; // this will be the next key value
$name = "";
?>
		<tr>
			<td><?= $key; ?>
			</td>
			<td>
				<select id='trunkpri<?= $key ?>' name="trunkpriority[<?= $key ?>]">
				<option value="" SELECTED></option>
				<?
				foreach ($trunks as $name=>$display) {
					echo "<option value=\"".$name."\">".$display."</option>";
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<br><input type="submit" value="Add">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<h6><input name="Submit" type="button" value="Submit Changes" onclick="checkRoute(routeEdit, '<?= ($extdisplay ? "editroute" : "addroute") ?>')"></h6>
			</td>
		</tr>
		</table>
	</form>
	
<? //Make sure the bottom border is low enuf
foreach ($tresults as $tresult) {
    echo "<br><br><br>";
}

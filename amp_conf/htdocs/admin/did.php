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


//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	
$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];
$dispnum = 7; //used for switch on config.php

//add group
if ($action == 'addDID') {
	
	$account = $_REQUEST['account'];	
	
			$goto = $_REQUEST['goto0'];
			if ($goto == 'extension') {
				$args = 'ext-local,'.$_REQUEST['extension'].',1';
				$addarray = array('ext-did',$account,'1','Goto',$args,'','0'); 
			}
			elseif ($goto == 'voicemail') {
				$args = 'vm,'.$_REQUEST['voicemail'];
				$addarray = array('ext-did',$account,'1','Macro',$args,'','0');
			}
			elseif ($goto == 'ivr') {
				$args = 'aa_'.$_REQUEST['ivr'].',s,1';
				$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
			}
			elseif ($goto == 'group') {
				$args = 'ext-group,'.$_REQUEST['group'].',1';
				$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
			}
			elseif ($goto == 'from-pstn') {
					$args = 'from-pstn,s,1';
					$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
			}
	
	addextensions($addarray);
	
	
	//write out extensions_additional.conf
	exec($wScript1);
	
	//indicate 'need reload' link in header.php 
	needreload();
}

//del group
if ($action == 'delGRP') {
	delextensions('ext-did',ltrim($extdisplay,'DID-'));
	
	//write out extensions_additional.conf
	exec($wScript1);
	
	//indicate 'need reload' link in header.php 
	needreload();
}

//edit group - just delete and then re-add the extension
if ($action == 'edtGRP') {
	
	$account = $_REQUEST['account'];

		delextensions('ext-did',$account);
		
				$goto = $_REQUEST['goto0'];
				if ($goto == 'extension') {
					$args = 'ext-local,'.$_REQUEST['extension'].',1';
					$addarray = array('ext-did',$account,'1','Goto',$args,'','0'); 
				}
				elseif ($goto == 'voicemail') {
					$args = 'vm,'.$_REQUEST['voicemail'];
					$addarray = array('ext-did',$account,'1','Macro',$args,'','0');
				}
				elseif ($goto == 'ivr') {
					$args = 'aa_'.$_REQUEST['ivr'].',s,1';
					$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
				}
				elseif ($goto == 'group') {
					$args = 'ext-group,'.$_REQUEST['group'].',1';
					$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
				}
				elseif ($goto == 'from-pstn') {
					$args = 'from-pstn,s,1';
					$addarray = array('ext-did',$account,'1','Goto',$args,'','0');
				}
		
		addextensions($addarray);
		
		//write out extensions_additional.conf
		exec($wScript1);
		
		//indicate 'need reload' link in header.php 
		needreload();

}

?>
</div>

<div class="rnav">
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $dispnum?>">Add DID</a><br></li>
<?
//get unique Ring Groups
$dresults = getdids();

foreach ($dresults as $dresult) {
    echo "<li><a id=\"".($extdisplay=='DID-'.$dresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=DID-{$dresult[0]}\">DID # {$dresult[0]}</a></li>";
}
?>
</div>

<div class="content">
<?
switch($extdisplay) {
    default:
		
		if ($action == 'delGRP') {
			echo '<br><h3>DID # '.ltrim($extdisplay,'DID-').' deleted!</h3><br><br><br><br><br><br><br><br>';
		} else {
			
			//query for exisiting aa_N contexts
			$unique_aas = getaas();
			//get unique extensions
			$extens = getextens();
			//get unique Ring Groups
			$gresults = getgroups();
	
			//get goto for this group
			$thisGRPgoto = getgroupgoto(ltrim($extdisplay,'DID-'));

			$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delGRP';
	?>
			<h2>DID #: <? echo ltrim($extdisplay,'DID-'); ?></h2>
			<p><a href="<? echo $delURL ?>">Delete DID <? echo ltrim($extdisplay,'DID-'); ?></a></p>
			<h4>Edit:</h4>
			<form name="editGRP" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="display" value="<?echo $dispnum?>">
			<input type="hidden" name="action" value="edtGRP">
			<input type="hidden" name="account" value="<? echo ltrim($extdisplay,'DID-'); ?>">
			<table>
			<tr>
				<td><a href="#" class="info">DID Number:<span>Define the expected DID digits if your trunk passes DID for incoming calls.</span></a></td>
				<td><input type="text" name="account" value="<? echo ltrim($extdisplay,'DID-') ?>"></td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td valign="top">Destination:</td>
				<td>
			
				<input type="hidden" name="goto0" value="">		
				
				<input type="radio" name="goto_indicate" value="from-pstn" <? echo strpos($thisGRPgoto[0][0],'from-pstn') === false ? '' : 'CHECKED=CHECKED';?> /> 
				Use 'Incoming Calls' settings<br>
				<br>				
				
				<input type="radio" name="goto_indicate" value="ivr" disabled="true" <? echo strpos($thisGRPgoto[0][0],'aa_') === false ? '' : 'CHECKED=CHECKED';?> /> 
				<select name="ivr" onclick="javascript:document.editGRP.goto_indicate[1].checked=true;"/>
			<?
				foreach ($unique_aas as $unique_aa) {
					$menu_num = substr($unique_aa[0],3);
					$menu_name = $unique_aa[1];
					echo '<option value="'.$menu_num.'" '.(strpos($thisGRPgoto[0][0],'aa_'.$menu_num) === false ? '' : 'SELECTED').'>Menu #'.$menu_num.': '.$menu_name;
				}
			?>
				</select><br>
				<input type="radio" name="goto_indicate" value="extension" disabled="true" <? echo strpos($thisGRPgoto[0][0],'ext-local') === false ? '' : 'CHECKED=CHECKED';?>/>
				<select name="extension" onclick="javascript:document.editGRP.goto_indicate[2].checked=true;"/>
			<?
				foreach ($extens as $exten) {
					echo '<option value="'.$exten[0].'" '.(strpos($thisGRPgoto[0][0],$exten[0]) === false ? '' : 'SELECTED').'>Extension #'.$exten[0];
				}
			?>		
				</select><br>
				<input type="radio" name="goto_indicate" value="voicemail" disabled="true" <? echo strpos($thisGRPgoto[0][0],'vm') === false ? '' : 'CHECKED=CHECKED';?> />
				<select name="voicemail" onclick="javascript:document.editGRP.goto_indicate[3].checked=true;"/>
			<?
				foreach ($extens as $exten) {
					echo '<option value="'.$exten[0].'" '.(strpos($thisGRPgoto[0][0],$exten[0]) === false ? '' : 'SELECTED').'>Voicemail #'.$exten[0];
				}
			?>		
				</select><br>
				<input type="radio" name="goto_indicate" value="group" disabled="true" <? echo strpos($thisGRPgoto[0][0],'ext-group') === false ? '' : 'CHECKED=CHECKED';?> />
				<select name="group<? echo $i ?>" onclick="javascript:document.editGRP.goto_indicate[4].checked=true;"/>
			<?
				foreach ($gresults as $gresult) {
					echo '<option value="'.$gresult[0].'" '.(strpos($thisGRPgoto[0][0],$gresult[0]) === false ? '' : 'SELECTED').'>Group #'.$gresult[0];
				}
			?>			
				</select><br>
				
				
				
				</td>
				
			</tr><tr>
			<td>&nbsp;</td>
			<td><h6><input name="Submit" type="button" value="Submit" onclick="checkDID(editGRP);"></h6></td>		
			
			</tr>
			</table>
			</form>
<?		
		} //end if action == delGRP
		

	break;
	case '':
	
		//query for exisiting aa_N contexts
		$unique_aas = getaas();
		//get unique extensions
		$extens = getextens();
		//get unique Ring Groups
		$gresults = getgroups();
?>

	<h2>Add DID #:</h2>
	<form name="addDID" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="action" value="addDID">
	<input type="hidden" name="account" value="<? echo $grp ?>">
	<table>
	<tr>
		<td><a href="#" class="info">DID Number:<span>Define the expected DID digits if your trunk passes DID for incoming calls.</span></a></td>
		<td><input type="text" name="account" value="<? echo substr($thisGRP[0][0],6) ?>"></td>	
	</tr>
	<tr>
		<td><br></td>
	</tr>
	<tr>
		<td valign="top">Destination:</td>
		<td>
	<input type="hidden" name="display" value="<?echo $dispnum?>">		
	<input type="hidden" name="goto0" value="">
	
	<input type="radio" name="goto_indicate" value="from-pstn" CHECKED=CHECKED /> 
	Use 'Incoming Calls' settings<br>
	<br>	
				
	<input type="radio" name="goto_indicate" value="ivr" disabled="true"/> 
	<select name="ivr" onclick="javascript:document.addDID.goto_indicate[1].checked=true;javascript:document.addDID.goto0.value='ivr';"/>
<?
	foreach ($unique_aas as $unique_aa) {
		$menu_num = substr($unique_aa[0],3);
		$menu_name = $unique_aa[1];
		echo '<option value="'.$menu_num.'">Menu #'.$menu_num.': '.$menu_name;
	}
?>
	</select><br>
	<input type="radio" name="goto_indicate" value="extension" disabled="true"/>
	<select name="extension" onclick="javascript:document.addDID.goto_indicate[2].checked=true;javascript:document.addDID.goto0.value='extension';"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="'.$exten[0].'">Extension #'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="goto_indicate" value="voicemail" disabled="true"/>
	<select name="voicemail" onclick="javascript:document.addDID.goto_indicate[3].checked=true;javascript:document.addDID.goto0.value='voicemail';"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="'.$exten[0].'">Voicemail #'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="goto_indicate" value="group" disabled="true"/>
	<select name="group<? echo $i ?>" onclick="javascript:document.addDID.goto_indicate[4].checked=true;javascript:document.addDID.goto0.value='group';"/>
<?
	foreach ($gresults as $gresult) {
		echo '<option value="'.$gresult[0].'">Group #'.$gresult[0];
	}
?>			
	</select><br>
	
	</tr><tr>
		<td>&nbsp;</td>
		<td><h6><input name="Submit" type="button" value="Submit" onclick="checkDID(addDID)"></h6></td>		
		
	</tr>
	</table>
	</form>


<?php
    break;
}
?>
<br><br><br><br><br><br><br><br><br>
<? //Make sure the bottom border is low enuf
foreach ($dresults as $dresult) {
    echo "<br>";
}
?>





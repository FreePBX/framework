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
$dispnum = 4; //used for switch on config.php

//add group
if ($action == 'addGRP') {
	
	$account = $_REQUEST['account'];
	$grplist = $_REQUEST['grplist'];
	$grptime = $_REQUEST['grptime'];
	$grppre = $_REQUEST['grppre'];
	
	$addarray = array('ext-group',$account,'1','Setvar','GROUP='.$grplist,'','0');
	addextensions($addarray);
	$addarray = array('ext-group',$account,'2','Setvar','RINGTIMER='.$grptime,'','0');
	addextensions($addarray);
	$addarray = array('ext-group',$account,'3','Setvar','PRE='.$grppre,'','0');
	addextensions($addarray);
	$addarray = array('ext-group',$account,'4','Macro','rg-group','','0');
	addextensions($addarray);
	
	
			$goto = $_REQUEST['goto0'];
			if ($goto == 'extension') {
				$args = 'ext-local,'.$_REQUEST['extension'].',1';
				$addarray = array('ext-group',$account,'5','Goto',$args,'','0'); 
			}
			elseif ($goto == 'voicemail') {
				$args = 'vm,'.$_REQUEST['voicemail'];
				$addarray = array('ext-group',$account,'5','Macro',$args,'','0');
			}
			elseif ($goto == 'ivr') {
				$args = 'aa_'.$_REQUEST['ivr'].',s,1';
				$addarray = array('ext-group',$account,'5','Goto',$args,'','0');
			}
			elseif ($goto == 'group') {
				$args = 'ext-group,'.$_REQUEST['group'].',1';
				$addarray = array('ext-group',$account,'5','Goto',$args,'','0');
			}
	
	addextensions($addarray);
	
	
	//write out extensions_additional.conf
	exec($wScript1);
	
	//indicate 'need reload' link in header.php 
	needreload();
}

//del group
if ($action == 'delGRP') {
	delextensions('ext-group',ltrim($extdisplay,'GRP-'));
	
	//write out extensions_additional.conf
	exec($wScript1);
	
	//indicate 'need reload' link in header.php 
	needreload();
}

//edit group - just delete and then re-add the extension
if ($action == 'edtGRP') {
	
	$account = $_REQUEST['account'];
	$grplist = $_REQUEST['grplist'];
	$grptime = $_REQUEST['grptime'];
	$grppre = $_REQUEST['grppre'];

		delextensions('ext-group',$account);
		
		$addarray = array('ext-group',$account,'1','Setvar','GROUP='.$grplist,'','0');
		addextensions($addarray);
		$addarray = array('ext-group',$account,'2','Setvar','RINGTIMER='.$grptime,'','0');
		addextensions($addarray);
		$addarray = array('ext-group',$account,'3','Setvar','PRE='.$grppre,'','0');
		addextensions($addarray);
		$addarray = array('ext-group',$account,'4','Macro','rg-group','','0');
		addextensions($addarray);
		
		
				$goto = $_REQUEST['goto0'];
				if ($goto == 'extension') {
					$args = 'ext-local,'.$_REQUEST['extension'].',1';
					$addarray = array('ext-group',$account,'5','Goto',$args,'','0'); 
				}
				elseif ($goto == 'voicemail') {
					$args = 'vm,'.$_REQUEST['voicemail'];
					$addarray = array('ext-group',$account,'5','Macro',$args,'','0');
				}
				elseif ($goto == 'ivr') {
					$args = 'aa_'.$_REQUEST['ivr'].',s,1';
					$addarray = array('ext-group',$account,'5','Goto',$args,'','0');
				}
				elseif ($goto == 'group') {
					$args = 'ext-group,'.$_REQUEST['group'].',1';
					$addarray = array('ext-group',$account,'5','Goto',$args,'','0');
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
    <li><a id="<? echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?echo $dispnum?>">Add Call Group</a><br></li>
<?
//get unique call groups
$gresults = getgroups();

foreach ($gresults as $gresult) {
    echo "<li><a id=\"".($extdisplay=='GRP-'.$gresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=GRP-{$gresult[0]}\">Call Group {$gresult[0]}</a></li>";
}
?>
</div>

<div class="content">
<?
switch($extdisplay) {
    default:
		
		if ($action == 'delGRP') {
			echo '<br><h3>Group '.ltrim($extdisplay,'GRP-').' deleted!</h3><br><br><br><br><br><br><br><br>';
		} else {
			
			//query for exisiting aa_N contexts
			$unique_aas = getaas();
			//get unique extensions
			$extens = getextens();
			//get unique call groups
			$gresults = getgroups();
	
			//get extensions in this group
			$thisGRP = getgroupextens(ltrim($extdisplay,'GRP-'));
			//get ringtime for this group
			$thisGRPtime = getgrouptime(ltrim($extdisplay,'GRP-'));
			//get goto for this group
			$thisGRPgoto = getgroupgoto(ltrim($extdisplay,'GRP-'));
			//get prefix for this group
			$thisGRPprefix = getgroupprefix(ltrim($extdisplay,'GRP-'));

			$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delGRP';
	?>
			<h2>Call Group: <? echo ltrim($extdisplay,'GRP-'); ?></h2>
			<p><a href="<? echo $delURL ?>">Delete Group <? echo ltrim($extdisplay,'GRP-'); ?></a></p>
			<h4>Edit:</h4>
			<form name="editGRP" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="display" value="<?echo $dispnum?>">
			<input type="hidden" name="action" value="edtGRP">
			<input type="hidden" name="account" value="<? echo ltrim($extdisplay,'GRP-'); ?>">
			<table>
			<tr>
				<td><a href="#" class="info">extension list:<span>Separate extensions with a | (pipe) character. Ex: 201|202|203</span></a></td>
				<td><input type="text" name="grplist" value="<? echo substr($thisGRP[0][0],6) ?>"></td>
			</tr>
			<tr>
				<td><a href="#" class="info">CID name prefix:<span>You can optionally prefix the Caller ID name when ringing extensions in this group. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.</span></a></td>
				<td><input size="4" type="text" name="grppre" value="<? echo substr($thisGRPprefix[0][0],4) ?>"></td>
			</tr><tr>
				<td>ring time (max 60 sec):</td>
				<td><input size="4" type="text" name="grptime" value="<? echo substr($thisGRPtime[0][0],10) ?>"></td>
			</tr><tr>
				<td valign="top">if no answer:</td>
				<td>
			
				<input type="hidden" name="goto0" value="">				
				<input type="radio" name="goto_indicate" value="ivr" disabled="true" <? echo strpos($thisGRPgoto[0][0],'aa_') === false ? '' : 'CHECKED=CHECKED';?> /> 
				
				<select name="ivr" onclick="javascript:document.editGRP.goto_indicate[0].checked=true;"/>
			<?
				foreach ($unique_aas as $unique_aa) {
					$menu_num = substr($unique_aa[0],3);
					echo '<option value="'.$menu_num.'" '.(strpos($thisGRPgoto[0][0],'aa_'.$menu_num) === false ? '' : 'SELECTED').'>Voice Menu #'.$menu_num;
				}
			?>
				</select><br>
				<input type="radio" name="goto_indicate" value="extension" disabled="true" <? echo strpos($thisGRPgoto[0][0],'ext-local') === false ? '' : 'CHECKED=CHECKED';?>/>
				<select name="extension" onclick="javascript:document.editGRP.goto_indicate[1].checked=true;"/>
			<?
				foreach ($extens as $exten) {
					echo '<option value="'.$exten[0].'" '.(strpos($thisGRPgoto[0][0],$exten[0]) === false ? '' : 'SELECTED').'>Extension #'.$exten[0];
				}
			?>		
				</select><br>
				<input type="radio" name="goto_indicate" value="voicemail" disabled="true" <? echo strpos($thisGRPgoto[0][0],'vm') === false ? '' : 'CHECKED=CHECKED';?> />
				<select name="voicemail" onclick="javascript:document.editGRP.goto_indicate[2].checked=true;"/>
			<?
				foreach ($extens as $exten) {
					echo '<option value="'.$exten[0].'" '.(strpos($thisGRPgoto[0][0],$exten[0]) === false ? '' : 'SELECTED').'>Voicemail #'.$exten[0];
				}
			?>		
				</select><br>
				<input type="radio" name="goto_indicate" value="group" disabled="true" <? echo strpos($thisGRPgoto[0][0],'ext-group') === false ? '' : 'CHECKED=CHECKED';?> />
				<select name="group<? echo $i ?>" onclick="javascript:document.editGRP.goto_indicate[3].checked=true;"/>
			<?
				foreach ($gresults as $gresult) {
					echo '<option value="'.$gresult[0].'" '.(strpos($thisGRPgoto[0][0],$gresult[0]) === false ? '' : 'SELECTED').'>Group #'.$gresult[0];
				}
			?>			
				</select><br>
				
				
				
				</td>
				
			</tr><tr>
			<td>&nbsp;</td>
			<td><h6><input name="Submit" type="button" value="Submit" onclick="checkGRP(editGRP);"></h6></td>		
			
			</tr>
			</table>
			</form>
<?		
		} //end if action == delGRP
		

	break;
	case '':
	
		$grp = '700';
		//use 1st available number from 700
		foreach ($gresults as $gresult) {
			if ($gresult[0] == $grp) {
				$grp++;
			}
		}
		
		//query for exisiting aa_N contexts
		$unique_aas = getaas();
		//get unique extensions
		$extens = getextens();
		//get unique call groups
		$gresults = getgroups();
?>

	<h2>Add Call Group: <? echo $grp ?></h2>
	<form name="addGRP" action="<? $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="action" value="addGRP">
	<input type="hidden" name="account" value="<? echo $grp ?>">
	<table>
	<tr>
		<td><a href="#" class="info">extension list:<span>Separate extensions with a | (pipe) character. Ex: 201|202|203</span></a></td>
		<td><input type="text" name="grplist" value="<? echo substr($thisGRP[0][0],6) ?>"></td>
			
	</tr><tr>
				<td><a href="#" class="info">CID name prefix:<span>You can optionally prefix the Caller ID name when ringing extensions in this group. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.</span></a></td>
				<td><input size="4" type="text" name="grppre" value=""></td>
			</tr><tr>
		<td>ring time (max 60 sec):</td>
		<td><input size="4" type="text" name="grptime" value="15"></td>
	</tr><tr>
		<td valign="top">if no answer:</td>
		<td>
	<input type="hidden" name="display" value="<?echo $dispnum?>">				
	<input type="radio" name="goto_indicate" value="ivr" disabled="true"/> 
	<input type="hidden" name="goto0" value="">
	<select name="ivr" onclick="javascript:document.addGRP.goto_indicate[0].checked=true;javascript:document.addGRP.goto0.value='ivr';"/>
<?
	foreach ($unique_aas as $unique_aa) {
		$menu_num = substr($unique_aa[0],3);
		echo '<option value="'.$menu_num.'">Voice Menu #'.$menu_num;
	}
?>
	</select><br>
	<input type="radio" name="goto_indicate" value="extension" disabled="true"/>
	<select name="extension" onclick="javascript:document.addGRP.goto_indicate[1].checked=true;javascript:document.addGRP.goto0.value='extension';"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="'.$exten[0].'">Extension #'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="goto_indicate" value="voicemail" disabled="true"/>
	<select name="voicemail" onclick="javascript:document.addGRP.goto_indicate[2].checked=true;javascript:document.addGRP.goto0.value='voicemail';"/>
<?
	foreach ($extens as $exten) {
		echo '<option value="'.$exten[0].'">Voicemail #'.$exten[0];
	}
?>		
	</select><br>
	<input type="radio" name="goto_indicate" value="group" disabled="true"/>
	<select name="group<? echo $i ?>" onclick="javascript:document.addGRP.goto_indicate[3].checked=true;javascript:document.addGRP.goto0.value='group';"/>
<?
	foreach ($gresults as $gresult) {
		echo '<option value="'.$gresult[0].'">Group #'.$gresult[0];
	}
?>			
	</select><br>
	
	</tr><tr>
		<td>&nbsp;</td>
		<td><h6><input name="Submit" type="button" value="Submit" onclick="checkGRP(addGRP)"></h6></td>		
		
	</tr>
	</table>
	</form>


<?php
    break;
}
?>

<? //Make sure the bottom border is low enuf
foreach ($gresults as $gresult) {
    echo "<br>";
}
?>





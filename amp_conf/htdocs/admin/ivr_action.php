<?
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

<?
//this file checks to see if we need to adjust the extensions table, makes changes, and then calls ivrmap.php to display map

$ivract_target = $_REQUEST['ivract_target'];
$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

switch($_REQUEST['ivr_action']) {
	case 'delete':
	
		$delsql = "DELETE FROM extensions WHERE context = 'aa_$ivract_target'";
		$delres = $db->query($delsql);
		if(DB::IsError($delres)) {
		   die('oops: '.$delres->getMessage());
		}	

		//we are going to call 'write' anyways - so check if editing
		if ($_REQUEST['map_display'] != 'no') {
			//write out conf file
			exec($wScript);
			//indicate 'need reload' link in header.php  	
			$sql = "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'";
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
		}
		
	break;
	case 'write':
	
		//write this auto-attendant to extensions table
		//context, extension, priority, application, args, descr, flags
		
		$context = 'aa_'.$promptnum;
		$extension = 's';
		
		//start the context with a standard set of commands
		$aa = array(array($context,'include','1','ext-local','','','2'));
		$aa[] = array($context,'include','2','app-messagecenter','','','2');
		$aa[] = array($context,'fax','1','Goto','ext-fax,in_fax,1','','0');
		$aa[] = array($context,'include','3','app-directory','','','2');
		$aa[] = array($context,'h','1','Hangup','','','0');
		//$aa[] = array($context,'t','1','Playback','vm-goodbye','','0');
		//$aa[] = array($context,'t','2','Hangup','','','0');
		$aa[] = array($context,'i','1','Playback','invalid','','0');
		$aa[] = array($context,'i','2','Goto','s,5','','0');
		//priority 1 - 3
		$aa[] = array($context,$extension,'1','DigitTimeout','3',$_REQUEST['mname'],'0');
		$aa[] = array($context,$extension,'2','ResponseTimeout','7','','0');
		$aa[] = array($context,$extension,'3','Background','custom/'.$context,$_REQUEST['notes'],'0');
		
		//get the user-defined options
		$ivr_num_options = $_REQUEST['ivr_num_options'];
	
		for ($i = 0; $i < $ivr_num_options; $i++) {
			//priority is # std items + promptnum
			//application depends on goto1,2,3
			//args will be exten, ivr, or voicemail
			$extension = $_REQUEST['ivr_option'.$i];
			$goto = $_REQUEST['goto'.$i];
			if ($goto == 'extension') {
				$args = 'exten-vm,'.$_REQUEST['extension'.$i].','.$_REQUEST['extension'.$i];
				$aa[] = array($context,$extension,'1','Macro',$args,'','0');
				//$describe[$i] = 'option '.$extension.' <b>dials extension #'.$_REQUEST['extension'.$i].'</b>'; 
			}
			elseif ($goto == 'voicemail') {
				$args = 'vm,'.$_REQUEST['voicemail'.$i];
				$aa[] = array($context,$extension,'1','Macro',$args,'','0');
				//$describe[$i] = 'option '.$extension.' <b>sends to voicemail box #'.$_REQUEST['voicemail'.$i].'</b>';
			}
			elseif ($goto == 'ivr') {
				$args = 'aa_'.$_REQUEST['ivr'.$i].',s,1';
				$aa[] = array($context,$extension,'1','Goto',$args,'','0');
				//$describe[$i] = 'option '.$extension.' <b>goes to Voice Menu #'.$_REQUEST['ivr'.$i].'</b>';
			}
			elseif ($goto == 'group') {
				$args = 'ext-group,'.$_REQUEST['group'.$i].',1';
				$aa[] = array($context,$extension,'1','Goto',$args,'','0');
				//$describe[$i] = 'option '.$extension.' <b>goes to Voice Menu #'.$_REQUEST['ivr'.$i].'</b>';
			}
			elseif ($goto == 'custom') {
				$args = $_REQUEST['custom'.$i];
				$aa[] = array($context,$extension,'1','Goto',$args,'','0');
			}
		}
		
		//plop the stuff into database
		$compiled = $db->prepare('INSERT INTO extensions (context, extension, priority, application, args, descr, flags) values (?,?,?,?,?,?,?)');
		$result = $db->executeMultiple($compiled,$aa);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		
		//write out conf file
		exec($wScript);
		
		//make sure the 'custom' sounds directory exists and then copy the recording to it
		if (!is_dir('/var/lib/asterisk/sounds/custom')) {
			if (!mkdir('/var/lib/asterisk/sounds/custom',0775))
				echo 'could not create /var/lib/asterisk/sounds/custom';
		}
		if (!copy('/var/lib/asterisk/sounds/ivrrecording.wav','/var/lib/asterisk/sounds/custom/'.$context.'.wav'))
			echo 'error: could not copy or rename the voice recording - please contact support';

	//indicate 'need reload' link in header.php 
	needreload();
		
	break;
}

//show the new map
if ($_REQUEST['map_display'] != 'no')
	include 'ivrmap.php';
?>

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
?>

<?php 
//this file checks to see if we need to adjust the extensions table, makes changes, and then calls ivrmap.php to display map
$menu_id = $_REQUEST['menu_id'];
$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

// individual AMP Users department prefix - has no effect if deptartment is empty
$dept = str_replace(' ','_',$_SESSION["AMP_user"]->_deptname);


switch($_REQUEST['ivr_action']) {
	case 'delete':
	
		$delsql = "DELETE FROM extensions WHERE context = '$menu_id'";
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
		
		$context = $menu_id;
		$extension = 's';
		
		//start the context with a standard set of commands
		$aa = array(array($context,'include','1','ext-local','','','2'));
		$aa[] = array($context,'include','2','app-messagecenter','','','2');
		$aa[] = array($context,'fax','1','Goto','ext-fax,in_fax,1','','0');
		$aa[] = array($context,'include','3','app-directory','','','2');
		$aa[] = array($context,'h','1','Hangup','','','0');
		$aa[] = array($context,'i','1','Playback','invalid','','0');
		$aa[] = array($context,'i','2','Goto','s,7','','0');
		//priority 1 - 7
		$aa[] = array($context,$extension,'1','GotoIf','$[${DIALSTATUS} = ANSWER]?4','','0');
		$aa[] = array($context,$extension,'2','Answer','','','0');
		$aa[] = array($context,$extension,'3','Wait','1','','0');
		$aa[] = array($context,$extension,'4','SetVar','LOOPED=1','','0');
		$aa[] = array($context,$extension,'5','GotoIf','$[${LOOPED} > 2]?hang,1','','0');
		$aa[] = array($context,$extension,'6','SetVar','DIR-CONTEXT='.$_REQUEST['dir-context'],'','0');
		$aa[] = array($context,$extension,'7','DigitTimeout','3',$_REQUEST['mname'],'0');
		$aa[] = array($context,$extension,'8','ResponseTimeout','7','','0');
		$aa[] = array($context,$extension,'9','Background','custom/'.$context,$_REQUEST['notes'],'0');
		
		$aa[] = array($context,'t','1','SetVar','LOOPED=$[${LOOPED} + 1]','','0');
		$aa[] = array($context,'t','2','Goto','s,5','','0');
		
		$aa[] = array($context,'hang','1','Playback','vm-goodbye','','0');
		$aa[] = array($context,'hang','2','Hangup','','','0');

		

		//plop the stuff into database
		$compiled = $db->prepare('INSERT INTO extensions (context, extension, priority, application, args, descr, flags) values (?,?,?,?,?,?,?)');
		$result = $db->executeMultiple($compiled,$aa);
		if(DB::IsError($result)) {
			die($result->getMessage().'<br>context='.$context);
		}
		
		//get the user-defined options
		$ivr_num_options = $_REQUEST['ivr_num_options'];
	
		for ($i = 0; $i < $ivr_num_options; $i++) {
			//priority is # std items + promptnum
			//application depends on goto1,2,3
			//args will be exten, ivr, or voicemail
			$extension = $_REQUEST['ivr_option'.$i];
			$goto = $_REQUEST['goto'.$i];
			setGoto($extension,$context,'1',$goto,$i);
		}
		
		
		//write out conf file
		exec($wScript);
		
		//make sure the 'custom' sounds directory exists and then copy the recording to it
		if (!is_dir('/var/lib/asterisk/sounds/custom')) {
			if (!mkdir('/var/lib/asterisk/sounds/custom',0775))
				echo 'could not create /var/lib/asterisk/sounds/custom';
		}
		if (!copy('/var/lib/asterisk/sounds/'.$_REQUEST['cidnum'].'ivrrecording.wav','/var/lib/asterisk/sounds/custom/'.$context.'.wav'))
			echo 'error: could not copy or rename the voice recording - please contact support';

	//indicate 'need reload' link in header.php 
	needreload();
		
	break;
}

//show the new map
if ($_REQUEST['map_display'] != 'no')
	include 'ivrmap.php';
?>

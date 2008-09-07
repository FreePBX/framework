<?php 

// do_reload.php
// David Rowe March 11 2008
// non-function version that executes in same php-cgi as config.php to
// save run-time memory      

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

# this file runs inside special window that makes it hard to log
# messages using regular HTML.  So we use a log file instead.

function reload_dbg($msg) {
	$myFile = "/tmp/log.txt";
	$fh = fopen($myFile, 'a') or die("can't open file");
	fwrite($fh, $msg);
	fclose($fh);
}

# This is more or less the code copied from do_reload() in
# admin/functions.inc.php

	$notify =& notifications::create($db);
	
	$return = array('num_errors'=>0,'test'=>'abc');
	$exit_val = null;
	
	if (isset($amp_conf["PRE_RELOAD"]) && !empty($amp_conf['PRE_RELOAD']))  {
		exec( $amp_conf["PRE_RELOAD"], $output, $exit_val );
		
		if ($exit_val != 0) {
			$desc = sprintf(_("Exit code was %s and output was: %s"), $exit_val, "\n\n".implode("\n",$output));
			$notify->add_error('freepbx','reload_pre_script', sprintf(_('Could not run %s script.'), $amp_conf['PRE_RELOAD']), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx', 'reload_pre_script');
		}
	}
	
	// use include file in current php-cgi, avoid repeating a lot
        // of the code

	require_once('retrieve_conf.inc.php');
	$return['retrieve_conf'] = $rc_output;

	// retrive_conf html output
	if ($exit_val != 0) {
		$return['status'] = false;
		$return['message'] = sprintf(_('Reload failed because retrieve_conf encountered an error: %s'),$exit_val);
		$return['num_errors']++;
		$notify->add_critical('freepbx','RCONFFAIL', _("retrieve_conf failed, config not applied"), $return['message']);
		return $return;
	}
	
	if (!isset($astman) || !$astman) {
		$return['status'] = false;
		$return['message'] = _('Reload failed because FreePBX could not connect to the asterisk manager interface.');
		$return['num_errors']++;
		$notify->add_critical('freepbx','RCONFFAIL', _("retrieve_conf failed, config not applied"), $return['message']);
		return $return;
	}
	$notify->delete('freepbx', 'RCONFFAIL');
	
	//reload MOH to get around 'reload' not actually doing that.
	$astman->send_request('Command', array('Command'=>'moh reload'));
	
	//reload asterisk
	$astman->send_request('Command', array('Command'=>'reload'));	
	
	$return['status'] = true;
	$return['message'] = _('Successfully reloaded');
	
	if ($amp_conf['FOPRUN']) {
		//bounce op_server.pl
		$wOpBounce = $amp_conf['AMPBIN'].'/bounce_op.sh';
		exec($wOpBounce.' &>'.$asterisk_conf['astlogdir'].'/freepbx-bounce_op.log', $output, $exit_val);
		
		if ($exit_val != 0) {
			$desc = _('Could not reload the FOP operator panel server using the bounce_op.sh script. Configuration changes may not be reflected in the panel display.');
			$notify->add_error('freepbx','reload_fop', _('Could not reload FOP server'), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx','reload_fop');
		}
	}
	
	//store asterisk reloaded status
	$sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'"; 
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		$return['message'] = _('Successful reload, but could not clear reload flag due to a database error: ').$db->getMessage();
		$return['num_errors']++;
	}
	
	if (isset($amp_conf["POST_RELOAD"]) && !empty($amp_conf['POST_RELOAD']))  {
		exec( $amp_conf["POST_RELOAD"], $output, $exit_val );
		
		if ($exit_val != 0) {
			$desc = sprintf(_("Exit code was %s and output was: %s"), $exit_val, "\n\n".implode("\n",$output));
			$notify->add_error('freepbx','reload_post_script', sprintf(_('Could not run %s script.'), 'POST_RELOAD'), $desc);
			
			$return['num_errors']++;
		} else {
			$notify->delete('freepbx', 'reload_post_script');
		}
	}
	$response = $return;	
	
?>

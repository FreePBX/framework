#!/usr/bin/env php
<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
	$current_user = posix_getpwuid(posix_geteuid());
	if ($current_user['uid'] !== 0) {
		die('Forbidden - must be root');
	}
	// Generate the a list of variables that can be sourced by
	// a bash script
	$bootstrap_settings['freepbx_auth'] = false;
	$bootstrap_settings['skip_astman'] = true;//no need for astman here
	$restrict_mods = true;//no need for modules here
	if (!@include_once(getenv("FREEPBX_CONF") ? getenv("FREEPBX_CONF") : "/etc/freepbx.conf")) {
		include_once("/etc/asterisk/freepbx.conf");
	}
	foreach($amp_conf as $key => $val) {
		if (is_bool($val)) {
			echo "export " . trim($key) . "=" . ($val?"TRUE":"FALSE") ."\n";
		} else {
			//new lines aren't exported properly
			$val = str_replace("\n","\\\\n",$val);
			echo "export " . trim($key) . "=" . escapeshellcmd(trim($val)) ."\n";
		}
	}
?>

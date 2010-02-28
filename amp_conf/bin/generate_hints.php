#!/usr/bin/php -q
<?php

	$debug = -1;

	// If set to nointercom then don't generate any hints
	//
	$intercom_code = isset($argv[1]) ? $argv[1] : '';
	$dnd_mode      = isset($argv[2]) ? $argv[2] : '';

	$amp_conf = parse_amportal_conf_bootstrap("/etc/amportal.conf");

	require_once($amp_conf['AMPWEBROOT'].'/admin/functions.inc.php');
	require_once($amp_conf['AMPWEBROOT'].'/admin/common/php-asmanager.php');

	$amp_conf = parse_amportal_conf("/etc/amportal.conf");

	$astman         = new AGI_AsteriskManager();

  $astmanagerhost = (isset($amp_conf['ASTMANAGERHOST']) && trim($amp_conf['ASTMANAGERHOST']) != '')?$amp_conf['ASTMANAGERHOST']:'127.0.0.1';
  if (isset($amp_conf['ASTMANAGERPORT']) && trim($amp_conf['ASTMANAGERPORT']) != '') {
    $astmanagerhost .= ':'.$amp_conf['ASTMANAGERPORT'];
  }

	if (! $res = $astman->connect($astmanagerhost, $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		exit;
	}
	$ast_with_dahdi = ast_with_dahdi();

	$var = $astman->database_show('AMPUSER');
	foreach ($var as $key => $value) {
		$myvar = explode('/',trim($key,'/'));
		$user_hash[$myvar[1]] = true;
	}

	foreach (array_keys($user_hash) as $user) {
		if ($user != 'none' && $user != '') {
			$devices = get_devices($user);
			debug("Set hints for user: $user for devices:  ".$devices,5);
			set_hint($user, $devices);
		}
	}

	//---------------------------------------------------------------------

	function parse_amportal_conf_bootstrap($filename) {
		$file = file($filename);
		foreach ($file as $line) {
			if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\*\%-]*)\"?\s*([;#].*)?/",$line,$matches)) {
				$conf[ $matches[1] ] = $matches[2];
			}
		}
		if ( !isset($conf["AMPWEBROOT"]) || ($conf["AMPWEBROOT"] == "")) {
			$conf["AMPWEBROOT"] = "/var/www/html";
		} else {
			$conf["AMPWEBROOT"] = rtrim($conf["AMPWEBROOT"],'/');
		}
		return $conf;
	}

	// Set the hint for a user based on the devices in their AMPUSER object
	//
	function set_hint($user, $devices) {
		debug("set_hint: user: $user, devices: $devices",8);
		global $astman;
		global $dnd_mode;
		global $intercom_code;

		$dnd_string = ($dnd_mode == 'dnd')?"&Custom:DND$user":'';

		if ($devices) {
			$dial_string = get_dial_string($devices);
			echo "exten => $user,hint,$dial_string"."$dnd_string\n";
			if ($intercom_code != 'nointercom' && $intercom_code != '') {
				echo "exten => $intercom_code"."$user,hint,$dial_string"."$dnd_string\n";
			}
		} else if ($dnd_mode == 'dnd') {
			echo "exten => $user,hint,Custom:DND$user\n";
			if ($intercom_code != 'nointercom' && $intercom_code != '') {
				echo "exten => $intercom_code"."$user,hint,Custom:DND$user\n";
			}
		}
	}

	// Get the actual technology dialstrings from the DEVICE objects (used
	// to create proper hints)
	//
	function get_dial_string($devices) {
		debug("get_dial_string: devices: $devices",8);
		global $astman;
		global $ast_with_dahdi;

		$device_array = explode( '&', $devices );
		foreach ($device_array as $adevice) {
			$dds = $astman->database_get('DEVICE',$adevice.'/dial');
			$dialstring .= $dds.'&';
		}
		if ($ast_with_dahdi) {
			$dialstring = str_replace('ZAP/', 'DAHDI/', $dialstring);
		}
		return trim($dialstring," &");
	}

	// Get the list of current devices for this user
	//
	function get_devices($user) {
		debug("get_devices: user: $user", 8);
		global $astman;

		$devices = $astman->database_get('AMPUSER',$user.'/device');
		return trim($devices);
	}

	function debug($string, $level=3) {
		global $debug;
		if ($debug >= $level) {
			echo $string."\n";
		}
	}

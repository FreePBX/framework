#!/usr/bin/php -q
<?php

	$debug = -1;
	$include_mode = true;

	$amp_conf = parse_amportal_conf_bootstrap("/etc/amportal.conf");

	require_once($amp_conf['AMPWEBROOT'].'/admin/common/php-asmanager.php');
	$astman         = new AGI_AsteriskManager();
	if (! $res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		exit;
	}

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
		if (!isset($conf['ASTAGIDIR']) || $conf['ASTAGIDIR'] == '') {
			$conf['ASTAGIDIR'] = '/var/lib/asterisk/agi-bin';
		}

		return $conf;
	}

	// Set the hint for a user based on the devices in their AMPUSER object
	//
	function set_hint($user, $devices) {
		debug("set_hint: user: $user, devices: $devices",8);
		global $astman;

		if ($devices) {
			$dial_string = get_dial_string($devices);
			echo "exten => $user,hint,$dial_string\n";
		}
	}

	// Get the actual technology dialstrings from the DEVICE objects (used
	// to create proper hints)
	//
	function get_dial_string($devices) {
		debug("get_dial_string: devices: $devices",8);
		global $astman;

		$device_array = explode( '&', $devices );
		foreach ($device_array as $adevice) {
			$dds = $astman->database_get('DEVICE',$adevice.'/dial');
			$dialstring .= $dds.'&';
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

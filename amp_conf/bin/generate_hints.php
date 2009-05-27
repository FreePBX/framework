#!/usr/bin/php -q
<?php

	$debug = -1;

	// If set to nointercom then don't generate any hints
	//
	$intercom_code = isset($argv[1]) ? $argv[1] : '';
	$dnd_mode      = isset($argv[2]) ? $argv[2] : '';

	$amp_conf = parse_amportal_conf_bootstrap("/etc/amportal.conf");

	require_once($amp_conf['AMPWEBROOT'].'/admin/common/php-asmanager.php');
	$astman         = new AGI_AsteriskManager();
	if (! $res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
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
	function ast_with_dahdi() {
		global $astman;
		global $amp_conf;
	
		if (!$amp_conf['ZAP2DAHDICOMPAT']) {
			return false;
		}
	
		$engine_info = engine_getinfo();
		$version = $engine_info['version'];
		
		if (version_compare($version, '1.4', 'ge') && $amp_conf['AMPENGINE'] == 'asterisk') {		
			if (isset($astman) && $astman->connected()) {
				$response = $astman->send_request('Command', array('Command' => 'module show like chan_dahdi'));
				if (preg_match('/1 modules loaded/', $response['data'])) {
					return true;
				}
			}
		}
		return false;
	}

	function engine_getinfo() {
		global $amp_conf;
		global $astman;

		switch ($amp_conf['AMPENGINE']) {
			case 'asterisk':
				if (isset($astman) && $astman->connected()) {
					//get version (1.4)
					$response = $astman->send_request('Command', array('Command'=>'core show version'));
					if (preg_match('/No such command/',$response['data'])) {
						// get version (1.2)
						$response = $astman->send_request('Command', array('Command'=>'show version'));
					}
					$verinfo = $response['data'];
				} else {
					// could not connect to asterisk manager, try console
					$verinfo = exec('asterisk -V');
				}
			
				if (preg_match('/Asterisk (\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[4], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk SVN-(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[4], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk SVN-branch-(\d+(\.\d+)*)-r(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => $matches[1].'.'.$matches[4], 'additional' => $matches[4], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk SVN-trunk-r(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => '1.6', 'additional' => $matches[1], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk SVN-.+-(\d+(\.\d+)*)-r(-?(\S*))-(.+)/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => $matches[1], 'additional' => $matches[3], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk [B].(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => '1.2', 'additional' => $matches[3], 'raw' => $verinfo);
				} elseif (preg_match('/Asterisk [C].(\d+(\.\d+)*)(-?(\S*))/', $verinfo, $matches)) {
					return array('engine'=>'asterisk', 'version' => '1.4', 'additional' => $matches[3], 'raw' => $verinfo);
				}

				return array('engine'=>'ERROR-UNABLE-TO-PARSE', 'version'=>'0', 'additional' => '0', 'raw' => $verinfo);
			break;
		}
		return array('engine'=>'ERROR-UNSUPPORTED-ENGINE-'.$amp_conf['AMPENGINE'], 'version'=>'0', 'additional' => '0', 'raw' => $verinfo);
	}

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
		if (!isset($conf['ZAP2DAHDICOMPAT'])) {
			$conf['ZAP2DAHDICOMPAT'] = false;
		} else {
			switch (strtoupper(trim($conf['ZAP2DAHDICOMPAT']))) {
				case '1':
				case 'TRUE':
				case 'ON':
					$conf['ZAP2DAHDICOMPAT'] = true;
					break;
				default:
					$conf['ZAP2DAHDICOMPAT'] = false;
			}
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

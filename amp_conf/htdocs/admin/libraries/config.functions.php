<?php

$amp_conf_defaults = array(
	'AMPDBENGINE'    => array('std' , 'mysql'),
	'AMPDBNAME'      => array('std' , 'asterisk'),
	'AMPENGINE'      => array('std' , 'asterisk'),
	'ASTMANAGERPORT' => array('std' , '5038'),
	'ASTMANAGERHOST' => array('std' , 'localhost'),
	'AMPDBHOST'      => array('std' , 'localhost'),
	'AMPDBUSER'      => array('std' , 'asteriskuser'),
	'AMPDBPASS'      => array('std' , 'amp109'),
	'AMPMGRUSER'     => array('std' , 'admin'),
	'AMPMGRPASS'     => array('std' , 'amp111'),
	'FOPPASSWORD'    => array('std' , 'passw0rd'),
	'FOPSORT'        => array('std' , 'extension'),
	'AMPSYSLOGLEVEL '=> array('std' , 'LOG_ERR'),
	'ARI_ADMIN_PASSWORD' => array('std' , 'ari_password'),

	'ASTETCDIR'      => array('dir' , '/etc/asterisk'),
	'ASTMODDIR'      => array('dir' , '/usr/lib/asterisk/modules'),
	'ASTVARLIBDIR'   => array('dir' , '/var/lib/asterisk'),
	'ASTAGIDIR'      => array('dir' , '/var/lib/asterisk/agi-bin'),
	'ASTSPOOLDIR'    => array('dir' , '/var/spool/asterisk/'),
	'ASTRUNDIR'      => array('dir' , '/var/run/asterisk'),
	'ASTLOGDIR'      => array('dir' , '/var/log/asterisk'),
	'AMPBIN'         => array('dir' , '/var/lib/asterisk/bin'),
	'AMPSBIN'        => array('dir' , '/usr/sbin'),
	'AMPWEBROOT'     => array('dir' , '/var/www/html'),
	'FOPWEBROOT'     => array('dir' , '/var/www/html/panel'),
	'MOHDIR'         => array('dir' , '/mohmp3'),
	'FPBXDBUGFILE'	 => array('dir' , '/tmp/freepbx_debug.log'),

	'USECATEGORIES'  => array('bool' , true),
	'ENABLECW'       => array('bool' , true),
	'CWINUSEBUSY'    => array('bool' , true),
	'FOPRUN'         => array('bool' , true),
	'AMPBADNUMBER'   => array('bool' , true),
	'DEVEL'          => array('bool' , false),
	'DEVELRELOAD'    => array('bool' , false),
	'CUSTOMASERROR'  => array('bool' , true),
	'DYNAMICHINTS'   => array('bool' , false),
	'BADDESTABORT'   => array('bool' , false),
	'SERVERINTITLE'  => array('bool' , false),
	'XTNCONFLICTABORT' => array('bool' , false),
	'USEDEVSTATE'    => array('bool' , false),
	'MODULEADMINWGET'=> array('bool' , false),
	'AMPDISABLELOG'  => array('bool' , true),
	'AMPENABLEDEVELDEBUG'=> array('bool' , false),
	'AMPMPG123'       => array('bool' , true),
	'FOPDISABLE'      => array('bool' , false),
	'ZAP2DAHDICOMPAT' => array('bool' , false),
	'USEQUEUESTATE'   => array('bool' , false),
	'CHECKREFERER'    => array('bool' , true),
	'USEDIALONE'      => array('bool' , false),
	'RELOADCONFIRM'   => array('bool' , true),
	'DISABLECUSTOMCONTEXTS'   => array('bool' , false),

);

function parse_amportal_conf($filename) {
	global $amp_conf_defaults;

	/* defaults
	 * This defines defaults and formating to assure consistency across the system so that
	 * components don't have to keep being 'gun shy' about these variables.
	 * 
	 */
	$file = file($filename);
	if (is_array($file)) {
		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_!<>\"\']+)\s*$/",$line,$matches)) {
				$conf[ $matches[1] ] = $matches[2];
			}
		}
	} else {
		die_freepbx("<h1>".sprintf(_("Missing or unreadable config file (%s)...cannot continue"), $filename)."</h1>");
	}
	
	// set defaults
	foreach ($amp_conf_defaults as $key=>$arr) {

		switch ($arr[0]) {
			// for type dir, make sure there is no trailing '/' to keep consistent everwhere
			//
			case 'dir':
				if (!isset($conf[$key]) || trim($conf[$key]) == '') {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = rtrim($conf[$key],'/');
				}
				break;
			// booleans:
			// "yes", "true", "on", true, 1 (case-insensitive) will be treated as true, everything else is false
			//
			case 'bool':
				if (!isset($conf[$key])) {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = ($conf[$key] === true || strtolower($conf[$key]) == 'true' || $conf[$key] === 1 || $conf[$key] == '1' 
					                                    || strtolower($conf[$key]) == 'yes' ||  strtolower($conf[$key]) == 'on');
				}
				break;
			default:
				if (!isset($conf[$key])) {
					$conf[$key] = $arr[1];
				} else {
					$conf[$key] = trim($conf[$key]);
				}
		}
	}
	return $conf;
}

function parse_asterisk_conf($filename) {
	//TODO: Should the correction of $amp_conf be passed by refernce and optional?
	//
	global $amp_conf;
	$conf = array();
		
	$convert = array(
		'astetcdir'    => 'ASTETCDIR',
		'astmoddir'    => 'ASTMODDIR',
		'astvarlibdir' => 'ASTVARLIBDIR',
		'astagidir'    => 'ASTAGIDIR',
		'astspooldir'  => 'ASTSPOOLDIR',
		'astrundir'    => 'ASTRUNDIR',
		'astlogdir'    => 'ASTLOGDIR'
	);

	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([a-zA-Z0-9]+)\s* => \s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			$conf[ $matches[1] ] = rtrim($matches[2],"/ \t");
		}
	}

	// Now that we parsed asterisk.conf, we need to make sure $amp_conf is consistent
	// so just set it to what we found, since this is what asterisk will use anyhow.
	//
	foreach ($convert as $ast_conf_key => $amp_conf_key) {
		if (isset($conf[$ast_conf_key])) {
			$amp_conf[$amp_conf_key] = $conf[$ast_conf_key];
		}
	}
	return $conf;
}

/** Replaces variables in a string with the values from ampconf
 * eg, "%AMPWEBROOT%/admin" => "/var/www/html/admin"
 */
function ampconf_string_replace($string) {
	global $amp_conf;
	
	$target = array();
	$replace = array();
	
	foreach ($amp_conf as $key=>$value) {
		$target[] = '%'.$key.'%';
		$replace[] = $value;
	}
	
	return str_replace($target, $replace, $string);
}

/** Expands variables from amportal.conf 
 * Replaces any variables enclosed in percent (%) signs with their value
 * eg, "%AMPWEBROOT%/admin/functions.inc.php"
 */
//TODO: seems this the exact same as the above function. Should either be removed?
function expand_variables($string) {
	return ampconf_string_replace($string);
}
?>
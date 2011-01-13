<?php
/* Bootstrap Settings:
 *
 * bootstrap_settings['skip_astman']           - legacy $skip_astman, default false
 *
 * bootstrap_settings['astman_config']         - default null, config arguemnt when creating new Astman
 * bootstrap_settings['astman_options']        - default array(), config options creating new Astman
 *                                               e.g. array('cachemode' => true), see astman documentation
 * bootstrap_settings['astman_events']         - default 'off' used when connecting, Astman defaults to 'on'
 *
 * bootstrap_settings['freepbx_error_handler'] - false don't set it, true use default, named use what is passed
 *
 * bootstrap_settings['freepbx_auth']          - true (default) - authorize, false - bypass authentication
 *
 * $restrict_mods: false means include all modules functions.inc.php, true skip all modules
 *                 array of hashes means each module where there is a hash
 *                 e.g. $restrict_mods = array('core' => true, 'dashboard' => true)
 */

if (!isset($bootstrap_settings['skip_astman'])) {
  $bootstrap_settings['skip_astman'] = isset($skip_astman) ? $skip_astman : false;
}
$bootstrap_settings['astman_config'] = isset($bootstrap_settings['astman_config']) ? $bootstrap_settings['astman_config'] : null;
$bootstrap_settings['astman_options'] = isset($bootstrap_settings['astman_options']) && is_array($bootstrap_settings['astman_options']) ? $bootstrap_settings['astman_options'] : array();
$bootstrap_settings['astman_events'] = isset($bootstrap_settings['astman_events']) ? $bootstrap_settings['astman_events'] : 'off';

$bootstrap_settings['freepbx_error_handler'] = isset($bootstrap_settings['freepbx_error_handler']) ? $bootstrap_settings['freepbx_error_handler'] : true;
$bootstrap_settings['freepbx_auth'] = isset($bootstrap_settings['freepbx_auth']) ? $bootstrap_settings['freepbx_auth'] : true;


$restrict_mods = isset($restrict_mods) ? $restrict_mods : false;



//enable error reporting and start benchmarking
error_reporting(1);
function microtime_float() { list($usec,$sec) = explode(' ',microtime()); return ((float)$usec+(float)$sec); }
$benchmark_starttime = microtime_float();

// include base functions
require_once(dirname(__FILE__) . '/functions.inc.php');
$bootstrap_settings['framework_functions_included'] = true;

//now that its been included, use our own error handler as it tends to be much more verbose.
if ($bootstrap_settings['freepbx_error_handler']) {
  $error_handler = $bootstrap_settings['freepbx_error_handler'] === true ? 'freepbx_error_handler' : $bootstrap_settings['freepbx_error_handler'];
  if (function_exists($error_handler)) {
    set_error_handler($error_handler, E_ALL & ~E_STRICT);
  }
}

//include database conifguration
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}

// connect to database
require_once(dirname(__FILE__) . '/common/db_connect.php'); //PEAR must be installed
//keep old values as well so that we have the db settings handy
// get settings
$freepbx_conf =& freepbx_conf::create();

// passing by reference, this means that the $amp_conf available to everyone is the same one as present
// within the class, which is probably a direction we want to go to use the class.
//
$amp_conf =& $freepbx_conf->parse_amportal_conf("/etc/amportal.conf",$amp_conf);
$asterisk_conf =& $freepbx_conf->get_asterisk_conf();
$bootstrap_settings['amportal_conf_initialized'] = true;

//set error reporting level if set
if (isset($amp_conf['php_error_reporting'])) {
	error_reporting($amp_conf['php_error_reporting']);
}

if (!$bootstrap_settings['skip_astman']) {
	require_once(dirname(__FILE__) . '/libraries/php-asmanager.php');
  $astman	= new AGI_AsteriskManager($bootstrap_settings['astman_config'], $bootstrap_settings['astman_options']);
	// attempt to connect to asterisk manager proxy
	if (!isset($amp_conf["ASTMANAGERPROXYPORT"]) || !$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPROXYPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], $bootstrap_settings['astman_events'])) {
		// attempt to connect directly to asterisk, if no proxy or if proxy failed
		if (!$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], $bootstrap_settings['astman_events'])) {
			// couldn't connect at all
			unset( $astman );
			$bootstrap_settings['astman_connected'] = false;
		} else {
			$bootstrap_settings['astman_connected'] = true;
		}
	}
} else {
	$bootstrap_settings['astman_connected'] = true;
}

//include gui functions + auth if nesesarry
// If set to freepbx_auth but we are in a cli mode, then don't bother authenticating either way.
// TODO: is it ever possible through an apache or httplite configuration to run a web launched php script
//       as 'cli' ? Also, from a security perspective, should we just require this always be set to false
//       if we want to bypass authentication and not try to be automatic about it?
//
if (!$bootstrap_settings['freepbx_auth'] || (php_sapi_name() == 'cli')) {
	if (!defined('FREEPBX_IS_AUTH')) {
		define('FREEPBX_IS_AUTH', 'TRUE');
	}
} else {
	require(dirname(__FILE__) . '/libraries/framework_view.functions.php');
	frameworkPasswordCheck();
}
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }//we should never need this, just another line of defence

// I'm pretty sure if this is == true then there is no need to even pull all the module info as we are going down a path
// such as an ajax path that this is just overhead. (We'll no soon enough if this is too restrcitive).
//
if ($restrict_mods !== true) {
  $active_modules = module_getinfo(false, MODULE_STATUS_ENABLED);

  if(is_array($active_modules)){

	  foreach($active_modules as $key => $module) {
		  //include module functions if there not dissabled
      if ((!$restrict_mods || (is_array($restrict_mods) && isset($restrict_mods[$key]))) && is_file($amp_conf['AMPWEBROOT']."/admin/modules/{$key}/functions.inc.php")) {
        require_once($amp_conf['AMPWEBROOT']."/admin/modules/{$key}/functions.inc.php");
      } 
		
		  //create an array of module sections to display
		  // stored as [items][$type][$category][$name] = $displayvalue
		  if (isset($module['items']) && is_array($module['items'])) {
			  // loop through the types
			  foreach($module['items'] as $itemKey => $item) {
				
				  //if asterisk isnt running, mark moduels that depend on asterisk as disbaled
				  if (!isset($astman) || !$astman) {
					  if (( isset($item['needsenginedb']) && strtolower($item['needsenginedb']) == 'yes') 
					  || (isset($item['needsenginerunning']) && strtolower($item['needsenginerunning']) == 'yes')) {
						  $active_modules[$key]['items'][$itemKey]['disabled'] = true;
					  }
				  }
				
				  // reference to the actual module
				  //$active_modules[$key][$itemKey]['module'] =& $active_modules[$key];

			  }
		  }
	  }
	  $bootstrap_settings['function_modules_included'] = true;
  }
} else {
	$bootstrap_settings['function_modules_included'] = false;
}
?>

<?php /* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca) 
//Copyright (C) 2006-2010 Philippe Lindheimer 
/*
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

$type = isset($_REQUEST['type'])?$_REQUEST['type']:'setup';
$display = isset($_REQUEST['display'])?$_REQUEST['display']:'';
if (isset($_REQUEST['extdisplay'])) {
	$extdisplay = htmlspecialchars($_REQUEST['extdisplay'],ENT_QUOTES);
	$_REQUEST['extdisplay'] = $extdisplay;
} else {
	$extdisplay = null;
}
$skip = isset($_REQUEST['skip'])?$_REQUEST['skip']:0;
$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
$quietmode = isset($_REQUEST['quietmode'])?$_REQUEST['quietmode']:'';
$bootstrap_settings['skip_astman'] = isset($_REQUEST['skip_astman'])?$_REQUEST['skip_astman']:false;
if (isset($_REQUEST['restrictmods'])) {
	$restrict_mods = explode('/',$_REQUEST['restrictmods']);
	$restrict_mods = array_flip($restrict_mods);
} else {
	$restrict_mods = false;
}

// determine module type to show, default to 'setup'
$type_names = array(
	'tool'=>_('Tools'),
	'setup'=>_('Setup'),
	'cdrcost'=>_('Call Cost'),
);

@header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
@header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
@header('Cache-Control: post-check=0, pre-check=0',false);
@header('Pragma: no-cache');
//session_cache_limiter('public, no-store');
if (isset($_REQUEST['handler'])) {
  $restrict_mods = true;
  // I think reload is the only handler that requires astman, so skip it for others
  switch ($_REQUEST['handler']) {
  case 'reload':
    break;
  default:
    $bootstrap_settings['skip_astman'] = true;
  }
}
require('bootstrap.php');

/* If there is an action request then some sort of update is usually being done.
   This will protect from cross site request forgeries unless disabled.
*/
if ($action != '' && $amp_conf['CHECKREFERER']) {
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = parse_url($_SERVER['HTTP_REFERER']);
		$refererok = (trim($referer['host']) == trim($_SERVER['SERVER_NAME'])) ? true : false;
	} else {
		$refererok = false;
	}

	if (!$refererok) {
		showview('bad_refferer');
		exit;
	}
}

// handle special requests
if (isset($_REQUEST['handler'])) {
	$module = isset($_REQUEST['module'])	? $_REQUEST['module']	: '';
	$file 	= isset($_REQUEST['file'])		? $_REQUEST['file']		: '';
	fileRequestHandler($_REQUEST['handler'], $module, $file);
}
	
if (!$quietmode) {
	module_run_notification_checks();
}

//draw up freepbx menu
$fpbx_menu = array();
// pointer to current item in $fpbx_menu, if applicable
$cur_menuitem = null;

// add module sections to $fpbx_menu
$types = array();

if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
	
		//create an array of module sections to display
		// stored as [items][$type][$category][$name] = $displayvalue
		if (isset($module['items']) && is_array($module['items'])) {
			// loop through the types
			foreach($module['items'] as $itemKey => $item) {

				// check access, unless module.xml defines all have access
				//TODO: move this to bootstrap and make it work
				if (!isset($item['access']) || strtolower($item['access']) != 'all') {
					if (!$_SESSION["AMP_user"]->checkSection($itemKey)) {
						// no access, skip to the next 
						continue;
					}
				}

				if (!in_array($item['type'], $types)) {
					$types[] = $item['type'];
				}
				
				if (!isset($item['display'])) {
					$item['display'] = $itemKey;
				}
				
				// reference to the actual module
				$item['module'] =& $active_modules[$key];
				
				// item is an assoc array, with at least array(module=> name=>, category=>, type=>, display=>)
				$fpbx_menu[$itemKey] = $item;
				
				// allow a module to replace our main index page
				if (($item['display'] == 'index') && ($display == '')) {
					$display = 'index';
				}
				
				// check current item
				if ($display == $item['display']) {
					// found current menuitem, make a reference to it 
					$cur_menuitem =& $fpbx_menu[$itemKey];
				}
			}
		}
	}
}

sort($types);

// new gui hooks
if(!$quietmode && is_array($active_modules)){
	foreach($active_modules as $key => $module) {

		if (isset($module['items']) && is_array($module['items'])) {
			foreach($module['items'] as $itemKey => $itemName) {
				//list of potential _configpageinit functions
				$initfuncname = $key . '_' . $itemKey . '_configpageinit';
				if ( function_exists($initfuncname) ) {
					$configpageinits[] = $initfuncname;
				}
			}
		}
		//check for module level (rather than item as above) _configpageinit function
		$initfuncname = $key . '_configpageinit';
		if ( function_exists($initfuncname) ) {
			$configpageinits[] = $initfuncname;
		}
	}
}

// extensions vs device/users ... this is a bad design, but hey, it works
if (!$quietmode) {
	if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
		unset($fpbx_menu["extensions"]);
	} else {
		unset($fpbx_menu["devices"]);
		unset($fpbx_menu["users"]);
	}
}

// check access
if (!is_array($cur_menuitem) && $display != "") {
	showview("noaccess");
	exit;
}

// load the component from the loaded modules
if ( $display != '' && isset($configpageinits) && is_array($configpageinits) ) {

	$currentcomponent = new component($display,$type);

	// call every modules _configpageinit function which should just
	// register the gui and process functions for each module, if relevant
	// for this $display
	foreach ($configpageinits as $func) {
		$func($display);
	}
	
	// now run each 'process' function and 'gui' function
	$currentcomponent->processconfigpage();
	$currentcomponent->buildconfigpage();
}

//  note: we buffer all the output from the 'page' being loaded..
// This may change in the future, with proper returns, but for now, it's a simple 
// way to support the old page.item.php include module format.

ob_start();

$module_name = "";
$module_page = "";
$module_file = "";



// hack to have our default display handler show the "welcome" view 
// Note: this probably isn't REALLY needed if there is no menu item for "Welcome"..
// but it doesn't really hurt, and it provides a handler in case some page links
// to "?display=index"
if (($display == 'index') && ($cur_menuitem['module']['rawname'] == 'builtin')) {
	$display = '';
}



// show the appropriate page
switch($display) {
	default:
		//display the appropriate module page
		$module_name = $cur_menuitem['module']['rawname'];
		$module_page = $cur_menuitem['display'];
		$module_file = 'modules/'.$module_name.'/page.'.$module_page.'.php';

		//TODO Determine which item is this module displaying. Currently this is over the place, we should standardize on a "itemid" request var for now, we'll just cover all possibilities :-(
		$possibilites = array(
			'userdisplay',
			'extdisplay',
			'id',
			'itemid',
			'selection'
		);
		$itemid = '';
		foreach($possibilites as $possibility) {
			if ( isset($_REQUEST[$possibility]) && $_REQUEST[$possibility] != '' ) {
				$itemid = htmlspecialchars($_REQUEST[$possibility], ENT_QUOTES);
				$_REQUEST[$possibility] = $itemid;
			}
		}

		// create a module_hook object for this module's page
		$module_hook = new moduleHook;
		
		// populate object variables
		$module_hook->install_hooks($module_page,$module_name,$itemid);

		// let hooking modules process the $_REQUEST
		$module_hook->process_hooks($itemid, $module_name, $module_page, $_REQUEST);


		// include the module page
		if (isset($cur_menuitem['disabled']) && $cur_menuitem['disabled']) {
			showview("menuitem_disabled",$cur_menuitem);
			break; // we break here to avoid the generateconfigpage() below
		} else if (file_exists($module_file)) {
			// load language info if available
			if (extension_loaded('gettext')) {
				if (is_dir("modules/{$module_name}/i18n")) {
					bindtextdomain($module_name,"modules/{$module_name}/i18n");
					bind_textdomain_codeset($module_name, 'utf8');
					textdomain($module_name);
				}
			}
			include($module_file);
		} else {
			// TODO: make this a showview()
			echo "404 Not found";
		}
		
		// global component
		if ( isset($currentcomponent) ) {
			echo $currentcomponent->generateconfigpage();
		}

	break;
	case 'modules':
		// set these to avoid undefined variable warnings later
		//
		$module_name = 'modules';
		$module_page = $cur_menuitem['display'];
		include 'page.modules.php';
	break;
	case '':
		if ($astman) {
			showview('welcome', array('AMP_CONF' => &$amp_conf));
		} else {
			// no manager, no connection to asterisk
			showview('welcome_nomanager', array('mgruser' => $amp_conf["AMPMGRUSER"]));
		}
	break;
}

if ($quietmode) {
	// send the output buffer
	@ob_end_flush();
} else {
	$admin_template = $template = array();
	$admin_template['content'] = ob_get_contents();
	@ob_end_clean();

	// build the admin interface (with menu)
	$admin_template['fpbx_types'] = $types;
	$admin_template['fpbx_type_names'] = $type_names;
	$admin_template['fpbx_menu'] = $fpbx_menu;
	$admin_template['fpbx_usecategories'] = $amp_conf['USECATEGORIES'];
	$admin_template['fpbx_type'] = $type;
	$admin_template['display'] = $display;

	// set the language so local module languages take
	set_language();

	// then load it and put it into the main freepbx interface
	$template['content'] = loadview('freepbx_admin', $admin_template);
	$template['use_nav_background'] = true;

	// setup main template
	$template['module_name'] = $module_name;
	$template['module_page'] = $module_page;
	if ($amp_conf['SERVERINTITLE']) {
		// set the servername
		$server_hostname = '';
		if (isset($_SESSION['session_hostname'])){
			$server_hostname = $_SESSION['session_hostname'];
		}else{
			if (function_exists('gethostname')){
				$server_hostname = trim(gethostname());
			}else{
				$server_hostname = trim(php_uname('n'));
			}
			if ($server_hostname != ''){
				$server_hostname = ' (' . substr($server_hostname, 0, 30) . ')';
			}
			$_SESSION['session_hostname'] = $server_hostname;
		}

		$template['title'] = $_SERVER['SERVER_NAME'] . $server_hostname . ' - ' . _('FreePBX Administration');
	} else {
		$template['title'] = _('FreePBX Administration');
	}
	$template['amp_conf'] = &$amp_conf;
	$template['reload_needed'] = check_reload_needed();
	$template['benchmark_starttime'] = $benchmark_starttime;

	showview('freepbx', $template);
}

?>

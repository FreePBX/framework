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

//set variables
$vars = array(
		'action'		=> null,
		'display'		=> '',
		'extdisplay'	=> null,
		'logout'		=> false,
		'password'		=> '',
		'quietmode'		=> '',
		'restrictmods'	=> false,
		'skip'			=> 0,
		'skip_astman'	=> false,
		'username'		=> '',
		'type'			=> ''
		);

foreach ($vars as $k => $v) {
	$$k = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
	
	//special handeling
	switch ($k) {
		case 'extdisplay':
			$extdisplay		= $extdisplay 
							?  htmlspecialchars($extdisplay, ENT_QUOTES) 
							: false;
			$_REQUEST['extdisplay'] = $extdisplay;
			break;

		case 'restrictmods':
			$restrict_mods	= $restrictmods 
							? array_flip(explode('/', $restrictmods)) 
							: false;
			break;
			
		case 'skip_astman':
			$bootstrap_settings['skip_astman']	= $skip_astman;
			break;
	}
}

header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

// This needs to be included BEFORE the session_start or we fail so
// we can't do it in bootstrap and thus we have to depend on the
// __FILE__ path here.
require_once(dirname(__FILE__) . '/libraries/ampuser.class.php');

session_set_cookie_params(60 * 60 * 24 * 30);//(re)set session cookie to 30 days
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);//(re)set session to 30 days
if (!isset($_SESSION)) {
	//start a session if we need one
    session_start();
}

//unset the ampuser if the user logged out
if ($logout == 'true') {
	unset($_SESSION['AMP_user']);
	exit();
}

//session_cache_limiter('public, no-store');
if (isset($_REQUEST['handler'])) {
	$restrict_mods = true;
	// I think reload is the only handler that requires astman, so skip it for others
	switch ($_REQUEST['handler']) {
		case 'api':
			$restrict_mods = false;
			break;
		case 'reload';
			break;
		default:
			$bootstrap_settings['skip_astman'] = true;
			break;
	}
}

// call bootstrap.php through freepbx.conf
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) { 
	 	  include_once('/etc/asterisk/freepbx.conf'); 
} 

/* If there is an action request then some sort of update is usually being done.
   This will protect from cross site request forgeries unless disabled.
*/
$badrefer = false;
if (!isset($no_auth) && $action != '' && $amp_conf['CHECKREFERER']) {
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = parse_url($_SERVER['HTTP_REFERER']);
		$refererok = (trim($referer['host']) == trim($_SERVER['SERVER_NAME'])) ? true : false;
	} else {
		$refererok = false;
	}
	if (!$refererok) {
		$badrefer = load_view($amp_conf['VIEW_BAD_REFFERER'], $amp_conf);
	}
}

// handle special requests
if (!isset($no_auth) && isset($_REQUEST['handler']) && !$badrefer) {
	$module = isset($_REQUEST['module'])	? $_REQUEST['module']	: '';
	$file 	= isset($_REQUEST['file'])		? $_REQUEST['file']		: '';
	fileRequestHandler($_REQUEST['handler'], $module, $file);
	exit();
}

$fw_gui_html = '';
//buffer & compress our responce
ob_start();

if (!$quietmode) {	
	//send header
	$header['title']	= framework_server_name();
	$header['amp_conf']	= $amp_conf;
	$fw_gui_html .=		load_view($amp_conf['VIEW_HEADER'], $header);
	
	if ($badrefer) {
		$fw_gui_html .= load_view($amp_conf['VIEW_MENU'], $header);
		$fw_gui_html .= $badrefer;
	} elseif (isset($no_auth)) {
		$fw_gui_html .= load_view($amp_conf['VIEW_MENU'], $header);
		$fw_gui_html .= $no_auth;
	}
	if ($badrefer || isset($no_auth)) {
		$footer['footer_content']	= load_view($amp_conf['VIEW_FOOTER_CONTENT']);
		$footer['no_auth']	= $no_auth;
		$fw_gui_html .= load_view($amp_conf['VIEW_FOOTER'], $footer);
		echo $fw_gui_html;
		exit();
	}
	module_run_notification_checks();
}
$fw_gui_html .= ob_get_contents();
ob_end_clean();

//draw up freepbx menu
$fpbx_menu = array();

// pointer to current item in $fpbx_menu, if applicable
$cur_menuitem = null;

// add module sections to $fpbx_menu

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
					if (is_object($_SESSION["AMP_user"]) && !$_SESSION["AMP_user"]->checkSection($itemKey)) {
						// no access, skip to the next 
						continue;
					}
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


// new gui hooks
if(!$quietmode && is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		modgettext::push_textdomain($module['rawname']);
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
		modgettext::pop_textdomain();
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
	$fw_gui_html .= show_view($amp_conf['VIEW_NOACCESS'], array('amp_conf'=>&$amp_conf));
}

// load the component from the loaded modules
if ($display != '' && isset($configpageinits) && is_array($configpageinits) ) {

	$CC = $currentcomponent = new component($display,$type);
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
ob_start();
$module_name = "";
$module_page = "";
$module_file = "";



// hack to have our default display handler show the "welcome" view 
// Note: this probably isn't REALLY needed if there is no menu item for "Welcome"..
// but it doesn't really hurt, and it provides a handler in case some page links
// to "?display=index"
if ($display == 'index' && ($cur_menuitem['module']['rawname'] == 'builtin')) {
	$display = '';
}

// show the appropriate page
switch($display) {
	default:
		//display the appropriate module page
		$module_name = $cur_menuitem['module']['rawname'];
		$module_page = $cur_menuitem['display'];
		$module_file = 'modules/'.$module_name.'/page.'.$module_page.'.php';

		//TODO Determine which item is this module displaying. 
		//Currently this is over the place, we should standardize on a "itemid" request var 
		//for now, we'll just cover all possibilities :-(
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
			show_view($amp_conf['VIEW_MENUITEM_DISABLED'], $cur_menuitem);
			break; // we break here to avoid the generateconfigpage() below
		} else if (file_exists($module_file)) {
			// load language info if available
			modgettext::textdomain($module_name);
			include($module_file);
		} else {
			echo "404 Not found (" . $module_file  . ')';
		}
		
		// global component
		if ( isset($currentcomponent) ) {
			echo  $currentcomponent->generateconfigpage();
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
			show_view($amp_conf['VIEW_WELCOME'], array('AMP_CONF' => &$amp_conf));
		} else {
			// no manager, no connection to asterisk
			show_view($amp_conf['VIEW_WELCOME_NOMANAGER'], array('mgruser' => $amp_conf["AMPMGRUSER"]));
		}
		break;
}

if ($quietmode) {
	// send the output buffer
	ob_end_flush();
} else {
	$admin_template 				= $template = array();
	$content		 				= ob_get_contents();
	ob_end_clean();
	//now restart buffering so that our data is compressed again
    ob_start();

	//if we have a module loaded, load its css
	if (isset($module_name)) {
		$fw_gui_html .= framework_include_css();
	}

	// send menu
	$menu['fpbx_menu']				= $fpbx_menu; //array of modules & settings
	$menu['display']				= $display; //currently displayed item
	$menu['authtype']				= $amp_conf['AUTHTYPE'];
	$menu['reload_confirm']			= $amp_conf['RELOADCONFIRM'];

	// set the language so local module languages take
	set_language();

	// menu + page content + footer
	
	$fw_gui_html .=						load_view($amp_conf['VIEW_MENU'], $menu);
	
	//send actual page content
	$fw_gui_html .=						$content;
		 
	//send footer
	$footer['module_name']			= $module_name;
	$footer['module_page']			= $module_page;
	$footer['benchmark_starttime']	= $benchmark_starttime;
	$footer['reload_needed']		= check_reload_needed();
	$footer['footer_content']		= load_view($amp_conf['VIEW_FOOTER_CONTENT'], $footer);
	$fw_gui_html 					.= load_view($amp_conf['VIEW_FOOTER'], $footer);


	//$template['benchmark_starttime']	= $benchmark_starttime;

}

echo $fw_gui_html;
?>

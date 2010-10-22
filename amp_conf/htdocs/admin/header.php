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

@header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); 
@header('Expires: Sat, 01 Jan 2000 00:00:00 GMT'); 
@header('Cache-Control: post-check=0, pre-check=0',false); 
@header('Pragma: no-cache'); 
header('Content-type: text/html; charset=utf-8');
//session_cache_limiter('public, no-store'); 

// include base functions
require_once('functions.inc.php');

// get settings
$amp_conf	= parse_amportal_conf("/etc/amportal.conf");
$asterisk_conf  = parse_asterisk_conf($amp_conf["ASTETCDIR"]."/asterisk.conf");
if (!$skip_astman) {
	require_once('common/php-asmanager.php');
	$astman	= new AGI_AsteriskManager();

	// attempt to connect to asterisk manager proxy
  if (!isset($amp_conf["ASTMANAGERPROXYPORT"]) || !$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPROXYPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"],'off')) {
		// attempt to connect directly to asterisk, if no proxy or if proxy failed
		if (!$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], 'off')) {
			// couldn't connect at all
			unset( $astman );
		}
	}
}

// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

// setup locale
set_language();

// default password check, first for Asterisk Manager, then for ARI
if (!$quietmode && !isset($_REQUEST['handler'])) {
	$nt = notifications::create($db);
	if ($amp_conf['AMPMGRPASS'] == $amp_conf_defaults['AMPMGRPASS'][1]) {
		$nt->add_warning('core', 'AMPMGRPASS', _("Default Asterisk Manager Password Used"), _("You are using the default Asterisk Manager password that is widely known, you should set a secure password"));
	} else {
		$nt->delete('core', 'AMPMGRPASS');
	}
}

if (!$quietmode && !isset($_REQUEST['handler'])) {
	$nt = notifications::create($db);
	if ($amp_conf['ARI_ADMIN_PASSWORD'] == $amp_conf_defaults['ARI_ADMIN_PASSWORD'][1]) {
		$nt->add_warning('ari', 'ARI_ADMIN_PASSWORD', _("Default ARI Admin password Used"), _("You are using the default ARI Admin password that is widely known, you should change to a new password. Do this in amportal.conf"));
	} else {
		$nt->delete('ari', 'ARI_ADMIN_PASSWORD');
	}
}

// always run a session
@session_start();

// do authentication - header_auth exits if unauthorized
include('header_auth.php');


/** Loads a view (from the views/ directory) with a number of named parameters created as local variables.
 * @param  string   The name of the view.
 * @param  array    The parameters to pass. Note that the key will be turned into a variable name for use by the view.
 *                  For example, passing array('foo'=>'bar'); will create a variable $foo that can be used by
 *                  the code in the view.
 */
function loadview($viewname, $parameters = false) {
	ob_start();
	showview($viewname, $parameters);
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}
/** Outputs the contents of a view.
 * @param  string   The name of the view.
 * @param  array    The parameters to pass. Note that the key will be turned into a variable name for use by the view.
 *                  For example, passing array('foo'=>'bar'); will create a variable $foo that can be used by
 *                  the code in the view.
 */
function showview($viewname, $parameters = false) {
	if (is_array($parameters)) {
		extract($parameters);
	}
	
	$viewname = str_replace('..','.',$viewname); // protect against going to subdirectories
	if (file_exists('views/'.$viewname.'.php')) {
		include('views/'.$viewname.'.php');
	}
}

// setup locale
function set_language() {
	if (extension_loaded('gettext')) {
		if (isset($_COOKIE['lang'])) {
			setlocale(LC_ALL,  $_COOKIE['lang']);
			putenv("LANGUAGE=".$_COOKIE['lang']);
		} else {
			setlocale(LC_ALL,  'en_US');
		}
		bindtextdomain('amp','./i18n');
		bind_textdomain_codeset('amp', 'utf8');
		textdomain('amp');
	}
}

?>

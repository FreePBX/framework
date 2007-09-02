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
//session_cache_limiter('public, no-store'); 

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

//get the current file name
$currentFile = $_SERVER["PHP_SELF"];
$parts = explode('/', $currentFile);
//header('Content-type: text/html; charset=utf-8');
$currentFile = $parts[count($parts) - 1];
//todo: can this be removed? what is it used for?


// Emulate gettext extension functions if gettext is not available
if (!function_exists('_')) {
	function _($str) {
		return $str;
	}
}
if (!function_exists('gettext')) {
	function gettext($message) {
		return $message;
	}
}
if (!function_exists('dgettext')) {
	function dgettext($domain, $message) {
		return $message;
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
set_language();


// systems running on sqlite3 (or pgsql) this function is not available
// instead of changing the whole code, lets hack our own version of this function.
// according to the documentation found here: http://il2.php.net/mysql_real_escape_string
// this shold be enough.
// Fixes ticket: http://freepbx.org/trac/ticket/1963
if (!function_exists('mysql_real_escape_string')) {
	function mysql_real_escape_string($str) {
		$str = str_replace( "\x00", "\\" . "\x00", $str );
		$str = str_replace( "\x1a", "\\" . "\x1a", $str );
		$str = str_replace( "\n" , "\\". "\n"    , $str );
		$str = str_replace( "\r" , "\\". "\r"    , $str );
		$str = str_replace( "\\" , "\\". "\\"    , $str );
		$str = str_replace( "'" , "\\". "'"      , $str );
		$str = str_replace( '"' , "\\". '"'      , $str );
		return $str;
	}
}

// include base functions
require_once('functions.inc.php');
require_once('common/php-asmanager.php');

// get settings
$amp_conf	= parse_amportal_conf("/etc/amportal.conf");
$asterisk_conf  = parse_asterisk_conf($amp_conf["ASTETCDIR"]."/asterisk.conf");
$astman		= new AGI_AsteriskManager();

// attempt to connect to asterisk manager proxy
if (!isset($amp_conf["ASTMANAGERPROXYPORT"]) || !$res = $astman->connect("127.0.0.1:".$amp_conf["ASTMANAGERPROXYPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
	// attempt to connect directly to asterisk, if no proxy or if proxy failed
	if (!$res = $astman->connect("127.0.0.1:".$amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		// couldn't connect at all
		unset( $astman );
	}
}
// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

// default password check
$nt = notifications::create($db);
if ($amp_conf['AMPMGRPASS'] == $amp_conf_defaults['AMPMGRPASS'][1]) {
	$nt->add_warning('core', 'AMPMGRPASS', _("Default Asterisk Manager Password Used"), _("You are using the default Asterisk Manager password that is widely known, you should set a secure password"));
} else {
	$nt->delete('core', 'AMPMGRPASS');
}

// always run a session
@session_start();

// do authentication - header_auth exits if unauthorized
include('header_auth.php');

?>

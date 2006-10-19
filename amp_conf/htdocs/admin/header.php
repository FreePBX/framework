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

// helper function, to draw the upper links
function print_sub_tool( $name, $page, $is_current, $href=NULL, $new_window=false )
{
	if (!is_file($page))
		return;

	$html = "<a ";

	if ($href == NULL)
		$href .= $page;

	if ($new_window != NULL)
		$html .= " target=\"_blank\" ";
	
	if ($is_current)
		$html .= "id=current ";
	$html .= "href=\"$href\"> &#8226; <li>$name</li></a>";

	print("\t\t$html\n");
}


//get the current file name
$currentFile = $_SERVER["PHP_SELF"];
$parts = Explode('/', $currentFile);
//header('Content-type: text/html; charset=utf-8');
$currentFile = $parts[count($parts) - 1];

if (!extension_loaded('gettext')) {
	function _($str) {
		return $str;
	}
}

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

if (!$quietmode) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
	<title><?php  echo _($title) ?></title>
	<meta http-equiv="Content-Type" content="text/html">
	<link href="common/mainstyle.css" rel="stylesheet" type="text/css"> 
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<?php 
	if (isset($display) && is_file("modules/{$display}/{$display}.css")) {
		echo "	<link href=\"modules/{$display}/{$display}.css\" rel=\"stylesheet\" type=\"text/css\">\n";
	}
	?>
	
	<script type="text/javascript" src="common/script.js.php"></script>
	<script type="text/javascript"> 
		<!--
		// Disable browser's Back button on another pg being able to go back to this pg.
		history.forward();
		//-->
	</script>
<!--[if IE]>
    <style type="text/css">div.inyourface a{position:absolute;}</style>
<![endif]-->
</head>

<body onload="setAllInfoToHideSelects();"  <?
// Check if it's a RIGHT TO LEFT character set (eg, hebrew, arabic, whatever)
//$_COOKIE['lang']="he_IL";
if ($_COOKIE['lang']==="he_IL") 
	echo "dir=\"rtl\"";
?> >
<div id="page">
	<div class="header">
<?php
		if (isset($amp_conf["AMPADMINLOGO"]) && is_file($amp_conf["AMPWEBROOT"]."/admin/images/".$amp_conf["AMPADMINLOGO"]))
			echo "\t\t<a href=\"index.php\"><img src=\"images/" . $amp_conf["AMPADMINLOGO"] . "\"/></a>\n";
		else
			echo "\t\t<a href=\"index.php\"><img src=\"images/freepbx.png\"/></a>\n";
		
		print_sub_tool( _("Management"), "manage.php" , $currentFile=='manage.php' );
		print_sub_tool( _("Setup")     , "config.php" , $currentFile=='config.php' && $_REQUEST['type']=='setup', "config.php?type=setup", false );
		print_sub_tool( _("Tools")     , "config.php" , $currentFile=='config.php' && $_REQUEST['type']=='tool' , "config.php?type=tool", false );
		print_sub_tool( _("Reports")   , "reports.php", $currentFile=='reports.php' );
		print_sub_tool( _("Panel")     , "panel.php"  , $currentFile=='panel.php' );
		print_sub_tool( _("Recordings"), "../recordings/index.php"  ,0, NULL, true );
?>
	</div>

	<div class="message"><?php
	if ( isset($_SESSION['AMP_user']) &&  $amp_conf['AUTHTYPE'] != 'none' ) {
		echo _('Logged in: ').$_SESSION['AMP_user']->username;
		echo ' (<a href="http'.(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=''?'s':'').'://';

		if (!ereg('MSIE', $_SERVER['HTTP_USER_AGENT'])) {
			// use other logout for Firefox and other browsers 
			echo 'logout:logout@';
		}

		$pathLength = strrpos($_SERVER['PHP_SELF'],'/');
		$logoutPath = ($pathLength === false) ? '' : substr($_SERVER['PHP_SELF'],0,$pathLength);

		echo $_SERVER['HTTP_HOST'].$logoutPath.'/logout.php">Logout</a>)&nbsp;::&nbsp;';
	}
	echo _($message);
?></div>

<?php
} // End 'quietmode' check
?>

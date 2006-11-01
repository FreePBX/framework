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

	$html = "<li";
	
	if ($is_current)
		$html .= " class=\"current\"";
		
	$html .= "><a ";
	if ($href == NULL)
		$href .= $page;

	if ($new_window != NULL)
		$html .= "target=\"_blank\" ";

	$html .= "href=\"$href\">$name</a></li>";

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
	<!--[if IE]>
	<link href="common/ie.css" rel="stylesheet" type="text/css">
	<![endif]-->	
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
<?php 
	// check if in the amp configuration the user has set that
	// he wants to use an alternative style-sheet.
	// on Xorcom's TS1, it's used when the system is in rescue mode.
	if (isset($amp_conf["ALTERNATIVE_CSS"]))
	{
		if (($amp_conf["ALTERNATIVE_CSS"] == "1") ||
			($amp_conf["ALTERNATIVE_CSS"] == "yes") ||
			($amp_conf["ALTERNATIVE_CSS"] == "true"))
			echo "\t<link href=\"common/mainstyle-alternative.css\" rel=\"stylesheet\" type=\"text/css\">";
	}

	if (isset($display) && is_file("modules/{$display}/{$display}.css")) {
		echo "\t<link href=\"modules/{$display}/{$display}.css\" rel=\"stylesheet\" type=\"text/css\">\n";
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

<body onload="body_loaded();"  <?
// Check if it's a RIGHT TO LEFT character set (eg, hebrew, arabic, whatever)
//$_COOKIE['lang']="he_IL";
if (isset($_COOKIE['lang']) && $_COOKIE['lang']==="he_IL") 
	echo "dir=\"rtl\"";

?> >
<div id="page">
	<div id="header">
<?php
			
	echo "\t\t<div id=\"version\">";
	echo sprintf(_("%s %s on %s"), 
		"<a href=\"index.php\">"._("freePBX")."</a>",
		getversion(),
		"<a href=\"http".(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=''?'s':'')."://".$_SERVER['HTTP_HOST']."\">".$_SERVER["SERVER_NAME"]."</a>"
		 );
	echo "</div>\n";

	echo "\t\t<ul id=\"metanav\">\n";
	print_sub_tool( _("Management"), "manage.php" , $currentFile=='manage.php' );
	print_sub_tool( _("Setup")     , "config.php" , $currentFile=='config.php' && isset($_REQUEST['type']) && ($_REQUEST['type']=='setup' || $_REQUEST['type'] == ""), "config.php?type=setup", false );
	print_sub_tool( _("Tools")     , "config.php" , $currentFile=='config.php' && isset($_REQUEST['type']) && $_REQUEST['type']=='tool' , "config.php?type=tool", false );
	print_sub_tool( _("Reports")   , "reports.php", $currentFile=='reports.php' );
	print_sub_tool( _("Panel")     , "panel.php"  , $currentFile=='panel.php' );
	print_sub_tool( _("Recordings"), "../recordings/index.php"  ,0, NULL, true );
	echo "\t\t</ul>\n";

	$freepbx_alt = _("freePBX");
	$freepbx_logo = (isset($amp_conf["AMPADMINLOGO"]) && is_file($amp_conf["AMPWEBROOT"]."/admin/images/".$amp_conf["AMPADMINLOGO"])) ? $amp_conf["AMPADMINLOGO"] : 'freepbx_small.png';
	echo "\t\t<div id=\"logo\"><a href=\"http://www.freepbx.org\" target=\"_blank\" title=\"".$freepbx_alt."\"><img src=\"images/".$freepbx_logo."\" alt=\"".$freepbx_alt."\" /></a></div>\n";

	echo "\t</div>";

	// need reload bar - hidden by default
	echo "\n\t\t<div class=\"attention\" id=\"need_reload_block\" style=\"display:none;\"><a href=\"javascript:void(null);\" onclick=\"amp_apply_changes();\" class=\"info\">";
	echo _("Apply Configuration Changes");
	echo "<span>".sprintf(_("You have made changes to the configuration that have not yet been applied. When you are ".
				   "finished making all changes, click on %s to put them into effect."), "<strong>"._("Apply Configuration Changes")."</strong>");
	echo "</span></a></div>\n\n";


	echo "\t<div id=\"message\">";

// TODO: this is ugly, need to code this better!
//       mixing php + html is bad!
	if (extension_loaded('gettext')) {
		if (!isset($_COOKIE['lang'])) {
			$_COOKIE['lang'] = "en_US";
		} 
?>
&nbsp;&nbsp;&nbsp;<?php echo _("Language:") ?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<select onchange="javascript:changeLang(this.value)">
		<option value="en_US" <? echo ($_COOKIE['lang']=="en_US" ? "selected" : "") ?> >English</option>
		<option value="fr_FR" <? echo ($_COOKIE['lang']=="fr_FR" ? "selected" : "") ?> >Fran&ccedil;ais</option>
		<option value="de_DE" <? echo ($_COOKIE['lang']=="de_DE" ? "selected" : "") ?> >Deutsch</option>
		<option value="it_IT" <? echo ($_COOKIE['lang']=="it_IT" ? "selected" : "") ?> >Italiano</option>
		<option value="es_ES" <? echo ($_COOKIE['lang']=="es_ES" ? "selected" : "") ?> >Espa&ntilde;ol</option>
		<option value="ru_RU" <? echo ($_COOKIE['lang']=="ru_RU" ? "selected" : "") ?> >Russki</option>
		<option value="pt_PT" <? echo ($_COOKIE['lang']=="pt_PT" ? "selected" : "") ?> >Portuguese</option>
		<option value="he_IL" <? echo ($_COOKIE['lang']=="he_IL" ? "selected" : "") ?> >Hebrew</option>
		</select>
<?php
	}
?>

<script type="text/javascript">
<!--
function changeLang(lang) {
	document.cookie='lang='+lang;
	window.location.reload();
}
//-->
</script>

<?php
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

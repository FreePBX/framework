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

//get the current file name
    $currentFile = $_SERVER["PHP_SELF"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1];

    if (!extension_loaded('gettext')) {
           function _($str) {
                   return $str;
           }
    }

if (!$quietmode) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
    <title><?php  echo _($title) ?></title>
    <meta http-equiv="Content-Type" content="text/html">
    <link href="common/mainstyle.css" rel="stylesheet" type="text/css"> 
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

<?php
	if (extension_loaded('gettext')) {
		if (isset($_COOKIE['lang'])) {
			setlocale(LC_MESSAGES,  $_COOKIE['lang']);
			putenv("LANG=".$_COOKIE['lang']);
		} else {
			setlocale(LC_MESSAGES,  'en_US');
		}
		bindtextdomain('amp','./i18n');
		textdomain('amp');
	}
?>

<body onload="setAllInfoToHideSelects();">
<div id="page">

<div class="header">

<?php
if (isset($amp_conf["AMPADMINLOGO"]) && is_file($amp_conf["AMPWEBROOT"]."/admin/images/".$amp_conf["AMPADMINLOGO"]))
{ ?>
    <a href="index.php"><img src="images/<?php echo $amp_conf["AMPADMINLOGO"] ?>"/></a>
<?php } else { ?>
    <a href="index.php"><img src="images/freepbx.png"/></a>
<?php } 
if (!isset($_REQUEST['type'])) { $_REQUEST['type'] = 'setup'; } 
?>

<?php if (is_file("manage.php")){ ?>
	<a id="<?php echo ($currentFile=='manage.php' ? 'current':'') ?>" href="manage.php?">
		&#8226;
		<li><?php echo _("Management") ?></li>
	</a>
<?php } ?>

	<a id="<?php echo ($currentFile=='config.php' && $_REQUEST['type']=='setup' ? 'current':'') ?>" href="config.php?type=setup">
		&#8226;
		<li><?php echo _("Setup") ?></li>
	</a>

	<a id="<?php echo ($currentFile=='config.php' && $_REQUEST['type']=='tool' ? 'current':'') ?>" href="config.php?type=tool">
		&#8226;
		<li><?php echo _("Tools") ?></li>
	</a>

<?php if (is_file("cdr/cdr.php")){ ?>
	<a id="<?php echo ($currentFile=='reports.php' ? 'current':'') ?>" href="reports.php?">
		&#8226;
		<li><?php echo _("Reports") ?></li>
	</a>
<?php } ?>

<?php if (is_file("../panel/index_amp.php")){ ?>
	<a id="<?php echo ($currentFile=='panel.php' ? 'current':'') ?>" href="panel.php?">
		&#8226;
		<li><?php echo _("Panel") ?></li>
	</a>
<?php } ?>

<?php if (is_file("../recordings/index.php")){ ?>
	<a href="../recordings/index.php" target="_blank">
		&#8226;
		<li><?php echo _("Recordings") ?></li>
	</a>
<?php } ?>

</div>

<div class="message">
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
?>
</div>

<?php
} // End 'quietmode' check
?>

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
?>

<?php 
	//get the current file name
    $currentFile = $_SERVER["PHP_SELF"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1];

    if (!extension_loaded('gettext')) {
           function _($str) {
                   return $str;
           }
    }

?>

<html>

<head>
    <title><?php  echo $title ?></title>
    <meta http-equiv="Content-Type" content="text/html">
    <link href="common/mainstyle.css" rel="stylesheet" type="text/css"> 
    <script src="common/script.js.php"></script>  
    <script type="text/javascript"> 
		<!--
		// Disable browser's Back button on another pg being able to go back to this pg.
		history.forward();
		//-->
	</script> 
</head>

<?php
	setlocale(LC_MESSAGES,  $_COOKIE['lang'] ? $_COOKIE['lang']:'en_US');
	bindtextdomain('amp','./i18n');
	textdomain('amp');
?>

<body>
<div id="page">

<div class="header">

<?php
if (isset($amp_conf["AMPADMINLOGO"])){?>
    <a href="index.php"><img src="images/<?php echo $amp_conf["AMPADMINLOGO"] ?>"/></a>
<?php } else{ ?>
    <a href="index.php"><img src="images/amp.png"/></a>
<?php }  ?>

    <a id="<?php  echo ($currentFile=='config.php' ? 'current':'') ?>" href="config.php?">
        &#8226;
        <li><?php echo _("Setup") ?></li>
    </a>
    <a id="<?php  echo ($currentFile=='reports.php' ? 'current':'') ?>" href="reports.php?">
        &#8226;
        <li><?php echo _("Reports") ?></li>
    </a>
    <a id="<?php  echo ($currentFile=='panel.php' ? 'current':'') ?>" href="panel.php?">
        &#8226;
        <li><?php echo _("Panel") ?></li>
    </a>
</div>

<div class="message">
        <?php  
	if (isset($_SESSION["user"])) {
		if ($amp_conf["AUTHTYPE"] != "none") {
			echo "Logged in: ".$_SESSION["user"]->username;
			echo " (<a href=index.php?action=logout>logout</a>)";
			echo "&nbsp;::&nbsp;";
		}
	}
	echo $message;
	?>
</div>


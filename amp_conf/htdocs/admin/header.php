<?
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

<?
	//get the current file name
    $currentFile = $_SERVER["PHP_SELF"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1];
	
?>

<html>

<head>
    <title><? echo $title ?></title>
    <meta http-equiv="Content-Type" content="text/html">
    <link href="common/mainstyle.css" rel="stylesheet" type="text/css"> 
    <script src="common/script.js"></script>  
    <script type="text/javascript"> 
		<!--
		// Disable browser's Back button on another pg being able to go back to this pg.
		history.forward();
		//-->
	</script> 
</head>

<body>
<div id="page">

<div class="header">

    <a href="index.php"><img src="images/amp.png"/></a>

    <a id="<? echo ($currentFile=='config.php' ? 'current':'') ?>" href="config.php?">
        &#8226;
        <li>Setup</li>
    </a>
    <a id="<? echo ($currentFile=='reports.php' ? 'current':'') ?>" href="reports.php?">
        &#8226;
        <li>Reports</li>
    </a>
</div>

<div class="message">
        <? 
	if (isset($_SESSION["user"])) {
		if ($amp_conf["AUTHTYPE"] != "none") {
			echo "Logged in: ".$_SESSION["user"]->username;
			echo "&nbsp;::&nbsp;";
		}
	}
	echo $message;
	?>
</div>


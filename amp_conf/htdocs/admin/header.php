<?
// Asterisk Management Portal (AMP)
// Copyright (C) 2004 Coalescent Systems Inc
?>

<?
	//get the current file name
    $currentFile = $_SERVER["PHP_SELF"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1];
	
	require_once('common/db_connect.php'); //PEAR must be installed
	require_once('functions.php');
	
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
        <? echo $message;?>
</div>


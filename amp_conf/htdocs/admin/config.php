<?php
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

$title="Asterisk Management Portal";
$message="Setup";
include 'header.php';

require_once('common/db_connect.php'); //PEAR must be installed

$display=$_REQUEST['display'];

?>

<div class="nav">
	<li><a id="<? echo ($display=='' ? 'current':'') ?>" href="config.php?">Incoming Calls</a></li>
	<li><a id="<? echo ($display=='3' ? 'current':'') ?>" href="config.php?display=3">Extensions</a></li>
	<li><a id="<? echo ($display=='4' ? 'current':'') ?>" href="config.php?display=4">Call Groups</a></li>
	<li><a id="<? echo ($display=='2' ? 'current':'') ?>" href="config.php?display=2">Digital Receptionist</a></li>
	<li><a id="<? echo ($display=='1' ? 'current':'') ?>" href="config.php?display=1">On Hold Music</a></li>
	<li><a id="<? echo ($display=='5' ? 'current':'') ?>" href="config.php?display=5">General Settings</a></li>
	
	
	
</div>

<div class="content">

<?
switch($display) {
	default:
		
		echo "<h2>Incoming Calls</h2>";
		include 'incoming.php';

    break;
    case '1':

		echo "<h2>On Hold Music</h2>";
        include 'moh.php';

    break;
    case '2':
	
		echo "<h2>Digital Receptionist</h2>";
		//if promptnum is being passed, assume we want to record a menu
		if ($_REQUEST['promptnum'] == null)
			include 'ivr_action.php'; 
		else
			include 'ivr.php'; //wizard to create a new menu
			
    break;
    case '3':	
			
		include 'extensions.php';
		
	break;
    case '4':	
			
		include 'callgroups.php';
		
	break;
	   case '5':	
			
	    echo "<h2>General Settings</h2>";
		include 'general.php';
		
	break;
}
?>

</div>
<br><br>
<?php include 'footer.php' ?>

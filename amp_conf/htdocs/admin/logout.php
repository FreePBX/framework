<?php /* $Id: $ */
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
// start session
session_start();

$quietmode = isset($_REQUEST['quietmode'])?$_REQUEST['quietmode']:'';

$title=_("freePBX administration");

$message=_("Logged Out");

require_once('functions.inc.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");

// to do a logout, all session-variables will be deleted,
// a variable 'logout' is added:
$_SESSION = array('logout' => true);

include 'header.php'; 
?>

<p>
	<br><br><br><br>
	<h2><center> <?php echo _('You are now logged out.') ?> </center></h2>
	<br><br><br><br>
	<?php include 'footer.php'; ?>
</p>

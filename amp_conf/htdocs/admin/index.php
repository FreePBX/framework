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

$title=_("freePBX administration");
$message=_("Administration");

require_once('functions.inc.php');

// start session
session_start();

// get settings
$amp_conf      = parse_amportal_conf("/etc/amportal.conf");
$asterisk_conf = parse_asterisk_conf("/etc/asterisk/asterisk.conf");

// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

$quietmode = false;
include 'header.php';

?>
<div id="nav">
	<ul>
		<li>
			<a href="index.php" id="current"><?php echo _("Welcome"); ?></a>
		</li>
	</ul>
</div>

<div id="wrapper">
	<div class="content">
		<h2>freePBX</h2>
		<p>
		<?php echo _("Welcome to the FreePBX Administration") ?> <?php $ver=getversion(); echo $ver[0][0];?>
		<br><br><br><br><br><br>
		</p>
	</div>
</div>


<?
include "footer.php";
?>


</div> <!-- /page -->

</body>

</html>

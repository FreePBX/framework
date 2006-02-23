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

$title="AMP: "._("Flash Operator Panel");
$message=_("Flash Operator Panel");

require_once('functions.inc.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");

// start session
session_start();

// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

//  unset server vars if we are logged out
if (isset($_SESSION["AMP_logout"])) {
	unset($_SERVER["PHP_AUTH_USER"]);
	unset($_SERVER["PHP_AUTH_PW"]);
	unset($_SESSION["AMP_logout"]);
}

switch ($amp_conf["AUTHTYPE"]) {
	case "database":
		if (!isset($_SERVER["PHP_AUTH_USER"])) {
			header("WWW-Authenticate: Basic realm=\"AMPortal\"");
			header("HTTP/1.0 401 Unauthorized");
			echo _("You are not authorized to use this resource")."<br>";
			echo "<a href=index.php?action=logout>"._("Go Back")."</a>";
			exit;
		} else {
			$_SESSION["AMP_user"] = new ampuser($_SERVER["PHP_AUTH_USER"]);
			if (!$_SESSION["AMP_user"]->checkPassword($_SERVER["PHP_AUTH_PW"])) {
			
				// one last chance -- check admin user
				if (($_SERVER["PHP_AUTH_USER"] == $amp_conf["AMPDBUSER"]) && ($_SERVER["PHP_AUTH_PW"] == $amp_conf["AMPDBPASS"])) {
					// set admin access
					$_SESSION["AMP_user"]->setAdmin();
				} else {
					header("HTTP/1.0 401 Unauthorized");
					echo _("You are not authorized to use this resource")."<br>";
					echo "<a href=index.php?action=logout>"._("Go Back")."</a>";
					exit;
				}
			}
		}
	break;
	case "http":
		
	break;
	default: 
		if (!isset($_SESSION["AMP_user"])) {
			$_SESSION["AMP_user"] = new ampuser($amp_conf["AMPDBUSER"]);
		}
		$_SESSION["AMP_user"]->setAdmin();
	break;
}


include 'header.php';
?>
</div>
<iframe width="97%" height="600" frameborder="0" align="top" src="../panel/index_amp.php?context=<?php echo $_SESSION["AMP_user"]->_deptname?>"></iframe>

</body>
</html>

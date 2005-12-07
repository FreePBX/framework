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

$title="Asterisk Management Portal";
$message="Setup";

require_once('functions.inc.php');

//obsolete stuff
require_once('functions.php');

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
			echo "You are not authorized to use this resource<br>";
			echo "<a href=index.php?action=logout>Go Back</a>";
			exit;
		} else {
			$_SESSION["AMP_user"] = new ampuser($_SERVER["PHP_AUTH_USER"]);
			if (!$_SESSION["AMP_user"]->checkPassword($_SERVER["PHP_AUTH_PW"])) {
			
				// one last chance -- check admin user
				if ( !(count(getAmpAdminUsers()) > 0) && ($_SERVER["PHP_AUTH_USER"] == $amp_conf["AMPDBUSER"]) && ($_SERVER["PHP_AUTH_PW"] == $amp_conf["AMPDBPASS"])) {
					// set admin access
					$_SESSION["AMP_user"]->setAdmin();
				} else {
					header("HTTP/1.0 401 Unauthorized");
					echo "You are not authorized to use this resource<br>";
					echo "<a href=index.php?action=logout>Go Back</a>";
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

//make sure our tables are there
sipexists();
iaxexists();
zapexists();
backuptableexists();

// setup html
include 'header.php';

if (isset($_REQUEST['display'])) {
	$display=$_REQUEST['display'];
}

/*
// simple 'extensions' admin or 'device/user separation'?
// have to do it this way, since array_splice resets all the array keys as integers
if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
	$amp_sections = array(
		9=>_("Incoming Calls"),
		"devices"=>_("Devices"),
		"users"=>_("Users"),
		4=>_("Ring Groups"),
		11=>_("Queues"),
		2=>_("Digital Receptionist"),
		6=>_("Trunks"),
		7=>_("Inbound Routing"),
		8=>_("Outbound Routing"),
		1=>_("On Hold Music"),
		12=>_("System Recordings"),
		13=>_("Backup & Restore"),
		5=>_("General Settings"),
		99=>_("Apply Changes Bar")
	);
} else {
	$amp_sections = array(
		9=>_("Incoming Calls"),
		"extensions"=>("Extensions"),
		4=>_("Ring Groups"),
		11=>_("Queues"),
		2=>_("Digital Receptionist"),
		6=>_("Trunks"),
		7=>_("Inbound Routing"),
		8=>_("Outbound Routing"),
		1=>_("On Hold Music"),
		12=>_("System Recordings"),
		13=>_("Backup & Restore"),
		5=>_("General Settings"),
		99=>_("Apply Changes Bar")
	);
}*/

	$amp_sections = array(
		'modules'=>_("Module Admin")
	);

/*
// only show AMP Users if they have authtype set approiately
if (isset($amp_conf["AUTHTYPE"]) && ($amp_conf["AUTHTYPE"] != "none")) {
	$amp_sections[10] = _("AMP Users");
}*/

// query for our modules
$modules = find_allmodules();

// include any module global functions
// add module sections to $amp_sections
if(is_array($modules)){
	foreach($modules as $key => $module) {
		//only use this module if it's enabled (status=2)
		if ($module['status'] == 2) {
			// active_modules array used in drawselects function and genConf function
			$active_modules[] = $key;
			//include module functions
			if (is_file("modules/{$key}/functions.inc.php")) {
				require_once("modules/{$key}/functions.inc.php");
			}
			foreach($module['items'] as $itemKey => $itemName) {
				$amp_sections[$itemKey] = $itemName;
			}
		}
	}
}

echo "<table width=\"100%\" cellspacing='0' cellpadding='0'><tr><td>";
// show menu
echo "<div class=\"nav\">";

foreach ($amp_sections as $key=>$value) {

	// check access
	if ($_SESSION["AMP_user"]->checkSection($key)) {
		if ($key != 99) {
			echo "<li><a id=\"".(($display==$key) ? 'current':'')."\" href=\"config.php?display=".$key."\">".$value."</a></li>";
		}
	} else {
		// they don't have access to this, remove it completely
		unset($amp_sections[$key]);
	}
}
	
echo "</div>";

?>

<div class="content">

<?php 
// check access
if (!empty($display) && !isset($amp_sections[$display])) {
	$display = "noaccess";
}


// show the approiate page
switch($display) {
	default:
		//display the appropriate module page
		if (is_array($modules)) {
			foreach ($modules as $modkey => $module) {
				if (is_array(array_keys($module['items']))){
					foreach (array_keys($module['items']) as $item){
						if ($display == $item)  {
							include "modules/{$modkey}/page.{$item}.php";
						}
					}
				}
			}
		}
	break;
	case 'noaccess':
		echo "<h2>"._("Not found")."</h2>";
		echo "<p>"._("The section you requested does not exist or you do not have access to it.")."</p>";
	break;
	case 'modules':
		include 'page.modules.php';
	break;
/*	case '1':
		include 'music.php';
	break;
	case '2':
		echo "<h2>"._("Digital Receptionist")."</h2>";
		// The Digital Receptionist code is a rat's nest.  If you are planning on making significant modifications, just re-write from scratch.
		//if menu_id is being empty, or if we are requesting delete, just use ivr_action.php
		if ((empty($_REQUEST['menu_id'])) || ($_REQUEST['ivr_action'] == 'delete'))
			include 'ivr_action.php'; 
		else
			include 'ivr.php'; //wizard to create/edit a menu
	break;
	case '4':
		include 'callgroups.php';
	break;
	case '5':
		echo "<h2>"._("General Settings")."</h2>";
		include 'general.php';
	break;
	case '6':
		include 'trunks.php';
	break;
	case '7':
		include 'did.php';
	break;
	case '8':
		include 'routing.php';
	break;
	case '9':
		echo "<h2>"._("Incoming Calls")."</h2>";
		include 'incoming.php';
	break;
	case '10':
		include 'ampusers.php';
	break;
	case '11':
		include 'queues.php';
	break;
	case '12':
		include 'recordings.php';
	break;
	case '13':
		include 'backup.php';
	break;
	case 'devices':
		include 'devices.php';
	break;
	case 'users':
		include 'users.php';
	break;
	case 'extensions':
		include 'extensions.php';
	break;*/
}
?>
</div>
</td></tr></table>
<?php include 'footer.php' ?>

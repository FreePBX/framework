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

$title="freePBX administration";
$message="Setup";

require_once('functions.inc.php');

//obsolete stuff
//require_once('functions.php');

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

// setup html
include 'header.php';

if (isset($_REQUEST['display'])) {
	$display=$_REQUEST['display'];
} else {
        $display='';
}
	$amp_sections = array(
		'modules'=>_("Module Admin")
	);

/*
// only show AMP Users if they have authtype set approiately
if (isset($amp_conf["AUTHTYPE"]) && ($amp_conf["AUTHTYPE"] != "none")) {
	$amp_sections[10] = _("AMP Users");
}*/

// determine module type to show, default to 'setup'
if(isset($_REQUEST['type']) && $_REQUEST['type'] == "tool") {
	$type='tool';
} else {
	$type='setup';
}
// get all enabled modules
// active_modules array used below and in drawselects function and genConf function
$active_modules = find_modules(2,$type);

// include any module global functions
// add module sections to $amp_sections
if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		//include module functions
		if (is_file("modules/{$key}/functions.inc.php")) {
			require_once("modules/{$key}/functions.inc.php");
		}
		//create an array of module sections to display
		foreach($module['items'] as $itemKey => $itemName) {
			$amp_sections[$itemKey] = $itemName;
		}
	}
}

echo "<table width=\"100%\" cellspacing='0' cellpadding='0'><tr><td>";
// show menu
echo "<div class=\"nav\">";

// extensions vs device/users ... this is a bad design, but hey, it worksv
if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
	unset($amp_sections["extensions"]);
} else {
	unset($amp_sections["devices"]);
	unset($amp_sections["users"]);
}


	
foreach ($amp_sections as $key=>$value) {
	// check access
	if ($_SESSION["AMP_user"]->checkSection($key)) {
		if ($key != 99) {
			// if the module has it's own translations, use them for displaying menu item
			if (extension_loaded('gettext')) {
				if(is_dir("modules/{$key}/i18n")) {
					bindtextdomain($key,"modules/{$key}/i18n");
					textdomain($key);
				} else {
					bindtextdomain('amp','./i18n');
					textdomain('amp');
				}
			}
			if(preg_match("/^(<a.+>)(.+)(<\/a>)$/",$value,$matches))
				echo "<li>".$matches[1]._($matches[2]).$matches[3]."</li>";
			else
				echo "<li><a id=\"".(($display==$key) ? 'current':'')."\" href=\"config.php?".(isset($_REQUEST['type'])?"type={$_REQUEST['type']}&":"")."display=".$key."\">"._($value)."</a></li>";
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
		if (is_array($active_modules)) {
			foreach ($active_modules as $modkey => $module) {
				if (is_array(array_keys($module['items']))){
					foreach (array_keys($module['items']) as $item){
						if ($display == $item)  {
							// modules can use their own translation files
							if (extension_loaded('gettext')) {
								if(is_dir("./modules/{$modkey}/i18n")) {
									bindtextdomain($modkey,"./modules/{$modkey}/i18n");
									textdomain($modkey);
								}
							}
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
}

//use main translation file for footer
if (extension_loaded('gettext')) {
	bindtextdomain('amp','./i18n');
	textdomain('amp');
}
	
?>
</div>
</td></tr></table>
<?php include 'footer.php' ?>

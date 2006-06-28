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

// determine module type to show, default to 'setup'
if(isset($_REQUEST['type']) && $_REQUEST['type'] == "tool") {
	$message="Tools";
} else {
	$message="Setup";
}

$quietmode = isset($_REQUEST['quietmode'])?$_REQUEST['quietmode']:'';
require_once('functions.inc.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");
$asterisk_conf = parse_asterisk_conf("/etc/asterisk/asterisk.conf");

include 'header_auth.php';

if (isset($_REQUEST['display'])) {
	$display=$_REQUEST['display'];
} else {
	$display='';
}

// if we are looking at tools, then show module admin
if ($_REQUEST['type'] == "tool") {
	$amp_sections = array(
		'modules'=>_("Module Admin")
	);
}

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
$active_modules = find_modules(2);

// include any module global functions
// add module sections to $amp_sections
if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		//include module functions
		if (is_file("modules/{$key}/functions.inc.php")) {
			require_once("modules/{$key}/functions.inc.php");
		}
		//create an array of module sections to display
		// only of the type we are displaying though
		if ($module['type'] == $type) {
			if (is_array($module['items'])) {
				foreach($module['items'] as $itemKey => $itemName) {
					$amp_sections[$itemKey] = $itemName;
				}
			}
		}
		//sort it? probably not right but was getting in a mess to be honest
		//so something better than nothing
		if (is_array($amp_sections))
			asort($amp_sections);
	}
}
if (!$quietmode) {
	echo "<table width=\"100%\" cellspacing='0' cellpadding='0'><tr><td>";
	// show menu
	echo "<div class=\"nav\">\n";
}

// extensions vs device/users ... this is a bad design, but hey, it works
if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
	unset($amp_sections["extensions"]);
} else {
	unset($amp_sections["devices"]);
	unset($amp_sections["users"]);
}


// add the APPLY Changes bar as a section, so it shows in the Administrators module
$amp_sections[99] = _("Apply Changes Bar");

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
			if (!$quietmode) {
				if(preg_match("/^(<a.+>)(.+)(<\/a>)/",$value,$matches))
					echo "<li>".$matches[1]._($matches[2]).$matches[3]."</li>\n";
				else
				echo "<li><a id=\"".(($display==$key) ? 'current':'')."\" href=\"config.php?".(isset($_REQUEST['type'])?"type={$_REQUEST['type']}&":"")."display=".$key."\">"._($value)."</a></li>\n";
			}
		}
	} else {
		// they don't have access to this, remove it completely
		unset($amp_sections[$key]);
	}
}
if (!$quietmode) {	
	echo "</div>\n<div class=\"content\">\n";
}
// check access
if ( ($display != '') && !isset($amp_sections[$display]) ) {
	$display = "noaccess";
}


// show the approiate page
switch($display) {
	default:
		//display the appropriate module page
		if (!is_array($active_modules)) {
			break;
		}
		
		foreach ($active_modules as $modkey => $module) {
			if (!is_array($module['items'])){
				continue;
			}
			
			foreach (array_keys($module['items']) as $item){
				if ($display != $item)  {
					continue;
				}
				
				// modules can use their own translation files
				if (extension_loaded('gettext')) {
					if(is_dir("./modules/{$modkey}/i18n")) {
						bindtextdomain($modkey,"./modules/{$modkey}/i18n");
						textdomain($modkey);
					}
				}
				
				//TODO Determine which item is this module displaying. Currently this is over the place, we should standarize on a "itemid" request var for now, we'll just cover all possibilities :-(
				$possibilites = array(
					'userdisplay',
					'extdisplay',
					'id',
					'itemid',
					'category',
					'selection'
				);
				$itemid = '';
				foreach($possibilites as $possibility) {
					if ( isset($_REQUEST[$possibility]) && $_REQUEST[$possibility] != '' ) 
						$itemid = $_REQUEST[$possibility];
				}

				// create a module_hook object for this module's page
				$module_hook = new moduleHook;
				
				// populate object variables
				$module_hook->install_hooks($itemid,$modkey,$item);
				
				// include the module page
				include "modules/{$modkey}/page.{$item}.php";
				
				// let hooking modules process the $_REQUEST
				$module_hook->process_hooks($itemid,$modkey,$item,$_REQUEST);
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

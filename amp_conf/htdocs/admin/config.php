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
		if (isset($amp_sections) && is_array($amp_sections))
			asort($amp_sections);
	}
}

// new gui hooks
if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		if (is_array($module['items'])) {
			foreach($module['items'] as $itemKey => $itemName) {
				//list of potential _configpageinit functions
				$initfuncname = $key . '_' . $itemKey . '_configpageinit';
				if ( function_exists($initfuncname) ) {
					$configpageinits[] = $initfuncname;
				}
			}
		}
		//check for module level (rather than item as above) _configpageinit function
		$initfuncname = $key . '_configpageinit';
		if ( function_exists($initfuncname) ) {
			$configpageinits[] = $initfuncname;
		}
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

// load the component from the loaded modules
if ( $display != '' && isset($configpageinits) && is_array($configpageinits) ) {
	$currentcomponent = new component($display);

	// call every modules _configpageinit function which should just
	// register the gui and process functions for each module, if relevent
	// for this $display
	foreach ($configpageinits as $func) {
		$func($display);
	}
	
	// now run each 'process' function and 'gui' function
	$currentcomponent->processconfigpage();
	$currentcomponent->buildconfigpage();
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

				// global component
				if ( isset($currentcomponent) ) {
					echo $currentcomponent->generateconfigpage();
				}

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
	case '':
		// on the main page, alert the user if asterisk is not running
		// try to reuse as much strings as needed
		require_once('common/php-asmanager.php');
		$astman = new AGI_AsteriskManager();
		if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
			$astman->disconnect();
	?>
	<h2>Welcome to freePBX.</h2>
	<p>If you're new to freePBX, Welcome. Here are some quick instructions to get you started.</p>
	<p>There are a large number of Plug-in modules available from the Online repostiory. This is
	available by clicking on the <a href="config.php?type=tool">Tools menu</a> up the top, then
	<a href="config.php?type=tool&display=modules">Module Admin</a>, then
	<a href="config.php?display=modules&amp;type=tool&amp;extdisplay=online">Connect to Online Module Repository</a>.
	Modules are updated and patched often, so if you are having a problem, it's worth checking there to see if there's
	a new version of the module available.</p>
	<p>If you're having any problems, you can also use the <a href="config.php?type=tool&amp;display=irc">Online Support</a>
	module (<b>you need to install this through the <a href="config.php?display=modules&amp;type=tool&amp;extdisplay=online">Module Repository</a> first</b>)
	to talk to other users and the devlopers in real time. Click on <a href="config.php?type=tool&amp;display=irc&amp;action=start">Start IRC</a>,
	when the module is installed, to start a Java IRC client.</p>
	<p>There is also a community based <a href="http://forums.freepbx.org" target="_new">freePBX Web Forum</a> where you can post
	questions and search for answers for any problems you may be having.</p>
	<p>We hope you enjoy using freePBX!</p>
	<?php
		}
		else{
			echo "<style>.clsError{ border: #BB0A0A 1px solid; background-color: #ffc0c0; }</style>\n";
			echo "<p><div class='clsError'>\n";
			echo "<b>" . _("Warning:") . "</b>\n";
			echo "<br>";
			echo "<br>\n";
			echo _("Cannot connect to Asterisk Manager with ").$amp_conf["AMPMGRUSER"];
			echo "<br>";
			echo _("Asterisk may not be running.");
			echo "</div></p>\n";
		}
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

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

$title = "freePBX administration";

$type = isset($_REQUEST['type'])?$_REQUEST['type']:'setup';
$display = isset($_REQUEST['display'])?$_REQUEST['display']:'';
$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
$skip = isset($_REQUEST['skip'])?$_REQUEST['skip']:0;
$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
$quietmode = isset($_REQUEST['quietmode'])?$_REQUEST['quietmode']:'';

// determine module type to show, default to 'setup'
if($type == "tool") {
	$message = "Tools";
	$fpbx_menu = array(
		'modules' => array('category' => 'System Administration', 'name' => 'Module Admin', 'sort' => 0)
	);
} elseif($type == "cdrcost") {
	$message = "Call Cost";
} else {
	$message = "Setup";
}

require_once('common/php-asmanager.php');
require_once('functions.inc.php');

// get settings
$amp_conf	= parse_amportal_conf("/etc/amportal.conf");
$asterisk_conf	= parse_asterisk_conf("/etc/asterisk/asterisk.conf");
$astman		= new AGI_AsteriskManager();
if (! $res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
	unset( $astman );
}

include 'header_auth.php';

// get all enabled modules
// active_modules array used below and in drawselects function and genConf function
$active_modules = module_getinfo(false, MODULE_STATUS_ENABLED);

// include any module global functions
// add module sections to $fpbx_menu
if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		//include module functions
		if (is_file("modules/{$key}/functions.inc.php")) {
			require_once("modules/{$key}/functions.inc.php");
		}
		//create an array of module sections to display
		// only of the type we are displaying though
		
		// stored as [items][$type][$category][$name] = $displayvalue
		if (isset($module['items']) && is_array($module['items'])) {
			// loop through the types
			foreach($module['items'] as $itemKey => $item) {
				if ($item['type'] == $type) {
					// item is an assoc array, with at least array(name=>, category=>, type=>)
					$fpbx_menu[$itemKey] = $item;
				}
			}
		
		}
		
	}
}

// new gui hooks
if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		if (isset($module['items']) && is_array($module['items'])) {
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


// extensions vs device/users ... this is a bad design, but hey, it works
if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
	unset($fpbx_menu["extensions"]);
} else {
	unset($fpbx_menu["devices"]);
	unset($fpbx_menu["users"]);
}

if (is_array($fpbx_menu)) {
	foreach ($fpbx_menu as $key => $value) {
		// check access
		if ($_SESSION["AMP_user"]->checkSection($key)) {
			// if the module has it's own translations, use them for displaying menu item
			if (extension_loaded('gettext')) {
				if (is_dir("modules/{$key}/i18n")) {
					bindtextdomain($key,"modules/{$key}/i18n");
					bind_textdomain_codeset($key, 'utf8');
					textdomain($key);
				} else {
					bindtextdomain('amp','./i18n');
					textdomain('amp');
				}
			}
		} else {
			// they don't have access to this, remove it completely
			unset($fpbx_menu[$key]);
		}
	}
}

if (!$quietmode) {
	if (is_array($fpbx_menu)) {
		$category = Array();
		$sort = Array();
		$name = Array();
		// Sorting menu by category and name
		foreach ($fpbx_menu as $key => $row) {
			$category[$key] = $row['category'];
			$sort[$key] = $row['sort'];
			$name[$key] = $row['name'];
		}
		
		if ($amp_conf['USECATEGORIES']) {
			array_multisort(
				$category, SORT_ASC, 
				$sort, SORT_ASC, SORT_NUMERIC, 
				$name, SORT_ASC, 
				$fpbx_menu
			);
		} else {
			array_multisort(
				$sort, SORT_ASC, SORT_NUMERIC, 
				$name, SORT_ASC, 
				$fpbx_menu
			);
		}
		
		// Printing menu
		echo "<div id=\"nav\"><ul>\n";
		
		$prev_category = '';
		
		foreach ($fpbx_menu as $key => $row) {
			if ($amp_conf['USECATEGORIES'] && ($row['category'] != $prev_category)) {
				echo "\t\t<li>"._($row['category'])."</li>\n";
				$prev_category = $row['category'];
			}
			
			$href = isset($row['href']) ? $row['href'] : "config.php?type=".$type."&amp;display=".$key;
			$extra_attributes = '';
			if (isset($row['target'])) {
				$extra_attributes .= ' target="'.$row['target'].'"';
			}
			
			echo "\t<li" .
				(($display==$key) ? ' class="current"':'') .
				'><a href="'.$href.'" '.$extra_attributes.' >'._($row['name'])."</a></li>\n";
			
		}
		echo "</ul></div>\n\n";
	}

	echo "<div id=\"wrapper\"><div id=\"background-wrapper\">\n";
	
	echo "<div id=\"left-corner\"></div>\n";
	echo "<div id=\"right-corner\"></div>\n";
	
	echo "<div class=\"content\">\n";


	echo "<noscript><div class=\"attention\">"._("WARNING: Javascript is disabled in your browser. The freePBX administration interface requires Javascript to run properly. Please enable javascript or switch to another  browser that supports it.")."</div></noscript>";
}



// check access
if ( ($display != '') && !isset($fpbx_menu[$display]) ) {
	$display = "noaccess";
}

// load the component from the loaded modules
if ( $display != '' && isset($configpageinits) && is_array($configpageinits) ) {

	$currentcomponent = new component($display,$type);

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

// show the appropriate page
switch($display) {
	default:
		//display the appropriate module page
		if (!isset($active_modules) || (isset($active_modules) && !is_array($active_modules))) {
			break;
		}
		
		foreach ($active_modules as $modkey => $module) {
			if (!isset($module['items']) || (isset($module['items']) && !is_array($module['items']))){
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
						bind_textdomain_codeset($modkey, 'utf8');
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

				// let hooking modules process the $_REQUEST
				$module_hook->process_hooks($itemid,$modkey,$item,$_REQUEST);
				
				// include the module page
				include "modules/{$modkey}/page.{$item}.php";

				// global component
				if ( isset($currentcomponent) ) {
					echo $currentcomponent->generateconfigpage();
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
	case '':
		if ($astman) {
			printf( "<h2>%s</h2>", dgettext("welcome page", "Welcome to freePBX.") );

			$modules_needup = module_getinfo(false, MODULE_STATUS_NEEDUPGRADE);
			$modules_broken = module_getinfo(false, MODULE_STATUS_BROKEN);
			if (count($modules_needup) || count($modules_broken)) {
				echo "<div class=\"warning\">";
				if (count($modules_needup)) {
					echo "<p>"._("Warning: The following modules are disabled because they need upgrading: ");
					echo implode(", ",array_keys($modules_needup));
					echo "</p>";
				}
				if (count($modules_broken)) {
					echo "<p>"._("Warning: The following modules are disabled because they are broken: ");
					echo implode(", ",array_keys($modules_broken));
					echo "</p>";
				}
				echo "<p>", sprintf(dgettext("welcome page","You should go to the <a href='%s'>Module Admin</a> page to fix these.</p>"), "config.php?display=modules&amp;type=tool");
				echo "</div>";
			}
			
// BETA code - remove later.
			echo "<div class=\"warning\">";
			printf( "<p>%s</p>", dgettext("welcome page", "You are running a BETA VERSION of freePBX. This release probably has bugs, and the point of this release is to get as many of them fixed as possible.") );
			
			printf( "<p>%s</p>"  , dgettext("welcome page", "We urge to you report any bugs you find. Current known bugs are maintained on <a href='http://www.aussievoip.com/wiki/freePBX-2.2'>the users website</a>. If you find a bug, please <a href='http://www.freepbx.org/trac/newticket'>create a bug report</a> and update the information on the <a href='http://www.aussievoip.com/wiki/freePBX-2.2'>users website</a> so that it can be easily tracked by other users.") );
			echo "</div>";

			printf( "<!--[if IE]><p>%s</p><![endif]-->"  , dgettext("welcome page", "Note, presently, Microsoft's Internet Explorer is <b>not</b> a supported web browser, and you must use a standards compliant browser, such as Firefox. There is a link on the <a href='http://www.freepbx.org'>right hand side of freePBX.org</a> that will allow you to download Firefox easily. By using that link, Google will donate US$1 to the freePBX project.") );
			
			if ($amp_conf['AMPMGRPASS'] == 'amp111') {
				printf( "<div class=\"warning\"><p>%s</p></div>", dgettext("welcome text", "Warning: You are running freePBX and ").$amp_conf['AMPENGINE'].dgettext("welcome page", " with the default manager pass. You should consider changing this to something else.") );
			}
			if ($amp_conf['AMPDBPASS'] == 'amp109') {
				printf( "<div class=\"warning\"><p>%s</p></div>", dgettext("welcome text", "Warning: You are running freePBX and ").$amp_conf['AMPDBENGINE'].dgettext("welcome page", " with the default password ") );
			}



			printf( "<p>%s</p>"  , dgettext("welcome page", "If you're new to freePBX, Welcome. Here are some quick instructions to get you started") );
			
			echo "<p>";
			printf( dgettext("welcome page", 
"There are a large number of Plug-in modules available from the Online Repository. This is
available by clicking on the <a href='%s'>Tools menu</a> up the top, then
<a href='%s'>Module Admin</a>, then
<a href='%s'>Check for updates online</a>.
Modules are updated and patched often, so if you are having a problem, it's worth checking there to see if there's
a new version of the module available."), 
				"config.php?type=tool",
				"config.php?display=modules&amp;type=tool",
				"config.php?display=modules&amp;type=tool&amp;extdisplay=online"
			);
			echo "</p>\n";

			echo "<p>";
			printf( dgettext( "welcome page",
"If you're having any problems, you can also use the <a href='%s'>Online Support</a> 
module (<b>you need to install this through the <a href='%s'>Module Repository</a> first</b>)
to talk to other users and the devlopers in real time. Click on <a href='%s'>Start IRC</a>,
when the module is installed, to start a Java IRC client." ),
				"config.php?type=tool&amp;display=irc",
				"config.php?display=modules&amp;type=tool&amp;extdisplay=online",
				"config.php?type=tool&amp;display=irc&amp;action=start"
			);
			echo "</p>\n";

			echo "<p>";
			printf( dgettext( "welcome page",
"There is also a community based <a href='%s' target='_new'>freePBX Web Forum</a> where you can post
questions and search for answers for any problems you may be having."),
"http://forums.freepbx.org"  );
			echo "</p>\n";

			print( "<p>" . _("We hope you enjoy using freePBX!") . "</p>\n" );
		} // no manager, no connection to asterisk
		else {
			echo "<p><div class='clsError'>\n";
			echo "<b>" . _("Warning:") . "</b>\n";
			echo "<br>";
			echo "<br>\n";
			echo _("Cannot connect to Asterisk Manager with "). "<i>" .$amp_conf["AMPMGRUSER"] . "</i>";
			echo "<br>";
			echo _("Asterisk may not be running.");
			echo "</div></p>\n";
		}
	break;
	
}

if (!$quietmode) {
	echo "\t</div> <!-- /content -->\n";
	
	include('footer.php');
	echo "</div></div> <!-- /background-wrapper, /wrapper -->\n";

	echo "</div> <!-- /page -->\n";
	echo "</body>\n";
	echo "</html>\n";
}
?>

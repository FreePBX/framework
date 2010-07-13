<?php 
/** Main freepbx view - sets up the base HTML page, and FreePBX header
 */

// helper function, to draw the upper links
function print_sub_tool( $name, $page, $is_current, $href=NULL, $new_window=false )
{
	static $first_tab_shown; // has the first tab been displayed?
	
	if (!(is_file($page) || strpos($page,"http") === 0))
		return;

	$html = "<li class=";
	if ($is_current)
		$html .=  !isset($first_tab_shown) ? "\"first-current\"" : "\"current\"" ;
	else
		$html .=  !isset($first_tab_shown) ? "\"first\"" : "\"noselect\"" ;
	
	$first_tab_shown = true;
	
	$html .= "><a ";
	if ($href == NULL)
		$href .= $page;

	// If not NULL and just true, then previous behavior open a new window. If
	// A name is given, then use javascript to target that window if already open
	//
	if ($new_window != NULL) {
		if ($new_window === true) {
			$html .= "target=\"_blank\" ";
		} else {
			$html .= "target=\"$new_window\" ";
			//$html .= "onClick=\"return menu_popup(this, '$new_window')\" ";
		}
	}
	$html .= "href=\"$href\">$name</a></li>";

	print("\t\t$html\n");
}

if (!isset($title)) {
  $title = 'FreePBX';
}
if (!isset($amp_conf)) {
  $amp_conf = array();
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- should also validate ok with DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/xhtml1-transitional.dtd" -->
<html>

<head>
	<title><?php  echo _($title) ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="chrome=1" />
	<link href="common/mainstyle.css" rel="stylesheet" type="text/css" />
<?php if (isset($use_nav_background) && $use_nav_background) { ?>
	<style type="text/css">
		body {
		  background-image: url(images/shadow-side-background.png);
			background-repeat: repeat-y;
			background-position: left;
		}
	</style>
<?php } ?>
	<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="favicon.ico" />
	<!--[if IE]>
	<link href="common/ie.css" rel="stylesheet" type="text/css" />
	<![endif]-->	
<?php 
	// check if in the amp configuration the user has set that
	// he wants to use an alternative style-sheet.
	// on Xorcom's TS1, it's used when the system is in rescue mode.
	if (isset($amp_conf["ALTERNATIVE_CSS"]))
	{
		if (($amp_conf["ALTERNATIVE_CSS"] == "1") ||
			($amp_conf["ALTERNATIVE_CSS"] == "yes") ||
			($amp_conf["ALTERNATIVE_CSS"] == "true"))
			echo "\t<link href=\"common/mainstyle-alternative.css\" rel=\"stylesheet\" type=\"text/css\" />";
	}

	if (isset($module_name)) {
		if (is_file('modules/'.$module_name.'/'.$module_name.'.css')) {
			echo "\t".'<link href="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_name.'.css" rel="stylesheet" type="text/css" />'."\n";
		}
		if (isset($module_page) && ($module_page != $module_name) && is_file('modules/'.$module_name.'/'.$module_page.'.css')) {
			echo "\t".'<link href="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_page.'.css" rel="stylesheet" type="text/css" />'."\n";
		}
	}
?>

	<script type="text/javascript" src="common/script.js.php"></script>
<?php
	// Production versions should include the packed consolidated javascript library but if it
	// is not present (useful for development, then include each individual library below
	//
	if (file_exists("common/libfreepbx.javascripts.js")) {
?>
	<script type="text/javascript" src="common/libfreepbx.javascripts.js" language="javascript"></script>
<?php
	} else {
?>
	<script type="text/javascript" src="common/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="common/jquery.cookie.js"></script> <!-- plugin for setting/reteiving cookies -->
	<script type="text/javascript" src="common/jquery-ui-1.8.custom.min.js"></script>
	<script type="text/javascript" src="common/script.legacy.js"></script> <!-- legacy script.js.php -->
	<script type="text/javascript" src="common/jquery.dimensions.js"></script> <!-- used by reload/module admin -->
	<script type="text/javascript" src="common/jquery.toggleval.3.0.js"></script> <!-- plugin for adding help text to input boxes -->
	<script type="text/javascript" src="common/interface.dim.js"></script> <!-- used for interface blocking (reload, modadmin) -->
	<script type="text/javascript" src="common/tabber-minimized.js"></script> <!-- used for module admin (hiding content) -->
<?php
	}
if (isset($module_name) && $module_name != '') {
	if (is_file('modules/'.$module_name.'/'.$module_name.'.js')) {
		echo "\t".'<script type="text/javascript" src="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_name.'.js"></script>'."\n";
	}
	if (isset($module_page) && ($module_page != $module_name) && is_file('modules/'.$module_name.'/'.$module_page.'.js')) {
		echo "\t".'<script type="text/javascript" src="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_page.'.js"></script>'."\n";
	}

	// Note - include all the module js files first, then the page specific files, in case a page specific file requires a module level file
	$js_dir = "modules/$module_name/js";
  if (is_dir($js_dir)) {
    $d = opendir($js_dir);
		$file_list = array();
    while ($file = readdir($d)) {
			$file_list[] = $file;
		}
		sort($file_list);
		foreach ($file_list as $file) {
			if (substr($file,-3) == '.js' && is_file("$js_dir/$file")) {
				echo "\t<script type='text/javascript' src='{$_SERVER['PHP_SELF']}?handler=file&module=$module_name&file=$js_dir/$file'></script>\n";
			}
		}
		unset($file_list);
		$js_subdir ="$js_dir/$module_page";
		if ($module_page != '' && is_dir($js_subdir)) {
			$sd = opendir($js_subdir);

			$file_list = array();
			while ($p_file = readdir($sd)) {
				$file_list[] = $p_file;
			}
			sort($file_list);
			foreach ($file_list as $p_file) {
				if (substr($p_file,-3) == '.js' && is_file("$js_subdir/$p_file")) {
					echo "\t<script type='text/javascript' src='{$_SERVER['PHP_SELF']}?handler=file&module=$module_name&file=$js_subdir/$p_file'></script>\n";
				}
			}
		}
  }
}
?>
	
<!--[if IE]>
    <style type="text/css">div.inyourface a{position:absolute;}</style>
<![endif]-->
</head>

<body onload="body_loaded();"  <?php
// Check if it's a RIGHT TO LEFT character set (eg, hebrew, arabic, whatever)
//$_COOKIE['lang']="he_IL";
if (isset($_COOKIE['lang']) && $_COOKIE['lang']==="he_IL") 
	echo "dir=\"rtl\"";

?> >

<?php
//IE6 doesn't do z-order properly, so this bit has to be outside of #page

// initialized if not, some bug reports indicate it may not be
//
$reload_needed = isset($reload_needed) ? $reload_needed : false;

if ($reload_needed) {
	showview('freepbx_reload');
}
?>
<!-- module process box, used by module admin (page.modules.php) - has to be here because of IE6's z-order stupidity -->
<div id="moduleBox" style="display:none;"></div>

<div id="page">
	<div id="header">
<?php
	global $amp_conf;
	$freepbx_alt = _("FreePBX");
	$freepbx_logo = 'freepbx_large.png';
	echo "\t\t<div id=\"freepbx\"><a href=\"http://www.freepbx.org\" target=\"_blank\" title=\"".$freepbx_alt."\"><img src=\"images/".$freepbx_logo."\" alt=\"".$freepbx_alt."\" /></a></div>\n";
			
	echo "\t\t<div id=\"version\">";
	$version = get_framework_version();
	$version = $version ? $version : getversion();
	echo sprintf(_("%s %s on %s"), 
		//TODO : make this go somewhere more useful? or no link?
		"<a href=\"http://www.freepbx.org\" target=\"_blank\">"._("FreePBX")."</a>",
		$version,
		"<a href=\"http".(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=''?'s':'')."://".$_SERVER['HTTP_HOST']."\">".$_SERVER["SERVER_NAME"]."</a>"
		 );
	echo "</div>\n";

	$currentFile = basename($_SERVER["SCRIPT_NAME"]);

	$help_args = "?freepbx_version=".urlencode($version);
	if (isset($_REQUEST['display'])) {
		$help_args .= "&amp;freepbx_menuitem=".urlencode($_REQUEST['display']);
	}
	
	echo "\t\t<ul id=\"metanav\">\n";
	//print_sub_tool( _("Home"), "index.php"  ,$currentFile=='index.php');
	print_sub_tool( _("Management"), "manage.php" , $currentFile=='manage.php' );
	print_sub_tool( _("Admin")   , "config.php", $currentFile=='config.php' );
	print_sub_tool( _("Reports")   , "reports.php", $currentFile=='reports.php' );
	if(!$amp_conf["FOPDISABLE"]) {
		print_sub_tool( _("Panel"), "panel.php", $currentFile=='panel.php' );
	}
	print_sub_tool( _("Recordings"), "../recordings/index.php"  ,0, NULL, "ari" );
	print_sub_tool( _("Help"), "http://www.freepbx.org/freepbx-help-system$help_args"  ,0, NULL, "help" );
	echo "<li class=\"last\"><a>&nbsp;</a></li>";
	echo "\t\t</ul>\n";

	$freepbx_logo = (isset($amp_conf["AMPADMINLOGO"]) && is_file($amp_conf["AMPWEBROOT"]."/admin/images/".$amp_conf["AMPADMINLOGO"])) ? $amp_conf["AMPADMINLOGO"] : 'logo.png';
	echo "\t\t<div id=\"logo\"><a href=\"http://www.freepbx.org\" target=\"_blank\" title=\"".$freepbx_alt."\"><img src=\"images/".$freepbx_logo."\" alt=\"".$freepbx_alt."\" /></a></div>\n";

	
	// need reload bar - hidden by default
	if ($reload_needed) {
		showview('freepbx_reloadbar');
	}
	
	
	echo "\t<div id=\"login_message\">";

	if ( isset($_SESSION['AMP_user']) &&  isset($amp_conf['AUTHTYPE']) && ($amp_conf['AUTHTYPE'] != 'none')) {
		echo _('Logged in: ').$_SESSION['AMP_user']->username;
		echo ' (<a href="'.$_SERVER['PHP_SELF'].'?logout">'._('Logout').'</a>)&nbsp;';
	}
	//echo '::&nbsp;'._($message);
	
	echo "\t</div>\t</div>"; // login_message, header
?>


<div id="content">

<?php

echo $content;

//	echo "\t</div> <!-- /content -->\n";
	
//	include('footer.php');
//	echo "</div></div> <!-- /background-wrapper, /wrapper -->\n";

?>
</div> <!-- content -->
</div> <!-- page -->
<?php
if (isset($amp_conf['DEVEL']) && $amp_conf['DEVEL']) {
       $benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
       echo '<div id="benchmark_time">Page loaded in '.$benchmark_time.'s</div>';
}
?>
</body>
</html>

<?php 
/** Main FreePBX view - sets up the base HTML page, and FreePBX header
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

// BRANDABLE COMPONENTS
//

// get version info to be used to version images, css, etc.
//
$version = get_framework_version();
$version = $version ? $version : getversion();
$version_tag = '?load_version='.urlencode($version);
if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
  $this_time_append = '.'.time();
  $version_tag .= $this_time_append;
}

if ($amp_conf['BRAND_IMAGE_HIDE_NAV_BACKGROUND']) {
  $use_nav_background = false;
} else {
  $shadow_side_background = ($amp_conf['BRAND_IMAGE_SHADOW_SIDE_BACKGROUND'] ? $amp_conf['BRAND_IMAGE_SHADOW_SIDE_BACKGROUND'] : 'images/shadow-side-background.png').$version_tag;
}
// $freepbx_logo_r
// AMPADMINLOGO takes precedence for backwards compatibility
if ($amp_conf["AMPADMINLOGO"] && is_file($amp_conf["AMPWEBROOT"]."/admin/images/".$amp_conf["AMPADMINLOGO"])) {
  $freepbx_logo_r = 'images/'.$amp_conf["AMPADMINLOGO"].$version_tag; 
} else {
  $freepbx_logo_r =  ($amp_conf['BRAND_IMAGE_FREEPBX_RIGHT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_RIGHT'] : 'images/logo.png').$version_tag;
}
$freepbx_alt_l      = $amp_conf['BRAND_FREEPBX_ALT_LEFT'] ? $amp_conf['BRAND_FREEPBX_ALT_LEFT'] : _("FreePBX");
$freepbx_alt_r      = $amp_conf['BRAND_FREEPBX_ALT_RIGHT'] ? $amp_conf['BRAND_FREEPBX_ALT_RIGHT'] : _("FreePBX");
$freepbx_logo_l     = ($amp_conf['BRAND_IMAGE_FREEPBX_LEFT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_LEFT'] : 'images/freepbx_large.png').$version_tag;
$freepbx_link_l     = $amp_conf['BRAND_IMAGE_FREEPBX_LINK_LEFT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_LINK_LEFT'] : 'http://www.freepbx.org';
$freepbx_link_r     = $amp_conf['BRAND_IMAGE_FREEPBX_LINK_RIGHT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_LINK_RIGHT'] : 'http://www.freepbx.org';
$use_freepbx_logo_r = ! $amp_conf['BRAND_HIDE_LOGO_RIGHT'];
$hide_version       = $amp_conf['BRAND_HIDE_HEADER_VERSION'];
$hide_toolbar       = $amp_conf['BRAND_HIDE_HEADER_MENUS'];

$mainstyle_css      = $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] ? $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] : 'common/mainstyle.css'; 
$custom_css         = $amp_conf['BRAND_CSS_CUSTOM'];

if (!$amp_conf['DISABLE_CSS_AUTOGEN'] && version_compare(phpversion(),'5.0','ge')) {
  $wwwroot = $amp_conf['AMPWEBROOT']."/admin";

  // stat the css files and check if they have been modified since we last generated a css
  //
  $mainstyle_css_full_path = $wwwroot."/".$mainstyle_css;
  $stat_mainstyle = stat($mainstyle_css_full_path);
  $css_changed = isset($amp_conf['mainstyle_css_mtime']) ? ($stat_mainstyle['mtime'] != $amp_conf['mainstyle_css_mtime']) : true;

  if (!$css_changed && file_exists($wwwroot.'/'.$amp_conf['mainstyle_css_generated'])) {
    $mainstyle_css = $amp_conf['mainstyle_css_generated'];
  } else {
    include_once('libraries/cssmin.class.php');
    $ms_path = dirname($mainstyle_css);

    // If it's actually set and exists then delete it, we no it has changed
    //
    if (isset($amp_conf['mainstyle_css_generated']) && file_exists($wwwroot.'/'.$amp_conf['mainstyle_css_generated'])) {
      unlink($wwwroot.'/'.$amp_conf['mainstyle_css_generated']);
    }

    // Now generate a new one using the mtime as part of the file name to make it fairly unique
    // it's important to be unique because that will force browsers to reload vs. caching it
    //
    $mainstyle_css_generated = $ms_path.'/mstyle_autogen_'.$stat_mainstyle['mtime'].'.css';
    $css_buff = file_get_contents($mainstyle_css_full_path);

    $css_buff_compressed = CssMin::minify($css_buff);

    $ret = file_put_contents($wwwroot."/".$mainstyle_css_generated,$css_buff_compressed);
    unset($css_buff);
    unset($css_buff_compressed);

    // Now assuming we write something reasonable, we need to save the generated file name and mtimes so
    // next time through this ordeal, we see everything is setup and skip all of this.
    // 
    // we skip this all this if we get back false or 0 (nothing written) in which case we will use the original
    // TOOD: maybe consider a number higher than 0 for sanity check, at least some number of bytes we know it will
    //       always be bigger than?
    //
    // We need to set the value in addition to defining the setting since if already defined the value won't be reset.
    if ($ret) {
      $freepbx_conf =& freepbx_conf::create();

      $settings['value'] = $mainstyle_css_generated;
      $settings['description'] = 'internal use';
      $settings['type'] = CONF_TYPE_TEXT;
      $settings['defaultval'] = '';
      $settings['category'] = 'Internal Use';
      $settings['name'] = 'Auto Generated Copy of Main CSS';
      $settings['level'] = 10;
      $settings['readonly'] = 1;
      $settings['hidden'] = 1;
      $freepbx_conf->define_conf_setting('mainstyle_css_generated',$settings);
      $val_update['mainstyle_css_generated'] = $settings['value'];

      $settings['value'] = $stat_mainstyle['mtime'];
      $settings['name'] = 'Last Mod Time of Main CSS';
      $freepbx_conf->define_conf_setting('mainstyle_css_mtime',$settings);
      $val_update['mainstyle_css_mtime'] = $settings['value'];

      // Update the values (in case these are new) and commit
      $freepbx_conf->set_conf_values($val_update,true,true);

      $mainstyle_css = $mainstyle_css_generated;
    }
  }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- should also validate ok with DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/xhtml1-transitional.dtd" -->
<html>

<head>
	<title><?php  echo _($title) ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="chrome=1">
	<link href="<?php echo $mainstyle_css.$version_tag ?>" rel="stylesheet" type="text/css">
<?php if (isset($use_nav_background) && $use_nav_background) { ?>
	<style type="text/css">
		body {
		background-image: url(<?php echo $shadow_side_background ?>);
		background-repeat: repeat-y;
		background-position: left;
		}
	</style>
<?php } ?>
	<link rel="shortcut icon" href="images/favicon.ico">
<?php 
	if (isset($module_name)) {

    global $active_modules;
    $view_module_version = isset($active_modules[$module_name]['version']) ? $active_modules[$module_name]['version'] : $version_tag;
    $mod_version_tag = '&load_version='.urlencode($view_module_version);
    if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
      $mod_version_tag .= $this_time_append;
    }

    // DEPECRATED but still supported for a while, the assets directory is the new preferred mode
    //
		if (is_file('modules/'.$module_name.'/'.$module_name.'.css')) {
			echo "\t".'<link href="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_name.'.css'.$mod_version_tag.'" rel="stylesheet" type="text/css" />'."\n";
		}
		if (isset($module_page) && ($module_page != $module_name) && is_file('modules/'.$module_name.'/'.$module_page.'.css')) {
			echo "\t".'<link href="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_page.'.css'.$mod_version_tag.'" rel="stylesheet" type="text/css" />'."\n";
		}
	}

  // Check assets/css and then assets/css/page_name for any css files which will have been symlinked to
  // assets/module_name/css/*
  //
	$css_dir = "modules/$module_name/assets/css";
  if (is_dir($css_dir)) {
    $d = opendir($css_dir);
		$file_list = array();
		while ($file = readdir($d)) {
			$file_list[] = $file;
		}
		sort($file_list);
		foreach ($file_list as $file) {
			if (substr($file,-4) == '.css' && is_file("$css_dir/$file")) {
			  echo "\t<link href=\"assets/$module_name/css/$file\" rel=\"stylesheet\" type=\"text/css\" />\n";
			}
		}
		unset($file_list);
		$css_subdir ="$css_dir/$module_page";
		if ($module_page != '' && is_dir($css_subdir)) {
			$sd = opendir($css_subdir);

			$file_list = array();
			while ($p_file = readdir($sd)) {
				$file_list[] = $p_file;
			}
			sort($file_list);
			foreach ($file_list as $p_file) {
				if (substr($p_file,-4) == '.css' && is_file("$css_subdir/$p_file")) {
			    echo "\t<link href=\"assets/$module_name/css/$module_page/$p_file\" rel=\"stylesheet\" type=\"text/css\" />\n";
				}
			}
		}
  }

  // Insert a custom CSS sheet if specified (this can change what is in the main CSS
  if ($custom_css) { ?>
  <link href="<?php echo $custom_css.$version_tag ?>" rel="stylesheet" type="text/css">
<?php } ?>

  <script type="text/javascript" src="common/script.js.php<?php echo $version_tag ?>"></script>
<?php
	// Production versions should include the packed consolidated javascript library but if it
	// is not present (useful for development, then include each individual library below
	//
	if ($amp_conf['USE_PACKAGED_JS'] && file_exists("common/libfreepbx.javascripts.js")) {
?>
  <script type="text/javascript" src="common/libfreepbx.javascripts.js<?php echo $version_tag ?>" language="javascript"></script>
<?php
	} else {
	// TODO: include this in some sort of meta-data or xml file for parsing? Order is important so can't just read the directory
?>
	<script type="text/javascript" src="assets/js/jquery-1.4.x.min.js"></script>
	<script type="text/javascript" src="assets/js/jquery.cookie.js"></script> <!-- plugin for setting/retrieving cookies -->
	<script type="text/javascript" src="assets/js/jquery-ui-1.8.x.min.js"></script>
	<script type="text/javascript" src="assets/js/script.legacy.js"></script> <!-- legacy script.js.php -->
	<script type="text/javascript" src="assets/js/jquery.dimensions.js"></script> <!-- used by reload/module admin -->
	<script type="text/javascript" src="assets/js/jquery.toggleval.3.0.js"></script> <!-- plugin for adding help text to input boxes -->
	<script type="text/javascript" src="assets/js/interface.dim.js"></script> <!-- used for interface blocking (reload, modadmin) -->
	<script type="text/javascript" src="assets/js/tabber-minimized.js"></script> <!-- used for module admin (hiding content) -->
<?php
	}
if (isset($module_name) && $module_name != '') {
	if (is_file('modules/'.$module_name.'/'.$module_name.'.js')) {
		echo "\t".'<script type="text/javascript" src="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_name.'.js'.$mod_version_tag.'"></script>'."\n";
	}
	if (isset($module_page) && ($module_page != $module_name) && is_file('modules/'.$module_name.'/'.$module_page.'.js')) {
		echo "\t".'<script type="text/javascript" src="'.$_SERVER['PHP_SELF'].'?handler=file&amp;module='.$module_name.'&amp;file='.$module_page.'.js'.$mod_version_tag.'"></script>'."\n";
	}

  // Check assets/js and then assets/js/page_name for any js files which will have been symlinked to
  // assets/module_name/js/*
  //
	$js_dir = "modules/$module_name/assets/js";
  if (is_dir($js_dir)) {
    $d = opendir($js_dir);
		$file_list = array();
		while ($file = readdir($d)) {
			$file_list[] = $file;
		}
		sort($file_list);
		foreach ($file_list as $file) {
			if (substr($file,-3) == '.js' && is_file("$js_dir/$file")) {
				echo "\t<script type=\"text/javascript\" src=\"assets/$module_name/js/$file\"></script>\n";
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
				  echo "\t<script type=\"text/javascript\" src=\"assets/$module_name/js/$module_page/$p_file\"></script>\n";
				}
			}
		}
  }

  // DEPCRETATED but still supported:
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
				echo "\t<script type=\"text/javascript\" src=\"{$_SERVER['PHP_SELF']}?handler=file&module=$module_name&file=$js_dir/$file".$mod_version_tag."\"></script>\n";
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
					echo "\t<script type=\"text/javascript\" src=\"{$_SERVER['PHP_SELF']}?handler=file&module=$module_name&file=$js_subdir/$p_file".$mod_version_tag."\"></script>\n";
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
	show_view($amp_conf['VIEW_FREEPBX_RELOAD']);
}
?>
<!-- module process box, used by module admin (page.modules.php) - has to be here because of IE6's z-order stupidity -->
<div id="moduleBox" style="display:none;"></div>

<div id="page">
	<div id="header">
<?php
	echo "\t\t<div id=\"freepbx\"><a href=\"$freepbx_link_l\" target=\"_blank\" title=\"".$freepbx_alt_l."\"><img src=\"$freepbx_logo_l\" alt=\"".$freepbx_alt_l."\" /></a></div>\n";
			
	echo "\t\t<div id=\"version\">";
	if (!$hide_version) {
		echo sprintf(_("%s %s on %s"), 
			"<a href=\"$freepbx_link_l\" target=\"_blank\">$freepbx_alt_l</a>",
			$version,
			"<a href=\"http".(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=''?'s':'')."://".$_SERVER['HTTP_HOST']."\">".$_SERVER["SERVER_NAME"]."</a>"
		 	);
		}
	echo "</div>\n";

	$currentFile = basename($_SERVER["SCRIPT_NAME"]);

	$help_args = "?freepbx_version=".urlencode($version);
	if (isset($_REQUEST['display'])) {
		$help_args .= "&amp;freepbx_menuitem=".urlencode($_REQUEST['display']);
	}
	
  //TODO: really need to make this based out of some sort of XML which we can then have a module manage, or override it based on an optional file if exists
	if (!$hide_toolbar) {
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
	}

  if ($use_freepbx_logo_r) {
	  echo "\t\t<div id=\"logo\"><a href=\"$freepbx_link_r\" target=\"_blank\" title=\"".$freepbx_alt_r."\"><img src=\"$freepbx_logo_r\" alt=\"".$freepbx_alt_r."\" /></a></div>\n";
  }
	
	// need reload bar - hidden by default
	if ($reload_needed) {
		show_view($amp_conf['VIEW_FREEPBX_RELOADBAR']);
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

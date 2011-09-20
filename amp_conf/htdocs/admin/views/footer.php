<?php
global $amp_conf;
global $module_name, $active_modules;

$html = '';
$html .= '</div>';//page_body
$html .= '<div id="footer"><hr />';
$html .= '<div id="footer_content">';
$version			= get_framework_version();
$version_tag		= '?load_version=' . urlencode($version);
if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
  $this_time_append	= '.' . time();
  $version_tag 		.= $this_time_append;
} else {
	$this_time_append = '';
}

// Brandable logos in footer
$freepbx_alt_f	= $amp_conf['BRAND_FREEPBX_ALT_FOOT'] 
				? $amp_conf['BRAND_FREEPBX_ALT_FOOT'] 
				: _("FreePBX&reg;");
$freepbx_logo_f = ($amp_conf['BRAND_IMAGE_FREEPBX_FOOT'] 
				? $amp_conf['BRAND_IMAGE_FREEPBX_FOOT'] 
				: 'images/freepbx_small.png').$version_tag;
$freepbx_link_f = $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] 
				? $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] 
				: 'http://www.freepbx.org';

$html .= '<a target="_blank" href="' . $freepbx_link_f . '">';
$html .= '<img id="footer_logo" src="'.$freepbx_logo_f.'" alt="'.$freepbx_alt_f.'"/></a>';
$html .= '<span class="footer-float-left">';
//$html .= '<h3>'.'Let Freedom Ring<sup>&#153;</sup>'.'</h3>';
$html .= "\t\t".sprintf(_('%s is a registered trademark of %s'),
     '<a href="http://www.freepbx.org" target="_blank">' . _('FreePBX') . '</a>',
     '<a href="http://www.freepbx.org/copyright.html" target="_blank">Bandwidth.com</a>') . "<br/>\n";
$html .= "\t\t".sprintf(_('%s is licensed under %s'),
     '<a href="http://www.freepbx.org" target="_blank">' . _('FreePBX') . ' ' . $version . '</a>',
     '<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GPL</a>');
$html .= br() . sprintf(_('Current module licensed under %s'),
				trim($active_modules[$module_name]['license']));
//echo benchmarking
if (isset($amp_conf['DEVEL']) && $amp_conf['DEVEL']) {
	$benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
	$html .= '<br><span id="benchmark_time">Page loaded in ' . $benchmark_time . 's</span>';
}
$html .= '</span>';
$html .= '</div>'; //footer_content
$html .= '</div>'; //footer
$html .= '</div>'; //page


//add javascript

//localized strings and other javascript values that need to be set dynamically
//TODO: this should be dove via callbacks so that all modules can hook in to it
if (!isset($no_auth)) {
	$fpbx['conf']				= $amp_conf;
	unset($fpbx['conf']['AMPMGRPASS'], 
		$fpbx['conf']['AMPMGRUSER'], 
		$fpbx['conf']['AMPDBUSER'], 
		$fpbx['conf']['AMPDBPASS']);
}

$fpbx['conf']['text_dir']		= isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))
									? 'rtl' : 'ltr';
$fpbx['conf']['uniqueid']		= sql('SELECT data FROM module_xml WHERE id = "installid"', 'getOne');
$fpbx['conf']['dist']			= _module_distro_id();
$fpbx['conf']['ver']			= get_framework_version();
$fpbx['conf']['reload_needed']  = $reload_needed; 
$fpbx['msg']['framework']['reload_unidentified_error'] = _(" error(s) occurred, you should view the notification log on the dashboard or main screen to check for more details.");
$fpbx['msg']['framework']['close'] = _("Close");
$fpbx['msg']['framework']['continuemsg'] = _("Continue");//continue is a resorved word!
$fpbx['msg']['framework']['cancel'] = _("Cancel");
$fpbx['msg']['framework']['retry'] = _("Retry");
$fpbx['msg']['framework']['invalid_responce'] = _("Error: Did not receive valid response from server");
$fpbx['msg']['framework']['validateSingleDestination']['required'] = _('Please select a "Destination"');
$fpbx['msg']['framework']['validateSingleDestination']['error'] = _('Custom Goto contexts must contain the string "custom-".  ie: custom-app,s,1'); 
$fpbx['msg']['framework']['weakSecret']['length'] = _("The secret must be at minimum six characters in length.");
$fpbx['msg']['framework']['weakSecret']['types'] = _("The secret must contain at least two numbers and two letters.");
$html .= "\n" . '<script type="text/javascript">'
		. 'var fpbx='
		. json_encode($fpbx)
 		. '</script>';

$html .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>';
$html .= '<script type="text/javascript" >window.jQuery.ui '
		. '|| document.write(\'<script src="assets/js/jquery-ui-1.8.x.min.js"><\/script>\')</script>';

// Production versions should include the packed consolidated javascript library but if it
// is not present (useful for development, then include each individual library below
if ($amp_conf['USE_PACKAGED_JS'] && file_exists("assets/js/pbxlib.js.php")) {
	$html .= '<script type="text/javascript" src="assets/js/pbxlib.js.php' 
			. $version_tag . '"></script>';
} else {
	/*
	 * files below:
	 * jquery.cookie.js - for setting cookies
	 * script.legacy.js - freepbx library
	 * jquery.toggleval.3.0.js - similar to html5 form's placeholder. depreciated
	 * tabber-minimized.js - sed for module admin (hiding content) 
	 */
	$html .= ' <script type="text/javascript" src="assets/js/menu.js"></script>'
		. '<script type="text/javascript" src="assets/js/jquery.hotkeys.js"></script>'
	 	. '<script type="text/javascript" src="assets/js/jquery.cookie.js"></script>'
	 	. '<script type="text/javascript" src="assets/js/script.legacy.js"></script>'
	 	. '<script type="text/javascript" src="assets/js/jquery.toggleval.3.0.js"></script>'
	 	. '<script type="text/javascript" src="assets/js/tabber-minimized.js"></script>';
}

if (isset($module_name) && $module_name != '') {
	$html .= framework_include_js($module_name, $module_page);
}

if ($amp_conf['BROWSER_STATS']) {
	$ga = "<script type=\"text/javascript\">
			var _gaq=_gaq||[];
			_gaq.push(['_setAccount','UA-25724109-1'],
					['_setCustomVar',1,'type',fpbx.conf.dist.pbx_type,2],
					['_setCustomVar',1,'typever',fpbx.conf.dist.pbx_version,3],
					['_setCustomVar',1,'astver',fpbx.conf.ASTVERSION,3],
					['_setCustomVar',1,'fpbxver',fpbx.conf.ver,3],
					['_setCustomVar',1,'display',$.urlParam('display'),3],
					['_setCustomVar',1,'uniqueid',fpbx.conf.uniqueid,1],
					['_setCustomVar',1,'lang',$.cookie('lang')||'en_US',3],
					['_trackPageview']);
			(function(){
				var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;
				ga.src=('https:'==document.location.protocol
							?'https://ssl':'http://www') 
							+'.google-analytics.com/ga.js';
				var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);
			})();</script>";
	$html .= str_replace(array("\t", "\n"), '', $ga);
}

//add IE specifc styling polyfills
$html .= '<!--[if lte IE 10]>';
$html .= '<link rel="stylesheet" href="assets/css/progress-polyfill.css" type="text/css">';
$html .= '<script type="text/javascript" src="assets/js/progress-polyfill.min.js"></script>';
$html .= '<![endif]-->';
echo $html;
?>
</body>
</html>

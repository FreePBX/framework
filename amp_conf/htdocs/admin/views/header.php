<?php
$version			= get_framework_version();
$version_tag		= '?load_version=' . urlencode($version);
if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
	$this_time_append	= '.' . time();
	$version_tag 		.= $this_time_append;
} else {
	$this_time_append = '';
}

//Chrome Frame and IE sub fixes for IE when it doesnt support html5
if ((isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))) {
	header('X-UA-Compatible: IE=edge,chrome=1');
}

//html head
$html = '';
$html .= '<!DOCTYPE html>'; //html5 functionality support
$html .= '<html>';
$html .= '<head>';
$html .= '<title>'
		. (isset($title) ? _($title) : $amp_conf['BRAND_TITLE'])
		. '</title>';

$html .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8">'
		. '<meta name="robots" content="noindex" />'
		. '<link rel="shortcut icon" href="' . $amp_conf['BRAND_IMAGE_FAVICON'] . '">';

//CSS First THEN JS (http://uxmovement.com/content/why-you-should-place-style-sheets-before-scripts/)
//less compiled into css
foreach($compiled_less_files as $file) {
	$html .= '<link href="assets/less/'.$file.'" rel="stylesheet" type="text/css">';
}

if(!empty($amp_conf['BRAND_CSS_ALT_MAINSTYLE'])) {
	$css_ver = '.' . filectime($amp_conf['BRAND_CSS_ALT_MAINSTYLE']);
	$html .= '<link href="' . $amp_conf['BRAND_CSS_ALT_MAINSTYLE'].$version_tag.$css_ver . '" rel="stylesheet" type="text/css">';
}

//$html .= '<link href="' . $amp_conf['JQUERY_CSS'] . $version_tag . '" rel="stylesheet" type="text/css">';

//add the popover.css stylesheet if we are displaying a popover to override mainstyle.css styling
if ($use_popover_css) {
	$popover_css = $amp_conf['BRAND_CSS_ALT_POPOVER'] ? $amp_conf['BRAND_CSS_ALT_POPOVER'] : 'assets/css/popover.css';
	$html .= '<link href="' . $popover_css.$version_tag . '" rel="stylesheet" type="text/css">';
}

//include rtl stylesheet if using a right to left langauge
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))) {
	$html .= '<link href="assets/css/mainstyle-rtl.css" rel="stylesheet" type="text/css" />';
}

// Insert a custom CSS sheet if specified (this can change what is in the main CSS)
if ($amp_conf['BRAND_CSS_CUSTOM']) {
	$html .= '<link href="' . $amp_conf['BRAND_CSS_CUSTOM'] . $version_tag . '" rel="stylesheet" type="text/css">';
}

//shiv
$html .= '<!--[if lt IE 9]>';
$html .= '<script src="assets/js/html5shiv.js"></script>';
$html .= '<![endif]-->';
$html .= '<!--[if (gte IE 6)&(lte IE 8)]>';
$html .= '<script type="text/javascript" src="assets/js/selectivizr.js"></script>';
$html .= '<![endif]-->';
//Javascripts
$html .= '<script type="text/javascript" src="assets/js/modernizr.js"></script>';
$html .= '<script type="text/javascript" src="assets/js/browser-support.js"></script>';
//CSS3 buttons or jqueryUI buttons
//TODO the jqueryUI code to turn on buttons needs to be moved here eventually
$html .= "<script>var firsttypeofselector = true</script>";
$html .= '<link href="assets/less/' .$extra_compiled_less_files['buttons'] . '" rel="stylesheet" type="text/css">';

//it seems extremely difficult to put jquery in the footer with the other scripts
if ($amp_conf['USE_GOOGLE_CDN_JS']) {
	$html .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>';
	$html .= '<script>window.jQuery || document.write(\'<script src="assets/js/jquery-1.11.2.min.js"><\/script>\')</script>';
} else {
	$html .= '<script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>';
}

//development
if($amp_conf['JQMIGRATE']) {
	$html .= '<script type="text/javascript" src="assets/js/jquery-migrate-1.2.1.js"></script>';
}

// As we have code in the header acting as a class, this has to be up here.
$html .= '<script type="text/javascript" src="assets/js/class.js"></script>';

// Add global variables to be used later
$html .= "<script type='text/javascript'>
	var fpbx = Class.extend({
		params: {},
		init: function() {
			var self = this;
			var path = window.location.pathname.toString().split('/');

			path[path.length - 1] = 'ajax.php';
			if (typeof(window.location.origin) == 'undefined') {
				// Oh look, IE. Hur Dur, I'm a bwowsah.
				window.location.origin = window.location.protocol+'//'+window.location.host;
				if (window.location.port.length != 0) {
					window.location.origin = window.location.origin+':'+window.location.port;
				}
			}
			this.ajaxurl = window.location.origin + path.join('/');
			if (window.location.search.length) {
				var params = window.location.search.split(/\?|&/);
				// NOT using jquery here. This is a bit more annoying, yes, but it means we
				// can move it out of the way later. Note we break compat with IE8 and below
				// here.
				params.forEach(function(v) {
					if (res = v.match(/(.+)=(.+)/)) {
						self.params[res[1]] = res[2];
					}
				});
			}
		}
	});
	window.FreePBX = new fpbx();
</script>";

$html .= '<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="apple-touch-icon" href="assets/images/badge.png" />
<link rel="apple-touch-icon-precomposed" href="assets/images/badge.png" />';

$html .= '</head>';

//open body
$html .= '<body>';

$html .= '<div id="page">';//open page

//add script warning
$html .= '<noscript><div class="attention">'
		. _('WARNING: Javascript is disabled in your browser. '
		. 'The FreePBX administration interface requires Javascript to run properly. '
		. 'Please enable javascript or switch to another  browser that supports it.')
		. '</div></noscript>';

echo $html;

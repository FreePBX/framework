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

$html .= '<link href="' . $amp_conf['JQUERY_CSS'] . $version_tag . '" rel="stylesheet" type="text/css">';

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
//Javascripts
$html .= '<script type="text/javascript" src="assets/js/modernizr.js"></script>';
$html .= '<script type="text/javascript" src="assets/js/browser-support.js"></script>';
//CSS3 buttons or jqueryUI buttons
//TODO the jqueryUI code to turn on buttons needs to be moved here eventually
$html .= "<script>var firsttypeofselector = Modernizr.firsttypeofselector; if(firsttypeofselector) { document.write('<link href=\"assets/less/".$extra_compiled_less_files['buttons']."\" rel=\"stylesheet\" type=\"text/css\">'); }</script>";


//it seems extremely difficult to put jquery in the footer with the other scripts
/* We are using a custom Jquery file for now, it's beta
if ($amp_conf['USE_GOOGLE_CDN_JS']) {
	$html .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $amp_conf['JQUERY_VER'] . '/jquery.min.js"></script>';
	$html .= '<script>window.jQuery || document.write(\'<script src="assets/js/jquery-' . $amp_conf['JQUERY_VER'] . '.min.js"><\/script>\')</script>';
} else {
	$html .= '<script type="text/javascript" src="assets/js/jquery-' . $amp_conf['JQUERY_VER'] . '.min.js"></script>';
}
*/
$html .= '<script type="text/javascript" src="assets/js/jquery-' . $amp_conf['JQUERY_VER'] . '.min.js"></script>';

//development
if($amp_conf['JQMIGRATE']) {
	$html .= '<script type="text/javascript" src="assets/js/jquery-migrate-1.2.1.js"></script>';
}

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

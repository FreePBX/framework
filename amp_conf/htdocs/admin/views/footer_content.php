<?php
global $amp_conf;
$html = '';
$version	 = get_framework_version();
$version_tag = '?load_version=' . urlencode($version);
if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
  $this_time_append	= '.' . time();
  $version_tag 		.= $this_time_append;
} else {
	$this_time_append = '';
}


// Brandable logos in footer
//fpbx logo
$html .= '<a target="_blank" href="' 
		. $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT']
		. '" class ="footer-float-left">'
 	 	. '<img id="footer_logo1" src="'.$amp_conf['BRAND_IMAGE_FREEPBX_FOOT'].$version_tag
		. '" alt="'.$amp_conf['BRAND_FREEPBX_ALT_FOOT'] .'"/></a>';

//text
$html .= '<span class="footer-float-left" id="footer_text">';
$html .= '<a href="http://www.freepbx.org" target="_blank">FreePBX</a> ' 
		. _('is a registered trademark of') 
     	. br() . '<a href="http://www.freepbx.org/copyright.html" target="_blank"> Schmooze Com., Inc.</a>'
		. br();
$html .= _('FreePBX') . ' ' . $version . ' ' . _('is licensed under the')
		. '<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank"> GPL</a>' . br();
$html .= '<a href="http://www.freepbx.org/copyright.html" target="_blank">Copyright&copy; 2007-'.date('Y',time()).'</a>';

//module license
if (!empty($active_modules[$module_name]['license'])) {
  $html .= br() . sprintf(_('Current module licensed under %s'),
  trim($active_modules[$module_name]['license']));
}

//benchmarking
if (isset($amp_conf['DEVEL']) && $amp_conf['DEVEL']) {
	$benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
	$html .= '<br><span id="benchmark_time">Page loaded in ' . $benchmark_time . 's</span>';
}
$html .= '</span>';

$html .= '<a target="_blank" href="' . $amp_conf['BRAND_IMAGE_SPONSOR_LINK_FOOT'] 
		. '" class="footer-float-left">'
		. '<img id="footer_logo" src="' . $amp_conf['BRAND_IMAGE_SPONSOR_FOOT'] . '" '
		. 'alt="' . $amp_conf['BRAND_SPONSOR_ALT_FOOT'] . '"/></a>';
echo $html;
?>

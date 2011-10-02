<?php
global $amp_conf;
$html = '';
$html .= '<div id="footer_content_fpbx">';
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
$html .= '<a target="_blank" href="' . $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT']. '">'
 	 	. '<img id="footer_logo" src="'.$amp_conf['BRAND_IMAGE_FREEPBX_FOOT'].$version_tag
		. '" alt="'.$amp_conf['BRAND_FREEPBX_ALT_FOOT'] .'"/></a>';

//fpbx data
$html .= '<span class="footer-float-left">';
$html .= '<a href="http://www.freepbx.org" target="_blank">FreePBX</a> ' 
		. _('is a registered trademark of') 
     	. '<a href="http://www.freepbx.org/copyright.html" target="_blank"> Bandwidth.com</a>'
		. br();
$html .= _('FreePBX') . ' ' . $version . ' ' . _('is licensed under')
		. '<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank"> GPL</a>';

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
$html .= br() . _('Sponsored by:');
$html .= '<a href="http://www.bandwidth.com" target="_blank">Bandwidth.com</a>' . br();
$html .= '</span>';
$html .= '</div>'; //footer_content_fpbx

//sponsors
$html .= '<div id="footer_content_sponsor" class="footer-float-left">';

$html .= '<a target="_blank" href="http://www.schmoozecom.com">'
		. '<img id="footer_logo" src="images/schmooze-logo.png" '
		. ' style="margin-right:10px"'
		. 'alt="www.schmoozecom.com"/></a>';
$html .= '<span class="footer-float-left">';
$html .= _('The FreePBX project is sponsored in part by:') . br();
$html .= '<a href="http://www.schmoozecom.com" target="_blank">Schmooze Com., Inc.</a>' . br();
$html .= _('Proud sponsors, contributors,').' ' . br()
		. _('and providers of') 
		. ' <a href="http://www.freepbx.org/support-and-professional-services">'
		. _('Professional Support & Services') . '</a>';
//$html .= _('All Rights Reserved');
$html .= '</div>'; //footer_content_sponsor
echo $html;
?>
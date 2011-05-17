<?php
global $amp_conf, $benchmark_starttime;
echo "\n" . '<hr /><div id="footer">';
$version		= get_framework_version();
$version_tag	= '?load_version=' . urlencode($version);
if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
	$this_time_append = '.'.time();
	$version_tag .= $this_time_append;
}
// Brandable logos in footer
$freepbx_alt_f	= $amp_conf['BRAND_FREEPBX_ALT_FOOT'] ? $amp_conf['BRAND_FREEPBX_ALT_FOOT'] : _("FreePBX&reg;");
$freepbx_logo_f = ($amp_conf['BRAND_IMAGE_FREEPBX_FOOT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_FOOT'] : 'images/freepbx_small.png').$version_tag;
$freepbx_link_f = $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] ? $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] : 'http://www.freepbx.org';

echo '<span class="footer-float-left">';
echo '<a target="_blank" href="' . $freepbx_link_f . '">';
echo '<img id="footer_logo" src="'.$freepbx_logo_f.'" alt="'.$freepbx_alt_f.'"/></a>';
echo '</span>';

echo '<span class="footer-float-left">';
echo '<h3>'.'Let Freedom Ring<sup>&#153;</sup>'.'</h3>';
echo "\t\t".sprintf(_('%s is a registered trademark of %s'),
     '<a href="http://www.freepbx.org" target="_blank">' . _('FreePBX') . '</a>',
     '<a href="http://www.freepbx.org/copyright.html" target="_blank">Bandwidth.com</a>') . "<br/>\n";
echo "\t\t".sprintf(_('%s is licensed under %s'),
     '<a href="http://www.freepbx.org" target="_blank">' . _('FreePBX') . ' ' . $version . '</a>',
     '<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GPL</a>')
	."<br />";
//echo benchmarking
if (isset($amp_conf['DEVEL']) && $amp_conf['DEVEL']) {
	$benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
	echo 'Page loaded in ' . $benchmark_time . 's';
}
echo '</span>';
echo '</div>';
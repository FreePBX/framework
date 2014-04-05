<?php
global $amp_conf;
include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
$freepbx_conf =& freepbx_conf::create();

if (!$freepbx_conf->conf_setting_exists('ASTSIPDRIVER')) {
	$set['value'] = 'both';
} else {
	$old_setting = $freepbx_conf->get_conf_setting('ASTSIPDRIVER');
	$freepbx_conf->remove_conf_setting('ASTSIPDRIVER');
	$set['value'] = $old_setting;
}

$set['category'] = 'Dialplan and Operational';
$set['level'] = 2;
$set['defaultval'] = 'both';
$set['options'] = array('both', 'chan_sip', 'chan_pjsip');
$set['name'] = 'SIP Channel Driver';
$set['description'] = 'The Asterisk channel driver to use for SIP. The default is both for Asterisk 12 and higher. For Asterisk 11 and lower the default will be chan_sip. If only one is compiled into asterisk, FreePBX will auto detect and change this value if set wrong. The chan_pjsip channel driver is considered "experimental" with known issues and does not work on Asterisk 11 or lower.';
$set['emptyok'] = 0;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['type'] = CONF_TYPE_SELECT;
$freepbx_conf->define_conf_setting('ASTSIPDRIVER', $set);

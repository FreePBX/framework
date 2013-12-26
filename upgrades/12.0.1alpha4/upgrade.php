<?php
global $amp_conf;
include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
$freepbx_conf =& freepbx_conf::create();


if (!$freepbx_conf->conf_setting_exists('AMPTRACKENABLE')) {
	$set['category'] = 'System Setup';
	$set['level'] = 2;

	$set['value'] = true;
	$set['defaultval'] =& $set['value'];
	$set['options'] = '';
	$set['name'] = 'Enable Module Tracks';
	$set['description'] = 'This enables the setting of module tracks (sub repositories of modules). Whereas a user could select a beta release track of a module or keep it on standard. Disabling this will force all modules into the stable track and disallow users to change the tracks';
	$set['emptyok'] = 0;
	$set['readonly'] = 0;
	$set['hidden'] = 0;
	$set['type'] = CONF_TYPE_BOOL;
	$freepbx_conf->define_conf_setting('AMPTRACKENABLE',$set);
}

if (!$freepbx_conf->conf_setting_exists('ASTSIPDRIVER')) {
	$set['category'] = 'Dialplan and Operational';
	$set['level'] = 2;
	
	// ASTCONFAPP
	$set['value'] = 'chan_sip';
	$set['defaultval'] =& $set['value'];
	$set['options'] = array('chan_sip', 'chan_pjsip');
	$set['name'] = 'SIP Channel Driver';
	$set['description'] = 'The Asterisk channel driver to use for SIP. If only one is compiled into asterisk, FreePBX will auto detect and change this value if set wrong. The chan_pjsip channel driver is considered "experimental" with known issues and does not work on Asterisk 11 or lower.';
	$set['emptyok'] = 0;
	$set['readonly'] = 0;
	$set['hidden'] = 0;
	$set['type'] = CONF_TYPE_SELECT;
	$freepbx_conf->define_conf_setting('ASTSIPDRIVER', $set);
}

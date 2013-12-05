<?php
global $amp_conf;
include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
$freepbx_conf =& freepbx_conf::create();

if ($freepbx_conf->conf_setting_exists('JQUERYUI_VER')) {
	$freepbx_conf->set_conf_values(array('JQUERYUI_VER' => "1.10.3"), true);
}
if ($freepbx_conf->conf_setting_exists('JQUERY_VER')) {
	$freepbx_conf->set_conf_values(array('JQUERY_VER' => "1.11.0-beta2"), true);
}

if (!$freepbx_conf->conf_setting_exists('JQMIGRATE')) {
	$set['category'] = 'Developer and Customization';
	$set['level'] = 2;

	$set['value'] = true;
	$set['defaultval'] =& $set['value'];
	$set['options'] = '';
	$set['name'] = 'Enable jQuery Migrate';
	$set['description'] = 'This plugin can be used to detect and restore APIs or features that have been deprecated in jQuery and removed as of version 1.9';
	$set['emptyok'] = 0;
	$set['readonly'] = 0;
	$set['type'] = CONF_TYPE_BOOL;
	$freepbx_conf->define_conf_setting('JQMIGRATE',$set);
}

if (!$freepbx_conf->conf_setting_exists('BOOTSTRAP_VER')) {
	//JQUERYUI_VER
	$set['value'] = '3.0.2';
	$set['options'] = '';
	$set['defaultval'] =& $set['value'];
	$set['readonly'] = 1;
	$set['hidden'] = 1;
	$set['level'] = 0;
	$set['module'] = '';
	$set['category'] = 'System Setup';
	$set['emptyok'] = 0;
	$set['name'] = 'Bootstrap Version';
	$set['description'] = 'The version of Bootstrap that we wish to use.';
	$set['type'] = CONF_TYPE_TEXT;
	$freepbx_conf->define_conf_setting('BOOTSTRAP_VER', $set);
	$set['hidden'] = 0;

	$freepbx_conf->commit_conf_settings();
}
<?php
global $amp_conf;
include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
$freepbx_conf =& freepbx_conf::create();

//Depreciated advanced settings that no longer apply FREEPBX-12312
$set = array();
$set['hidden'] = 1;
$set['value'] = false;
$set['defaultval'] = false;
if ($freepbx_conf->conf_setting_exists('DYNAMICHINTS')) {
	//$freepbx_conf->set_conf_values(array('DYNAMICHINTS' => false), true);
	//$freepbx_conf->define_conf_setting('DYNAMICHINTS',$set,true);
}

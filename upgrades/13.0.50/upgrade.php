<?php
global $amp_conf;
if(!class_exists('freepbx_conf')) {
	include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
}
$freepbx_conf =& freepbx_conf::create();
if ($freepbx_conf->conf_setting_exists('ASTVARLIBPLAYBACK')) {
	$value = $freepbx_conf->get_conf_setting('ASTVARLIBPLAYBACK');
	$freepbx_conf->remove_conf_setting('ASTVARLIBPLAYBACK');
	$freepbx_conf->set_conf_values(array('AMPPLAYBACK' => $value), true, true);
}

<?php
//add setting for buffer compression callback
global $amp_conf;
include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
$freepbx_conf =& freepbx_conf::create();
$set = array(
		'value'			=> 'ob_gzhandler',
		'defaultval'	=> 'ob_gzhandler',
		'readonly'		=> 1,
		'hidden'		=> 1,
		'level'			=> 6,
		'module'		=> '',
		'category'		=> 'Internal Use',
		'emptyok'		=> 1,
		'name'			=> 'ob_start callback',
		'description'	=> 'This is the callback that will be passed to ob_start.'
						. ' In its default state, ob_gzhandler will be passed which will'
						. ' case all data passed directly by the system to be compressed'
						. ' set this to be blank or something else if this creates issues.',
		'type'			=> CONF_TYPE_TEXT
);
$freepbx_conf->define_conf_setting('buffering_callback', $set);
$freepbx_conf->commit_conf_settings();

?>
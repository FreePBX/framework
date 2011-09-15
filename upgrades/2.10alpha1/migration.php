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
		'category'		=> 'Developer and Customization',
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

$set = array(
		'value'			=> 'http://mirror.freepbx.org',
		'defaultval'	=> 'http://mirror.freepbx.org',
		'readonly'		=> 1,
		'hidden'		=> 1,
		'level'			=> 10,
		'module'		=> '',
		'category'		=> 'Internal Use',
		'emptyok'		=> 0,
		'name'			=> 'repo server',
		'description'	=> 'repo server',
		'type'			=> CONF_TYPE_TEXT
);
$freepbx_conf->define_conf_setting('MODULE_REPO', $set);
$freepbx_conf->commit_conf_settings();

//login view
$set = array(
		'value'			=> 'views/login.php',
		'defaultval'	=> 'views/login.php',
		'readonly'		=> 1,
		'hidden'		=> 1,
		'level'			=> 10,
		'module'		=> '',
		'category'		=> 'Styling and Logos',
		'emptyok'		=> 0,
		'name'			=> 'View: login.php',
		'description'	=> 'login.php view. This should never be changed except for very advanced layout changes',
		'type'			=> CONF_TYPE_TEXT
);
$freepbx_conf->define_conf_setting('VIEW_LOGIN', $set);
$freepbx_conf->commit_conf_settings();


//menu
$set = array(
		'value'			=> 'views/menu.php',
		'defaultval'	=> 'views/menu.php',
		'readonly'		=> 1,
		'hidden'		=> 1,
		'level'			=> 10,
		'module'		=> '',
		'category'		=> 'Styling and Logos',
		'emptyok'		=> 0,
		'name'			=> 'View: menu.php',
		'description'	=> 'menu.php view. This should never be changed except for very advanced layout changes',
		'type'			=> CONF_TYPE_TEXT
);
$freepbx_conf->define_conf_setting('VIEW_MENU', $set);
$freepbx_conf->commit_conf_settings();


//footer
$set = array(
		'value'			=> 'views/footer.php',
		'defaultval'	=> 'views/footer.php',
		'readonly'		=> 1,
		'hidden'		=> 1,
		'level'			=> 10,
		'module'		=> '',
		'category'		=> 'Styling and Logos',
		'emptyok'		=> 0,
		'name'			=> 'View: freepbx.php',
		'description'	=> 'footer.php view. This should never be changed except for very advanced layout changes',
		'type'			=> CONF_TYPE_TEXT
);
$freepbx_conf->define_conf_setting('VIEW_FOOTER', $set);
$freepbx_conf->commit_conf_settings();

//browser stats
$set = array(
		'value'			=> true,
		'defaultval'	=> true,
		'readonly'		=> 0,
		'hidden'		=> 1,
		'level'			=> 10,
		'module'		=> '',
		'category'		=> 'System Setup',
		'emptyok'		=> 0,
		'name'			=> 'Browser Stats',
		'description'	=> 'Anonymous browser stat collection utiltiy for improved visuals '
						. 'and browser targeted devlopment foucus',
		'type'			=> CONF_TYPE_BOOL
);
$freepbx_conf->define_conf_setting('BROWSER_STATS', $set);
$freepbx_conf->commit_conf_settings();

//depreciated
//views
$freepbx_conf->remove_conf_settings('VIEW_FREEPBX');
$freepbx_conf->remove_conf_settings('VIEW_FREEPBX_ADMIN');
$freepbx_conf->remove_conf_settings('VIEW_FREEPBX_RELOAD');
$freepbx_conf->remove_conf_settings('VIEW_FREEPBX_RELOADBAR');
$freepbx_conf->remove_conf_settings('VIEW_FREEPBX_RELOADBAR');
$freepbx_conf->remove_conf_settings('VIEW_UNAUTHORIZED');
$freepbx_conf->remove_conf_settings('VIEW_LOGGEDOUT');
$freepbx_conf->remove_conf_settings('VIEW_LOGGEDOUT');

//settings
global $amp_conf;

$outdated = array(
	$amp_conf['AMPWEBROOT'] . '_asterisk',
	$amp_conf['AMPWEBROOT'].'/admin/common',
	$amp_conf['AMPWEBROOT'].'/admin/views/freepbx.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/freepbx_admin.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/freepbx_reload.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/freepbx_reloadbar.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/freepbx_footer.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/unauthorized.php',
	$amp_conf['AMPWEBROOT'].'/admin/views/loggedout.php',	
);

out("Cleaning up deprecated or moved files:");

foreach ($outdated as $file) {
	outn("Checking $file..");
	if (file_exists($file) && !is_link($file)) {
		unlink($file) ? out("removed") : out("failed to remove");
	} else {
		out("Not Required");
	}
}
?>
<?php
global $amp_conf;

if (!class_exists('freepbx_conf')) {
	include_once ($amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php');
}

$freepbx_conf =& freepbx_conf::create();
$notify = notifications::create($db);

//move freepbx debug log
if ($freepbx_conf->conf_setting_exists('FPBXDBUGFILE')) {
	$freepbx_conf->set_conf_values(array('FPBXDBUGFILE' => $amp_conf['ASTLOGDIR'] . '/freepbx_dbug'), true);
}

$rm_command = function_exists('fpbx_which') ? fpbx_which('rm') : 'rm';

$cdr_dir =  $amp_conf['AMPWEBROOT'] . '/admin/cdr';
outn("Trying to remove dir $cdr_dir..");
if (is_dir($cdr_dir) && !is_link($cdr_dir)) {
	exec($rm_command . ' -rf ' . $cdr_dir, $out, $ret);
	if ($ret) {
		out("could not remove");
	} else {
		out("ok");
	}
} else {
	out("Not Required");
}
?>

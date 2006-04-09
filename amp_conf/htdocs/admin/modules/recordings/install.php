<?php

require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/recordings/functions.inc.php');

$fcc = new featurecode('recordings', 'record_save');
$fcc->setDescription('Save Recording');
$fcc->setDefault('*77');
$fcc->update();
unset($fcc);

$fcc = new featurecode('recordings', 'record_check');
$fcc->setDescription('Check Recording');
$fcc->setDefault('*99');
$fcc->update();
unset($fcc);

// load up any recordings that might be in the directory
$recordings_directory = "/var/lib/asterisk/sounds/custom/";
if (!is_writable($recordings_directory)) {
	print "<h2>Error</h2><br />I can not access the directory $recordings_directory. ";
	print "Please make sure that it exists, and is writable by the web server.";
	die;
}
$dh = opendir($recordings_directory);
while (false !== ($file = readdir($dh))) { // http://au3.php.net/readdir 
	if ($file[0] != "." && $file != "CVS") {
		// Ignore the suffix..
		$fname = ereg_replace('.wav', '', $file);
		recordings_add($fname, "custom/$file");
	}
}
$result = sql("INSERT INTO recordings values ('', '__invalid', 'install done', '')");

?>
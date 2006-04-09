<?php

// Add FeatureCodes for 'core' module
// note i'm not even checking if core is loaded but that doesn't matter
// codes are only used where relevent so doesn't matter if a code is added
// and that module isn't in use

require_once($amp_conf["AMPWEBROOT"].'/admin/functions.inc.php');

$fcc = new featurecode('core', 'userlogon');
$fcc->setDescription('User Logon');
$fcc->setDefault('*11');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'userlogoff');
$fcc->setDescription('User Logoff');
$fcc->setDefault('*12');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'zapbarge');
$fcc->setDescription('ZapBarge');
$fcc->setDefault('888');
$fcc->update();
unset($fcc);

?>

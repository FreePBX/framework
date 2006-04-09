<?php

$fcc = new featurecode('callforward', 'cfon');
$fcc->setDescription('Call Forward All Activate');
$fcc->setDefault('*72');
$fcc->update();
unset($fcc);

$fcc = new featurecode('callforward', 'cfoff');
$fcc->setDescription('Call Forward All Deactivate');
$fcc->setDefault('*73');
$fcc->update();
unset($fcc);

$fcc = new featurecode('callforward', 'cfoff_any');
$fcc->setDescription('Call Forward All Prompting Deativate');
$fcc->setDefault('*74');
$fcc->update();
unset($fcc);

$fcc = new featurecode('callforward', 'cfbon');
$fcc->setDescription('Call Forward Busy Activate');
$fcc->setDefault('*90');
$fcc->update();
unset($fcc);

$fcc = new featurecode('callforward', 'cfboff');
$fcc->setDescription('Call Forward Busy Deativate');
$fcc->setDefault('*91');
$fcc->update();
unset($fcc);

$fcc = new featurecode('callforward', 'cfboff_any');
$fcc->setDescription('Call Forward Busy Prompting Deactive');
$fcc->setDefault('*92');
$fcc->update();
unset($fcc);

?>
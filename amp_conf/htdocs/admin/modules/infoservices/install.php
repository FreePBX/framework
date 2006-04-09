<?php

$fcc = new featurecode('infoservices', 'directory');
$fcc->setDescription('Directory');
$fcc->setDefault('#');
$fcc->update();
unset($fcc);

$fcc = new featurecode('infoservices', 'calltrace');
$fcc->setDescription('Call Trace');
$fcc->setDefault('*69');
$fcc->update();
unset($fcc);	

$fcc = new featurecode('infoservices', 'echotest');
$fcc->setDescription('Echo Test');
$fcc->setDefault('*43');
$fcc->update();
unset($fcc);	

?>
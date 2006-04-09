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

$fcc = new featurecode('infoservices', 'speakingclock');
$fcc->setDescription('Speaking Clock');
$fcc->setDefault('*60');
$fcc->update();
unset($fcc);	

$fcc = new featurecode('infoservices', 'speakextennum');
$fcc->setDescription('Speak Your Exten Number');
$fcc->setDefault('*65');
$fcc->update();
unset($fcc);	

?>
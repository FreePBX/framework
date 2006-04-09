<?php

// Register FeatureCode - Activate
$fcc = new featurecode('callwaiting', 'cwon');
$fcc->setDescription('Call Waiting - Activate');
$fcc->setDefault('*70');
$fcc->update();
unset($fcc);

// Register FeatureCode - Deactivate
$fcc = new featurecode('callwaiting', 'cwoff');
$fcc->setDescription('Call Waiting - Deactivate');
$fcc->setDefault('*71');
$fcc->update();
unset($fcc);	

?>
<?php

// Register FeatureCode - Activate
$fcc = new featurecode('voicemail', 'myvoicemail');
$fcc->setDescription('My Voicemail');
$fcc->setDefault('*98');
$fcc->update();
unset($fcc);

// Register FeatureCode - Deactivate
$fcc = new featurecode('voicemail', 'dialvoicemail');
$fcc->setDescription('Dial Voicemail');
$fcc->setDefault('*96');
$fcc->update();
unset($fcc);

?>
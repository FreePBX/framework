<?php
global $amp_conf;

if (!$db->getAll('SHOW COLUMNS FROM featurecodes WHERE FIELD = "helptext"')) {
	out("Adding helptext to featurecodes table");
    $sql = "ALTER TABLE `featurecodes` ADD COlUMN `helptext` varchar (250) NOT NULL AFTER `description`";
    $db->query($sql);
}
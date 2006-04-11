<?php

global $db;

// Version 1.1 upgrade
$sql = "SELECT description FROM ringgroups";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE ringgroups ADD description VARCHAR( 35 ) NULL ;";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die($result->getDebugInfo());
    }

    // update existing groups
    $sql = "UPDATE ringgroups SET description = CONCAT('Ring Group ', grpnum) WHERE description IS NULL ;";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die($result->getDebugInfo());
    }

	// make new field required
	$sql = "ALTER TABLE `ringgroups` CHANGE `description` `description` VARCHAR( 35 ) NOT NULL ;";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die($result->getDebugInfo());
    }
}

?>


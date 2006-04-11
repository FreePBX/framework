<?php

// Add default TONEZONE of 'us' if no TONEZONE exists already
$sql = "SELECT value FROM globals WHERE variable = 'TONEZONE' ";
$tz = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!is_array($tz)) { // does not exist already
        // Default to 'us'
        $sql = "INSERT INTO globals (variable, value) VALUES ('TONEZONE', 'us') ";
        $result = $db->query($sql);
        if(DB::IsError($result)) {
                die($result->getDebugInfo());
        }
}

// Add column 'channel' to incoming routing
$sql = "SELECT channel FROM incoming";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
        $sql = "ALTER TABLE incoming ADD channel VARCHAR( 20 ) DEFAULT \"\"";
        $result = $db->query($sql);
        if(DB::IsError($result)) {
                die($result->getDebugInfo());
        }
}

// New description on Ring Groups
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

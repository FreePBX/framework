<?php

// Meetme table will only exist if Conferencing module installed
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

?>

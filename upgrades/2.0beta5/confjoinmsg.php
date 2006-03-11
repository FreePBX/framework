<?php

// Meetme table will only exist if Conferencing module installed
$sql = "SELECT exten FROM meetme";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error...module not installed
	$sql = "SELECT joinmsg FROM meetme";
	$confjoin = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($confjoin)) { // error, new field doesn't exist
		$sql = "ALTER TABLE meetme ADD joinmsg VARCHAR( 255 ) NULL";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getDebugInfo());
		}
	}
}
?>


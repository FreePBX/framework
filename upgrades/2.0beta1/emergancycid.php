<?php

$sql = "SELECT * from devices";
$devices = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($devices)) {     
	die($devices->getMessage()); 
}


if(!array_key_exists("emergency_cid",$devices)) {
	$sql = "ALTER TABLE devices ADD emergency_cid VARCHAR( 100 ) NULL";
	$result = $db->query($sql); 
	if(DB::IsError($result)) {     
		die($result->getDebugInfo()); 
	}
}

?>
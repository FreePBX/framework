<?php


outn("Upgrading Inbound Routing to allow for Music on Hold per DID..");

$sql = "SELECT mohclass FROM incoming";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql = "ALTER TABLE incoming ADD mohclass VARCHAR ( 80 ) DEFAULT \"default\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	out("Done");
}


outn("Upgrading Users/Extension Table to allow for Music on Hold per Direct DID..");

$sql = "SELECT mohclass FROM users";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql = "ALTER TABLE users ADD mohclass VARCHAR ( 80 ) DEFAULT \"default\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	out("Done");
}

?>

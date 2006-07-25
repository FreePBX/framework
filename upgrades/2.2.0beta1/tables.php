<?php


outn("Upgrading Inbound Routing to allow for RINGING signalling..");

$sql = "SELECT ringing FROM incoming";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql = "ALTER TABLE incoming ADD ringing VARCHAR ( 20 ) NULL ";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	out("Done");
}

outn("Checking for Global var OPERATOR_XTN..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='OPERATOR_XTN'");
if (!$nrows) {
	$db->query("insert into globals values ('OPERATOR_XTN', '')");
	out("Created");
} else {
	out("Already exists!");
}
?>

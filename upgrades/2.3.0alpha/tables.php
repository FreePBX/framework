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

$sql = "SELECT sipname FROM users";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql = "ALTER TABLE users ADD sipname VARCHAR ( 50 ) NULL ";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	out("Done");
}

outn("Checking for Global var VMX_CONTEXT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_CONTEXT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_CONTEXT', 'from-internal')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_PRI..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_PRI'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_PRI', '1')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_TIMEDEST_CONTEXT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_TIMEDEST_CONTEXT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_TIMEDEST_CONTEXT', '')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_TIMEDEST_EXT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_TIMEDEST_EXT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_TIMEDEST_EXT', 'dovm')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_TIMEDEST_PRI..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_TIMEDEST_PRI'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_TIMEDEST_PRI', '1')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_LOOPDEST_CONTEXT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_LOOPDEST_CONTEXT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_LOOPDEST_CONTEXT', '')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_LOOPDEST_EXT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_LOOPDEST_EXT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_LOOPDEST_EXT', 'dovm')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_LOOPDEST_PRI..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_LOOPDEST_PRI'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_LOOPDEST_PRI', '1')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_OPTS_TIMEOUT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_OPTS_TIMEOUT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_OPTS_TIMEOUT', '')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_OPTS_LOOP..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_OPTS_LOOP'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_OPTS_LOOP', '')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_OPTS_DOVM..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_OPTS_DOVM'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_OPTS_DOVM', '')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_TIMEOUT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_TIMEOUT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_TIMEOUT', '2')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_REPEAT..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_REPEAT'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_REPEAT', '1')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Checking for Global var VMX_LOOPS..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='VMX_LOOPS'");
if (!$nrows) {
	$db->query("insert into globals values ('VMX_LOOPS', '1')");
	out("Created");
} else {
	out("Already exists!");
}

outn("Alter tables sip, iax, zap to increase field length.. ");
$db->query("ALTER TABLE sip CHANGE data data VARCHAR( 255 ) NOT NULL");
$db->query("ALTER TABLE iax CHANGE data data VARCHAR( 255 ) NOT NULL");
$db->query("ALTER TABLE zap CHANGE data data VARCHAR( 255 ) NOT NULL");
out("Altered");

// Create module_xml - this was done in the code before, needed to be pulled out
//
outn("Creating module_xml table..");
$sql = "SELECT * FROM module_xml";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql =	"CREATE TABLE module_xml (
	           id varchar(20) NOT NULL default 'xml',
	           time int(11) NOT NULL default '0',
	           data blob NOT NULL,
	         PRIMARY KEY  (id)
	         )";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	out("Done");
}

// Add id filed to table so more key/values can be kept in it
//
outn("Adding id to module_xml table..");
$sql = "SELECT id FROM module_xml";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	out("Not Required");
} else {
	$sql = "ALTER TABLE module_xml ADD id VARCHAR( 20 ) NOT NULL DEFAULT 'xml' FIRST";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	$sql = "ALTER TABLE module_xml ADD PRIMARY KEY ( id )";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
out("Altered");

?>

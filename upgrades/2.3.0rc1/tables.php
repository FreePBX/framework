<?php

// This will be used to put an optional wait in from-zaptel to handle lines with
// badly behaved CID
//
outn("Checking for Global var ZAPTEL_DELAY..");
$nrows = $db->getOne("SELECT count(*) from globals where variable='ZAPTEL_DELAY'");
if (!$nrows) {
	$db->query("insert into globals values ('ZAPTEL_DELAY', '0')");
	out("Created");
} else {
	out("Already exists!");
}

?>

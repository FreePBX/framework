<?php
if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

global $db;

outn("Add field providedest to featurecodes..");
$sql = "SELECT providedest FROM featurecodes";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
  out("Not Required");
} else {
  $sql = "ALTER TABLE featurecodes ADD providedest TINYINT ( 4 ) NOT NULL DEFAULT '0'";
  $results = $db->query($sql);
  if(DB::IsError($results)) {
    die($results->getMessage());
  }
  out("Done");
}

<?php

function encrypt_passwords()
{
	global $db;
	$pwds = 0;
	out("Updating passwords..");
	$sql = "SELECT * FROM ampusers";
	$users = $db->getAll($sql,NULL,DB_FETCHMODE_ASSOC);
        if (DB::IsError($users)) { // Error while getting the users list to update... bad
		die($users->getMessage());
	} else {
		outn("(".count($users)." accounts) ");	
		foreach ($users as $index => $ufields)
		{
			$sql = "UPDATE ampusers SET password_sha256='".hash("sha256",$ufields['password'])."' WHERE username='".$ufields['username']."'";
			$result = $db->query($sql);
			if (DB::IsError($result)) {
				outn("Error while updating account: ".$ufields['username']." (".$result->getMessage.")");
			} else {
				$pwds++;
			}	
		}
	}
	out("Done.");
}

outn("Checking for sha256 passwords...");
$sql = "SELECT password_sha256 FROM ampusers";
$passfield = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($passfield)) { // no error... Already done
	$sql = "SELECT password FROM ampusers";
	$passfield = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if (DB::IsError($passfield)) { //password field do not exist, done
		out("OK.");
	} else { //Field password still exist, update of passwords is needed.
		encrypt_passwords();
	}
} else {
	if ($passfield->code == DB_ERROR_NOSUCHFIELD) {
		outn("Updating database..");
		$sql = "ALTER TABLE ampusers ADD password_sha256 VARCHAR ( 64 ) NOT NULL AFTER password";
		$results = $db->query($sql);
		if (DB::IsError($results)) {
			die($results->getMessage());
		} else {
			out("Done.");
			encrypt_passwords();
			outn("Removing old password column..");
			$sql = "ALTER TABLE ampusers DROP password";
			$results = $db->query($sql);
			if (DB::IsError($results)) {
        	                die($results->getMessage());
	                } else {
				out("Done.");
			}
		}
	} else { //The error was not about the field...
		die($passfield->getMessage());
	}
}
			
?>

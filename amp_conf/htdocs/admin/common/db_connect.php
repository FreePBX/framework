<?php

require_once('DB.php'); //PEAR must be installed

$db_user = 'asteriskuser';
$db_pass = 'amp109';
$db_host = 'localhost';
$db_name = 'asterisk';
$db_engine = 'mysql';

$datasource = $db_engine.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;

/* datasource in in this style:

dbengine://username:password@host/database */

$db = DB::connect($datasource); // attempt connection

// if connection failed show error
// don't worry about this for now, we get to it in the errors section
if(DB::isError($db)) {
	die($db->getMessage());
}

<?php

define ("WEBROOT", "http://AMPWEBADDRESS/admin/cdr/");
define ("FSROOT", "AMPWEBROOT/admin/cdr/");



define ("LIBDIR", FSROOT."lib/");



define ("HOST", "localhost");
define ("PORT", "5432");
define ("USER", "AMPDBUSER");
define ("PASS", "AMPDBPASS");
define ("DBNAME", "asteriskcdrdb");
define ("DB_TYPE", "mysql"); // mysql or postgres


define ("DB_TABLENAME", "cdr");

// Regarding to the dst you can setup an application name
// Make more sense to have a text that just a number
// especially if you have a lot of extension in your dialplan
$appli_list['*78']=array("dnd-enable");
$appli_list['*79']=array("dnd-disable");
$appli_list['*98']=array("Voicemail");
$appli_list['*72']=array("Call_Forward-enable");
$appli_list['*73']=array("Call_Forward-disable");
$appli_list['*69']=array("Call_Trace");
$appli_list['80']=array("Conf-80");
$appli_list['81']=array("Conf-81");
$appli_list['82']=array("Conf-82");
$appli_list['83']=array("Conf-83");
$appli_list['84']=array("Conf-84");
$appli_list['85']=array("Conf-85");
$appli_list['s']=array("Catch-All");


include (FSROOT."lib/DB-modules/phplib_".DB_TYPE.".php");


function DbConnect()
  {
	
	$DBHandle = new DB_Sql();
	$DBHandle -> Database = DBNAME;
	$DBHandle -> Host = HOST;
	$DBHandle -> User = USER;
	$DBHandle -> Password = PASS;

	$DBHandle -> connect ();


	return $DBHandle;
  }


function getpost_ifset($test_vars)
{
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) { 
		if (isset($_POST[$test_var])) { 
			global $$test_var;
			$$test_var = $_POST[$test_var]; 
		} elseif (isset($_GET[$test_var])) {
			global $$test_var; 
			$$test_var = $_GET[$test_var];
		}
	}
}

?>

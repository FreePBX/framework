<?php
include_once(dirname(__FILE__) . "/lib/defines.php");
include_once(dirname(__FILE__) . "/lib/Class.Table.php");
include_once(dirname(__FILE__) . "/lib/iam_csvdump.php");

session_start();


  #  Set the parameters: SQL Query, hostname, databasename, dbuser and password                                       #
  #####################################################################################################################
  $dumpfile = new iam_csvdump;

  #  Call the CSV Dumping function and THAT'S IT!!!!  A file named dump.csv is sent to the user for download          #
  #####################################################################################################################

if (strlen($_SESSION["pr_sql_export"])<10){
		echo "ERROR CSV EXPORT";
}else{
		//echo $_SESSION["pr_sql_export"];
		  $dumpfile->dump($_SESSION["pr_sql_export"], "Report_cdr_". date("Y-m-d"), "csv", DBNAME, USER, PASS, HOST, DB_TYPE );
		  //$dumpfile->dump($_SESSION["pr_sql_export"], "", "csv", DBNAME, USER, PASS, HOST );
}

?>

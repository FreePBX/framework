<?php

/**
 * @file
 * Functions for the database
 */

/*
 * Database Class
 */
class Database {

  var $error;

  /*
   * Logs into database and returns database handle
   *
   * @param $username
   *   username for database
   * @param $password
   *   password for database
   * @param $host
   *   database host
   * @param $host
   *   database host computer
   * @param $name
   *   database name
   * @param $engine
   *   database type
   * @param $dbh
   *   variable to hold the returned database handle
   */
  function logon($username,$password,$host,$name,$engine,&$dbh) {

    require_once('DB.php'); //PEAR must be installed

    // datasource in in this style: dbengine://username:password@host/database 
    $datasource = $engine.'://'.$username.':'.$password.'@'.$host.'/'.$name;
    
    $dbh = DB::connect($datasource); // attempt connection

    // if connection failed show error
    // don't worry about this for now, we get to it in the errors section
    if(DB::isError($dbh)) {
    	$this->error = $dbh->getDebugInfo(); 
      return false;
    }
    return true;
  } 
} 


?>
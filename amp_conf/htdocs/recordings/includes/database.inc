<?php

/**
 * @file
 * Functions for the database
 */

/*
 * Database Class
 */
class Database {

  /*
   * Constructor
   */
  function Database() {

    // PEAR must be installed
    require_once('DB.php');
  }

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

    // datasource in in this style: dbengine://username:password@host/database 
    $datasource = $engine . '://' . $username . ':' . $password . '@' . $host . '/' . $name;
    
    $dbh = DB::connect($datasource); // attempt connection

    // if connection failed show error
    if(DB::isError($dbh)) {
      $_SESSION['ari_error'] = $dbh->getMessage(); 
      return false;
    }
    return true;
  } 
} 


?>
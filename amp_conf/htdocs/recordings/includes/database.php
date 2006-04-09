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

   * @param $engine
   *   database engine
   * @param $dbfile
   *   database file
   * @param $username
   *   username for database
   * @param $password
   *   password for database
   * @param $host
   *   database host
   * @param $name
   *   database name
   * @return $dbh
   *   variable to hold the returned database handle
   */
  function logon($engine,$dbfile,$username,$password,$host,$name) {

    // connect string
    if ($dbfile) {
      // datasource mostly to support sqlite: dbengine://dbfile?mode=xxxx 
      $dsn = $engine . '://' . $dbfile . '?mode=0666';
    } 
    else {
      // datasource in in this style: dbengine://username:password@host/database 
      $datasource = $engine . '://' . $username . ':' . $password . '@' . $host . '/' . $name;
    }

    // options
    $options = array(
      'portability' => DB_PORTABILITY_ALL,
    );
    
    // attempt connection
    $dbh = DB::connect($datasource,$options); 

    // if connection failed show error
    if(DB::isError($dbh)) {
      $_SESSION['ari_error'] .= $dbh->getMessage() . "<br><br>"; 
      return;
    }
    return $dbh;
  } 
} 


?>
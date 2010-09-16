<?php

die("'odbc.php' is incomplete. Will not be completed. Use PDO instead.\n");

/*
   This is just another SQL interface wrapper. It reimplements the ODBC
   functions in PHP by itself chaining to PEAR::DB (a double wrapper,
   to simplify this initial version).
    - does not use integers as connection_id

   Because any mysql_*() calls are easier replaced with odbc_() funcs,
   but at the same time offer the same degree of database independence,
   this can often make more sense than transitioning to PEAR::DB or the
   ADOdb classes.
    - PEAR::DB provides the saner OO-interface
    - ADOdb is slightly faster, but has a less nicely abstracted API
*/


#-- declare odbc interface functions
if (!function_exists("odbc_connect")) {

   #-- load PEAR::DB
   require_once("DB.php");


   #-- initialize connection
   function odbc_connect($dsn, $user, $password, $cursor_type=NULL) {

      #-- mangle $dsn for PEAR
      $dsn = str_replace("://", "://$user:$password@", $dsn);
      // ... rename dbtype identifiers

      #-- connect
      $c = DB::connect($dsn);
      if (!PEAR::isError($c)) {
         return($c);
      }
   }

   #-- incomplete
   function odbc_pconnect($dsn, $user, $password, $cursor_type=NULL) {
      return odbc_connect($dsn, $user, $password, $cursor_type);
   }
   
   #-- end connection
   function odbc_close($db) {
      $db->disconnect();
   }
   
   
   #-- SQL command execution
   function odbc_exec($db, $query) {
      return $db->query($db);
   }
   function odbc_do($db, $query) {
      return odbc_exec($db, $query);
   }


   #-- sql pre-parsing
   function odbc_prepare($db, $query) {
      return( array($db, $db->prepare($db)) );
   }
   #-- and execution of prepared query
   function odbc_execute($pq, $args=NULL) {
      return $pq[0]->execute($pq[1], $args);
   }


   #-- return result row
   function odbc_fetch_array($res) {
      return $res->fetchRow(DB_FETCHMODE_ASSOC);
   }
   function odbc_fetch_row($res) {
      return $res->fetchRow(DB_FETCHMODE_ORDERED);
   }
   function odbc_fetch_object($res) {
      return $res->fetchRow(DB_FETCHMODE_OBJECT);
   }
   function odbc_fetch_into($res, $count, &$array) {
      $array = array();
      while ($count--) {
         $array[] =  $res->fetchRow(DB_FETCHMODE_ORDERED);
      }
   }
   
   
   #-- more functions on result sets
   function odbc_free_result(&$res) {
      $res->free();
      $res = NULL;
   }
   function odbc_next_result($res) {
      return $res->nextResult();
   }
   function odbc_num_fields($res) {
      return $res->numCols();
   }
   function odbc_num_rows($res) {
      return $res->numRows();
   }
   
   
   #-- and there's more
   //...

   

}


?>
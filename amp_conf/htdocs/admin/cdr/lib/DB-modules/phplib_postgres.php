<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * modified by Ben Drushell http://www.technobreeze.com/
 *
 * $Id$
 *
 */

class DB_Sql {
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";

  var $Link_ID  = 0;
  var $Query_ID = 0;
  var $Record   = array();
  var $Row      = 0;

  var $Halt_On_Error = "report"; ## "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
  var $Seq_Table     = "db_sequence";

  var $Errno    = 0;
  var $Error    = "";

  var $Auto_Free = 0; # Set this to 1 for automatic pg_freeresult on
                      # last record.

  /* copied from db_mysql for completeness */
  /* public: identification constant. never change this. */
  var $type     = "postgres";
  var $revision = "1.2";

  function ifadd($add, $me) {
      if("" != $add) return " ".$me.$add;
  }

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function connect($Database = "", $Host = "", $User = "", $Password = "") {

	  /* Handle defaults */
		if ("" == $Database)
		  $Database = $this->Database;
		if ("" == $Host)
		  $Host     = $this->Host;
		if ("" == $User)
		  $User     = $this->User;
		if ("" == $Password)
		  $Password = $this->Password;


      if ( 0 == $this->Link_ID ) {
          $cstr = "dbname=".$this->Database.
          $this->ifadd($this->Host, "host=").
          $this->ifadd($this->Port, "port=").
          $this->ifadd($this->User, "user=").
          $this->ifadd($this->Password, "password=");
		  
          $this->Link_ID=pg_connect($cstr);
          if (!$this->Link_ID) {
              $this->halt("Link-ID == false, pconnect failed");
          }
      }
      return($this->Link_ID);
  }

  function query($Query_String) {
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "")
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      return 0;

    $this->connect();

	//printf("<br>Debug: query = %s<br>\n", $Query_String);

    $this->Query_ID = @ pg_Exec($this->Link_ID, $Query_String);
    $this->Row   = 0;

	//echo "<br>:::::::::>".pg_ErrorMessage($this->Link_ID);
    $this->Error = pg_ErrorMessage($this->Link_ID);
    $this->Errno = ($this->Error == "")?0:1;

	
    /*if (!$this->Query_ID) {
      $this->halt("Invalid SQL: ".$Query_String);
    } */

    return $this->Query_ID;
  }


  function next_record() {
    $this->Record = @pg_fetch_array($this->Query_ID, $this->Row++, PGSQL_NUM);
	// PGSQL_NUM  ::  PGSQL_ASSOC
	//print_r ($this->Record);

    $this->Error = pg_ErrorMessage($this->Link_ID);
    $this->Errno = ($this->Error == "")?0:1;

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      pg_freeresult($this->Query_ID);
      $this->Query_ID = 0;
    }
    return $stat;
  }

  function seek($pos) {
    $this->Row = $pos;
  }

  function lock($table, $mode = "write") {
    if ($mode == "write") {
      $result = pg_Exec($this->Link_ID, "lock table $table");
    } else {
      $result = 1;
    }
    return $result;
  }

  function unlock() {
    return pg_Exec($this->Link_ID, "commit");
  }


  /* public: sequence numbers */
  function nextid($seq_name) {
    $this->connect();

    if ($this->lock($this->Seq_Table)) {
      /* get sequence number (locked) and increment */
      $q  = sprintf("select nextid from %s where seq_name = '%s'",
                $this->Seq_Table,
                $seq_name);
      $id  = @pg_Exec($this->Link_ID, $q);
      $res = @pg_Fetch_Array($id, 0);

      /* No current value, make one */
      if (!is_array($res)) {
        $currentid = 0;
        $q = sprintf("insert into %s values('%s', %s)",
                 $this->Seq_Table,
                 $seq_name,
                 $currentid);
        $id = @pg_Exec($this->Link_ID, $q);
      } else {
        $currentid = $res["nextid"];
      }
      $nextid = $currentid + 1;
      $q = sprintf("update %s set nextid = '%s' where seq_name = '%s'",
               $this->Seq_Table,
               $nextid,
               $seq_name);
      $id = @pg_Exec($this->Link_ID, $q);
      $this->unlock();
    } else {
      $this->halt("cannot lock ".$this->Seq_Table." - has it been created?");
      return 0;
    }
    return $nextid;
  }



  function metadata($table) {
    $count = 0;
    $id    = 0;
    $res   = array();

    $this->connect();
    $id = pg_exec($this->Link_ID, "select * from $table");

    if ($id < 0) {
      $this->Error = pg_ErrorMessage($id);
      $this->Errno = 1;
      $this->halt("Metadata query failed.");
    }
    $count = pg_NumFields($id);
    
    for ($i=0; $i<$count; $i++) {
      $res[$i]["table"] = $table;
      $res[$i]["name"]  = pg_FieldName  ($id, $i); 
      $res[$i]["type"]  = pg_FieldType  ($id, $i);
      $res[$i]["len"]   = pg_FieldSize  ($id, $i);
      $res[$i]["flags"] = "";
    }
    
    pg_FreeResult($id);
    return $res;
  }

  function affected_rows() {
    return pg_cmdtuples($this->Query_ID);
  }

  function num_rows() {
    return @pg_numrows($this->Query_ID);
  }

  function num_fields() {
    return pg_numfields($this->Query_ID);
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    return $this->Record[$Name];
  }

  function p($Name) {
    print $this->Record[$Name];
  }
  
  function halt($msg) {
    if ($this->Halt_On_Error == "no")
      return;
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>PostgreSQL Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
    if ($this->Halt_On_Error != "report")
      die("Session halted.");
  }

  function table_names() {
    $this->query("select relname from pg_class where relkind = 'r' and not relname like 'pg_%'");
    $i=0;
    while ($this->next_record())
     {
      $return[$i]["table_name"]= $this->f(0);
      $return[$i]["tablespace_name"]=$this->Database;
      $return[$i]["database"]=$this->Database;
      $i++;
     }
    return $return;
  }

  /* public: sequence numbers */
  function currentid($seq_name) {
    $this->connect();

    $currentid = 0;
    $q  = sprintf("select nextid from %s where seq_name = '%s'",
              $this->Seq_Table,
              $seq_name);
    $id  = @pg_exec($this->Link_ID,$q);
    $res = @pg_fetch_array($id,0);

    /* No current value, make one */
    if (is_array($res)) {
      $currentid = $res["nextid"];
    }
    return $currentid;
  }
}
?>

<?php
/*
 * Access/ODBC accessor based on Session Management for PHP3
 *
 * modifications (C) Copyright 2001 Ben Drushell http://www.technobreeze.com/
 *
 * based on db_odbc.inc by Cameron Taggart and Guarneri Carmelo
 * based on db_mysql.inc by Boris Erdmann and Kristian Koehntopp
 *
 * $Id$
 *
 */

class DB_Sql {
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";
  var $UseODBCCursor = 0;

  var $Link_ID  = 0;
  var $Query_ID = 0;
  var $Record   = array();
  var $Row      = 0;

  var $Errno    = 0;
  var $Error    = "";

  var $Transaction_Status = 0; ## used by lock and unlock

  var $Auto_Free = 0;     ## set this to 1 to automatically free results
  var $Halt_On_Error = "report"; ## "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
  var $Seq_Table     = "db_sequence";

  /* public: this is an api revision, not a CVS revision. */
  var $type     = "odbc-access";
  var $revision = "1.2";

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function connect() {
    if ( 0 == $this->Link_ID ) {
      $this->Link_ID=odbc_pconnect($this->Database, $this->User, $this->Password, $this->UseODBCCursor);
      if (!$this->Link_ID) {
        $this->halt("Link-ID == false, odbc_pconnect failed");
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

#   printf("<br>Debug: query = %s<br>\n", $Query_String);

#   rei@netone.com.br suggested that we use this instead of the odbc_do().
#   He is on NT, connecting to a Unix MySQL server with ODBC. -- KK
#    $this->Query_ID = odbc_prepare($this->Link_ID,$Query_String);
#    $this->Query_Ok = odbc_execute($this->Query_ID);

    $this->Query_ID = odbc_do($this->Link_ID,$Query_String);
    $this->Row = 0;
    odbc_binmode($this->Query_ID, 1);
    odbc_longreadlen($this->Query_ID, 4096);

    if (!$this->Query_ID) {
      $this->Errno = 1;
      $this->Error = "General Error (The ODBC interface cannot return detailed error messages).";
      $this->halt("Invalid SQL: ".$Query_String);
    }
    return $this->Query_ID;
  }

  function next_record() {
    $this->Record = array();
    $stat      = odbc_fetch_into($this->Query_ID, ++$this->Row, &$this->Record);
    if (!$stat) {
      if ($this->Auto_Free) {
        odbc_free_result($this->Query_ID);
        $this->Query_ID = 0;
      };
    } else {
      // add to Record[<key>]
      $count = odbc_num_fields($this->Query_ID);
      for ($i=1; $i<=$count; $i++)
        $this->Record[strtolower(odbc_field_name ($this->Query_ID, $i)) ] = $this->Record[ $i - 1 ];
    }
    return $stat;
  }

  function seek($pos) {
    $this->Row = $pos;
  }

  function metadata($table) {
    $count = 0;
    $id    = 0;
    $res   = array();

    $this->connect();
    $id = odbc_do($this->Link_ID, "select * from $table");
    if (!$id) {
      $this->Errno = 1;
      $this->Error = "General Error (The ODBC interface cannot return detailed error messages).";
      $this->halt("Metadata query failed.");
    }
    $count = odbc_num_fields($id);

    for ($i=1; $i<=$count; $i++) {
      $res[$i]["table"] = $table;
      $name             = odbc_field_name ($id, $i);
      $res[$i]["name"]  = $name;
      $res[$i]["type"]  = odbc_field_type ($id, $name);
      $res[$i]["len"]   = 0;  // can we determine the width of this column?
      $res[$i]["flags"] = ""; // any optional flags to report?
    }

    odbc_free_result($id);
    return $res;
  }

  function affected_rows() {
    return odbc_num_rows($this->Query_ID);
  }

  function num_rows() {
    # Many ODBC drivers don't support odbc_num_rows() on SELECT statements.
    $num_rows = odbc_num_rows($this->Query_ID);
    //printf ($num_rows."<br>");

    # This is a workaround. It is intended to be ugly.
    if ($num_rows < 0) {
      $i=10;
      while (odbc_fetch_row($this->Query_ID, $i))
        $i*=10;

      $j=0;
      while ($i!=$j) {
        $k= $j+intval(($i-$j)/2);
        if (odbc_fetch_row($this->Query_ID, $k))
          $j=$k;
        else
          $i=$k;
        if (($i-$j)==1) {
          if (odbc_fetch_row($this->Query_ID, $i))
            $j=$i;
          else
            $i=$j;
        };
        //printf("$i $j $k <br>");
      };
      $num_rows=$i;
    }

    return $num_rows;
  }

  function num_fields() {
    return count($this->Record)/2;
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Field_Name) {
    return $this->Record[strtolower($Field_Name)];
  }

  function p($Field_Name) {
    print $this->f($Field_Name);
  }

  function halt($msg) {
    if($this->Halt_On_Error == "no")
      return;
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>ODBC Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
    if($this->Halt_On_Error != "report")
      die("Session halted.");
  }

/* public: table locking
 * Table locking is automatic in MS SQL and Access.
 * However, the automatic locking is based on session
 *  and transaction.
 * To better assure locking occurs at a certain area of
 *  a script the transaction statement is used.
 */
  function lock($table, $mode="write") {
    if($this->Transaction_Status) {
      $this->halt("transaction in progress... look for abandoned transaction");
      return 0;
    }

    $this->connect();

    $query="BEGIN TRANSACTION";
    $res = @odbc_do($this->Link_ID,$query);
    if (!$res) {
      $this->halt("transaction begin failed.");
      $this->Transaction_Status = 0;
      return 0;
    }
    $this->Transaction_Status = 1;
    return $res;
  }

/* public: table unlocking
 * Table locking is automatic in MS SQL and Access.
 * However, the automatic locking is based on database
 *  sessions and transactions.
 * To better assure locking occurs at a certain area of
 *  a script the transaction statement is used.
 */
  function unlock() {
    $this->connect();

    if($this->Transaction_Status) {
      $query="COMMIT TRANSACTION";
      $res = @odbc_do($this->Link_ID,$query);
      $this->Transaction_Status = 0;
      if (!$res) {
        $this->halt("transaction commit failed.");
        return 0;
      }
    } else {
      $res = @odbc_commit($this->Link_ID);
      /* do not give error if commit does not work...
       * some db-drivers do not support odbc_commit
       */
    }
    return $res;
  }

    /* public: sequence numbers */
  function nextid($seq_name) {
    $this->connect();

    if ($this->lock($this->Seq_Table)) {
      /* get sequence number (locked) and increment */
      $q  = sprintf("select nextid from %s where seq_name = '%s'",
                $this->Seq_Table,
                $seq_name);
      $id  = @odbc_do($this->Link_ID,$q);
      if($id == 0) {
        $this->unlock();
        $this->halt("cannot access ".$this->Seq_Table." - has it been created?");
        return 0;
      }

      $res = array();
      $stat      = odbc_fetch_into($id,&$res);
      if (!$stat) {
        if ($this->Auto_Free) {
          odbc_free_result($id);
          $id = 0;
        }
        $res = "";
      } else {
        // add to res[<key>]
        $count = odbc_num_fields($id);
        for ($i=1; $i<=$count; $i++)
          $res[strtolower(odbc_field_name ($id, $i)) ] = $res[ $i - 1 ];
      }

      /* No current value, make one */
      if (!is_array($res)) {
        $currentid = 0;
        $q = sprintf("insert into %s values('%s', %s)",
                 $this->Seq_Table,
                 $seq_name,
                 $currentid);
        $id = @odbc_do($this->Link_ID,$q);
      } else {
        $currentid = $res["nextid"];
      }
      $nextid = $currentid + 1;
      $q = sprintf("update %s set nextid = '%s' where seq_name = '%s'",
               $this->Seq_Table,
               $nextid,
               $seq_name);
      $id = @odbc_do($this->Link_ID,$q);
      $this->unlock();
    }
    return $nextid;
  }

    /* public: sequence numbers */
  function getid($seq_name) {
    $this->connect();

    $q  = sprintf("select nextid from %s where seq_name = '%s'",
              $this->Seq_Table,
              $seq_name);
    $id  = @odbc_do($this->Link_ID,$q);

    $res = array();
    $stat      = odbc_fetch_into($id,&$res);
    if (!$stat) {
      if ($this->Auto_Free) {
        odbc_free_result($id);
        $id = 0;
      }
      $res = "";
    } else {
      // add to res[<key>]
      $count = odbc_num_fields($id);
      for ($i=1; $i<=$count; $i++)
        $res[strtolower(odbc_field_name ($id, $i)) ] = $res[ $i - 1 ];
    }

    /* No current value, make one */
    if (!is_array($res)) {
      $currentid = 0;
    } else {
      $currentid = $res["nextid"];
    }
    return $currentid;
  }
}
?>


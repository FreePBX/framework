<?php
      
/*
 * Oracle/OCI8 accessor based on Session Management for PHP3
 *
 * (C) Copyright 1999-2000 Stefan Sels phplib@sels.com
 *
 * based on db_oracle.inc by Luis Francisco Gonzalez Hernandez
 * contains metadata() from db_oracle.inc 1.10
 *
 * modified by Ben Drushell http://www.technobreeze.com/
 *
 * $Id$
 *
 */ 

class DB_Sql {
  var $Debug    =  0;
  var $sqoe     =  1; // sqoe= show query on error

  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";

  var $Oci8PutEnv = true;

  var $Link_ID    = 0;
  var $Record    = array();
  var $Row;
  var $Parse;
  var $Error     = "";

  var $Halt_On_Error = "report"; ## "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
  var $Seq_Table     = "db_sequence";

  /* copied from db_mysql for completeness */
  /* public: identification constant. never change this. */
  var $type     = "oci8";
  var $revision = "1.2";

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

    
	  
	# Ben Drushell - added Oci8PutEnv for consistancy with db_oracle
      if ($this->Oci8PutEnv) {
        PutEnv("ORACLE_SID=$this->Database");
        PutEnv("ORACLE_HOME=$this->Host");
      }
      if ( 0 == $this->Link_ID ) {
          if($this->Debug) {
              printf("<br>Connecting to $this->Database...<br>\n");
          }
          $this->Link_ID=OCIplogon
                ("$this->User","$this->Password","$this->Database");

          if (!$this->Link_ID) {
              $this->halt("Link-ID == false " .
                          "($this->Link_ID), OCILogon failed");
          }
          
          if($this->Debug) {
              printf("<br>Obtained the Link_ID: $this->Link_ID<br>\n");
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

       $this->Parse=OCIParse($this->Link_ID,$Query_String);
      if(!$this->Parse) {
           $this->Error=OCIError($this->Parse);
      } else { OCIExecute($this->Parse);
          $this->Error=OCIError($this->Parse); 
      }

      $this->Row=0;

      if($this->Debug) {
          printf("Debug: query = %s<br>\n", $Query_String);
      }
      
      if ($this->Error["code"]!=1403 && $this->Error["code"]!=0 && $this->sqoe) 
      echo "<BR><FONT color=red><B>".$this->Error["message"]."<BR>Query :\"$Query_String\"</B></FONT>";
      return $this->Parse;
  }
  
  function next_record() {
      if(0 == OCIFetchInto($this->Parse,$result,OCI_ASSOC+OCI_RETURN_NULLS)) {
          if ($this->Debug) {
            printf("<br>ID: %d,Rows: %d<br>\n",
              $this->Link_ID,$this->num_rows());
          }
          $this->Row        +=1;
          
          $errno=OCIError($this->Parse);
          if(1403 == $errno) { # 1043 means no more records found
              $this->Error="";
              $this->disconnect();
              $stat=0;
          } else {
              $this->Error=OCIError($this->Parse);
              if($this->Debug) {
                  printf("<br>Error: %s",
                  $this->Error["message"]);
              }
              $stat=0;
          }
      } else { 
          for($ix=1;$ix<=OCINumcols($this->Parse);$ix++) {
              $col=strtoupper(OCIColumnname($this->Parse,$ix));
              $colreturn=strtolower($col);
              $this->Record[ "$colreturn" ] = $result["$col"]; 
              if($this->Debug) echo"<b>[$col]</b>:".$result["$col"]."<br>\n";
          }
          $stat=1;
      }

  return $stat;
  }

  function seek($pos) {
      $this->Row=$pos;
  }

  function metadata($table,$full=false) {
      $count = 0;
      $id    = 0;
      $res   = array();
      
    /*
     * Due to compatibility problems with Table we changed the behavior
     * of metadata();
     * depending on $full, metadata returns the following values:
     *
     * - full is false (default):
     * $result[]:
     *   [0]["table"]  table name
     *   [0]["name"]   field name
     *   [0]["type"]   field type
     *   [0]["len"]    field length
     *   [0]["flags"]  field flags ("NOT NULL", "INDEX")
     *   [0]["format"] precision and scale of number (eg. "10,2") or empty
     *   [0]["index"]  name of index (if has one)
     *   [0]["chars"]  number of chars (if any char-type)
     *
     * - full is true
     * $result[]:
     *   ["num_fields"] number of metadata records
     *   [0]["table"]  table name
     *   [0]["name"]   field name
     *   [0]["type"]   field type
     *   [0]["len"]    field length
     *   [0]["flags"]  field flags ("NOT NULL", "INDEX")
     *   [0]["format"] precision and scale of number (eg. "10,2") or empty
     *   [0]["index"]  name of index (if has one)
     *   [0]["chars"]  number of chars (if any char-type)
     *   ["meta"][field name]  index of field named "field name"
     *   The last one is used, if you have a field name, but no index.
     *   Test:  if (isset($result['meta']['myfield'])) {} ...
     */

      $this->connect();

      ## This is a RIGHT OUTER JOIN: "(+)", if you want to see, what
      ## this query results try the following:
      ## $table = new Table; $db = new my_DB_Sql; # you have to make
      ##                                          # your own class
      ## $table->show_results($db->query(see query vvvvvv))
      ##
      $this->query("SELECT T.table_name,T.column_name,T.data_type,".
           "T.data_length,T.data_precision,T.data_scale,T.nullable,".
           "T.char_col_decl_length,I.index_name".
           " FROM ALL_TAB_COLUMNS T,ALL_IND_COLUMNS I".
           " WHERE T.column_name=I.column_name (+)".
           " AND T.table_name=I.table_name (+)".
           " AND T.table_name=UPPER('$table') ORDER BY T.column_id");
      
      $i=0;
      while ($this->next_record()) {
        $res[$i]["table"] =  $this->Record[table_name];
        $res[$i]["name"]  =  strtolower($this->Record[column_name]);
        $res[$i]["type"]  =  $this->Record[data_type];
        $res[$i]["len"]   =  $this->Record[data_length];
        if ($this->Record[index_name]) $res[$i]["flags"] = "INDEX ";
        $res[$i]["flags"] .= ( $this->Record[nullable] == 'N') ? '' : 'NOT NULL';
        $res[$i]["format"]=  (int)$this->Record[data_precision].",".
                             (int)$this->Record[data_scale];
        if ("0,0"==$res[$i]["format"]) $res[$i]["format"]='';
        $res[$i]["index"] =  $this->Record[index_name];
        $res[$i]["chars"] =  $this->Record[char_col_decl_length];
        if ($full) {
                $j=$res[$i]["name"];
                $res["meta"][$j] = $i;
                $res["meta"][strtoupper($j)] = $i;
        }
        if ($full) $res["meta"][$res[$i]["name"]] = $i;
        $i++;
      }
      if ($full) $res["num_fields"]=$i;
#      $this->disconnect();
      return $res;
  }


  function affected_rows() {
    return $this->num_rows();
  }

  function num_rows() {
    return OCIrowcount($this->Parse);
  }

  function num_fields() {
      return OCINumcols($this->Parse);
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    if (is_object($this->Record[$Name]))
    {
      return $this->Record[$Name]->load();
    } else
    {
      return $this->Record[$Name];
    }
  }

  function p($Name) {
    print $this->f($Name);
  }

  function nextid($seqname)
  {
    $this->connect();

    $Query_ID=@ociparse($this->Link_ID,"SELECT $seqname.NEXTVAL FROM DUAL");

    if(!@ociexecute($Query_ID))
    {
    $this->Error=@OCIError($Query_ID);
    if($this->Error["code"]==2289)
    {
        $Query_ID=ociparse($this->Link_ID,"CREATE SEQUENCE $seqname");
        if(!ociexecute($Query_ID))
        {
        $this->Error=OCIError($Query_ID);
        $this->halt("<BR> nextid() function - unable to create sequence<br>".$this->Error["message"]);
        } else
         {
        $Query_ID=ociparse($this->Link_ID,"SELECT $seqname.NEXTVAL FROM DUAL");
        ociexecute($Query_ID);
        }
    }
    }

    if (ocifetch($Query_ID))
    {
       $next_id = ociresult($Query_ID,"NEXTVAL");
    } else
    {
       $next_id = 0;
    }
    ocifreestatement($Query_ID);
    return $next_id;
  }

  function disconnect() {
      if($this->Debug) {
          printf("Disconnecting...<br>\n");
      }
      OCILogoff($this->Link_ID);
  }

  function halt($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>ORACLE Error</b>: %s<br>\n",
      $this->Error["message"]);
    die("Session halted.");
  }

  function lock($table, $mode = "write") {
    $this->connect();
    if ($mode == "write") {
      $Parse=OCIParse($this->Link_ID,"lock table $table in row exclusive mode");
      OCIExecute($Parse);
    } else {
      $result = 1;
    }
    return $result;
  }

  function unlock() {
    return $this->query("commit");
  }

  function table_names() {
   $this->connect();
   $this->query("
   SELECT table_name,tablespace_name
     FROM user_tables");
   $i=0;
   while ($this->next_record())
   {
   $info[$i]["table_name"]     =$this->Record["table_name"];
   $info[$i]["tablespace_name"]=$this->Record["tablespace_name"];
   $i++;
   }
  return $info;
  }

  function add_specialcharacters($query)
  {
  return str_replace("'","''",$query);
  }

  function split_specialcharacters($query)
  {
  return str_replace("''","'",$query);
  }

  function currentid($seqname)
  {
    $this->connect();

    $Query_ID=@ociparse($this->Link_ID,"SELECT $seqname.CURRVAL FROM DUAL");
    @ociexecute($Query_ID);
    if(@ocifetch($Query_ID))
    {
       $current_id = ociresult($Query_ID,"CURRVAL");
    } else {
       $current_id = 0;
    }
    ocifreestatement($Query_ID);
    return $current_id;
  }
}
?>

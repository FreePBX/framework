<?php
# Ciao-SQL - an abstraction of phpLib's database classes
# Copyright (C) 2001 Ben Drushell
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published
# by the Free Software Foundation; version 2.1 of the License.
#
# This program is distributed in the hope that it will be useful.
# There is NO WARRANTY.  NO implied warranty of MERCHANTABILITY.
# NO implied warranty of FITNESS FOR A PARTICULAR PURPOSE.
# The entire risk is with you.
# See the GNU Lesser General Public License for more details.
#
# A copy of the GNU Lesser General Public License is included with this program
# and is also available at http://www.technobreeze.com/license/lgpl.txt
#---------------------------------------------------------
# FILE: ciaosql.php
# VERSION: 0.0.01
# CREATED ON: 2001.07.17
# CREATED BY: Ben Drushell - http://www.technobreeze.com/
# CONTRIBUTORS:
#
#---------------------------------------------------------
?>

<?php
# SHORT DESCRIPTION
# This module provides an abstract interface for connecting to an SQL database.
#---------------------------------------------------------
?>

<?php
class CiaoSQL extends DB_Sql # DB_Sql is provided in phpLib under the terms of LGPL.
{
    var $tableprefix = ""; # saved table prefix value
    var $altprefix = ""; # saved alternative prefix used by some modules

# Laziness on not wanting to use an underscore...
###############################
    function nextrecord()
    { return $this->next_record(); }

# Use "clone" in situations where multiple queries need to run simultaneously.
# In other words, output from one looping query is used as input for another.
###############################
    function clone($SQL)
    {
    # CiaoSQL properties
        $this->tableprefix = $SQL->tableprefix;
        $this->altprefix   = $SQL->altprefix;
    # DB_SQL properties (parent class)
        $this->Host        = $SQL->Host;
        $this->Database    = $SQL->Database;
        $this->User        = $SQL->User;
        $this->Password    = $SQL->Password;
        $this->Link_ID     = $SQL->Link_ID;
        $this->Seq_Table   = $SQL->Seq_Table;
    }

# Use "nid" in place of "nextid" function.
# "nid" handles "PREFIX" and "ALTPREFIX" data in queries.
###############################
    function nid($input)
    {
        $input = str_replace("ALTPREFIX",$this->altprefix,$input);
        $input = str_replace("PREFIX",$this->tableprefix,$input);
        $result = $this->nextid(md5($input));
        return($result);
    }

# Use "cid" in place of "currentid" function.
# "cid" handles "PREFIX" and "ALTPREFIX" data in queries.
###############################
    function cid($input)
    {
        $input = str_replace("ALTPREFIX",$this->altprefix,$input);
        $input = str_replace("PREFIX",$this->tableprefix,$input);
        $result = $this->currentid(md5($input));
        return($result);
    }

# This "limit" function is a generic replacement to the SQL LIMIT statement
# that is only available in MySQL and Postgres.
# It is NOT as efficient, and needs to be added into the WHERE clause.
###############################
    function limit($field,$offset=0,$length=20)
    {
        if($field == '')
        { $this->halt("Ciao-SQL Error: TABLE field is empty in 'limit' function."); return(""); }
        $query = "($field >= '" . (0 + $offset) . "' AND $field < '" . (0 + $offset + $length) . "')";
        return($query);
    }

# Use "locktable" function in place of "lock" function.
# "locktable" handles "PREFIX" and "ALTPREFIX" data in queries.
###############################
    function locktable($table,$mode="write")
    {
        $table = str_replace("ALTPREFIX",$this->altprefix,$table);
        $table = str_replace("PREFIX",$this->tableprefix,$table);
        $result = $this->lock($table,$mode);
        return($result);
    }

# "unlocktable" is provided to be consistant with "locktable" function.
###############################
    function unlocktable()
    {
        $result = $this->unlock();
        return($result);
    }

# "unlocktables" is provided to be consistant with "locktable" function.
###############################
    function unlocktables()
    {
        $result = $this->unlock();
        return($result);
    }

# Use "q" in place of "query" function.
# "q" handles "PREFIX" and "ALTPREFIX" data in queries.
# "q" also handles optional table locking.
###############################
    function q($sqlstmt,$lock="")
    {
        $sqlstmt = str_replace("ALTPREFIX",$this->altprefix,$sqlstmt);
        $sqlstmt = str_replace("PREFIX",$this->tableprefix,$sqlstmt);

        if(strlen($lock) > 0)
        {
            $this->locktable($lock);
            $result = $this->query($sqlstmt);
            $this->unlocktable();
        }
        else
        {
            $result = $this->query($sqlstmt);
        }

        return($result);
    }

# "translate" alters database create/alter commands from generic to sql-type-specific
###############################
# LIST OF VALID CiaoSQL DATA TYPES:
# CHAR     => syntax CHAR(size)
# UINT     => unsigned integer, large size
# SINT     => signed integer, normal size (about 4 bytes)
# FLOAT    => largest available for each server
# DATETIME => format "YYYY-MM-DD HH:MM:SS"
# DATE     => format "YYYY-MM-DD"
# TIME     => format "HH:MM:SS"
# YEAR     => format "YYYY"
# MEMO     => stores text up to 2GB in size (if possible).
###############################

    function translate($sqlstmt)
    {
        switch($this->type) # "type" is a DB_SQL property
        {
    # MySQL database
        case "mysql":
            $sqlstmt = $this->mysql_translate($sqlstmt);
            break;
    # PostgreSQL database
        case "postgres":
            $sqlstmt = $this->postgres_translate($sqlstmt);
            break;
    # Oracle database
        case "oracle":
        case "oci8":
            $sqlstmt = $this->oracle_translate($sqlstmt);
            break;
    # Sybase database
        case "sybase":
            $sqlstmt = $this->sybase_translate($sqlstmt);
            break;
    # MS-SQL database
        case "odbc":
            $sqlstmt = $this->mssql_translate($sqlstmt);
            break;
    # ODBC database
        case "odbc":
            $sqlstmt = $this->odbc_translate($sqlstmt);
            break;
    # nothing was specified... spit out an error
        default:
            $this->halt("\nCiaoSQL Error: No SQL type was specified!\n");
        }
        $result = $this->q($sqlstmt);
        return($result);
    }

#########################################
# Database translation functions

    function mysql_translate($sqlstmt)
    { # MySQL Database
        $TRANS = array(
        "UINT"=>"INT(10) UNSIGNED",
        "SINT"=>"INTEGER",
        "FLOAT"=>"FLOAT(8)",
        "DATETIME"=>"CHAR(19)",
        "DATE"=>"CHAR(10)",
        "TIME"=>"CHAR(8)",
        "YEAR"=>"CHAR(4)",
        "MEMO"=>"LONGTEXT"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }

    function postgres_translate($sqlstmt)
    { # PostgreSQL Database
        $TRANS = array(
        "CHAR"=>"CHARACTER",
        "UINT"=>"INT4",
        "SINT"=>"INT4",
        "FLOAT"=>"FLOAT8",
        "DATETIME"=>"CHARACTER(19)",
        "DATE"=>"CHARACTER(10)",
        "TIME"=>"CHARACTER(8)",
        "YEAR"=>"CHARACTER(4)",
        "MEMO"=>"TEXT"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }

    function oracle_translate($sqlstmt)
    { # Oracle Database
        $TRANS = array(
        "UINT"=>"INTEGER",
        "SINT"=>"INTEGER",
        "FLOAT"=>"NUMBER",
        "DATETIME"=>"CHAR(19)",
        "DATE"=>"CHAR(10)",
        "TIME"=>"CHAR(8)",
        "YEAR"=>"CHAR(4)",
        "MEMO"=>"CLOB"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }

    function sybase_translate($sqlstmt)
    { # Sybase Database
        $TRANS = array(
        "UINT"=>"UNSIGNED BIGINT",
        "SINT"=>"BIGINT",
        "FLOAT"=>"DOUBLE",
        "DATETIME"=>"CHAR(19)",
        "DATE"=>"CHAR(10)",
        "TIME"=>"CHAR(8)",
        "YEAR"=>"CHAR(4)",
        "MEMO"=>"LONG VARCHAR"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }

    function mssql_translate($sqlstmt)
    { # MS-SQL Database
        $TRANS = array(
        "UINT"=>"INT",
        "SINT"=>"INT",
        "FLOAT"=>"FLOAT",
        "DATETIME"=>"CHAR(19)",
        "DATE"=>"CHAR(10)",
        "TIME"=>"CHAR(8)",
        "YEAR"=>"CHAR(4)",
        "MEMO"=>"TEXT"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }

    function odbc_translate($sqlstmt)
    { # Generic ODBC Database Drivers
        $TRANS = array(
        "CHAR"=>"SQL_CHAR",
        "UINT"=>"SQL_INTEGER",
        "SINT"=>"SQL_INTEGER",
        "FLOAT"=>"SQL_FLOAT",
        "DATETIME"=>"SQL_CHAR(19)",
        "DATE"=>"SQL_CHAR(10)",
        "TIME"=>"SQL_CHAR(8)",
        "YEAR"=>"SQL_CHAR(4)",
        "MEMO"=>"SQL_LONGVARCHAR"
        );
        while(list($key,$value) = each($TRANS))
        { $sqlstmt = str_replace($key,$value,$sqlstmt); }
        return($sqlstmt);
    }
}
?>

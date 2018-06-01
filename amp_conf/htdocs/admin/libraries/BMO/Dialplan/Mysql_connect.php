<?php
namespace FreePBX\Dialplan;
class Mysql_connect extends Extension{
	var $connid;
	var $dbhost;
	var $dbuser;
	var $dbpass;
	var $dbname;
	var $charset;

	function __construct($connid, $dbhost, $dbuser, $dbpass, $dbname, $charset='') {
		$this->connid = $connid;
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->dbname = $dbname;
		$this->charset = $charset;
	}

	function output() {
		return "MYSQL(Connect ".$this->connid." ".$this->dbhost." ".$this->dbuser." ".$this->dbpass." ".$this->dbname." ".$this->charset.")";
	}
}
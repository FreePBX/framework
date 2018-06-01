<?php
namespace FreePBX\Dialplan;
class Mysql_query extends Extension{
	var $resultid;
	var $connid;
	var $query;

	function __construct($resultid, $connid, $query) {
		$this->resultid = $resultid;
		$this->connid = $connid;
		$this->query = $query;
		// Not escaping mysql query here, you may want to insert asterisk variables in it
	}

	function output() {
		return 'MYSQL(Query '.$this->resultid.' ${'.$this->connid.'} '.$this->query.')';
	}
}
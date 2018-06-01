<?php
namespace FreePBX\Dialplan;
class Mysql_clear extends Extension{
	var $resultid;

	function __construct($resultid) {
		$this->resultid = $resultid;
	}

	function output() {
		return 'MYSQL(Clear ${'.$this->resultid.'})';
	}
}
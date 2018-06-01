<?php
namespace FreePBX\Dialplan;
class Mysql_fetch extends Extension{
	var $fetchid;
	var $resultid;
	var $fars;

	function __construct($fetchid, $resultid, $vars) {
		$this->fetchid = $fetchid;
		$this->resultid = $resultid;
		$this->vars = $vars;
	}

	function output() {
		return 'MYSQL(Fetch '.$this->fetchid.' ${'.$this->resultid.'} '.$this->vars.')';
	}
}
<?php
namespace FreePBX\Dialplan;
class Mysql_disconnect extends Extension{
	var $connid;

	function __construct($connid) {
		$this->connid = $connid;
	}

	function output() {
		return 'MYSQL(Disconnect ${'.$this->connid.'})';
	}
}
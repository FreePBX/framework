<?php
namespace FreePBX\Dialplan;
class Log extends Extension{
	var $level;
	var $msg;

	function __construct($level,$msg) {
		$this->level = $level;
		$this->msg = $msg;
	}
	function output() {
		return "Log(".$this->level.",".$this->msg.")";
	}
}
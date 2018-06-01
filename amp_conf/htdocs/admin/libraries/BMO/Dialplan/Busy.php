<?php
namespace FreePBX\Dialplan;
class Busy extends Extension{
	var $time;

	function __construct($time = '20') {
		$this->time = $time;
	}
	function output() {
		return "Busy(".$this->time.")";
	}
}
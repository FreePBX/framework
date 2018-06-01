<?php
namespace FreePBX\Dialplan;
class Congestion extends Extension{
	var $time;

	function __construct($time = '20') {
		$this->time = $time;
	}
	function output() {
		return "Congestion(".$this->time.")";
	}
}
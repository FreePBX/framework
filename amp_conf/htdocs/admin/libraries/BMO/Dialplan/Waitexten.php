<?php
namespace FreePBX\Dialplan;
class Waitexten extends Extension{
	var $seconds;
	var $options;

	function __construct($seconds = "", $options = "") {
		$this->seconds = $seconds;
		$this->options = $options;
	}

	function output() {
		return "WaitExten(".$this->seconds.",".$this->options.")";
	}
}
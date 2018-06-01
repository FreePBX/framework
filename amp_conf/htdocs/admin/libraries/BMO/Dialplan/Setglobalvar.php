<?php
namespace FreePBX\Dialplan;
class Setglobalvar{
	var $var;
	var $value;

	function __construct($var, $value) {
		$this->var = $var;
		$this->value = $value;
	}

	function output() {
		return "Set(".$this->var."=".$this->value.",g)";
	}
}
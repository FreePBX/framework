<?php
namespace FreePBX\Dialplan;
class Dial extends Extension{
	var $number;
	var $options;

	function __construct($number, $options = "tr") {
		$this->number = $number;
		$this->options = $options;
	}

	function output() {
		return "Dial(".$this->number.",".$this->options.")";
	}
}
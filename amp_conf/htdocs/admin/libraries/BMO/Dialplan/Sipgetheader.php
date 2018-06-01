<?php
namespace FreePBX\Dialplan;
class Sipgetheader{
	var $header;
	var $value;

	function __construct($value, $header) {
		$this->value = $value;
		$this->header = $header;
	}

	function output() {
		return "SIPGetHeader(".$this->value."=".$this->header.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Sipaddheader{
	var $header;
	var $value;

	function __construct($header, $value) {
		$this->header = $header;
		$this->value = $value;
	}

	function output() {
		return "SIPAddHeader(".$this->header.": ".$this->value.")";
	}
}
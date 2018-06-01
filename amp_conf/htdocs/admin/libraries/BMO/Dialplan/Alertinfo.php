<?php
namespace FreePBX\Dialplan;
class Alertinfo{
	var $value;

	function __construct($value) {
		$this->value = $value;
	}

	function output() {
		return "SIPAddHeader(Alert-Info: ".$this->value.")";
	}
}
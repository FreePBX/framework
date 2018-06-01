<?php
namespace FreePBX\Dialplan;
class Authenticate{
	var $pass;
	var $options;

	function __construct($pass, $options='') {
		$this->pass = $pass;
		$this->options = $options;
	}
	function output() {
		return "Authenticate(".$this->pass.",".$this->options.")";
	}
}
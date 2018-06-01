<?php
namespace FreePBX\Dialplan;
class Confbridge{
	var $confno;
	var $options;
	var $pin;

	function __construct($confno, $options='', $pin='') {
		$this->confno = $confno;
		$this->options = $options;
		$this->pin = $pin;
	}

	function output() {
		return "ConfBridge(".$this->confno.",".$this->options.",".$this->pin.")";
	}
}
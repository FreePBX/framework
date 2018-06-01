<?php
namespace FreePBX\Dialplan;
class Read{
	var $astvar;
	var $filename;
	var $maxdigits;
	var $option;
	var $attempts; // added in ast 1.2
	var $timeout;  // added in ast 1.2

	function __construct($astvar, $filename='', $maxdigits='', $option='', $attempts ='', $timeout ='') {
		$this->astvar = $astvar;
		$this->filename = $filename;
		$this->maxdigits = $maxdigits;
		$this->option = $option;
		$this->attempts = $attempts;
		$this->timeout = $timeout;
	}

	function output() {
		return "Read(".$this->astvar.",".$this->filename.",".$this->maxdigits.",".$this->option.",".$this->attempts.",".$this->timeout.")";
	}
}
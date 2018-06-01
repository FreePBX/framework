<?php
namespace FreePBX\Dialplan;
class Saynumber extends Extension{
	var $gender;
	function __construct($data, $gender = 'f') {
		parent::__construct($data);
		$this->gender = $gender;
	}
	function output() {
		return "SayNumber(".$this->data.",".$this->gender.")";
	}
}
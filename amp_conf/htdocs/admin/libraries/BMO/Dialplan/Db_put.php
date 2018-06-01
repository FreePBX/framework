<?php
namespace FreePBX\Dialplan;
class Db_put extends Extension{
	var $family;
	var $key;
	var $value;

	function __construct($family, $key, $value) {
		$this->family = $family;
		$this->key = $key;
		$this->value = $value;
	}

	function output() {
		return 'Set(DB('.$this->family.'/'.$this->key.')='.$this->value.')';
	}
}
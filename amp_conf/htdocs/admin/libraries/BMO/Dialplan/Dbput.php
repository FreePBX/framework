<?php
namespace FreePBX\Dialplan;
class Dbput extends Extension{
	var $key;
	function __construct($key, $data) {
		$this->key = $key;
		$this->data = $data;
	}
	function output() {
		return 'Set(DB('.$this->key.')='.$this->data.')';
	}
}
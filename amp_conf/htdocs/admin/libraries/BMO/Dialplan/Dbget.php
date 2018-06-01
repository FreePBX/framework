<?php
namespace FreePBX\Dialplan;
class Dbget extends Extension{
	var $varname;
	var $key;
	function __construct($varname, $key) {
		$this->varname = $varname;
		$this->key = $key;
	}
	function output() {
		return 'Set('.$this->varname.'=${DB('.$this->key.')})';
	}
}
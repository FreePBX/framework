<?php
namespace FreePBX\Dialplan;
class Txtcidname extends Extension{
	var $cidnum;

	function __construct($cidnum) {
		$this->cidnum = $cidnum;
	}

	function output() {
		return 'Set(TXTCIDNAME=${TXTCIDNAME('.$this->cidnum.')})';
	}
}
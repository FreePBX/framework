<?php
namespace FreePBX\Dialplan;
class Extgoto extends Extension{
	var $pri;
	var $ext;
	var $context;

	function __construct($pri, $ext = false, $context = false) {
		if ($context !== false && $ext === false) {
			trigger_error("\$ext is required when passing \$context in ext_goto::ext_goto()");
		}

		$this->pri = $pri;
		$this->ext = $ext;
		$this->context = $context;
	}

	function incrementContents($value) {
		$this->pri += $value;
	}

	function gotoEmpty($value) {
		return ($value === "" || $value === null || $value === false);
	}

	function output() {
		return 'Goto('.(!$this->gotoEmpty($this->context) ? $this->context.',' : '').(!$this->gotoEmpty($this->ext) ? $this->ext.',' : '').$this->pri.')' ;
	}
}
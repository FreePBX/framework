<?php
namespace FreePBX\Dialplan;
class Macro{
	var $macro;
	var $args;

	function __construct($macro, $args='') {
		$this->macro = $macro;
		$this->args = $args;
	}

	function output() {
		//$callers=debug_backtrace();
		//freepbx_log(FPBX_LOG_UPDATE, "Need to remove Macro from ".$callers[1]['file']." on line ".$callers[1]['line']);
		return "Macro(".$this->macro.",".$this->args.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Macroexit extends Extension{
	function output() {
		//$callers=debug_backtrace();
		//freepbx_log(FPBX_LOG_UPDATE, "Need to remove Macro from ".$callers[1]['file']." on line ".$callers[1]['line']);
		return "MacroExit()";
	}
}
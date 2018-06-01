<?php
namespace FreePBX\Dialplan;
class Stopmonitor extends Extension{
	function output() {
		return "StopMonitor(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Stopmixmonitor extends Extension{
	function output() {
		return "StopMixMonitor(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Deadagi extends Extension{
	function output() {
		return "DeadAGI(".$this->data.")";
	}
}
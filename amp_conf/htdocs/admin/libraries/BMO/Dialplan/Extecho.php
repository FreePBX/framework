<?php
namespace FreePBX\Dialplan;
class Extecho extends Extension{
	function output() {
		return "Echo(".$this->data.")";
	}
}
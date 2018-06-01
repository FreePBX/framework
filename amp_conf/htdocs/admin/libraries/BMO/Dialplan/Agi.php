<?php
namespace FreePBX\Dialplan;
class Agi extends Extension{
	function output() {
		return "AGI(".$this->data.")";
	}
}
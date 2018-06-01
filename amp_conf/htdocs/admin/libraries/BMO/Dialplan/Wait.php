<?php
namespace FreePBX\Dialplan;
class Wait extends Extension{
	function output() {
		return "Wait(".$this->data.")";
	}
}
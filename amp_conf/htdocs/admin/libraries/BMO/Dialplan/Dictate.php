<?php
namespace FreePBX\Dialplan;
class Dictate extends Extension{
	function output() {
		return "Dictate(".$this->data.")";
	}
}
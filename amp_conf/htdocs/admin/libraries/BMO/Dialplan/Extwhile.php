<?php
namespace FreePBX\Dialplan;
class Extwhile extends Extension{
	function output() {
		return "While(".$this->data.")";
	}
}
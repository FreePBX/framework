<?php
namespace FreePBX\Dialplan;
class System extends Extension{
	function output() {
		return "System(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Park extends Extension{
	function output() {
		return "Park(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Pickup extends Extension{
	function output() {
		return "Pickup(".$this->data.")";
	}
}
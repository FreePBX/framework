<?php
namespace FreePBX\Dialplan;
class Parkedcall extends Extension{
	function output() {
		return "ParkedCall(".$this->data.")";
	}
}
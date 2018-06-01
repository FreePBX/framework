<?php
namespace FreePBX\Dialplan;
class Sipremoveheader extends Extension{
	function output() {
		return "SIPRemoveHeader(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Sayalpha extends Extension{
	function output() {
		return "SayAlpha(".$this->data.")";
	}
}
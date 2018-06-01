<?php
namespace FreePBX\Dialplan;
class Saydigits extends Extension{
	function output() {
		return "SayDigits(".$this->data.")";
	}
}
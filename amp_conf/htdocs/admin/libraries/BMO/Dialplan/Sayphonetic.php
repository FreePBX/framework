<?php
namespace FreePBX\Dialplan;
class Sayphonetic extends Extension{
	function output() {
		return "SayPhonetic(".$this->data.")";
	}
}
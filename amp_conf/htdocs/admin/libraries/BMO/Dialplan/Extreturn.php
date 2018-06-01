<?php
namespace FreePBX\Dialplan;
class Extreturn extends Extension{
	function output() {
		return "Return(".$this->data.")";
	}
}
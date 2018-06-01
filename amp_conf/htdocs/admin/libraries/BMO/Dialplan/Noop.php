<?php
namespace FreePBX\Dialplan;
class Noop extends Extension{
	function output() {
		return "Noop(".$this->data.")";
	}
}
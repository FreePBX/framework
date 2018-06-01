<?php
namespace FreePBX\Dialplan;
class Background extends Extension{
	function output() {
		return "Background(".$this->data.")";
	}
}
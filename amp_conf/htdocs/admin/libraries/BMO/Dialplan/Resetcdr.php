<?php
namespace FreePBX\Dialplan;
class Resetcdr extends Extension{
	function output() {
		return "ResetCDR(".$this->data.")";
	}
}
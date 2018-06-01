<?php
namespace FreePBX\Dialplan;
class Setcidname extends Extension{
	function output() {
		return "Set(CALLERID(name)=".$this->data.")";
	}
}
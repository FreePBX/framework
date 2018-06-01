<?php
namespace FreePBX\Dialplan;
class Setmusiconhold extends Extension{
	function output() {
		return "Set(CHANNEL(musicclass)=".$this->data.")";
	}
}
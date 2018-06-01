<?php
namespace FreePBX\Dialplan;
class Startmusiconhold extends Extension{
	function output() {
		return "StartMusicOnHold(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Musiconhold extends Extension{
	function output() {
		return "MusicOnHold(".$this->data.")";
	}
}
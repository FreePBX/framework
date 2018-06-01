<?php
namespace FreePBX\Dialplan;
class Playback extends Extension{
	function output() {
		return "Playback(".$this->data.")";
	}
}
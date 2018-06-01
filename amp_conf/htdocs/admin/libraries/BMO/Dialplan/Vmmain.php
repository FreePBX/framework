<?php
namespace FreePBX\Dialplan;
class Vmmain extends Extension{
	function output() {
		return "VoiceMailMain(".$this->data.")";
	}
}
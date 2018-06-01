<?php
namespace FreePBX\Dialplan;
class Vm extends Extension{
	function output() {
		return "VoiceMail(".$this->data.")";
	}
}
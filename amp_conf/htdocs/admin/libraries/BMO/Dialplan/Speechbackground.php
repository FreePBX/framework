<?php
namespace FreePBX\Dialplan;
class Speechbackground extends Extension{
	var $sound_file;
	var $timeout;

	function __construct($sound_file,$timeout=null)  {
		$this->sound_file = $sound_file;
		$this->timeout = $timeout;
	}

	function output() {
		return "SpeechBackground(".$this->sound_file.($this->timeout?",$this->timeout":"").")";
	}
}
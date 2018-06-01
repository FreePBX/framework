<?php
namespace FreePBX\Dialplan;
class Speechprocessingsound extends Extension{
	var $sound_file;

	function __construct($sound_file)  {
		$this->sound_file = $sound_file;
	}

	function output() {
		return "SpeechProcessingSound(".$this->sound_file.")";
	}
}
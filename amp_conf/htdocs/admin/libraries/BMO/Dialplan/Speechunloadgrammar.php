<?php
namespace FreePBX\Dialplan;
class Speechunloadgrammar extends Extension{
	var $grammar_name;

	function __construct($grammar_name)  {
		$this->grammar_name = $grammar_name;
	}

	function output() {
		return "SpeechUnloadGrammar(".$this->grammar_name.")";
	}
}
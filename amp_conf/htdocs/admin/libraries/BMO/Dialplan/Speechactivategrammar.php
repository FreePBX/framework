<?php
namespace FreePBX\Dialplan;
class Speechactivategrammar extends Extension{
	var $grammar_name;

	function __construct($grammar_name)  {
		$this->grammar_name = $grammar_name;
	}

	function output() {
		return "SpeechActivateGrammar(".$this->grammar_name.")";
	}
}
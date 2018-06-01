<?php
namespace FreePBX\Dialplan;
class Speechdeactivategrammar extends Extension{
	var $grammar_name;

	function __construct($grammar_name)  {
		$this->grammar_name = $grammar_name;
	}

	function output() {
		return "SpeechDeactivateGrammar(".$this->grammar_name.")";
	}
}
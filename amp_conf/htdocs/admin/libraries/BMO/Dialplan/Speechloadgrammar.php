<?php
namespace FreePBX\Dialplan;
class Speechloadgrammar extends Extension{
	var $grammar_name;
	var $path_to_grammar;

	function __construct($grammar_name,$path_to_grammar)  {
		$this->grammar_name = $grammar_name;
		$this->path_to_grammar = $path_to_grammar;
	}

	function output() {
		return "SpeechLoadGrammar(".$this->grammar_name.",".$this->path_to_grammar.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Speechcreate extends Extension{
	var $engine;

	function __construct($engine = null)  {
		$this->engine = $engine;
	}

	function output() {
		return "SpeechCreate(".($this->engine?$this->engine:"").")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Speechdtmfmaxdigits  extends Extension{
	var $digits;
	function __construct($digits)  {
		$this->digits = $digits;
	}

	function output()  {
		return "Set(SPEECH_DTMF_MAXLEN=".$this->digits.")";
	}
}
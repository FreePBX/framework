<?php
namespace FreePBX\Dialplan;
class Senddtmf extends Extension{
	var $digits;
	function __construct($digits) {
		$this->digits = $digits;
	}
	function output() {
		return 'SendDTMF('.$this->digits.')';
	}
}
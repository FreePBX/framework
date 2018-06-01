<?php
namespace FreePBX\Dialplan;
class Vmauthenticate{
	var $mailbox;
	var $options;

	function __construct($mailbox='', $options='') {
		$this->mailbox = $mailbox;
		$this->options = $options;
	}
	function output() {
		return "VMAuthenticate(" .$this->mailbox . (($this->options != '') ? ','.$this->options : '' ) .")";
	}
}
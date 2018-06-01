<?php
namespace FreePBX\Dialplan;
class Chanspy extends Extension{
	var $prefix;
	var $options;
	function __construct($prefix = '', $options = '') {
		$this->prefix = $prefix;
		$this->options = $options;
	}
	function output() {
		return "ChanSpy(".$this->prefix.($this->options?','.$this->options:'').")";
	}
}
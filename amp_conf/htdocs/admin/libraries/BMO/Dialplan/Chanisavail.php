<?php
namespace FreePBX\Dialplan;
class Chanisavail extends Extension{
	var $chan;
	var $options;
	function __construct($chan, $options = '') {
		$this->chan = $chan;
		$this->options = $options;
	}

	function output() {
		return 'ChanIsAvail('.$this->chan.','.$this->options.')';
	}
}
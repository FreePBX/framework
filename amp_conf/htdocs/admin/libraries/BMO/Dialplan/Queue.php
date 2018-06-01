<?php
namespace FreePBX\Dialplan;
class Queue{
	var $var;
	var $value;

	// Queue(queuename,options,URL,announceoverride,timeout,AGI,macro,gosub,rule,position)
	function __construct($queuename, $options, $optionalurl, $announceoverride, $timeout, $agi='', $macro='', $gosub='', $rule='', $position='') {
		$this->queuename = $queuename;
		$this->options = $options;
		$this->optionalurl = $optionalurl;
		$this->announceoverride = $announceoverride;
		$this->timeout = $timeout;
		$this->agi = $agi;
		$this->macro = $macro;
		$this->gosub = $gosub;
		$this->rule = $rule;
		$this->position = $position;
	}

	function output() {
		// TODO: test blank: for some reason the Queue cmd takes an empty last param (timeout) as being 0
		// when really we want unlimited
		return "Queue("
			. $this->queuename . ","
			. $this->options . ","
			. $this->optionalurl . ","
			. $this->announceoverride . ","
			. $this->timeout . ","
			. $this->agi . ","
			. $this->macro . ","
			. $this->gosub . ","
			. $this->rule . ","
			. $this->position . ")";
	}
}
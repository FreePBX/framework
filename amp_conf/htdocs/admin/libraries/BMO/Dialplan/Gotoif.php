<?php
namespace FreePBX\Dialplan;
class Gotoif extends Extension{
	var $true_priority;
	var $false_priority;
	var $condition;
	function __construct($condition, $true_priority, $false_priority = false) {
		$this->true_priority = $true_priority;
		$this->false_priority = $false_priority;
		$this->condition = $condition;
	}
	function output() {
		return 'GotoIf(' .$this->condition. '?' .$this->true_priority.($this->false_priority ? ':' .$this->false_priority : '' ). ')' ;
	}
	function incrementContents($value) {
		$this->true_priority += $value;
		$this->false_priority += $value;
	}
}
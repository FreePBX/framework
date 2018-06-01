<?php
namespace FreePBX\Dialplan;
class Gosubif extends Extension{
	var $true_priority;
	var $false_priority;
	var $condition;
	function __construct($condition, $true_priority, $false_priority = false, $true_args = '', $false_args = '') {
		$this->true_priority = $true_priority;
		$this->false_priority = $false_priority;
		$this->true_args = $true_args;
		$this->false_args = $false_args;
		$this->condition = $condition;
	}
	function output() {
		return 'GosubIf(' .$this->condition. '?' .$this->true_priority.'('.$this->true_args.')'.($this->false_priority ? ':' .$this->false_priority.'('.$this->false_args.')' : '' ). ')' ;
	}
	function incrementContents($value) {
		$this->true_priority += $value;
		$this->false_priority += $value;
	}
}
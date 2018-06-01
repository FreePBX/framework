<?php
namespace FreePBX\Dialplan;
class Gotoiftime extends Extension{
	var $true_priority;
	var $condition;
	function __construct($condition, $true_priority) {
	    global $version;
	    if (version_compare($version, "1.6", "ge")) {
		//change from '|' to ','
		$this->condition = str_replace("|", ",", $condition);
		    }
		else {
		    $this->condition = $condition;
		    }
		$this->true_priority = $true_priority;
	}
	function output() {
		return 'GotoIfTime(' .$this->condition. '?' .$this->true_priority. ')' ;
	}
	function incrementContents($value) {
		$this->true_priority += $value;
	}
}
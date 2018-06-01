<?php
namespace FreePBX\Dialplan;
class Originate extends Extension{
	var $tech_data;
	var $type;
	var $arg1;
	var $arg2;
	var $arg3;

	function __construct($tech_data, $type, $arg1, $arg2, $arg3 = '') {
		$this->tech_data = $tech_data;
		$this->type = $type;
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
		$this->arg3 = $arg3;
	}
	function output() {
		return 'Originate(' . $this->tech_data
							. ',' . $this->type
							. ',' . $this->arg1
							. ',' . $this->arg2
							. ',' . $this->arg3
							. ')' ;
	}
}
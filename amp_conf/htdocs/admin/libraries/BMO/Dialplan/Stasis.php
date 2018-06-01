<?php
namespace FreePBX\Dialplan;
class Stasis extends Extension{
	var $app_name;
	var $args;

	function __construct($app_name, $args='') {
		$this->app_name = $app_name;
		$this->args = $args;
	}

	function output() {
		return "Stasis(".$this->app_name.",".$this->args.")";
	}
}
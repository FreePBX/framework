<?php
namespace FreePBX\Dialplan;
class Execif{
	var $expr;
	var $app_true;
	var $data_true;
	var $app_false;
	var $data_false;

	function __construct($expr, $app_true, $data_true='', $app_false = '', $data_false = '') {
		$this->expr = $expr;
		$this->app_true = $app_true;
		$this->data_true = $data_true;
		$this->app_false = $app_false;
		$this->data_false = $data_false;
	}

	function output() {
		if ($this->app_false != ''){
			return "ExecIf({$this->expr}?{$this->app_true}({$this->data_true}):{$this->app_false}({$this->data_false}))";
		}
		return "ExecIf({$this->expr}?{$this->app_true}({$this->data_true}))";
	}
}
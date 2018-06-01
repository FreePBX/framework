<?php
namespace FreePBX\Dialplan;
class extension {
	var $data;

	function __construct($data = '') {
		$this->data = $data;
	}

	function incrementContents($value) {
		return true;
	}

	function output() {
		return $this->data;
	}
}
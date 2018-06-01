<?php
namespace FreePBX\Dialplan;
class Gosub extends Extension{
	var $pri;
	var $ext;
	var $context;
	var $args;

	function __construct($pri, $ext = false, $context = false, $args='') {
		if ($context !== false && $ext === false) {
			trigger_error("\$ext is required when passing \$context in ext_gosub::ext_gosub()");
		}

		$this->pri = $pri;
		$this->ext = $ext;
		$this->context = $context;
		$this->args = $args;
	}

	function incrementContents($value) {
		$this->pri += $value;
	}

	function output() {
		return 'Gosub('.($this->context ? $this->context.',' : '').($this->ext ? $this->ext.',' : '').$this->pri.'('.$this->args.'))' ;
	}
}
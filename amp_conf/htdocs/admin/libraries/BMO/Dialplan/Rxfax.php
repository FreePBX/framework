<?php
namespace FreePBX\Dialplan;
class Rxfax extends Extension{
	function output() {
		return "rxfax(".$this->data.")";
	}
}
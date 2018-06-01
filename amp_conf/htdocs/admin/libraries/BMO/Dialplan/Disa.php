<?php
namespace FreePBX\Dialplan;
class Disa extends Extension{
	function output() {
		return "DISA(".$this->data.")";
	}
}
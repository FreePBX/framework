<?php
namespace FreePBX\Dialplan;
class Digittimeout extends Extension{
	function output() {
		return "Set(TIMEOUT(digit)=".$this->data.")";
	}
}
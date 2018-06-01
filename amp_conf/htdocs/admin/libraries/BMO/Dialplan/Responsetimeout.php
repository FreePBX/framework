<?php
namespace FreePBX\Dialplan;
class Responsetimeout extends Extension{
	function output() {
		return "Set(TIMEOUT(response)=".$this->data.")";
	}
}
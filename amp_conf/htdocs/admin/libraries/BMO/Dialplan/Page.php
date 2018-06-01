<?php
namespace FreePBX\Dialplan;
class Page extends Extension{
	function output() {
		return "Page(".$this->data.")";
	}
}
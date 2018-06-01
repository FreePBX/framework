<?php
namespace FreePBX\Dialplan;
class Lookupblacklist extends Extension{
	function output() {
		return "LookupBlacklist(".$this->data.")";
	}
}
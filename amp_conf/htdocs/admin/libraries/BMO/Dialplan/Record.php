<?php
namespace FreePBX\Dialplan;
class Record extends Extension{
	function output() {
		return "Record(".$this->data.")";
	}
}
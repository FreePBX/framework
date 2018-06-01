<?php
namespace FreePBX\Dialplan;
class Dbdeltree extends Extension{
	function output() {
		return "dbDeltree(".$this->data.")";
	}
}
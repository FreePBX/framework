<?php
namespace FreePBX\Dialplan;
class Festival extends Extension{
	function output() {
		return "Festival(".$this->data.")";
	}
}
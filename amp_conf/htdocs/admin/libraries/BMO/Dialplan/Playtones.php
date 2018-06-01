<?php
namespace FreePBX\Dialplan;
class Playtones extends Extension{
	function output() {
		return "Playtones(".$this->data.")";
	}
}
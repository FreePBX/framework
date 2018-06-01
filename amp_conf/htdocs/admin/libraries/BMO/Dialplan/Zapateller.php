<?php
namespace FreePBX\Dialplan;
class Zapateller extends Extension{
	function output() {
		return "Zapateller(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Dpickup extends Extension{
	function output() {
		return "DPickup(".$this->data.")";
	}
}
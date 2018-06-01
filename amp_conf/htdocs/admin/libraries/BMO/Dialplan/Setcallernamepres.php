<?php
namespace FreePBX\Dialplan;
class Setcallernamepres extends Extension{
	function output() {
		return "Set(CALLERID(name-pres)={$this->data})";
	}
}
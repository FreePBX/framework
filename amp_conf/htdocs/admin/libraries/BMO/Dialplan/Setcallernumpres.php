<?php
namespace FreePBX\Dialplan;
class Setcallernumpres extends Extension{
	function output() {
		return "Set(CALLERID(num-pres)={$this->data})";
	}
}
<?php
namespace FreePBX\Dialplan;
class Setcallerpres extends Extension{
	function output() {
		return "Set(CALLERPRES()={$this->data})";
	}
}
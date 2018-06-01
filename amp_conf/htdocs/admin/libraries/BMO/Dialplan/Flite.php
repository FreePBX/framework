<?php
namespace FreePBX\Dialplan;
class Flite extends Extension{
	function output() {
		return "Flite('".$this->data."')";
	}
}
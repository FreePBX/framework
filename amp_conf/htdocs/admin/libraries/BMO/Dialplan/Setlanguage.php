<?php
namespace FreePBX\Dialplan;
class Setlanguage extends Extension{
	function output() {
		return "Set(CHANNEL(language)={$this->data})";
	}
}
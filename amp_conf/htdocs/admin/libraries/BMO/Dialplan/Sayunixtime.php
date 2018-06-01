<?php
namespace FreePBX\Dialplan;
class Sayunixtime extends Extension{
	function output() {
		return "SayUnixTime(".$this->data.")";
	}
}
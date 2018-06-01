<?php
namespace FreePBX\Dialplan;
class Sendfax extends Extension{
	function output() {
		return "SendFAX(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Receivefax extends Extension{
	function output() {
		return "ReceiveFAX(".$this->data.")";
	}
}
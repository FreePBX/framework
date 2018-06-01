<?php
namespace FreePBX\Dialplan;
class Callcompletioncancel extends Extension{
	function output() {
		return "CallCompletionCancel(".$this->data.")";
	}
}
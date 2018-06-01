<?php
namespace FreePBX\Dialplan;
class Callcompletionrequest extends Extension{
	function output() {
		return "CallCompletionRequest(".$this->data.")";
	}
}
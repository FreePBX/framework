<?php
namespace FreePBX\Dialplan;
class Userevent extends Extension{
	var $eventname;
	var $body;

	function __construct($eventname, $body=""){
		$this->eventname = $eventname;
		$this->body = $body;
	}

	function output() {
		if ($this->body == ''){
			return "UserEvent({$this->eventname})";
		}
		return "UserEvent({$this->eventname})";
	}
}
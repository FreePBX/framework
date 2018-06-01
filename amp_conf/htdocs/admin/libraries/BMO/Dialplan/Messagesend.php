<?php
namespace FreePBX\Dialplan;
class Messagesend extends Extension{
	var $to;
	var $from;
	function __construct($to, $from = null) {
		$this->to = $to;
		$this->from = $from;
	}
	function output() {
		return isset($this->from) && !empty($this->from) ? "MessageSend(".$this->to.", ".$this->from.")" : "MessageSend(".$this->to.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Removequeuemember extends Extension{
	var $queue;
	var $channel;

	function __construct($queue, $channel){
		$this->queue = $queue;
		$this->channel = $channel;
	}

	function output() {
		return "RemoveQueueMember({$this->queue},{$this->channel})";
	}
}
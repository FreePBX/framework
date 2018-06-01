<?php
namespace FreePBX\Dialplan;
class Addqueuemember extends Extension{
	var $queue;
	var $channel;

	function __construct($queue, $channel){
		$this->queue = $queue;
		$this->channel = $channel;
	}

	function output() {
		return "AddQueueMember({$this->queue},{$this->channel})";
	}
}

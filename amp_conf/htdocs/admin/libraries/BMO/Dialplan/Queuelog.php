<?php
namespace FreePBX\Dialplan;
class Queuelog extends Extension{

	function __construct($queue, $uniqueid, $agent, $event, $additionalinfo = ''){
		$this->queue			= $queue;
		$this->uniqueid			= $uniqueid;
		$this->agent			= $agent;
		$this->event			= $event;
		$this->additionalinfo	= $additionalinfo;
	}

	function output() {

		return 'QueueLog('
					. $this->queue . ','
					. $this->uniqueid . ','
					. $this->agent . ','
					. $this->event . ','
					. $this->additionalinfo
					. ')';
	}
}
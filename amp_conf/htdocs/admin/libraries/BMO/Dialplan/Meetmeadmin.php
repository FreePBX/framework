<?php
namespace FreePBX\Dialplan;
class Meetmeadmin{
	var $confno;
	var $command;
	var $user;

	function __construct($confno, $command, $user='') {
		$this->confno = $confno;
		$this->command = $command;
		$this->user = $user;
	}

	function output() {
		return "MeetMeAdmin(".$this->confno.",".$this->command.",".$this->user.")";
	}
}
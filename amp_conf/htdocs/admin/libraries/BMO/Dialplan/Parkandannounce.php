<?php
namespace FreePBX\Dialplan;
class Parkandannounce extends Extension{
	function output() {
		return "ParkAndAnnounce(".$this->data.")";
	}
}
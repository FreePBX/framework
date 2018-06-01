<?php
namespace FreePBX\Dialplan;
class Zapbarge extends Extension{
	function output() {
		global $chan_dahdi;
		if($chan_dahdi) {
			$command = 'DAHDIBarge';
		} else {
			$command = 'ZapBarge';
		}
		return "$command(".$this->data.")";

	}
}
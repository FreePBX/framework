<?php
namespace FreePBX\Dialplan;
class Nvfaxdetect extends Extension{
	function output() {
		return "NVFaxDetect(".$this->data.")";
	}
}
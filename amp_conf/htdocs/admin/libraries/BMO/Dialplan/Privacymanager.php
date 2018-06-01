<?php
namespace FreePBX\Dialplan;
class Privacymanager extends Extension{
	function output() {
		return "PrivacyManager(".$this->data.")";
	}
}
<?php
namespace FreePBX\Dialplan;
class Vqa extends Extension{
	function output() {
		return "VQA(".$this->data.")";
	}
}
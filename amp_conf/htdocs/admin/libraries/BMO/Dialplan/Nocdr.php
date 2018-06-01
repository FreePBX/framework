<?php
namespace FreePBX\Dialplan;
class Nocdr extends Extension{
	function output() {
		return "Set(CDR_PROP(disable)=true)";
	}
}
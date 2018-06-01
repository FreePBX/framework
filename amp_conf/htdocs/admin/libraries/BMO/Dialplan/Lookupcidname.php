<?php
namespace FreePBX\Dialplan;
class Lookupcidname extends Extension{
	function output() {
		return 'ExecIf($["${DB(cidname/${CALLERID(num)})}" != ""]?Set(CALLERID(name)=${DB(cidname/${CALLERID(num)})}))';
	}
}
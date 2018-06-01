<?php
namespace FreePBX\Dialplan;
class Vmexists extends Extension{
	function output() {
		return 'Set(VMBOXEXISTSSTATUS=${IF(${VM_INFO('.$this->data.',exists)}?SUCCESS:FAILED)})';
	}
}
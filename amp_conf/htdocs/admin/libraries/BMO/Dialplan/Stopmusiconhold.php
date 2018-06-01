<?php
namespace FreePBX\Dialplan;
class Stopmusiconhold extends Extension{
	function output() {
		return "StopMusicOnHold()";
	}
}
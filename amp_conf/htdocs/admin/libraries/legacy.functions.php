<?php

/* below are legacy functions required to allow pre 2.0 modules to function (ie: interact with 'extensions' table) */

	//add to extensions table - used in callgroups.php
	function legacy_extensions_add($addarray) {
		global $db;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('".$addarray[0]."', '".$addarray[1]."', '".$addarray[2]."', '".$addarray[3]."', '".$addarray[4]."', '".$addarray[5]."' , '".$addarray[6]."')";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die_freepbx($sql."<br>\n".$result->getMessage());
		}
		return $result;
	}
	
	//delete extension from extensions table
	function legacy_extensions_del($context,$exten) {
		global $db;
		$sql = "DELETE FROM extensions WHERE context = '".$db->escapeSimple($context)."' AND `extension` = '".$db->escapeSimple($exten)."'";
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die_freepbx($sql."<br>\n".$result->getMessage());
		}
		return $result;
	}

	//get args for specified exten and priority - primarily used to grab goto destination
	function legacy_args_get($exten,$priority,$context) {
		global $db;
		$sql = "SELECT args FROM extensions WHERE extension = '".$db->escapeSimple($exten)."' AND priority = '".$db->escapeSimple($priority)."' AND context = '".$db->escapeSimple($context)."'";
		list($args) = $db->getRow($sql);
		return $args;
	}

/* end legacy functions */

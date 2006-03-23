<?php
 /* $Id$ */


function ivr_init() {
        global $db;

        // Check to make sure that install.sql has been run
        $sql = "SELECT ivr_id from ivr LIMIT 1";
        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);

        if (DB::IsError($results)) {
                // It couldn't locate the table. This is bad. Lets try to re-create it, just
                // in case the user has had the brilliant idea to delete it.
                // runModuleSQL taken from page.module.php. It's inclusion here is probably
                // A bad thing. It should be, I think, globally available.
                runModuleSQL('ivr', 'uninstall');
                if (runModuleSQL('ivr', 'install')==false) {
                        echo _("There is a problem with install.sql, cannot re-create databases. Contact support\n");
                        die;
                } else {
                        echo _("Database was deleted! Recreated successfully.<br>\n");
                        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
                }
        }
        if (!isset($results[0])) {
                // Note: There's an invalid entry created, __invalid, after this is run,
                // so as long as this has been run _once_, there will always be a result.
                print "First-time use. Searching for existing IVR's.<br>\n";
		// Read old IVR format, part of xtns..
		$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context,priority";
		$unique_aas = $db->getAll($sql);
		if (isset($unique_aas)) {
			foreach($unique_aas as $aa){
				print "Upgrading {$aa[0]}<br>\n";
				// This gets all the menu options
				$id = ivr_get_ivr_id($aa[0]);
				$sql = "SELECT extension,args from extensions where application='Goto' and context='{$aa[0]}'";
				$cmds = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (isset($cmds)) {
					foreach ($cmds as $cmd) {
						$arr=explode(',', $cmd['args']);
						// s == unset, so don't care
						if ($arr[0] != 's') 
							ivr_add_command($id,$cmd['extension'],$arr[0],$arr[1]);
					}
				}
			}
		} else {
			print "No IVR's found<br>\n";
		}	
		// Note, the __install_done line is for internal version checking - the second field
		// should be incremented and checked if the database ever changes.
                // $result = sql("INSERT INTO ivr values ('', '__install_done', '1', '', '')");
        }
}


function ivr_get_ivr_id($name) {
	global $db;
	$res = $db->getRow("SELECT ivr_id from ivr where descrname='$name'");
	if ($res->numRows == 0) {
		// It's not there. Create it and return the ID
		sql("INSERT INTO ivr values('','$name', '', 'Y', 'Y')");
		$res = $db->getRow("SELECT ivr_id from ivr where descrname='$name'");
	}
	return ($res[0]);
}

function ivr_add_command($id, $cmd, $dest, $dest_id) {
	global $db;
	// Does it already exist?
	$res = $db->getRow("SELECT * from ivr_dests where ivr_id='$id' and selection='$cmd'");
	if ($res->numRows == 0) {
		// Just add it.
		sql("INSERT INTO ivr_dests VALUES('$id', '$cmd', '$dest', '$dest_id')");
	} else {
		// Update it.
		sql("UPDATE ivr_dests SET dest_type='$dest', dest_id='$dest_id' where ivr_id='$id' and selection='$cmd'");
	}
}

?>

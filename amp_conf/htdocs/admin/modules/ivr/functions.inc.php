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
							ivr_add_command($id,$cmd['extension'],$cmd['args']);
					}
				}
			}
		} else {
			print "No IVR's found<br>\n";
		}	
		// Note, the __install_done line is for internal version checking - the second field
		// should be incremented and checked if the database ever changes.
                $result = sql("INSERT INTO ivr values ('', '__install_done', '1', '', '', '')");
        }
}


function ivr_get_ivr_id($name) {
	global $db;
	$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
	if ($res->numRows == 0) {
		// It's not there. Create it and return the ID
		sql("INSERT INTO ivr values('','$name', '', 'Y', 'Y', 10)");
		$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
	}
	return ($res[0]);
}

function ivr_add_command($id, $cmd, $dest) {
	global $db;
	// Does it already exist?
	$res = $db->getRow("SELECT * from ivr_dests where ivr_id='$id' and selection='$cmd'");
	if ($res->numRows == 0) {
		// Just add it.
		sql("INSERT INTO ivr_dests VALUES('$id', '$cmd', '$dest')");
	} else {
		// Update it.
		sql("UPDATE ivr_dests SET dest='$dest' where ivr_id='$id' and selection='$cmd'");
	}
}
function ivr_do_edit($id, $post) {

	$displayname = isset($post['displayname'])?$post['displayname']:'';
	$timeout = isset($post['timeout'])?$post['timeout']:'';
	$ena_directory = isset($post['ena_directory'])?$post['ena_directory']:'';
	$ena_directdial = isset($post['ena_directdial'])?$post['ena_directdial']:'';

	if (!empty($ena_directory)) {
		$ena_directory='CHECKED';
	}

	if (!empty($ena_directdial)) {
		$ena_directdial='CHECKED';
	}

	sql("UPDATE ivr SET displayname='$displayname', enable_directory='$ena_directory', enable_directdial='$ena_directdial', timeout='$timeout' WHERE ivr_id='$id'");
}


function ivr_list() {
	global $db;

	$sql = "SELECT * FROM ivr where displayname <> '__install_done' ORDER BY displayname";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
                $res = null;
        }
        return $res;
}

function ivr_get_details($id) {
	global $db;

	$sql = "SELECT * FROM ivr where ivr_id='$id'";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
                $res = null;
        }
        return $res[0];
}

function ivr_get_dests($id) {
	global $db;

	$sql = "SELECT selection, dest FROM ivr_dests where ivr_id='$id'";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
                $res = null;
        }
        return $res;
}
	
function ivr_get_name($id) {
	$res = ivr_get_details($id);
	if (isset($res['displayname'])) {
		return $res['displayname'];
	} else {
		return null;
	}
}
?>

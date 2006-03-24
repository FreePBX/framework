<?php

function recordings_init() {
        global $db;
	$recordings_directory = "/var/lib/asterisk/sounds/custom/";

        // Check to make sure that install.sql has been run
        $sql = "SELECT id from recordings LIMIT 1";
        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);

        if (DB::IsError($results)) {
                // It couldn't locate the table. This is bad. Lets try to re-create it, just
                // in case the user has had the brilliant idea to delete it.
                // runModuleSQL taken from page.module.php. It's inclusion here is probably
		// A bad thing. It should be, I think, globally available. 
                runModuleSQL('recordings', 'uninstall');
                if (runModuleSQL('recordings', 'install')==false) {
                        echo _("There is a problem with install.sql, cannot re-create databases. Contact support\n");
                        die;
                } else {
                        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
                }
        }
        if (!isset($results[0])) {
		// Note: There's an invalid entry created, __invalid, after this is run,
		// so as long as this has been run _once_, there will always be a result.
		// Check for write permissions on custom directory.
		// PHP 4.0 and above has 'is_writable'
		if (!is_writable($recordings_directory)) {
			print "<h2>Error</h2><br />I can not access the directory $recordings_directory. ";
			print "Please make sure that it exists, and is writable by the web server.";
			die;
		}
		$dh = opendir($recordings_directory);
		while (false !== ($file = readdir($dh))) { // http://au3.php.net/readdir 
			if ($file[0] != "." && $file != "CVS") {
				// Ignore the suffix..
				$fname = ereg_replace('.wav', '', $file);
				recordings_add($fname, "custom/$file");
			}
		}
		$result = sql("INSERT INTO recordings values ('', '__invalid', 'install done', '')");
        } 
}


function recordings_get_id($fn) {
	global $db;
	
	$sql = "SELECT id FROM recordings WHERE filename='$fn'";
        $results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if (isset($results['id']) {
		return $results['id'];
	} // else
	return null;
}
	

function recordings_list() {
	global $db;

	// I'm not clued on how 'Department's' work. There obviously should be 
	// somee checking in here for it.

        $sql = "SELECT * FROM recordings where displayname <> '__invalid' ORDER BY displayname";
        $results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
        return $results;
}

function recordings_get($id) {
	global $db;
        $sql = "SELECT * FROM recordings where id='$id'";
        $results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($results)) {
                $results = null;
        }
	return $results;
}

function recordings_add($displayname, $filename) {
	global $db;

	// Check to make sure we can actually read the file
	if (!is_readable('/var/lib/asterisk/sounds/'.$filename)) {
		print "Unable to add $filename - Can't read file!";
		return false;
	}
	// Now, we don't want a .wav on the end if there is one.
	if (strstr($filename, '.wav')) 
		$nowav = substr($filename, 0, -4);
	sql("INSERT INTO recordings values ('', '$displayname', '$nowav', 'No long description available')");
	return true;
	
}

function recordings_update($id, $rname, $descr) {
	 $results = sql("UPDATE recordings SET displayname = \"$rname\", description = \"$descr\" WHERE id = \"$id\"");
}

function recordings_del($id) {
	 $results = sql("DELETE FROM recordings WHERE id = \"$id\"");
}

?>

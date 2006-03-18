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
                        echo _("Database was deleted! Recreated successfully.<br>\n");
                        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
                }
        }
        if (!isset($results[0])) {
		// Note: There's an invalid entry created, __invalid, after this is run,
		// so as long as this has been run _once_, there will always be a result.
                print "First-time use. Searching for existing recordings.<br>\n";
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
				recording_add($fname, $file);
			}
		}
		$result = sql("INSERT INTO recordings values ('', '__invalid', 'install done')");
        } 
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

function recording_add($displayname, $filename) {
	global $db;
	$recordings_directory = "/var/lib/asterisk/sounds/custom/";

	// Check to make sure we can actually read the file
	if (!is_readable($recordings_directory.$filename)) {
		print "Unable to add $filename - Can't read file!";
		return false;
	}
	sql("INSERT INTO recordings values ('', '$displayname', '$filename')");
	return true;
	
}

function runModuleSQL($moddir,$type){
        global $db;
        $data='';
        if (is_file("modules/{$moddir}/{$type}.sql")) {
                // run sql script
                $fd = fopen("modules/{$moddir}/{$type}.sql","r");
                while (!feof($fd)) {
                        $data .= fread($fd, 1024);
                }
                fclose($fd);

                preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);

                foreach ($matches[1] as $sql) {
                                $result = $db->query($sql);
                                if(DB::IsError($result)) {
                                        return false;
                                }
                }
                return true;
        }
                return true;
}


?>

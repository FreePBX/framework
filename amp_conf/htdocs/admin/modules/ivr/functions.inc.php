<?php
 /* $Id$ */


function ivr_init() {
        global $db;

        // Check to make sure that install.sql has been run
        $sql = "SELECT deptname from ivr where displayname='__install_done' LIMIT 1";

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
                        $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
                }
        }
        if (!isset($results[0])) {
                // Note: There's an invalid entry created, __invalid, after this is run,
                // so as long as this has been run _once_, there will always be a result.

		// Read old IVR format, part of xtns..
		$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context,priority";
		$unique_aas = $db->getAll($sql);
		if (isset($unique_aas)) {
			foreach($unique_aas as $aa){
				// This gets all the menu options
				$id = ivr_get_ivr_id($aa[1]);
				// Save the old name, with a link to the new name, for upgrading
				$ivr_newname[$aa[0]] = "ivr-$id";
				// Get the old config
				$sql = "SELECT extension,args from extensions where application='Goto' and context='{$aa[0]}'";
				$cmds = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (isset($cmds)) {
					// There were some actions, so loop through them
					foreach ($cmds as $cmd) {
						$arr=explode(',', $cmd['args']);
						// s == old stuff. We don't care.
						if ($arr[0] != 's') 
							ivr_add_command($id,$cmd['extension'],$cmd['args']);
					}
				}
			}
			// Now. Upgrade all the links inside the old IVR's
			if (isset($ivr_newname)) {
				// Some IVR's were upgraded
				$sql = "SELECT * FROM ivr_dests WHERE dest LIKE '%aa_%'";
				$dests = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (isset($dests)) {
					foreach ($dests as $dest) {
						$arr=explode(',', $dest['dest']);
						sql("UPDATE ivr_dests set dest='".$ivr_newname[$arr[0]].",s,1' where ivr_id='".$dest['ivr_id']."' and selection='".$dest['selection']."'");
					}
				}
			}

			// Upgrade everything using IVR as a destination. Ick.

			// Are queue's using an ivr failover?
			// ***FIXME*** if upgrading queues away from legacy cruft.
			$queues = $db->getAll("select extensions,args from extensions where args LIKE '%aa_%' and context='ext-queues' and priority='6'"); 
			if (count($res) != 0) {
				foreach ($queues as $q) {
					$arr=explode(',', $q['args']);
					sql("UPDATE extensions set args='".$ivr_newname[$arr[0]].",s,1' where context='ext-queues' and priority='6' and extension='".$q['extension']."'");
                                }
			}

			// Now process everything else
			foreach (array_keys($ivr_newname) as $old) {
				// Timeconditions
				sql("UPDATE timeconditions set truegoto='".$ivr_newname[$arr[0]].",s,1' where truegoto='$old,s,1'");
				sql("UPDATE timeconditions set falsegoto='".$ivr_newname[$arr[0]].",s,1' where falsegoto='$old,s,1'");
				// Inbound Routes
				sql("UPDATE incoming set destination='".$ivr_newname[$arr[0]].",s,1' where destination='$old,s,1'");
				// Ring Groups
				sql("UPDATE ringgroups set postdest='".$ivr_newname[$arr[0]].",s,1' where postdest='$old,s,1'");
			}
		} 
		// Note, the __install_done line is for internal version checking - the second field
		// should be incremented and checked if the database ever changes.
                $result = sql("INSERT INTO ivr (displayname, deptname) VALUES ('__install_done', '1')");
		needreload();
        }
	// Now, we need to check for upgrades. 
	// V1.0, old IVR. You shouldn't see this, but check for it anyway, and assume that it's 2.0
	// V2.0, Original Release
	// V2.1, added 'directorycontext' to the schema
	// 
	if (modules_getversion('ivr') == "1.0" || modules_getversion('ivr') == "2.0") {
		// Add the col
		sql('ALTER TABLE ivr ADD COLUMN dircontext VARCHAR ( 50 ) DEFAULT "default"');
		modules_setversion('ivr', '1.1');
	}
}

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ivr_destinations() {
	//get the list of IVR's
	$results = ivr_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
			$extens[] = array('destination' => 'ivr-'.$result['ivr_id'].',s,1', 'description' => $result['displayname']);
		}
	}
	if (isset($extens)) 
		return $extens;
	else
		return null;
}

function ivr_get_config($engine) {
        global $ext;
        global $conferences_conf;

	switch($engine) {
		case "asterisk":
			$ivrlist = ivr_list();
			if(is_array($ivrlist)) {
				foreach($ivrlist as $item) {
					$id = "ivr-".$item['ivr_id'];
					$details = ivr_get_details($item['ivr_id']);
					if (!empty($details['enable_directdial'])) 
                                        	$ext->addInclude($id,'ext-local');
					// I'm not sure I like the ability of people to send voicemail from the IVR.
					// Make it a config option, possibly?
                                        // $ext->addInclude($item[0],'app-messagecenter');
					if (!empty($details['enable_directory']))
                                        	$ext->addInclude($id,'app-directory');
                                        $ext->add($id, 'h', '', new ext_hangup(''));
                                        $ext->add($id, 's', '', new ext_setvar('LOOPCOUNT', 0));
                                        $ext->add($id, 's', '', new ext_setvar('__DIR-CONTEXT', $details['dircontext']));
                                        $ext->add($id, 's', '', new ext_answer(''));
                                        $ext->add($id, 's', '', new ext_wait('1'));
                                        $ext->add($id, 's', 'begin', new ext_digittimeout(3));
                                        $ext->add($id, 's', '', new ext_responsetimeout($details['timeout']));
					if(function_exists('recordings_get')) {
						$recording = recordings_get($details['announcement']);
						$ext->add($id, 's', '', new ext_background($recording['filename']));
					}
                                        $ext->add($id, 'hang', '', new ext_playback('vm-goodbye'));
                                        $ext->add($id, 'hang', '', new ext_hangup(''));

                                        $default_t=true;
					// Actually add the IVR commands now.
					$dests = ivr_get_dests($item['ivr_id']);
					if (!empty($dests)) {
						foreach($dests as $dest) {
							if ($dest['selection'] == 't') $timeout=true;
							if ($dest['selection'] == 'i') $invalid=true;
							$ext->add($id, $dest['selection'],'', new ext_goto($dest['dest']));
						}
					}
					// Apply invalid if required
					if (!isset($invalid)) {
						$ext->add($id, 'i', '', new ext_playback('invalid'));
						$ext->add($id, 'i', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if (!isset($timeout)) {
						$ext->add($id, 't', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if (isset($addloop)) {
						$ext->add($id, 'loop', '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
						$ext->add($id, 'loop', '', new ext_gotoif('$[${LOOPCOUNT} > 2]','hang,1'));
						$ext->add($id, 'loop', '', new ext_goto($id.',s,begin'));
					}
					$ext->add($id, 'fax', '', new ext_goto('ext-fax,in_fax,1'));
                                }
                        }
                break;
        }
}



function ivr_get_ivr_id($name) {
	global $db;
	$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
	if (count($res) == 0) {
		// It's not there. Create it and return the ID
		sql("INSERT INTO ivr (displayname, enable_directory, enable_directdial, timeout)  values('$name', 'CHECKED', 'CHECKED', 10)");
		$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
		needreload();
	}
	return ($res[0]);
	
}

function ivr_add_command($id, $cmd, $dest) {
	global $db;
	// Does it already exist?
	$res = $db->getRow("SELECT * from ivr_dests where ivr_id='$id' and selection='$cmd'");
	if (count($res) == 0) {
		// Just add it.
		sql("INSERT INTO ivr_dests VALUES('$id', '$cmd', '$dest')");
	} else {
		// Update it.
		sql("UPDATE ivr_dests SET dest='$dest' where ivr_id='$id' and selection='$cmd'");
	}
	needreload();
}
function ivr_do_edit($id, $post) {

	$displayname = isset($post['displayname'])?$post['displayname']:'';
	$timeout = isset($post['timeout'])?$post['timeout']:'';
	$ena_directory = isset($post['ena_directory'])?$post['ena_directory']:'';
	$ena_directdial = isset($post['ena_directdial'])?$post['ena_directdial']:'';
	$annmsg = isset($post['annmsg'])?$post['annmsg']:'';
	$dircontext = isset($post['dircontext'])?$post['dircontext']:'';

	if (!empty($ena_directory)) 
		$ena_directory='CHECKED';


	if (!empty($ena_directdial)) 
		$ena_directdial='CHECKED';
	
	sql("UPDATE ivr SET displayname='$displayname', enable_directory='$ena_directory', enable_directdial='$ena_directdial', timeout='$timeout', announcement='$annmsg', dircontext='$dircontext' WHERE ivr_id='$id'");

	// Delete all the old dests
	sql("DELETE FROM ivr_dests where ivr_id='$id'");
	// Now, lets find all the goto's in the post. Destinations return goto_indicateN => foo and get fooN for the dest.
	// Is that right, or am I missing something?
	foreach(array_keys($post) as $var) {
		if (preg_match('/goto_indicate(\d+)/', $var, $match)) {
			// This is a really horrible line of code. take N, and get value of fooN. See above. Note we
			// get match[1] from the preg_match above
			$dest = $post[$post[$var].$match[1]];
			$cmd = $post['option'.$match[1]];
			// Debugging if it all goes pear shaped.
			// print "I think pushing $cmd does $dest<br>\n";
			if (strlen($cmd))
				ivr_add_command($id, $cmd, $dest);
		}
	}
	needreload();
}


function ivr_list() {
	global $db;

	$sql = "SELECT * FROM ivr where displayname <> '__install_done' ORDER BY displayname";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
		return null;
        }
        return $res;
}

function ivr_get_details($id) {
	global $db;

	$sql = "SELECT * FROM ivr where ivr_id='$id'";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
		return null;
        }
        return $res[0];
}

function ivr_get_dests($id) {
	global $db;

	$sql = "SELECT selection, dest FROM ivr_dests where ivr_id='$id' ORDER BY selection";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
                return null;
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

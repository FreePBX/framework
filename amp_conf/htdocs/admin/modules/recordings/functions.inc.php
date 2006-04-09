<?php

function recordings_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	
	$modulename = "recordings";
	
	switch($engine) {
		case "asterisk":
			// FeatureCodes for save / check
			$fcc = new featurecode($modulename, 'record_save');
			$fc_save = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'record_check');
			$fc_check = $fcc->getCodeActive();
			unset($fcc);

			if ($fc_save != '' || $fc_check != '') {
				$ext->addInclude('from-internal-additional', 'app-recordings'); // Add the include from from-internal
				
				if ($fc_save != '') {
					$ext->add('app-recordings', $fc_save, '', new ext_macro('user-callerid'));
					$ext->add('app-recordings', $fc_save, '', new ext_wait('2'));
					$ext->add('app-recordings', $fc_save, '', new ext_record('${CALLERID(number)}-ivrrecording:wav'));
					$ext->add('app-recordings', $fc_save, '', new ext_wait('2'));
					$ext->add('app-recordings', $fc_save, '', new ext_hangup(''));
				}

				if ($fc_check != '') {
					$ext->add('app-recordings', $fc_check, '', new ext_macro('user-callerid'));
					$ext->add('app-recordings', $fc_check, '', new ext_wait('2'));
					$ext->add('app-recordings', $fc_check, '', new ext_playback('${CALLERID(number)}-ivrrecording:wav'));
					$ext->add('app-recordings', $fc_check, '', new ext_wait('2'));
					$ext->add('app-recordings', $fc_check, '', new ext_hangup(''));
				}
}
		break;
	}
}			

function recordings_get_id($fn) {
	global $db;
	
	$sql = "SELECT id FROM recordings WHERE filename='$fn'";
        $results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if (isset($results['id'])) {
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

<?php 
/* $Id */


// This is the hook for 'destinations'
function disa_destinations() {
        $results = disa_list();
        // return an associative array with destination and description
        if (isset($results)) {
                foreach($results as $result){
                                $extens[] = array('destination' => 'disa,'.$result['disa_id'].',1', 'description' => $result['displayname']);
                }
                return $extens;
        } else {
                return null;
        }
}

// This actually generates the dialplan
function disa_get_config($engine) {
        global $ext; 
        switch($engine) {
                case "asterisk":
                        $disalist = disa_list();
                        if(is_array($disalist)) {
                                foreach($disalist as $item) {
					// Create the disa-$id.conf file
					print "Trying to open /etc/asterisk/disa-".$item['disa_id'].".conf\n";
					$fh = fopen("/etc/asterisk/disa-".$item['disa_id'].".conf", "w+");
					$pinarr = explode(',' , $item['pin'] );
					foreach($pinarr as $pin) {
						// empty password should be 'no-password'
						if ( (isset($pin) ? $pin : '') == '' )
							$pin = 'no-password';
						
						// Don't support remote MWI, too easy for users to break.
						fwrite($fh, "$pin|".$item['context']."|".$item['cid']."\n");
					}
					fclose($fh);
					
                                        $thisitem = disa_get(ltrim($item['disa_id']));
                                        // add dialplan
                                        $ext->add('disa', $item['disa_id'], '', new ext_playback('enter-password'));
                                        $ext->add('disa', $item['disa_id'], '', new ext_disa('/etc/asterisk/disa-'.$item['disa_id'].'.conf|from-internal'));
                                }
                        }
                break;
        }
}


function disa_list() {
       $results = sql("SELECT * FROM disa","getAll",DB_FETCHMODE_ASSOC);
        if(is_array($results)){
                foreach($results as $result){
                        // check to see if we have a dept match for the current AMP User.
                        if (!isset($results['deptname']) || checkDept($result['deptname'])){
                                // return this item's dialplan destination, and the description
                                $allowed[] = $result;
                        }
                }
        }
        if (isset($allowed)) {
                return $allowed;
        } else {
                return null;
        }
}

function disa_get($id){
        //get all the variables for the meetme
        $results = sql("SELECT * FROM disa WHERE disa_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
        return $results;
}

function disa_chk($post) {
	return true;
}

function disa_add($post) {
        if(!disa_chk($post))
                return null;
        extract($post);
        if(empty($displayname)) $displayname = "unnamed";
        $results = sql("INSERT INTO disa (displayname,pin,cid,context) values (\"$displayname\",\"$pin\",\"$cid\",\"$context\")");
}

function disa_del($id) {
	$results = sql("DELETE FROM disa WHERE disa_id = \"$id\"","query");
	unlink("/etc/asterisk/disa-{$id}.conf");
}

function disa_edit($id, $post) {
	if (!disa_chk($post))
		return null;
	extract($post);
        if(empty($displayname)) $displayname = "unnamed";
        $results = sql("UPDATE disa  set displayname = \"$displayname\", pin = \"$pin\", cid = \"$cid\", context = \"$context\" where disa_id = \"$id\"");
}
?>

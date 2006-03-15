<?php 
/* $Id $ */

/* paging_init - Is run every time the page is loaded, checks
   to make sure that the database is current and loaded, if not,
   it propogates it. I expect that extra code will go here to 
   check for version upgrades, etc, of the paging database, to
   allow for easy upgrades. */

function paging_init() {
	global $db;
	
	// Check to make sure that install.sql has been run
	$sql = "SELECT * from paging_overview";
	$results = $db->getAssoc($sql);
	
	if (DB::IsError($results)) {
		// It couldn't locate the table. This is bad. Lets try to re-create it, just
		// in case the user has had the brilliant idea to delete it. 
		// runModuleSQL stolen blatantly from page.module.php.
		runModuleSQL('paging', 'uninstall');
		if (runModuleSQL('paging', 'install')==false) {
			echo _("There is a problem with install.sql, cannot re-create databases. Contact support\n");
			die;
		} else {
			echo _("Database was deleted! Recreated successfully.<br>\n");
			$results = $db->getAll($sql);
		}
	}
	if (!isset($results['version'])) {
		print "First-time use. Propogating databases.<br>\n";
		// Here, you load up a current database schema. Below, if the version is 
		// different, you'd write some upgrade code. This is better than doing it
		// in install.sql, becuase you don't know what's in there already. 
		$sql = "INSERT INTO paging_overview VALUES ('version', 1)";
		$db->query($sql);
		/* Load up the phone definitions */
		$fd = fopen("modules/paging/phones.sql","r");
		while (!feof($fd)) {
			$data = fgets($fd, 1024);
			if ($data{0}!=';' && $data{0}!='#' && strlen($data) > 3) {
				// It's not a comment or a blank(ish) line. Add it.
				$phoneresult = $db->query($data);
				if(DB::IsError($phoneresult)) 
					die($phoneresult->getMessage()."<br><br>error adding to phones table");
			}
		}
		fclose($fd);
		print "Init complete. Please click on this page again to start using this module<br>\n";
		exit;
	} /* else ... check the version and upgrade if needed. */
}


//	Generates dialplan for paging  - is called from retrieve_conf

function paging_get_config($engine) {
	global $db;
	global $ext; 
	switch($engine) {
		case "asterisk":
			// Get a list of all the phones used for paging
			$sql = "SELECT DISTINCT ext FROM paging_groups";
			$results = $db->getAll($sql);
			if (!isset($results[0][0])) {
				// There are no phones here, no paging support, lets give up now.
				return 0;
			}
			// We have paging support.
			$ext->addInclude('from-internal-additional','ext-paging');
			// Lets give all the phones their PAGExxx lines.
			// TODO: Support for specific phones configurations
 			foreach ($results as $grouparr) {
				$xtn=trim($grouparr[0]);
				$ext->add('ext-paging', "PAGE${xtn}", '', new ext_setvar('_SIPADDHEADER', 'Call-Info: answer-after=0'));
				$ext->add('ext-paging', "PAGE${xtn}", '', new ext_setvar('ALERT_INFO', 'Ring Answer'));
				$ext->add('ext-paging', "PAGE${xtn}", '', new ext_setvar('__SIP_URI_OPTIONS', 'intercom=true'));
				$ext->add('ext-paging', "PAGE${xtn}", '', new ext_dial("SIP/${xtn}", 5));
			}
			// Now get a list of all the paging groups...
			$sql = "SELECT DISTINCT page_number FROM paging_groups";
			$paging_groups = $db->getAll($sql);
			foreach ($paging_groups as $thisgroup) {
				$grp=trim($thisgroup[0]);
				$sql = "SELECT ext FROM paging_groups WHERE page_number='$grp'";
				$all_exts = $db->getAll($sql);
				$dialstr='';
				foreach($all_exts as $local_dial) {
					$dialstr .= "LOCAL/PAGE".trim($local_dial[0])."@ext-paging&";
				}
				// It will always end with an &, so lets take that off.
				$dialstr = rtrim($dialstr, "&");
				$ext->add('ext-paging', "Debug", '', new ext_noop("dialstr is $dialstr"));
				$ext->add('ext-paging', $grp, '', new ext_page($dialstr));
			}

		break;
	}
}

function paging_list() {
	global $db;

	$sql = "SELECT DISTINCT page_number FROM paging_groups ORDER BY page_number";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	// There should be a checkRange here I think, but I haven't looked into it yet.
//	return array('999', '998', '997');
	return $results;
}

function paging_get_devs($grp) {
	global $db;

	// Just in case someone's trying to be smart with a SQL injection.
	$grp = addslashes($grp); 

	$sql = "SELECT ext FROM paging_groups where page_number='$grp'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) 
		$results = null;
	foreach ($results as $val)
		$tmparray[] = $val[0];
	return $tmparray;
}

function paging_modify($xtn, $plist) {
	global $db;

	// Just in case someone's trying to be smart with a SQL injection.
	$xtn = addslashes($xtn);

	// Delete it if it's there.
	paging_del($xtn);

	// Now add it all back in.
	paging_add($xtn, $plist);

	// Aaad we need a reload.
	needreload();

}

function paging_del($xtn) {
	global $db;
	$sql = "DELETE FROM paging_groups WHERE page_number='$xtn'";
	$db->query($sql);
	needreload();
}

function paging_add($xtn, $plist) {
	global $db;

	// $plist contains a string of extensions, with \n as a seperator. 
	// Split that up first.
	$xtns = explode("\n",$plist);
	foreach (array_keys($xtns) as $val) {
		$val = addslashes(trim($xtns[$val]));
		// Sanity check input.
		
		
		
		$sql = "INSERT INTO paging_groups VALUES ('$xtn', '$val')";
		$db->query($sql);
	}
	needreload();
}

	



?>





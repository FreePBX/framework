<?php  /* $Id */


out("Upgrading DID Routes..");

$sql = "SELECT extension,application,args FROM extensions where context = 'ext-did' and priority = '2' and (application = 'Goto' or application = 'Macro')";
$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::IsError($results)) {
	die($results->getMessage());
}

$sql = "SELECT extension,cidnum FROM incoming";
$existingresults = $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::IsError($existingresults)) {
	die($existingresults->getMessage());
}

foreach ($results as $result) {
	$extarray=explode('/',$result['extension'],2);
	$extension = $extarray[0];
	$cidnum = $extarray[1];
	$upgrade=true;
	foreach($existingresults as $exr) {
		if(($extension == $exr['extension']) && ($cidnum == $exr['cidnum'])) {
			$upgrade=false;
		}
	}
		
	if ($upgrade) {
		out("upgrading ".$extension);
		if($result['application'] == "Macro") {
			$destination = 'ext-local,${VM_PREFIX}'.ltrim($result['args'],'vm,').',1';
		} else if($result['args'] == "from-pstn,s,1") {
			$destination = '';
		} else {
			$destination = $result['args'];
		}
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman) values (\"$cidnum\",\"$extension\",\"$destination\",\"default\",\"\",\"0\",\"0\",\"0\")";
		$insertresults = $db->query($sql);
		if(DB::IsError($insertresult)) {
			die($insertresults->getMessage());
		}
	}
}

out("OK");
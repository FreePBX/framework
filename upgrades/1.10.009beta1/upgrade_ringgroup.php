<?php  /* $Id */


out("Upgrading CAll Groups to add in Strategies..");

$sql = "SELECT extension, args FROM extensions where context = 'ext-group' and priority = '1'";
$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::IsError($results)) {
        die($results->getMessage());
}

foreach ($results as $result) {
        $grparray=explode(',',$result['args'],4);
        $application = $grparray[0];
        $timer = $grparray[1];
        $CID = $grparray[2];
        $members = $grparray[3];
        $extension=$result['extension'];
        $arg_string="$application,ringall,$timer,$CID,$members";
        $sql="UPDATE extensions set args=\"$arg_string\" where extension=\"$extension\" and priority=\"1\"";
        $updateresults = $db->query($sql);
        if(DB::IsError($updateresults)) {
                die($updateresults->getMessage());
        }

}

out("OK");

?>
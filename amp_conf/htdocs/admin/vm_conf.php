<?
$vmconf = '/var/www/html/admin/voicemail.conf';
$vmconf_live = '/etc/asterisk/voicemail.conf';

$fh = fopen($vmconf_live,"r");
$row = 0;
// read values from voicemail.conf into uservm[][]
while ($line = fgets ($fh, 512))
{
    if (ereg(' => ',$line)) {  //only read rows with vmbox data
        $line = ereg_replace(" => ",',',$line);
	$line_arr = explode(',',$line);
	$thisbox = $line_arr[0];
	$uniquebox=true;
	for ($i = 0; $i < $row; $i++) { //never allow duplicate mailboxes!!
		if ($uservm[$i][0] == $thisbox)
			$uniquebox=false;
	}
	if ($uniquebox) {
		$uservm[$row] = $line_arr;
		//echo 'acc='.$uservm[$row][0].'<br>';
		$row++;		
	}
	
    }
}
fclose ($fh);

//add or edit a row
if ($action == 'bscEdit' || $action == 'advEdit' || $action == 'add') {
    $change[] = array($_REQUEST['account'],$_REQUEST['vmpwd'],$_REQUEST['name'],$_REQUEST['email'],$_REQUEST['pager'],$_REQUEST['options']);
    $foundit=false;
    for ($i = 0; $i < $row; $i++) {
        if ($uservm[$i][0] == $extdisplay) {
            array_splice($uservm,$i,1,$change); //replaces elements with $change
	    $foundit=true;
        }
    }
    if (!$foundit) {  //if the vmbox is not in conf file, add it
	    $uservm[] = $change[0];
	    $row++;
    }
}

//remove a row
if ($action == 'delete') {
    for ($i = 0; $i < $row; $i++) {
        if ($uservm[$i][0] == $extdisplay) {
            array_splice($uservm,$i,1);
            $row--;
        }
    }
}


$beginvm = "[general]\n#include vm_general.inc\n#include vm_email.inc\n[default]\n\n";
//write out working file
$fh = fopen($vmconf,"w");
fwrite($fh,$beginvm);
for ($i = 0; $i < $row; $i++) {
        $vmline = $uservm[$i][0].' => '.$uservm[$i][1].','.$uservm[$i][2].','.$uservm[$i][3].','.$uservm[$i][4].','.$uservm[$i][5]."\n";
        fwrite($fh,$vmline);
       // echo $vmline.'<br>';
}
fclose ($fh);

//make working file live
exec('cp '.$vmconf.' '.$vmconf_live);
?>

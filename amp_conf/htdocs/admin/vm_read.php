<?
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
?>
<?
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

for ($i = 0; $i < $row; $i++) {
    if ($uservm[$i][0] == $extdisplay) {
        $vmpwd = $uservm[$i][1];
        $name = $uservm[$i][2];
        $email = $uservm[$i][3];
        $pager = $uservm[$i][4];
        $options = $uservm[$i][5];
    }
}

?>

<?php

function recordings_list($path) {
	$i = 0;
	$arraycount = 0;
	
	if (is_dir($path)){
		if ($handle = opendir($path)){
			while (false !== ($file = readdir($handle))){ 
				if (($file != ".") && ($file != "..") && ($file != "CVS") && (strpos($file, "aa_") === FALSE)    ) 
				{
					$file_parts=explode(".",$file);
					$filearray[($i++)] = $file_parts[0];
				}
			}
		closedir($handle); 
		}
		   
	}
	if (isset($filearray)) sort($filearray);
	return ($filearray);
}
?>
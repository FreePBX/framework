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

<?php
 
$path_to_dir = "/var/lib/asterisk/mohmp3"; //path to directory u want to read.
 
function build_list() 
{
	global $path_to_dir;
	$handle=opendir($path_to_dir) ;
	$extensions = array('.mp3'); // list of extensions which only u want to read.
	
	//generate the pattern to look for.
	foreach ($extensions as $value)
		$pattern .= "$value|";
	
	$length = strlen($pattern);
	$length -= 1;
	$pattern = substr($pattern,0,$length);
	
	
	//store file names that match pattern in an array
	$i = 0;
	while (($file = readdir($handle))!==false) 
	{
		if ($file != "." && $file != "..") 
		{ 
		
			if(eregi($pattern,$file))
			{
				$file_array[$i] = $file; //pattern is matched store it in file_array.
				$i++;		
			}
		} 
	
	}
	closedir($handle); 
	
	return $file_array;  //return the size of the array
	
}

function draw_list($file_array) 
{
	global $path_to_dir;
	//list existing mp3s and provide delete buttons
	if ($file_array) {
		foreach ($file_array as $thisfile) {
			print "<div style=\"text-align:right;width:400px;border: 1px solid;padding:2px;\">";
			//print "<a style=\"float:left;margin-left:5px;\" href=\"file:". $path_to_dir ."/". $thisfile ."\">".$thisfile."</a>";
			print "<b style=\"float:left;margin-left:5px;\" >".$thisfile."</b>";
			print "<a style=\"margin-right:5px;\" href=\"".$_SERVER['SCRIPT_NAME']."?display=1&del=".$thisfile."\">Delete</a>";
			print "</div><br>";
		}
	}
}

function process_mohfile($mohfile)
{
	global $path_to_dir;
	$origmohfile=$path_to_dir."/orig_".$mohfile;
	$newmohfile=$path_to_dir."/". ((strpos($mohfile,'.mp3') === false) ? $mohfile.".mp3" : $mohfile);
	//echo $newmohfile;
	$lamecmd="lame --cbr -m m -t -F ".$origmohfile." ".$newmohfile;
	exec($lamecmd);
	$rmcmd="rm -f ". $origmohfile;
	exec($rmcmd);
}

function kill_mpg123()
{
	$killcmd="killall -9 mpg123";
	exec($killcmd);
}
?>

<form enctype="multipart/form-data" name="upload" action="<? echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
	Upload a .wav or .mp3 file:<br>
	<input type="hidden" name="display" value="1">
	<input type="file" name="mohfile"/>
	<input type="button" value="Upload" onclick="document.upload.submit(upload);alert('Please wait until the page loads. Your file is being processed.');"/>
</form>
<br><hr>

<?php

if (is_uploaded_file($_FILES['mohfile']['tmp_name'])) {
	//echo $_FILES['mohfile']['name']." uploaded OK";
	move_uploaded_file($_FILES['mohfile']['tmp_name'], $path_to_dir."/orig_".$_FILES['mohfile']['name']);
	process_mohfile($_FILES['mohfile']['name']);
	echo "<h5>Completed processing ".$_FILES['mohfile']['name']."!</h5>";
	kill_mpg123();
}

//build the array of files
$file_array = build_list();
$numf = count($file_array);


if ($_REQUEST['del']) {
	if ($numf == 1) 
	{
		echo "<h5>You must have at least one file for On Hold Music.  Please upload one before deleting this one.</h5>";
	} else {
		$rmcmd="rm -f ".$path_to_dir."/".$_REQUEST['del'];
		exec($rmcmd);
		echo "<h5>Deleted ".$_REQUEST['del']."!</h5>";
		kill_mpg123();
	}
}

//rebuild the array of files
$file_array = build_list();
//draw the list of files
draw_list($file_array);

?>

<?php /* $Id$ */
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
$action = $_REQUEST['action'];
$category = strtr($_REQUEST['category']," ", "-");
if ($category == null) $category = 'Default';
$display='music';

if ($category == "Default")
	$path_to_dir = "/var/lib/asterisk/mohmp3"; //path to directory u want to read.
else
	$path_to_dir = "/var/lib/asterisk/mohmp3/$category"; //path to directory u want to read.

switch ($action) {
	case "addednew":
		makemusiccategory($path_to_dir,$category); 
		createmusicconf();
		needreload();
	break;
	case "addedfile":
		createmusicconf();
		needreload();
	break;
	case "delete":
		music_rmdirr("$path_to_dir"); 
		$path_to_dir = "/var/lib/asterisk/mohmp3"; //path to directory u want to read.
		$category='Default';
		createmusicconf();
		needreload();
	break;
}


?>
</div>
<div class="rnav">
    <li><a href="config.php?display=<?php echo $display?>&action=add"><?php echo _("Add Music Category")?></a><br></li>
    <li><a id="<?php echo ($category=='Default' ? 'current':'')?>" href="config.php?display=<?php echo $display?>&category=Default"><?php echo _("Default")?></a><br></li>

<?php
//get existing trunk info
$tresults = music_list("/var/lib/asterisk/mohmp3");
if (isset($tresults)) {
	foreach ($tresults as $tresult) {
		echo "<li><a id=\"".($category==$tresult ? 'current':'')."\" href=\"config.php?display=".$display."&category={$tresult}&action=edit\">{$tresult}</a></li>";
	}
}
?>
</div>


<?php
function createmusicconf()
{
	$File_Write="";
	$tresults = music_list("/var/lib/asterisk/mohmp3");
	if (isset($tresults)) {
		foreach ($tresults as $tresult) 
			$File_Write.="{$tresult} => quietmp3:/var/lib/asterisk/mohmp3/{$tresult}\n";
	}

$handle = fopen("/etc/asterisk/musiconhold_additional.conf", "w");

if (fwrite($handle, $File_Write) === FALSE)
{
        echo _("Cannot write to file")." ($tmpfname)";
        exit;
}

fclose($handle);


}
function makemusiccategory($category)
{
	mkdir("$path_to_dir/$category", 0755); 
}
 
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

function draw_list($file_array, $path_to_dir, $category) 
{
	//list existing mp3s and provide delete buttons
	if ($file_array) {
		foreach ($file_array as $thisfile) {
			print "<div style=\"text-align:right;width:350px;border: 1px solid;padding:2px;\">";
			//print "<a style=\"float:left;margin-left:5px;\" href=\"file:". $path_to_dir ."". $thisfile ."\">".$thisfile."</a>";
			print "<b style=\"float:left;margin-left:5px;\" >".$thisfile."</b>";
			print "<a style=\"margin-right:5px;\" href=\"".$_SERVER['SCRIPT_NAME']."?display=1&del=".$thisfile."&category=".$category."\">"._("Delete")."</a>";
			print "</div><br>";
		}
	}
}

function process_mohfile($mohfile)
{
	global $path_to_dir;
	$origmohfile=$path_to_dir."/orig_".$mohfile;
	$newname = strtr($mohfile,"&", "_");
      $newmohfile=$path_to_dir."/". ((strpos($newname,'.mp3') === false) ? $newname.".mp3" : $newname);
	$lamecmd="lame --cbr -m m -t -F \"".$origmohfile."\" \"".$newmohfile."\"";
	exec($lamecmd);
	$rmcmd="rm -f \"". $origmohfile."\"";
	exec($rmcmd);
}

function kill_mpg123()
{
	$killcmd="killall -9 mpg123";
	exec($killcmd);
}
?>

<div class="content">
<h2><?php echo _("On Hold Music")?></h2>

<?php
if ($action == 'add')
{
	?>
	<form name="addcategory" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $display?>">
	<input type="hidden" name="action" value="addednew">
	<table>
	<tr><td colspan="2"><h5><?php echo _("Add Music Category")?><hr></h5></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Category Name:")?><span><?php echo _("Allows you to Set up Different Categories for music on hold.  This is useful if you would like to specify different Hold Music or Commercials for various ACD Queues.")?> </span></a></td>
		<td><input type="text" name="category" value=""></td>
	</tr>
	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="submit" value='<?php echo _("Submit Changes")?>' ></h6></td>		
	</tr>
	</table></form>
	<br><br><br><br><br>

<?php
}
else
{
?>

	<h5><?php echo _("Category:")?> <?php echo $category=="Default"?_("Default"):$category;?></h5>
	<?php  if ($category!="Default"){?>
	<p><a href="config.php?display=<?php echo $display ?>&action=delete&category=<?php echo $category ?>"><?php echo _("Delete Music Category")?> <?php echo $category; ?></a></p><?php }?>

	<form enctype="multipart/form-data" name="upload" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		<?php echo _("Upload a .wav or .mp3 file:")?><br>
		<input type="hidden" name="display" value="<?php echo $display?>">
		<input type="hidden" name="category" value="<?php echo "$category" ?>">
		<input type="hidden" name="action" value="addedfile">
		<input type="file" name="mohfile"/>
		<input type="button" value="Upload" onclick="document.upload.submit(upload);alert('<?php echo _("Please wait until the page loads. Your file is being processed.")?>');"/>
	</form>
	
	
	<?php

	if (is_uploaded_file($_FILES['mohfile']['tmp_name'])) {
		//echo $_FILES['mohfile']['name']." uploaded OK";
		move_uploaded_file($_FILES['mohfile']['tmp_name'], $path_to_dir."/orig_".$_FILES['mohfile']['name']);
		process_mohfile($_FILES['mohfile']['name']);
		echo "<h5>"._("Completed processing")." ".$_FILES['mohfile']['name']."!</h5>";
		kill_mpg123();
	}

	//build the array of files
	$file_array = build_list();
	$numf = count($file_array);


	if ($_REQUEST['del']) {
		if (($numf == 1) && ($category == "Default") ){
			echo "<h5>"._("You must have at least one file for On Hold Music.  Please upload one before deleting this one.")."</h5>";
		} else {
			$rmcmd="rm -f \"".$path_to_dir."/".$_REQUEST['del']."\"";
			exec($rmcmd);
			echo "<h5>"._("Deleted")." ".$_REQUEST['del']."!</h5>";
			kill_mpg123();
		}
	}
	$file_array = build_list();
	draw_list($file_array, $path_to_dir, $category);
	?>
	<br><br><br><br><br><br>
<?php
}
?>

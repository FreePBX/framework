#!/usr/bin/php -q
<?php

$libfreepbx = '../amp_conf/htdocs/admin/common/libfreepbx.javascripts.js';
$dir="../amp_conf/htdocs/admin/assets/js";
$output=array();

exec("ls $dir/*.js",$output,$ret);
$final=$finalB=array();
/*
 * to order js files: files will be appened to the array in the order they appear
 * in the switch statmenet below. To give a file priority, create a case for it and
 * add it to the $finalB array. All other files will be appended to the $final array.
 * $finalB is then merged with $final, with $finalB being put first
 */  
foreach ($output as $file) {
	switch(true){
		case preg_match("|$dir/jquery-.*\.js|",$file)://jquery
			 $finalB[] = $file;
		break;
		case preg_match("|$dir/jquery.cookie.js$|",$file)://jquery ui
			$finalB[] = $file;
		break;
		case preg_match("|$dir/jquery-ui-.*\.js$|",$file)://jquery ui
			$finalB[] = $file;
		break;
		case $file==$dir.'/script.legacy.js'://legacy script
			$finalB[] = $file;
		break;
		case $file != $dir.'/script.legacy.js'://default
			$final[] = $file;
		break;
	}
}

$final=array_merge($finalB,$final);

echo "creating $libfreepbx with:\n\n";
print_r($final);
echo 'cat '.implode(' ',$final)." | ./jsmin.rb >  $libfreepbx\n\n";

system('cat '.implode(' ',$final)." | ./jsmin.rb >  $libfreepbx");

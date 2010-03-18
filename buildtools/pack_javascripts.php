#!/usr/bin/php -q
<?php

$libfreepbx = 'libfreepbx.javascripts.js';
$dir="../amp_conf/htdocs/admin/common";
$output=array();

exec("ls $dir/*.js",$output,$ret);
$final = array();
foreach ($output as $file) {
  if (preg_match("|$dir/jquery-\d+\.\d+\.\d+\.js|",$file,$matches)) {
    array_unshift($final,$dir.'/script.legacy.js');
    array_unshift($final,$file);
  } else if ($file != $dir.'/'.$libfreepbx && $file != $dir.'/script.legacy.js') {
    $final[] = $file;
  }
}
echo "creating $libfreepbx with:\n\n";
echo 'cat '.implode(' ',$final)." | ./jsmin.rb >  $dir/$libfreepbx\n\n";

system('cat '.implode(' ',$final)." | ./jsmin.rb >  $dir/$libfreepbx");

<?php

/**
 * @file
 * Functions to get info
 */

/*
 * Gets top level directory names 
 *
 * @param $path
 *   directory to search
 * @param $filter
 *   string to use as a filter to match files to return
 * @param $directories
 *   directories found
 */
function get_directories($path,$filter,&$directories) {

  if (is_dir($path)) {

    $dh = opendir($path);
    while (false!== ($item = readdir($dh))) {
      if($item!="." && $item!="..") {

        CheckPathSlash($path);
        $directory = $path;
        AppendPath($directory,$item);

        if (is_dir($directory)) {

          $found = 0;
          if ($filter) {
            if (strpos($directory,$filter)) {
              $found = 1;
            }
          } else {
            $found = 1;
          }
          if ($found) {
            $directories[count($directories) + 1] = $directory;
          }
        }
      }
    } 
  }
}

/*
 * Gets file names recursively 6 folders deep
 *
 * @param $path
 *   directory to search
 * @param $filter
 *   string to use as a filter to match files to return
 * @param $recursiveMax
 *   max number of sub folders to search
 * @param $recursiveCount
 *   current sub folder count
 * @param $files
 *   files found
 */
function get_files($path,$filter,$recursiveMax,&$recursiveCount,&$files) {

  if (is_dir($path)) {
    $dh = opendir($path);
    while (false!== ($item = readdir($dh))) {
      if($item!="." && $item!="..") {

        CheckPathSlash($path);
        $msg_path = $path;
        AppendPath($msg_path,$item);

        if ($recursiveMax<$recursiveMax && is_dir($path)) {
          $recursiveCount++;
          get_files($path,$recursiveMax,$recursiveCount,$files);
        } else {
          $found = 0;
          if ($filter) {
            if (strpos($msg_path,$filter)) {
              $found = 1;
            }
          } else {
            $found = 1;
          }
          if ($found) {
            $files[count($files) + 1] = $msg_path;
          }
        }
      }
    } 
  }
}


?>
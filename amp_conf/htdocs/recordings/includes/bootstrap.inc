<?php

/**
 * @file
 * Functions that need to be loaded on every request.
 */

/**
 * Sets i18n locale language
 *
 * sets the language for i18n php gettext module
 * (gettext has to be enabled in the php.ini)
 *
 */
function set_locale_lang() {

  global $ari_language_default;

  if (extension_loaded('gettext')) {

    // try and find the default locale
    $default_lang = preg_replace('/-/','_',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

    $locale = 'en_US';
    $locale_dir = "./locale";
    $directories = array();
    get_directories($locale_dir,"",&$directories);
    foreach($directories as $directory) {
      $buf = substr($directory,strlen($locale_dir)+1,strlen($directory) - strlen($locale_dir));
      if (preg_match("/" . $buf . "/i",$default_lang)) {
        $locale = $buf;  
        break;
      }
    }

    // set locale
    $language = $_COOKIE['ari_lang'] ? $_COOKIE['ari_lang']:$locale;
    putenv("LANG=$language"); 
    putenv("LANGUAGE=$language");
    setlocale(LC_MESSAGES,$language);
    bindtextdomain('ari','./locale');
    bind_textdomain_codeset('ari', 'UTF-8');
    textdomain('ari');

  } else {
    function _($str) {
      return $str;
    }
  }
}

/**
 * Set a arguments.
 *
 * @param $args
 *   The name of the array being acted upon.
 * @param $name
 *   The name of the variable to set.
 * @param $value
 *   The value to set. This can be any PHP data type; these functions take care
 *   of serialization as necessary.
 */
function set_argument(&$args, $name, $value) {

  if (!isset($value)) {
    $value = '';
  }
  
  $args[$name] = $value;
}

/**
 * Unset a arguments.
 *
 * @param $args
 *   The name of the array being acted upon.
 * @param $name
 *   The name of the variable to undefine.
 */
function unset_argument(&$args, $name) {

  unset($args[$name]);
}

/**
 * Return a arguments.
 *
 * @param $args
 *   The name of the array being acted upon.
 * @param $name
 *   The name of the variable to return.
 * @return
 *   The value of the variable.
 */
function get_argument(&$args, $name) {

  return isset($args[$name]) ? $args[$name] : '';
}

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

/* Utilities */

/**
 * Checks the path for a trailing slash
 *
 * @param $path
 *   path to append
 */
function CheckPathSlash(&$path) {

  $slash = '';
  if (!preg_match('/\/$/',$path)) {
    $slash = '/';
  } 
  $path .= $slash; 
}

/**
 * Appends folder to end of path
 *
 * @param $path
 *   path to append
 * @param $folder
 *   folder to append to path
 */
function AppendPath(&$path,$folder) {

  $m = '';
  if (!preg_match('/\/$/',$path)) {
    $m = '/';
  } 
  $path .= $m . $folder; 
}

/**
 * Get Date format 
 *
 * @param $timestamp
 *   timestamp to be converted
 */
function GetDateFormat($timestamp) {
  return date('Y-m-d', $timestamp);
}

/**
 * Get time format 
 *
 * @param $timestamp
 *   timestamp to be converted
 */
function GetTimeFormat($timestamp) {   
  return date('G:i:s', $timestamp);
}

/* */

/**
 * Cleans up old versions with files that will crater the system
 */
function version_cleanup() {

  if (is_file("./includes/info.inc")) {
    unlink("./includes/info.inc");
  }
  if (is_file("./modules/database.module")) {
    unlink("./modules/database.module");
  }
  if (is_file("./modules/display.module")) {
    unlink("./modules/display.module");
  }
  if (is_file("./modules/info.module")) {
    unlink("./modules/info.module");
  }
  if (is_file("./theme/style.css")) {
    unlink("./theme/style.css");
  }
}

/**
 * Starts the session
 */
function ari_session_start() {

  if (!isset($_SESSION['ari_user']) ) {

    // start a new session for the user 
    ini_set('session.gc_maxlifetime', '3900');  // make the session timeout a long time
    set_time_limit (360);
    session_start();
  }
}

/**
 * Bootstrap
 *
 * Loads critical variables needed for every page request
 *
 */
function bootstrap() {

  if(isset($_REQUEST['logout'])) {
    $login = new Login();
    $login->Unauth();
  }

  if (!isset($_SESSION['ari_user'])) {
    $login = new Login();
    $login->Auth();
  }

  // set error reporting
  error_reporting (E_ALL & ~ E_NOTICE);  
}

/**
 * Set HTTP headers in preparation for a page response.
 *
 * TODO: Figure out caching
 */
function ari_page_header() {

  bootstrap();
}

/**
 * Perform end-of-request tasks.
 *
 * This function sets the page cache if appropriate, and allows modules to
 * react to the closing of the page by calling hook_exit().
 */
function ari_page_footer() {

}

/**
 * Includes and run functions
 */

version_cleanup();
set_locale_lang(); 
ari_session_start();

include_once("version.inc");
include_once("includes/main.conf");
include_once("includes/login.inc");


?>
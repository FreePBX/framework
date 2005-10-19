<?php

/**
 * @file
 * common functions - core handler
 */

/**
 * Builds database connections
 */
function DatabaseLogon() {

  global $amp_functions_file;
  global $amportal_conf_file;
  global $standalone;

  if ($standalone['use']) {
    $username = $standalone['asterisk_dbuser'];
    $password = $standalone['asterisk_dbpass'];
  } 
  else {
    if (is_file($amp_functions_file)) {
      include_once($amp_functions_file);
      $amp_conf = parse_amportal_conf($amportal_conf_file);
      $username = $amp_conf["AMPDBUSER"];
      $password = $amp_conf["AMPDBPASS"];
      unset($amp_conf);
    } 
  }

  require_once('DB.php'); // PEAR must be installed
 
  $db = new Database();

  $success = $db->logon($username, 
                        $password,
                        'localhost',
                        'asterisk',
                        'mysql',
                        $_SESSION['dbh_asterisk']);
  if (!$success) {
    return false;
  }

  $success = $db->logon($username, 
                        $password,
                        'localhost',
                        'asteriskcdrdb',
                        'mysql',
                        $_SESSION['dbh_cdr']);
  if (!$success) {
    return false;
  }

  return true;
}

/*
 * Checks if user is set and sets
 */
function CheckErrorMessage() {

  if ($_SESSION['ari_error']) {
    $ret .= "<div class='error'>
               " . $_SESSION['ari_error'] . "
             </div>
             <br>";
    unset($_SESSION['ari_error']);
  }

  return $ret;
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

/*
 * Checks if user is set and sets
 */
function LoginBlock() {

  if ( !isset($_SESSION['ari_user']) ) {

    if (isset($_REQUEST)) { $request = $_REQUEST; } else { $request = NULL; }

    $login = new Login();
    $display = new Display(NULL);

    // new header
    $ret .= $display->DisplayHeaderText("Login");
    $ret .= $display->DisplayLine();
    $ret .= CheckErrorMessage();

    // login form
    $ret .= $login->Form($request);

    return $ret;
  }
}

/*
 * Main handler for website
 */
function HandleBlock() {

  // check errors here and in login block
  $content .= CheckErrorMessage();

  # if nothing set goto user default page
  if (!isset($_REQUEST['m'])) {
    $_REQUEST['m'] = $_SESSION['ari_user']['default_page'];
  }
  # if not function specified then use display page function
  if (!isset($_REQUEST['f'])) {
    $_REQUEST['f'] = 'display';
  }

  $m = $_REQUEST['m'];     // module
  $f = $_REQUEST['f'];     // function
  $a = $_REQUEST['a'];     // action

  // set arguments
  foreach($_REQUEST as $key => $value) {
    set_argument($args,$key,$value);
  }

  $modules = array();

  // get modules
  $modules_path = "./modules";
  if (is_dir($modules_path)) {

    $filter = ".module";
    $recursiveMax = 1;
    $recursiveCount = 0;
    get_files($modules_path,$filter,$recursiveMax,$recursiveCount,$files);

    foreach($files as $key => $path) {

      // build module object 
      include_once($path); 
      $path_parts = pathinfo($path);
      list($name,$ext) = split("\.",$path_parts['basename']);

      // check for module and get rank
      if (class_exists($name)) {

        $module = new $name();
        $module_methods = get_class_methods($module);

        // set rank
        $rank = 99999;
        $rank_function = $name . "_rank";
        if (in_array(strtolower($rank_function), $module_methods)) {
          $rank = $module->$rank_function(); 
        }

        // add to module name to array
        $moduleNames[$name] = $rank;
      }
    }
    asort($moduleNames);
  }
  else {
    $_SESSION['ari_error'] = $path . " " . _("not a directory or not readable");
  }

  // process modules
  foreach (array_keys($moduleNames) as $name) {

    // check for module and process
    if (class_exists($name)) {

      $module = new $name();
      $module_methods = get_class_methods($module);

      // add nav menu item
      $nav_menu_function = $name . "_nav_menu";
      if (in_array(strtolower($nav_menu_function), $module_methods)) {
        $nav_menu .= $module->$nav_menu_function($args); 
      }

      if ($m==$name) {

        // build sub menu
        $nav_submenu_function = $m . "_nav_submenu";
        if (in_array(strtolower($nav_submenu_function), $module_methods)) {
          $nav_submenu .= $module->$nav_submenu_function($args); 
        }

        // execute function (usually to build content)
        $function = $m . "_" . $f;
        if (in_array(strtolower($function), $module_methods)) {
          $content .= $module->$function($args);
        }
      }
    }
  }

  // error message if no content
  if (!$content) {
    $content .= _("Page Not Found.");
  } 

  return array($nav_menu,$nav_submenu,$content);
}

/*
 * Main handler for website
 */
function Handler() {

  global $ari_version;
  global $ari_no_login;

  $error = $_SESSION['ari_error'];
  if ($_SESSION['ari_user'] && !$ari_no_login) {
    $logout = 1;
  }

  $success = DatabaseLogon();
  if ($success) {

    // check if login is needed (user auth done in bootstrap)
    $content = LoginBlock();
    if (!isset($content)) {
        list($nav_menu,$nav_submenu,$content) = HandleBlock();
    }
  }
  else {

    $_SESSION['ari_error'] = _("No database connection") . "<br>" .
                             _("Check AMP installation, asterisk database, or ARI main.conf");

    $display = new Display();

    $content .= $display->DisplayHeaderText("ARI");
    $content .= $display->DisplayLine();
  }

  // builds the page
  include_once("theme/page.tpl.php"); 
}

/**
 * Includes and run functions
 */  

include_once("includes/database.inc");
include_once("includes/display.inc");  
include_once("includes/info.inc");


?>
<?php

/**
 * @file
 * common functions - core handler
 */

/**
 * Builds database connections
 */
function DatabaseLogon() {

  global $amp_conf;

  if (!$amp_conf) {
    return false;
  }

  require_once('DB.php'); //PEAR must be installed

  $db = new Database();

  $db->logon($amp_conf["AMPDBUSER"], 
             $amp_conf["AMPDBPASS"],
             'localhost',
             'asteriskcdrdb',
             'mysql',
             $_SESSION['dbh_cdr']);
  
  return true;
}

/*
 * Checks if user is set and sets
 */
function LoginForm() {

  global $ari_no_login;

  if ($ari_no_login) {
    return;
  }

  if ( !isset($_SESSION['user']) ) {

    if (isset($_REQUEST)) { $request = $_REQUEST; } else { $request = NULL; }

    $login = new Login();
    $display = new Display(NULL);

    // new header
    $ret .= $display->DisplayHeaderText("Login");
    $ret .= $display->DisplayLine();

    // login form
    $ret .= $login->Form($request);

    return $ret;
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

  $s = '';
  if (!preg_match('/\/$/',$path)) {
    $s = '/';
  } 
  $path .= $s; 
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

  $s = '';
  if (!preg_match('/\/$/',$path)) {
    $s = '/';
  } 
  $path .= $s . $folder; 
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
 * Main handler for website
 */
function HandleBlock() {

  global $ari_no_login;

  if (isset($_REQUEST['s'])) {

    $s = $_REQUEST['s'];     // section
    $a = $_REQUEST['a'];     // action

    // set arguments
    foreach($_REQUEST as $key => $value) {
      set_argument($args,$key,$value);
    }

    if ($s=="callmonitor") {

      include_once("modules/callmonitor.module"); 
      $content .= callmonitor_detail_display($args);
    }
    elseif ($s=="voicemail") {

      include_once("modules/voicemail.module"); 

      $navsub_menu .= voicemail_subnav_menu($args);

      if ($a) {
        $content .= voicemail_msg_action($a,$args);
      }
      else {
        $content .= voicemail_detail_display($args);
      }
    }
    else {
      $content .= "Page Not Found.";
    }
  } 
  else {

    if ($ari_no_login) {

      set_argument($args,"s","callmonitor");

      include_once("modules/callmonitor.module"); 
      $content .= callmonitor_detail_display($args);
    } 
    else {

      set_argument($args,"s","voicemail");

      include_once("modules/voicemail.module"); 

      $navsub_menu .= voicemail_subnav_menu($args);
      $content .= voicemail_detail_display($args);
    }
  }  

  return array($navsub_menu,$content);
}

/*
 * Main handler for website
 */
function Handler() {

  global $ari_no_login;

  $error = $_SESSION['error'];
  if ($_SESSION['user'] && !$ari_no_login) {
    $logout = 1;
  }

  $success = DatabaseLogon();
  if ($success) {

    // check if login form is needed (user auth done in bootstrap)
    $content = LoginForm();

    if (!isset($content)) {
        list($navsub_menu,$content) = HandleBlock();
    }
  }
  else {

    $_SESSION['error'] = "No database connection<br>
                          Check AMP installation, asterisk database, or ARI main.conf";

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

include_once("modules/database.module");
include_once("modules/display.module");  
include_once("modules/info.module");


?>
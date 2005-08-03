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

  require_once('DB.php'); //PEAR must be installed

  $db = new Database();

  $db->logon($amp_conf["AMPDBUSER"], 
             $amp_conf["AMPDBPASS"],
             'localhost',
             'asteriskcdrdb',
             'mysql',
             $_SESSION['dbh_cdr']);
}



/*
 * Checks if user is set and sets
 */
function LoginForm() {

  if ( !isset($_SESSION['AMP_user']) ) {

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

/*
 * Main handler for website
 */
function HandleBlock() {

  $q = $_REQUEST[q]; 						// search string 
  $start = isset($_REQUEST[start]) ? $_REQUEST[start] : 0;	// start
  $span = isset($_REQUEST[span]) ? $_REQUEST[span] : 15;	// span

  set_argument($args,'q',$q);	
  set_argument($args,'start',$start);	
  set_argument($args,'span',$span);	

  include_once("modules/recordings.module"); 
  $ret .= recordings_detail_display($args);

  return $ret;
}

/*
 * Main handler for website
 */
function Handler() {

  DatabaseLogon();

  // check if login form is needed (user auth done in bootstrap)
//  $content = LoginForm();

//  if (!isset($content)) {
      $content = HandleBlock();
//  }

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

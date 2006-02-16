<?php

/**
 * @file
 * common functions - core handler
 */

/*
 * Checks if user is set and sets
 */
function checkErrorMessage() {

  if ($_SESSION['ari_error']) {
    $ret .= "<div class='error'>
               " . $_SESSION['ari_error'] . "
             </div>
             <br>";
    unset($_SESSION['ari_error']);
  }

  return $ret;
}

/**
 * Builds database connections
 */
function databaseLogon() {

  global $STANDALONE;
  global $AMP_FUNCTIONS_FILE;
  global $AMPORTAL_CONF_FILE;
  global $ASTERISK_DBHOST;
  global $ASTERISK_DBNAME;
  global $ASTERISK_DBTYPE;
  global $ASTERISKCDR_DBHOST;
  global $ASTERISKCDR_DBNAME;
  global $ASTERISKCDR_DBTYPE;

  // get user
  if ($STANDALONE['use']) {
    $mgruser = $STANDALONE['asterisk_mgruser'];
    $mgrpass = $STANDALONE['asterisk_mgrpass'];
    $dbuser = $STANDALONE['asterisk_dbuser'];
    $dbpass = $STANDALONE['asterisk_dbpass'];
    $cdrdbuser = $STANDALONE['asterisk_cdrdbuser'];
    $cdrdbpass = $STANDALONE['asterisk_cdrdbpass'];
  } 
  else {
    if (is_file($AMP_FUNCTIONS_FILE)) {
      include_once($AMP_FUNCTIONS_FILE);
      $amp_conf = parse_amportal_conf($AMPORTAL_CONF_FILE);
      $mgruser = $amp_conf['AMPMGRUSER'];
      $mgrpass = $amp_conf['AMPMGRPASS'];
      $dbuser = $amp_conf["AMPDBUSER"];
      $dbpass = $amp_conf["AMPDBPASS"];
      $cdrdbuser = $amp_conf["AMPDBUSER"];
      $cdrdbpass = $amp_conf["AMPDBPASS"];
      unset($amp_conf);
    } 
  }

  // asterisk manager interface (berkeley database I think)
  global $asterisk_manager_interface;
  $asterisk_manager_interface = new AsteriskManagerInterface();

  $success = $asterisk_manager_interface->Connect($mgruser,$mgrpass);
  if (!$success) {
    $_SESSION['ari_error'] .= _("No Asterisk Manager Interface connection") . "<br>" .
                              _("Check AMP installation, asterisk, and ARI main.conf");
    return FALSE;
  }

  // pear interface databases
  $db = new Database();

  // AMP asterisk database
  if (!$STANDALONE['use']) {
    $success = $db->logon($dbuser, 
                          $dbpass,
                          $ASTERISK_DBHOST,
                          $ASTERISK_DBNAME,
                          $ASTERISK_DBTYPE,
                          $_SESSION['dbh_asterisk']);
    if (!$success) {
      $_SESSION['ari_error'] = _("Cannot connect to the $ASTERISK_DBNAME database") . "<br>" .
                               _("Check AMP installation, asterisk, and ARI main.conf");
      return FALSE;
    }
  }

  // cdr database
  $success = $db->logon($cdrdbuser, 
                        $cdrdbpass,
                        $ASTERISKCDR_DBHOST,
                        $ASTERISKCDR_DBNAME,
                        $ASTERISKCDR_DBTYPE,
                        $_SESSION['dbh_cdr']);
  if (!$success) {
    $_SESSION['ari_error'] = _("Cannot connect to the $ASTERISKCDR_DBNAME database") . "<br>" .
                             _("Check AMP installation, asterisk, and ARI main.conf");
    return FALSE;
  }

  return TRUE;
}

/**
 * Logout if needed for any databases
 */
function databaseLogoff() {

  global $asterisk_manager_interface;

  $asterisk_manager_interface->Disconnect();
}

/*
 * Checks if user is set and sets
 */
function loginBlock() {

  if ( !isset($_SESSION['ari_user']) ) {

    if (isset($_REQUEST)) { $request = $_REQUEST; } else { $request = NULL; }

    $login = new Login();

    // login form
    $ret .= $login->GetForm($request);

    return $ret;
  }
}

/*
 * Main handler for website
 */
function handleBlock() {

  global $ARI_ADMIN_MODULES;

  // check errors here and in login block
  $content .= checkErrorMessage();

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
    SetArgument($args,$key,$value);
  }

  $modules = array();

  // get modules
  $modules_path = "./modules";
  if (is_dir($modules_path)) {

    $filter = ".module";
    $recursive_max = 1;
    $recursive_count = 0;
    getFiles($modules_path,$filter,$recursive_max,$recursive_count,$files);

    foreach($files as $key => $path) {

      // build module object 
      include_once($path); 
      $path_parts = pathinfo($path);
      list($name,$ext) = split("\.",$path_parts['basename']);

      // check for module and get rank
      if (class_exists($name)) {

        $module = new $name();
        $module_methods = get_class_methods($module);    // note that this returns all lowercase

        // set rank
        $rank = 99999;
        $rank_function = "rank";
        if (in_array(strtolower($rank_function), $module_methods)) {
          $rank = $module->$rank_function(); 
        }

        // check if admin module
        $found = 0;
        if ($ARI_ADMIN_MODULES) {
          $adminModules = split(',',$ARI_ADMIN_MODULES);
          foreach ($adminModules as $key => $value) {
            if ($name==$value) {
              $found = 1;
              break;
            }
          }
        }

       // if not admin module or admin user add to module name to array
       if (!$found || $ari_admin_user) {
         $modules[$rank] = $module;
       }
      }
    }
    ksort($modules);
  }
  else {
    $_SESSION['ari_error'] = _("$path not a directory or not readable");
  }

  // process modules
  foreach ($modules as $module) {

    // process module
    $name = get_class($module);    // note that this returns all lowercase
    $module_methods = get_class_methods($module);    // note that this returns all lowercase

    // add nav menu item
    $nav_menu_function = "navMenu";
    if (in_array(strtolower($nav_menu_function), $module_methods)) {
      $nav_menu .= $module->$nav_menu_function($args); 
    }

    if (strtolower($m)==$name) {

      // build sub menu
      $nav_submenu_function = "navSubmenu";
      if (in_array(strtolower($nav_submenu_function), $module_methods)) {
        $nav_submenu .= $module->$nav_submenu_function($args); 
      }

      // execute function (usually to build content)
      if (in_array(strtolower($f), $module_methods)) {

        $content .= $module->$f($args);
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
function handler() {

  global $ARI_VERSION;
  global $ARI_NO_LOGIN;

  $error = $_SESSION['ari_error'];
  if ($_SESSION['ari_user'] && !$ARI_NO_LOGIN) {
    $logout = 1;
  }

  // login to database
  $success = databaseLogon();
  if ($success) {

    // check if login is needed (user auth done in bootstrap)
    $content = loginBlock();
    if (!isset($content)) {
        list($nav_menu,$nav_submenu,$content) = handleBlock();
    }
  }
  else {

    $display = new Display();

    $content .= $display->displayHeaderText("ARI");
    $content .= $display->displayLine();
    $content .= checkErrorMessage();
  }

  // version
  $ari_version = $ARI_VERSION;

  // builds the page
  include_once("./theme/page.tpl.php"); 

  // log off any databases needed
  databaseLogoff();
}

/**
 * Includes and run functions
 */  

// create asterisk manager interface singleton
$asterisk_manager_interface = '';

include_once("./includes/asi.inc");
include_once("./includes/database.inc");
include_once("./includes/display.inc"); 


?>
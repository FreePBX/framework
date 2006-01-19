<?php

/**
 * @file
 * common functions - core handler
 */

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

/**
 * Builds database connections
 */
function DatabaseLogon() {

  global $standalone;
  global $amp_functions_file;
  global $amportal_conf_file;
  global $asterisk_dbhost;
  global $asterisk_dbname;
  global $asteriskcdr_dbhost;
  global $asteriskcdr_dbname;

  // get user
  if ($standalone['use']) {
    $mgruser = $standalone['asterisk_mgruser'];
    $mgrpass = $standalone['asterisk_mgrpass'];
    $dbuser = $standalone['asterisk_dbuser'];
    $dbpass = $standalone['asterisk_dbpass'];
    $cdrdbuser = $standalone['asterisk_cdrdbuser'];
    $cdrdbpass = $standalone['asterisk_cdrdbpass'];
  } 
  else {
    if (is_file($amp_functions_file)) {
      include_once($amp_functions_file);
      $amp_conf = parse_amportal_conf($amportal_conf_file);
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
  if (!$standalone['use']) {
    $success = $db->logon($dbuser, 
                          $dbpass,
                          $asterisk_dbhost,
                          $asterisk_dbname,
                          'mysql',
                          $_SESSION['dbh_asterisk']);
    if (!$success) {
      $_SESSION['ari_error'] = _("Cannot connect to the") . " " . $asterisk_dbname . " " . _("database") . "<br>" .
                               _("Check AMP installation, asterisk, and ARI main.conf");
      return FALSE;
    }
  }

  // cdr database
  $success = $db->logon($cdrdbuser, 
                        $cdrdbpass,
                        $asteriskcdr_dbhost,
                        $asteriskcdr_dbname,
                        'mysql',
                        $_SESSION['dbh_cdr']);
  if (!$success) {
    $_SESSION['ari_error'] = _("Cannot connect to the") . " " . $asteriskcdr_dbname . " " . _("database") . "<br>" .
                             _("Check AMP installation, asterisk, and ARI main.conf");
    return FALSE;
  }

  return TRUE;
}

/**
 * Logout if needed for any databases
 */
function DatabaseLogoff() {

  global $asterisk_manager_interface;

  $asterisk_manager_interface->Disconnect();
}

/*
 * Checks if user is set and sets
 */
function LoginBlock() {

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
function HandleBlock() {

  global $ari_admin_modules;

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

        // check if admin module
        $found = 0;
        if ($ari_admin_modules) {
          $adminModules = split(',',$ari_admin_modules);
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
    $_SESSION['ari_error'] = $path . " " . _("not a directory or not readable");
  }

  // process modules
  foreach ($modules as $module) {

    // process module
    $name = get_class($module);
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

  // login to database
  $success = DatabaseLogon();
  if ($success) {

    // check if login is needed (user auth done in bootstrap)
    $content = LoginBlock();
    if (!isset($content)) {
        list($nav_menu,$nav_submenu,$content) = HandleBlock();
    }
  }
  else {

    $display = new Display();

    $content .= $display->DisplayHeaderText("ARI");
    $content .= $display->DisplayLine();
    $content .= CheckErrorMessage();
  }

  // builds the page
  include_once("./theme/page.tpl.php"); 

  // log off any databases needed
  DatabaseLogoff();
}

/**
 * Includes and run functions
 */  

$asterisk_manager_interface = '';

include_once("./includes/asi.inc");
include_once("./includes/database.inc");
include_once("./includes/display.inc"); 


?>
<?php

/**
 * @file
 * Functions that need to be loaded on every request.
 */

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

/**
 * Sets language
 *
 * sets the language for i18n php gettext module
 * (gettext has to be enabled in the php.ini)
 *
 */
function set_i18n_lang() {

  if (extension_loaded('gettext')) {
    setlocale(LC_MESSAGES,$_COOKIE['ari_lang'] ? $_COOKIE['ari_lang']:'en_US');
    bindtextdomain('ari','./locale');
    textdomain('ari');
  } else {
    function _($str) {
      return $str;
    }
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
  set_i18n_lang();
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

ari_session_start();

include_once("version.inc");
include_once("includes/main.conf");
include_once("includes/login.inc");




?>
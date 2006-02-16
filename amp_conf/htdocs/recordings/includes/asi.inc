<?php

/**
 * @file
 * Asterisk manager interface for access to asterisk api (astdb)
 */

/**
  * Asterisk Manager Interface
  */
class AsteriskManagerInterface {

  var $socket;

  /**
   * constructor 
   */
  function AsteriskManagerInterface() {

    global $AMP_FUNCTIONS_FILE;
    global $AMPORTAL_CONF_FILE;
    global $ASTERISK_MANAGER_FILE;

    // get manager user
    if ($STANDALONE['use']) {
      $mgruser = $STANDALONE['asterisk_mgruser'];
    } 
    else {
      if (is_file($AMP_FUNCTIONS_FILE)) {
        include_once($AMP_FUNCTIONS_FILE);
        $amp_conf = parse_amportal_conf($AMPORTAL_CONF_FILE);
        $mgruser = $amp_conf['AMPMGRUSER'];
        unset($amp_conf);
      } 
    }

    // check if php is allowed to talk to Asterisk Manager
    $found_account = 0;
    $found = 0;
    $lines = file($ASTERISK_MANAGER_FILE);
    foreach ($lines as $key => $line) {

      if (preg_match("/$mgruser/",$line)) {
        $found_account = 1;
      }

      if ($found_account && $line=='permit=0.0.0.0') {
        break;
      }

      if ($found_account && preg_match('/permit/',$line)) {
        $found = 1;
        break;
      }
    }

    if (!$found) {
      echo _("ARI does not appear to have access to the Asterisk Manager.") . "<br>" . 
           _("Check the ARI 'main.conf' configuration file to set the Asterisk Manager Account.") . "<br>" . 
           _("Check /etc/asterisk/manager.conf for a proper Asterisk Manager Account including a 'permit=' line for localhost or the webserver.");
      exit();
    }
  }

  /*
   * Reloads Asterisk Configuration
   *
   * @param $username
   *   asterisk manager interface username
   * @param $password
   *   asterisk manager interface password 
   */
  function connect($username,$password) {

    $fp = fsockopen("localhost", 5038, $errno, $errstr, 10);
    if (!$fp) {
      $_SESSION['ari_error'] =  _("Unable to connect to Asterisk Manager") . " ($errno)<br />\n";
      return FALSE;
    } 
    else {
      $buffer='';
      if(version_compare(phpversion(), '4.3', '>=')) {
        stream_set_timeout($fp, 5);
      } 
      else {
        socket_set_timeout($fp, 5);
      }
      $buffer = fgets($fp);
      if (!preg_match('/Asterisk Call Manager/i', $buffer)) {
        $_SESSION['ari_error'] = _("Asterisk Call Manager not responding") . "<br />\n";
        return FALSE;
      }
      else {
        $out="Action: Login\r\nUsername: ".$username."\r\nSecret: ".$password."\r\n\r\n";
        fwrite($fp,$out);
        $buffer=fgets($fp);
        if ($buffer!="Response: Success\r\n") {
          $_SESSION['ari_error'] =  _("Asterisk authentication failed:") . "<br />" . $buffer . "<br />\n";
          return FALSE;
        }
        else {
          $buffers=fgets($fp); // get rid of Message: Authentication accepted

          // connected
          $this->socket = $fp;
        }
      }
    }
    return TRUE;
  }

  /*
   * Reloads Asterisk Configuration
   */
  function disconnect() {

    if ($this->socket) {
      fclose($this->socket);
    }
  }

  /*
   * Reloads Asterisk Configuration
   *
   * @param $command
   *   Command to be sent to the asterisk manager interface 
   */
  function command($command,&$response) {

    fwrite($this->socket,$command);

    $count = 0;
    while (($buffer = fgets($this->socket)) && (!preg_match('/Response: Follows/i', $buffer))) {

      if ($count>100) {
        $_SESSION['ari_error'] =  _("Asterisk command not understood") . "<br />" . $buffer . "<br />\n";
        return FALSE;
      }
      $count++;
    }

    $count = 0;
    while (($buffer = fgets($this->socket)) && (!preg_match('/END COMMAND/i', $buffer))) {

      if (preg_match('/Value/',$buffer)) {
        $response = $buffer;
      }

      if ($count>100) {
        $_SESSION['ari_error'] =  _("Asterisk command not understood") . "<br />" . $buffer . "<br />\n";
        return FALSE;
      }
      $count++;
    }

    return TRUE;
  }

}  


?>
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
  }

  /*
   * Reloads Asterisk Configuration
   *
   * @param $username
   *   asterisk manager interface username
   * @param $password
   *   asterisk manager interface password 
   */
  function Connect($username,$password) {

    $fp = fsockopen("localhost", 5038, $errno, $errstr, 10);
    if (!$fp) {
      $_SESSION['ari_error'] =  _("Unable to connect to Asterisk Manager") . " ($errno)<br />\n";
      return FALSE;
    } 
    else {
      $buffer='';
      stream_set_timeout($fp, 5);
      $buffer = fgets($fp);
      if ($buffer!="Asterisk Call Manager/1.0\r\n") {
        $_SESSION['ari_error'] = _("Asterisk Call Manager not responding") . "<br />\n";
        return FALSE;
      }
      else {
        $out="Action: Login\r\nUsername: ".$username."\r\nSecret: ".$password."\r\n\r\n";
        fwrite($fp,$out);
        $buffer=fgets($fp);
        if ($buffer!="Response: Success\r\n") {
          $_SESSION['ari_error'] =  _("Asterisk authentication failed:") . "<br />" . $buff . "<br />\n";
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
  function Disconnect() {

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
  function Command($command,&$response) {

    fwrite($this->socket,$command);
    $buffer=fgets($this->socket); // get rid of a blank line
    if (preg_match('/END COMMAND/',$buffer)) {
      return TRUE;
    }
    $buffer=fgets($this->socket);
    if ($buffer!="Response: Follows\r\n") {
      $_SESSION['ari_error'] =  _("Asterisk reload command not understood") . "<br />" . $buffer . "<br />\n";
      return FALSE;
    }
    $response=fgets($this->socket);
    if (!preg_match('/END COMMAND/',$response)) {
      $buffer=fgets($this->socket);
    }

    return TRUE;
  }

}  


?>
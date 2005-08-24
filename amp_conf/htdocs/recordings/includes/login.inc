<?php

/**
 * @file
 * login functions
 */

class Login {

  var $error;

  /**
    * Purpose: Authenticate user and register user information into a session
    */
  function Auth() {

    global $asterisk_voicemail_conf;
    global $asterisk_sip_conf;
    global $callmonitor_admin_mailboxes;
    global $ari_no_login;

    // when login, make a new session
    if ( (isset($_POST['username']) && isset($_POST['password']) ||
           $ari_no_login) ) {

      $username = $_POST['username'];
      $password = $_POST['password'];

// need to add the sip secret to the login

      $lines = file($asterisk_voicemail_conf);
      foreach ($lines as $key => $line) {

//echo $key . " " . $line . "<br>";

        unset($value);
        list($var,$value) = split('=>',$line);
        $var = trim($var);
        if ($var==$username && $value) {
          $buf = split(',',$value);
          if ($buf[0]==$password) {   
            $mailbox = $username ;
            $displayname = $buf[1];

            $admin_callmonitor = 0;
            if ($callmonitor_admin_mailboxes) {
              $mailboxes = split(',',$callmonitor_admin_mailboxes);
              foreach ($mailboxes as $key => $value) {
                if ($value=='all') {
                  $admin_callmonitor = 1;
                  break;
                }
                if ($mailbox==$value) {
                  $admin_callmonitor = 1;
                }
              }
            }
          }
          else {
            $_SESSION['error'] = "Incorrect Password";
            return;
          }
        }
      }



// here is where need to add the sip login stuff


//$asterisk_sip_conf




      $default_page = 'voicemail';

      if (!$category) {
        $category = "general";
      }
      if (!$context) {
        $context = "default";
      }

      // no login user
      if ($ari_no_login) {
        $mailbox = 'admin';
        $name = 'Administrator';
        $admin_callmonitor = 1;
        $default_page = 'callmonitor';
      } 

      if ($mailbox) {
        $_SESSION['ari_user']['mailbox'] = $mailbox;
        $_SESSION['ari_user']['name'] = $displayname;
        $_SESSION['ari_user']['category'] = $category;
        $_SESSION['ari_user']['context'] = $context;
        $_SESSION['ari_user']['admin_callmonitor'] = $admin_callmonitor;
        $_SESSION['ari_user']['default_page'] = $default_page;
      } 
    }
  } 

  /**
    * Purpose: logout
    */
  function Unauth() {
    unset($_SESSION['ari_user']);
  }

  /**
   * Purpose: Provide a login form for user
   *
   * @param $request
   *   Variable to hold data entered into form
   */
  function Form($request) {

    global $ari_no_login;

    if ($ari_no_login) {
      $ret = '';
      return;
    }

    // if user name and password were given, but there was a problem report the error
    if ($this->error!='') {
      $ret = $this->error;
    }

    if (isset($request)) {
      foreach ($request as $key => $value) {
        if ($key != "ID") {
          if ($key!="username" && $key!="password" ) {
            $hiddenInputText .= "<input type='hidden' name=" . $key . " value=" . $value . ">";
          }
        }
      }
    }

    $ret .= "
      <form id='login' action=" . $_SERVER['PHP_SELF'] . " method='POST' name='login'>
        " . $hiddenInputText . "
        <div class='spacer'></div>
        <span class='left'>
          <small><small>Login&nbsp;Name:&nbsp;&nbsp;</small></small>
        </span>
        <span class='right'>
          <input type='text' name='username' value='' maxlength=20 tabindex=1>
        </span>
        <span class='left'>
          <small><small>Password:&nbsp;&nbsp;</small></small>
        </span>
        <span class='right'>
          <input type='password' name='password' maxlength=20 tabindex=2>
        </span>
        <span class='left'>
        </span>
        <span class='right'>
          <input type='submit' name='btnSubmit' value='Submit' tabindex=3></small></small></p>
        </span>
        <div class='spacer'></div>
      </form>
      <div id='login_text'>
        <div class='spacer'></div>
        <span class='left'>
          &nbsp
        </span>
        <span class='right'>
          <small>
            Use your <b>Voicemail Mailbox and Password</b><br>
            This is the same password used for the phone<br>
            <br>
            For password maintenance or assistance, contact your Phone System Administrator.
          </small>
        </span>
        <div class='spacer'></div>
      </div>
      <br>   
      <br>   
      <br>";

    $ret .= "
      <script type='text/javascript'> 
      <!-- 
        if (document.login) { 
          document.login.username.focus(); 
        } 
      // --> 
      </script>";

    return $ret;

  } 

} 


?>
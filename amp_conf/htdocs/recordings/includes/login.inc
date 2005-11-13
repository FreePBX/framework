<?php

/**
 * @file
 * login functions
 */

/**
  * Class for login
  */
class Login {

  var $error;

  /**
    * Authenticate user and register user information into a session
    */
  function Auth() {

    global $ari_admin_username;
    global $ari_admin_password;
    global $ari_cookie_password;
    global $asterisk_voicemail_conf;
    global $asterisk_voicemail_context;
    global $asterisk_voicemail_path;
    global $asterisk_protocols;
    global $callmonitor_admin_extensions;
    global $ari_no_login;

    // get the ari authentication cookie 
    $buf = unserialize($_COOKIE['ari_auth']);
    list($data,$chksum) = $buf;
    if (md5($data) == $chksum) {
      $data = unserialize($this->decrypt($data,$ari_cookie_password));
      $username = $data['username'];
      $password = $data['password'];
    }

    if (isset($_POST['username']) && 
          isset($_POST['password'])) {
      $username = $_POST['username'];
      $password = $_POST['password'];
    }

    // when login, make a new session
    if ($username && !$ari_no_login) {

      $auth = false;

      // check admin
      if (!$auth) {
        if ($username==$ari_admin_username && 
              $password==$ari_admin_password) {

          // authenticated
          $auth = true; 

          $extension = 'admin';
          $name = 'Administrator';
          $admin = 1;
          $admin_callmonitor = 1;

          $default_page = 'callmonitor';
        }
      }

      // check voicemail login
      if (!$auth) {

        if (is_readable($asterisk_voicemail_conf)) {

          $lines = file($asterisk_voicemail_conf);
          foreach ($lines as $key => $line) {

            // check for current context and process
            if (preg_match("/\[.*\]/i",$line)) {
              $currentContext = trim(preg_replace('/\[|\]/', '', $line));
            }
            if ($asterisk_voicemail_context &&
                  $currentContext!=$asterisk_voicemail_context) {
              continue;
            }

            // check for user and process
            unset($value);
            list($var,$value) = split('=>',$line);
            $var = trim($var);
            if ($var==$username && $value) {
              $buf = split(',',$value);
              if ($buf[0]==$password) {  

                // authenticated
                $auth = true; 
                $extension = $username ;
                $displayname = $buf[1];
                $vm_password = $buf[0];
                $default_page = 'voicemail';
                $context = $currentContext;

                $admin = 0;
                if ($ari_admin_extensions) {
                  $extensions = split(',',$ari_admin_extensions);
                  foreach ($extensions as $key => $value) {
                    if ($extension==$value) {
                      $admin = 1;
                      break 2;
                    }
                  }
                }
  
                $admin_callmonitor = 0;
                if ($callmonitor_admin_extensions) {
                  $extensions = split(',',$callmonitor_admin_extensions);
                  foreach ($extensions as $key => $value) {
                    if ($value=='all' || $extension==$value) {
                      $admin_callmonitor = 1;
                      break 2;
                    }
                  }
                }
              }
              else {
                $_SESSION['ari_error'] = "Incorrect Password";
                return;
              }
            }
          }
        }
        else {
          $_SESSION['ari_error'] = "File not readable: " . $asterisk_voicemail_conf;
          return;
        }
      }

      // check sip login
      if (!$auth) {

        foreach($asterisk_protocols as $protocol => $data) {

          if (is_readable($data['config_file'])) {

            $lines = file($data['config_file']);
            foreach ($lines as $key => $line) {

              unset($value);
              list($var,$value) = split('=',$line);
              $var = trim($var);
              $value = trim($value);
              if ($var=="username") {
                $protocol_username = $value;
              }
              if ($var=="secret") {

                $protocol_password = $value;
                if ($protocol_username==$username &&
                      $protocol_password==$password) {  

                  // authenticated
                  $auth = true;  
                  $extension = $username ;
                  $displayname = $username;
                  $default_page = 'callmonitor';

                  $_SESSION['ari_error'] = _("Voicemail Login not found") . ", " . $protocol . " " . _("Login used") . "<br>" .
                                           _("No access to voicemail");

                  $admin = 0;
                  if ($ari_admin_extensions) {
                    $extensions = split(',',$ari_admin_extensions);
                    foreach ($extensions as $key => $value) {
                      if ($extension==$value) {
                        $admin = 1;
                        break 2;
                      }
                    }
                  }

                  $admin_callmonitor = 0;
                  if ($callmonitor_admin_extensions) {
                    $extensions = split(',',$callmonitor_admin_extensions);
                    foreach ($extensions as $key => $value) {
                      if ($value=='all' || $extension==$value) {
                        $admin_callmonitor = 1;
                        break 2;
                      }
                    }
                  }
                }
                else if ($protocol_username==$username &&
                           $protocol_password!=$password) {
                  $_SESSION['ari_error'] = _("Incorrect Password");
                  return;
                }
              }
            }
          }
        }
      }

      // let user know bad login
      if (!$auth) {
        $_SESSION['ari_error'] = _("Incorrect Username or Password");
      }

      // if authenticated and user wants to be remembered, set cookie 
      if ($auth && $_POST['remember']) {

        $data = array('username' => $username, 'password' => $password);
        $data = $this->encrypt(serialize($data),$ari_cookie_password);

        $chksum = md5($data);

        $buf = serialize(array($data,$chksum));
        setcookie('ari_auth',$buf,time()+365*24*60*60,'/');
      }

      // set category
      if (!$category) {
        $category = "general";
      }
   
      // set context
      if (!$context) {
        $context = "default";
      }

      // no login user
      if ($ari_no_login) {
        $extension = 'admin';
        $name = 'Administrator';
        $admin_callmonitor = 1;
        $default_page = 'callmonitor';
      } 

      if ($extension) {
        $_SESSION['ari_user']['extension'] = $extension;
        $_SESSION['ari_user']['name'] = $displayname;
        $_SESSION['ari_user']['voicemail_password'] = $vm_password;
        $_SESSION['ari_user']['category'] = $category;
        $_SESSION['ari_user']['context'] = $context;
        $_SESSION['ari_user']['admin'] = $admin;
        $_SESSION['ari_user']['admin_callmonitor'] = $admin_callmonitor;
        $_SESSION['ari_user']['default_page'] = $default_page;
      } 
    }
  } 

  /**
    * logout
    */
  function Unauth() {
    unset($_COOKIE["ari_auth"]);
    setcookie('ari_auth',"",time(),'/');
    unset($_SESSION['ari_user']);
  }

  /**
   * Provide a login form for user
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
          if ($key!="username" && $key!="password") {
            $hiddenInputText .= "<input type='hidden' name=" . $key . " value=" . $value . ">";
          }
        }
      }
    }

    $display = new Display(NULL);

    // new header
    $ret .= $display->DisplayHeaderText(_("Login"));
    $ret .= $display->DisplayLine();
    $ret .= CheckErrorMessage();

    $ret .= "
      <form action=" . $_SERVER['PHP_SELF'] . " method='POST' name='login'>
      " . $hiddenInputText . "
        <table id='login'>
          <tr>
            <td class='right'>
              <small><small>" . _("Login") . ":&nbsp;&nbsp;</small></small>
            </td>
            <td>
              <input type='text' name='username' value='' maxlength=20 tabindex=1>
            </td>
          </tr>
          <tr>
            <td class='right'>
              <small><small>" . _("Password") . ":&nbsp;&nbsp;</small></small>
            </td>
            <td colspan=1>
              <input type='password' name='password' maxlength=20 tabindex=2>
            </td>
          </tr>						
            <td></td>	
            <td>
              <input type='submit' name='btnSubmit' value='" . _("Submit") . "' tabindex=3></small></small></p>
            </td>
          </tr>
          <tr>
            <td class='right'>
              <input type='checkbox' name='remember'>
            </td>
            <td class='left'>
              <p class='small'>" . _("Remember Password") . "</p>
            </td>
          </tr>
          <tr><td>&nbsp;</td></tr>
        <table>
        <table id='login_text'>
          <tr>
            <td>" .
              _("Use your") . " " ."<b>" . _("Voicemail Mailbox and Password") . "</b><br>" .
              _("This is the same password used for the phone") . "<br>" .
              "<br>" . 
              _("For password maintenance or assistance, contact your Phone System Administrator.") . "
            </td>
          </tr>
        </table>
      </form>";

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

  /**
   * Gets a random value for encryption
   * - From php.net docs
   *
   * @param $iv_len
   *   length of random variable
   */
  function get_rnd_iv($iv_len) {

    $iv = '';
    while ($iv_len-- > 0) {
      $iv .= chr(mt_rand() & 0xff);
    }
    return $iv;
  }

  /**
   * Encrypts string
   * - From php.net docs
   *
   * @param $str
   *   string to encrypt
   * @param $salt
   *   password to use for encryption
   * @param $iv_len
   *   length of random number
   */
  function encrypt($str, $salt, $iv_len = 16) {

    $str .= "\x13";
    $n = strlen($str);
    if ($n % 16) $str .= str_repeat("\0", 16 - ($n % 16));
    $i = 0;
    $enc_text = $this->get_rnd_iv($iv_len);
    $iv = substr($salt ^ $enc_text, 0, 512);
    while ($i < $n) {
      $block = substr($str, $i, 16) ^ pack('H*', md5($iv));
      $enc_text .= $block;
      $iv = substr($block . $iv, 0, 512) ^ $salt;
      $i += 16;
    }
    return base64_encode($enc_text);
  }

  /**
   * Decrypts string
   * - From php.net docs
   *
   * @param $enc
   *   encrypted string to decrypt
   * @param $salt
   *   password to use for encryption
   * @param $iv_len
   *   length of random number
   */
  function decrypt($enc, $salt, $iv_len = 16) {

     $enc = base64_decode($enc);
     $n = strlen($enc);
     $i = $iv_len;
     $str = '';
     $iv = substr($salt ^ substr($enc, 0, $iv_len), 0, 512);
     while ($i < $n) {
         $block = substr($enc, $i, 16);
         $str .= $block ^ pack('H*', md5($iv));
         $iv = substr($block . $iv, 0, 512) ^ $salt;
         $i += 16;
     }
     return preg_replace('/\\x13\\x00*$/', '', $str);
  }

}


?>
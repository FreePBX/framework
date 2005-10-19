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

    global $ari_cookie_password;
    global $asterisk_voicemail_conf;
    global $asterisk_sip_conf;
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
    if ($username && $password && !$ari_no_login) {

      $auth = false;

      // check voicemail login
      if (!$auth) {
        $lines = file($asterisk_voicemail_conf);
        foreach ($lines as $key => $line) {

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

      // check sip login
      if (!$auth) {
        $lines = file($asterisk_sip_conf);
        foreach ($lines as $key => $line) {

          unset($value);
          list($var,$value) = split('=',$line);
          $var = trim($var);
          $value = trim($value);
          if ($var=="username") {
            $sip_username = $value;
          }
          if ($var=="secret") {

            $sip_password = $value;
            if ($sip_username==$username &&
                  $sip_password==$password) {  

              // authenticated
              $auth = true;  
              $extension = $username ;
              $displayname = $username;

              $default_page = 'callmonitor';

              $_SESSION['ari_error'] = _("Voicemail Login not found, SIP Login used") . "<br>" .
                                       _("No access to voicemail");

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
            else if ($sip_username==$username &&
                       $sip_password!=$password) {
              $_SESSION['ari_error'] = _("Incorrect Password");
              return;
            }
          }
        }

        // if authenticated and user wants to be remembered, set cookie 
        if ($auth &&$_POST['remember']) {

          $data = array('username' => $username, 'password' => $password);
          $data = $this->encrypt(serialize($data),$ari_cookie_password);

          $chksum = md5($data);

          $buf = serialize(array($data,$chksum));
          setcookie('ari_auth',$buf,time()+365*24*60*60,'/');
        }
      }

      if (!$category) {
        $category = "general";
      }
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

    $ret .= "
      <form id='login' action=" . $_SERVER['PHP_SELF'] . " method='POST' name='login'>
      " . $hiddenInputText . "
        <table width=600 border=0>
          <tr>
            <td align=right width=20% nowrap>
              <small><small>Login:&nbsp;&nbsp;</small></small>
            </td>
            <td colspan=1 width=70%>
              <input type='text' name='username' value='' maxlength=20 tabindex=1>
            </td>
          </tr>
          <tr>
            <td align=right width=20% nowrap>
              <small><small>Password:&nbsp;&nbsp;</small></small>
            </td>
            <td colspan=1>
              <input type='password' name='password' maxlength=20 tabindex=2>
            </td>
          </tr>						
          <td></td>	
          <td>
            <input type='submit' name='btnSubmit' value='Submit' tabindex=3></small></small></p>
            </td>
          </tr>
          <tr><td>&nbsp;</td></tr>
          <tr>
            <td></td>
            <td> 
              <small>" .
                _("Use your") . " " ."<b>" . _("Voicemail Mailbox and Password") . "</b><br>" .
                _("This is the same password used for the phone") . "<br>" .
                "<br>" . 
                _("For password maintenance or assistance, contact your Phone System Administrator.") . "
              </small>
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
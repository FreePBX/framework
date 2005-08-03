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

    // when login, make a new session
    if ( isset($_POST['username']) && isset($_POST['password']) ) {

      $username = $_POST['username'];
      $password = $_POST['password'];
/*
// disabled

      $user = new ampuser($username);

      $success = $ldap->Connect("$username","$password",&$user);
      if ($success) {

        // create the session array 
        $_SESSION['AMP_user'] = $user; 
      }

      $this->error = $ldap->error;  // in case there was an error save it to a local variable
*/
    }
  } 

  /**
   * Purpose: Provide a login form for user
   *
   * @param $request
   *   Variable to hold data entered into form
   */
  function Form($request) {

    // if user name and password were given, but there was a problem report the error
    if ($this->error!='') {
      $ret = $this->error;
    }

    $ret .= "
      <form id='login' action=" . $_SERVER['PHP_SELF'] . " method='POST' name='login'>
        <div class='spacer'></div>
        <div>
          <span class='left'>
              <small><small>Login&nbsp;Name:&nbsp;&nbsp;</small></small>
          </span>
          <span class='right'>
              <input type='text' name='username' value='' maxlength=20 tabindex=1>
          </span>
        </div>
        <div> 
          <span class='left'>
            <small><small>Password:&nbsp;&nbsp;</small></small>
          </span>
          <span class='right'>
              <input type='password' name='password' maxlength=20 tabindex=2>
          </span>
        </div>	
        <div'>
          <span class='left'>
          </span>
          <span class='right'>
            <input type='submit' name='btnSubmit' value='Submit' tabindex=3></small></small></p>
          </span>
        </div>
        <div class='spacer'></div>
      </form>";

    if ( isset($request) ) {
      foreach ($request as $key => $value) {
        if ($key != "ID") {
          if ($key!="username" && $key!="password" ) {

            $ret .= "<input type='hidden' name=" . $key . " value=" . $value . ">";
          }
        }
      }
    }

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

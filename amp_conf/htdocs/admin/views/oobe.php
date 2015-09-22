<?php
$c = FreePBX::Config();

$welcome = _('Welcome to ') . $c->get("BRAND_TITLE") . '!';
$is = _("Initial setup");
$cc = _('Please provide the core credentials that will be used to administer your system');
$er = _('Please correct the following errors:');
$un = array(_('Username'), _('Admin user name'));
$pw = array(_('Password'), _('Admin password'), _('Confirm Password'));
$em = array(_('Admin Email address'), _('Email Address'));

if (!isset($username)) {
	$username = "";
}

if (!isset($email)) {
	$email = "";
}

?>

<form method='post' id='loginform'>
<input type='hidden' name='action' value='setup_admin'>

<div class='container-fluid'>
  <div class='panel panel-default'>
    <div class='panel-heading'><?php echo $welcome; ?></div>
    <div class='panel-body'>
      <div class='row'>
        <div class='col-sm-12'>
          <h4><center><?php echo $is; ?></center></h4>
          <p><?php echo $cc; ?></p>
        </div>
      </div>
<?php if (isset($errors)) { ?>
      <div class='row'>
        <div class='col-sm-12'>
          <span class="obe_error"><?php echo $er.ul($errors); ?></span>
        </div>
      </div>
<?php } ?>
      <div class='row'>
        <div class='form-group'>
          <div class='col-sm-3'>
            <label for="username" style='margin-top: 1em'><?php echo $un[0];?></label>
          </div>
          <div class='col-sm-8'>
            <input type='text' class='form-control' id='username' name='username' value='<?php echo $username; ?>' placeholder='<?php echo $un[1]; ?>' autofocus>
          </div>
        </div>
      </div>
      <div class='row'>
        <div class='form-group'>
          <div class='col-sm-3'>
            <label for="password1" style='margin-top: 1em'><?php echo $pw[0];?></label>
          </div>
          <div class='col-sm-8'>
            <input type='password' class='form-control password-meter' id='password1' name='password1' placeholder='<?php echo $pw[1]; ?>'>
          </div>
        </div>
      </div>
      <div class='row'>
        <div class='form-group'>
          <div class='col-sm-3'>
            <label for="password2" style='margin-top: 1em'><?php echo $pw[2];?></label>
          </div>
          <div class='col-sm-8'>
            <input type='password' class='form-control' id='password2' name='password2' placeholder='<?php echo $pw[1]; ?>'>
          </div>
        </div>
      </div>
      <div class='row'>
        <div class='form-group'>
          <div class='col-sm-3'>
            <label for="email" style='margin-top: 1em'><?php echo $em[0];?></label>
          </div>
          <div class='col-sm-8'>
            <input type='text' class='form-control' id='email' name='email' placeholder='<?php echo $em[1]; ?>' value='<?php echo $email; ?>'>
          </div>
        </div>
      </div>
      <div class='row'>
        <div class='col-sm-3 col-sm-offset-8'><span class='pull-right'><button class='btn btn-default' type='submit' id='createacct'>Create Account</button></div>
      </div>
    </div>
  </div>
</div>

</form>

<script type='text/javascript'>
$(document).ready(function(){
	var emailregexp = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
	var alertcol = "rgb(255, 192, 192)";
	var origcol = $("#email").css("background-color");
	$("#createacct").click(function(e) {
		var canProceed = true;

		// Check the admin user name. No spaces.
		var un = $("#username").val().replace(/\s/g, "");
		$("#username").val(un);
		if (!un.length) {
			$("#username").css("background-color", alertcol);
			canProceed = false;
		} else {
			$("#username").css("background-color", origcol);
		}

		// Validate the email address
		if (!emailregexp.test($("#email").val())) {
			$("#email").css("background-color", alertcol);
			canProceed = false;
		} else {
			$("#email").css("background-color", origcol);
		}

		// Check the passwords.
		if ($("#password1").val().length < 4) {
			$("#password1").css("background-color", alertcol);
			$("#password2").css("background-color", origcol);
			canProceed = false;
		} else if ($("#password1").val() != $("#password2").val()) {
			$("#password1").css("background-color", origcol);
			$("#password2").css("background-color", alertcol);
			canProceed = false;
		} else {
			$("#password1").css("background-color", origcol);
			$("#password2").css("background-color", origcol);
		}

		if (!canProceed) {
			e.preventDefault();
			return false;
		} else {
			return true;
		}
	 });
});
</script>


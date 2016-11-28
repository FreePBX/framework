<?php
// vim: :set filetype=php tabstop=4 shiftwidth=4 autoindent smartindent:

$ena = _("Enabled");
$dis = _("Disabled");
$emo = _("Email Only");

?>

<div role="tabpanel" class="tab-pane" id="scheduletab" style='padding-top: 1em'>
  <div class='container-fluid'>

    <div class='row'>
      <div class='col-sm-4 col-md-3 pd'><?php echo _("Email Address:"); ?></div>
      <div class='col-sm-8 col-md-9'><input name='notification_emails' id='notemail' class='form-control' placeholder='<?php echo _("Enter a valid email address"); ?>' value='<?php echo $notification_emails; ?>'></div>
    </div>

    <div class='row'>
      <div class='col-sm-4 col-md-3 pd'><?php echo _("System Identifier:"); ?></div>
	  <div class='col-sm-8 col-md-9'><input name='system_ident' id='sysident' class='form-control' placeholder='<?php echo _("Enter an identifier for this machine"); ?>' value='<?php echo $system_ident; ?>'></div>
    </div>

    <div class='container-fluid' style='padding-top: .75em'>
<?php print show_help(sprintf(_("System updates are Operating-system level updates, such as the Linux Kernel, Asterisk, and the Apache Web Server. Module updates are parts of %s, such as IVR, Ring Groups, or Queues. Security updates will <strong>always</strong> be automatically installed. It is recommended that both are left on.<br />Note that updates <strong>do not cause an outage</strong>. Some may require a reboot to be applied, and you will be told this in the summary email."), \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND')), _('Updates'), false, false); ?>
    </div>
<?php
// Auto system updates radio buttons
$asue = ($auto_system_updates=="enabled")?"checked":"";
$asud = ($auto_system_updates=="disabled")?"checked":"";
$asuemail = ($auto_system_updates=="emailonly")?"checked":"";

// Audo module updates radio buttons
$amue = ($auto_module_updates=="enabled")?"checked":"";
$amud = ($auto_module_updates=="disabled")?"checked":"";
$amuemail = ($auto_module_updates=="emailonly")?"checked":"";
?>
    <div class='row'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Automatic System Updates"); ?></div>
	  <div class='col-xs-7 col-sm-8 col-md-9'>
	    <span class='radioset'>
          <input type='radio' name='auto_system_updates' id='asyse' <?php echo $asue; ?> value='enabled'>
          <label for='asyse'><?php echo $ena; ?></label>
          <input type='radio' name='auto_system_updates' id='asysemail' <?php echo $asuemail; ?> value='emailonly'>
          <label for='asysemail'><?php echo $emo; ?></label>
          <input type='radio' name='auto_system_updates' id='asysd' <?php echo $asud; ?> value='disabled'>
          <label for='asysd'><?php echo $dis; ?></label>
	    </span>
      </div>
    </div>

	<div class='row' style='padding-top: .25em'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Automatic Module Updates"); ?></div>
	  <div class='col-xs-7 col-sm-8 col-md-9'>
	    <span class='radioset'>
          <input type='radio' name='auto_module_updates' id='amode' <?php echo $amue; ?> value='enabled'>
          <label for='amode'><?php echo $ena; ?></label>
          <input type='radio' name='auto_module_updates' id='amodemail' <?php echo $amuemail; ?> value='emailonly'>
          <label for='amodemail'><?php echo $emo; ?></label>
          <input type='radio' name='auto_module_updates' id='amodd' <?php echo $amud; ?> value='disabled'>
          <label for='amodd'><?php echo $dis; ?></label>
	    </span>
      </div>
    </div>

	<div class='row' style='padding-top: .25em'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Check for Updates every"); ?></div>
	  <div class='col-xs-7 col-sm-3'>
        <select class='form-control' id='update_every' name='update_every'>
<?php
$days = [
	"day" => _("Day"),
	"monday" => _("Monday"),
	"tuesday" => _("Tuesday"),
	"wednesday" => _("Wednesday"),
	"thursday" => _("Thursday"),
	"friday" => _("Friday"),
	"saturday" => _("Saturday"),
	"sunday" => _("Sunday"),
];
foreach ($days as $v => $desc) {
	if ($v === $update_every) {
		echo "<option value='$v' selected>$desc</option>";
	} else {
		echo "<option value='$v'>$desc</option>";
	}
}

?>
		</select>
      </div>
	  <div class='col-sm-5 col-md-6' id='dailydiv'>
        <select class='form-control' id='update_period' name='update_period'>
<?php
$times = [
	"0to4" => _("Between Midnight and 4am"),
	"4to8" => _("Between 4am and 8am"),
	"8to12" => _("Between 8am and 12pm"),
	"12to16" => _("Between 12pm and 4pm"),
	"16to20" => _("Between 4pm and 8pm"),
	"20to0" => _("Between 8pm and Midnight"),
];

foreach ($times as $v => $desc) {
	if ($v === $update_period) {
		echo "<option value='$v' selected>$desc</option>";
	} else {
		echo "<option value='$v'>$desc</option>";
	}
}
?>
		</select>
      </div>
    </div>
	<div class='row' style='padding-top: .5em'>
	  <div class='col-xs-1 col-xs-offset-11'>
		<button class='btn btn-default pull-right' id='saveschedule'><?php echo _("Save"); ?></button>
	  </div>
	</div>
  </div>
</div>

<?php
// vim: :set filetype=php tabstop=4 shiftwidth=4 autoindent smartindent:

$ena = _("Enabled");
$dis = _("Disabled");
$emo = _("Email Only");

$notification_emails = "";
$sysid = "";

?>

<div role="tabpanel" class="tab-pane" id="scheduletab" style='padding-top: 1em'>
  <div class='container-fluid'>

    <div class='row'>
      <div class='col-sm-4 col-md-3 pd'><?php echo _("Email Address:"); ?></div>
      <div class='col-sm-8 col-md-9'><input name='notification_emails' id='notemail' class='form-control' placeholder='<?php echo _("Enter a valid email address"); ?>' value='<?php echo $sysid; ?>'></div>
    </div>

    <div class='row'>
      <div class='col-sm-4 col-md-3 pd'><?php echo _("System Identifier:"); ?></div>
	  <div class='col-sm-8 col-md-9'><input name='system_ident' id='sysident' class='form-control' placeholder='<?php echo _("Enter an identifier for this machine"); ?>' value='<?php echo $notification_emails; ?>'></div>
    </div>

    <div class='container-fluid' style='padding-top: .75em'>
<?php print show_help(sprintf(_("System updates are Operating-system level updates, such as the Linux Kernel, Asterisk, and the Apache Web Server. Module updates are parts of %s, such as IVR, Ring Groups, or Queues. Security updates will <strong>always</strong> be automatically installed. It is recommended that both are left on.<br />Note that updates <strong>do not cause an outage</strong>. Some may require a reboot to be applied, and you will be told this in the summary email."), \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND')), _('Updates'), false, false); ?>
    </div>
<?php
$asue = "checked";
$asud = "";
$asuemail = "";
$amue = "checked";
$amud = "";
$amuemail = "";
?>
    <div class='row'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Automatic System Updates"); ?></div>
	  <div class='col-xs-7 col-sm-8 col-md-9'>
	    <span class='radioset'>
          <input type='radio' name='auto_system_updates' id='asyse' <?php echo $asue; ?> value='true'>
          <label for='asyse'><?php echo $ena; ?></label>
          <input type='radio' name='auto_system_updates' id='asysd' <?php echo $asud; ?> value='false'>
          <label for='asysd'><?php echo $dis; ?></label>
          <input type='radio' name='auto_system_updates' id='asysemail' <?php echo $asuemail; ?> value='emailonly'>
          <label for='asysemail'><?php echo $emo; ?></label>
	    </span>
      </div>
    </div>

    <div class='row'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Automatic Module Updates"); ?></div>
	  <div class='col-xs-7 col-sm-8 col-md-9'>
	    <span class='radioset'>
          <input type='radio' name='auto_module_updates' id='amode' <?php echo $amue; ?> value='true'>
          <label for='amode'><?php echo $ena; ?></label>
          <input type='radio' name='auto_module_updates' id='amodd' <?php echo $amud; ?> value='false'>
          <label for='amodd'><?php echo $dis; ?></label>
          <input type='radio' name='auto_module_updates' id='amodemail' <?php echo $asuemail; ?> value='emailonly'>
          <label for='amodemail'><?php echo $emo; ?></label>
	    </span>
      </div>
    </div>

    <div class='row'>
      <div class='col-xs-5 col-sm-4 col-md-3 pd'><?php echo _("Check for Updates every"); ?></div>
	  <div class='col-xs-7 col-sm-3'>
        <select class='form-control' id='updateevery' name='updateevery'>
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
	echo "<option value='$v'>$desc</option>";
}
?>
		</select>
      </div>
	  <div class='col-sm-5 col-md-6' id='dailydiv'>
        <select class='form-control' id='updatedailytime' name='updatedailytime'>
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
	echo "<option value='$v'>$desc</option>";
}
?>
		</select>
      </div>
    </div>

  </div>
</div>

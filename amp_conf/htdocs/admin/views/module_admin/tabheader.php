<?php
if ($activetab == "modules") {
	$m = 'class="active"';
	$s = '';
} else {
	$s = 'class="active"';
	$m = '';
}
?>

<div class='fpbx-container container-fluid mt-2'>
  <?php
      echo $httpdRestart;
  ?>
  <ul class='nav nav-tabs' role='tablist'>
    <li class="nav-item" role="presentation"><a href="#summarytab" <?php echo $s; ?> aria-controls="summarytab" role="tab" data-toggle="tab"><?php echo _("Summary")?></a></li>
    <li class="nav-item" role="presentation"><a href="#scheduletab" aria-controls="scheduletab" role="tab" data-toggle="tab"><?php echo _("Scheduler and Alerts")?></a></li>
    <li class="nav-item" role="presentation"><a href="#modulestab" <?php echo $m; ?> aria-controls="modulestab" role="tab" data-toggle="tab"><?php echo _("Module Updates")?></a></li>
    <li class="nav-item" role="presentation"><a href="#systemupdatestab" aria-controls="systemupdatestab" role="tab" data-toggle="tab"><?php echo _("System Updates")?></a></li>
  </ul>
  <div class='tab-content display'>



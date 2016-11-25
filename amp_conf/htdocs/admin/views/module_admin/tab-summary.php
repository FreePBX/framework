<?php
// vim: :set filetype=php tabstop=4 shiftwidth=4 autoindent smartindent:
?>

<div role="tabpanel" class="tab-pane active" id="summarytab" style='padding-top: 1em'>
<?php
if($edgemode) {
	print "<div class='container-fluid'>";
	print show_help(sprintf(_("This system has edge mode enabled. This means you will get modules as they are released and may encounter bugs not seen in general availibility modules.</br> For more information visit %s"),'<a href="http://wiki.freepbx.org/x/boi3Aw">http://wiki.freepbx.org/x/boi3Aw</a>'), _('EDGE MODE'), false, 'info');
  	print "</div>";
}
?>
  <div class='container-fluid'>
    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Current PBX Version:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo get_framework_version(); ?></div>
    </div>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Current System Version:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $pbxversion; ?></div>
    </div>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Total Module Count:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $totalmodules; ?></div>
    </div>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Active Module Count:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $activemodules; ?></div>
    </div>

	<p style='padding-top: .5em'><?php echo _("The numbers below may be inaccurate, as they are taken from Cached data:"); ?></p>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Last online check:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $lastonlinecheck->format("c"); ?></div>
    </div>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("Modules with Upgrades:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $pendingupgradesmodules; ?></div>
    </div>

    <div class='row'>
      <div class='col-xs-7 col-sm-4'><?php echo _("System Upgrades Available:"); ?></div>
      <div class='col-xs-5 col-sm-8'><?php echo $pendingupgradessystem; ?></div>
    </div>

  </div>
</div>

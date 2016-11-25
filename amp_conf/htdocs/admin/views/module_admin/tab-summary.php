<?php
// vim: :set filetype=php tabstop=4 shiftwidth=4 autoindent smartindent:
?>

<div role="tabpanel" class="tab-pane active" id="summarytab" style='padding-top: 1em'>
<?php if($edgemode) { ?>
  <div class='container-fluid'>
<?php 
print show_help(sprintf(_("This system has edge mode enabled. This means you will get modules as they are released and may encounter bugs not seen in general availibility modules.</br> For more information visit %s"),'<a href="http://wiki.freepbx.org/x/boi3Aw">http://wiki.freepbx.org/x/boi3Aw</a>'), _('EDGE MODE'), false, 'info');
}
?>
  </div>

  <div class='row'>
    <p>This is stuff in a p in a row</p>
  </div>
  <p> This is stuff in a p outside of a row </p>
  <div class='row'>
    <div class='col-sm-12'>
      <p>This is stuff in a p in a row in a col-sm-12</p>
	</div>
  </div>


<p>This is the summary page. Stuff will go here</p>
</div>

<script src="assets/js/module_admin.js"></script>
<div id="db_online" style="display: none;">
<form name="db_online_form" action="#" method="post">
<p><?php echo $update_blurb ?></p>
<table>
	<tr>
		<td><?php echo _("Email") ?></td>
		<td>
			<input id="update_email" type="email" required size="40" name="update_email" saved-value="<?php echo $ue ?>" value="<?php echo $ue ?>"/>
		</td>
	</tr>
	<tr>
		<td><?php echo _("Machine ID") ?></td>
		<td>
			<input id="machine_id" type="text" required size="40" name="machine_id" saved-value="<?php echo $machine_id ?>" value="<?php echo $machine_id ?>"/>
		</td>
	</tr>
</table>
</form>
</div>
<h2><?php echo _("Module Administration") ?></h2>
<div id="shield_link_div">
	<a href="#" id="show_auto_update" title="<?php echo _("Click to configure Update Notifications") ?>"><span id="shield_link" class="<?php echo $shield_class ?>"></span></a>
</div>

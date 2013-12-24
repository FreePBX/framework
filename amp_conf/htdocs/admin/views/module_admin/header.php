<script src="assets/js/module_admin.js"></script>
<div id="db_online" style="display: none;">
<form name="db_online_form" action="#" method="post">
<p><?php echo $update_blurb ?></p>
<table>
	<tr>
		<td><?php echo _("Update Notifications") ?></td>
		<td>
			<span class="radioset">
				<input id="online_updates-yes" type="radio" name="online_updates" value="yes" <?php echo $online_updates == "yes" ? "checked=\"yes\"" : "" ?>/>
				<label for="online_updates-yes"><?php echo _("Yes") ?></label>
				<input id="online_updates-no" type="radio" name="online_updates" value="no" <?php echo $online_updates == "no" ? "checked=\"no\"" : "" ?>/>
				<label for="online_updates-no"><?php echo _("No") ?></label>
			</span>
		</td>
	</tr>
	<tr>
		<td><?php echo _("Email") ?></td>
		<td>
			<input id="update_email" type="email" required size="40" name="update_email" saved-value="<?php echo $ue ?>" value="<?php echo $ue ?>"/>
		</td>
	</tr>
</table>
</form>
</div>
<h2><?php echo _("Module Administration") ?></h2>
<div id="shield_link_div">
	<a href="#" id="show_auto_update" title="<?php echo _("Click to configure Update Notifications") ?>"><span id="shield_link" class="<?php echo $shield_class ?>"></span></a>
</div>
<?php if(!empty($warning)) {?>
	<div class="warning">
		<p><?php echo $warning?></p>
	</div>
	<br />
<?php } ?>
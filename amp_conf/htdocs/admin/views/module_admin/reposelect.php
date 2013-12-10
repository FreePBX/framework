<form name="onlineRepo" action="config.php" method="post">
	<input type="hidden" name="display" value="modules"/>
	<input type="hidden" name="online" value="<?php echo $online ?>"/>
	<?php if(!empty($repo_list)) {?>
		<table width="600px">
			<tr>
				<td>
					<?php echo fpbx_label(_("Repositories"), $tooltip); ?>
				</td>
				<td>
					<table>
						<tr class="repo_boxes">
							<?php foreach($repo_list as $repo) {?>
								<td>
									<input id="<?php echo $repo?>_repo" type="checkbox" name="active_repos[<?php echo $repo?>]" value="1" tabindex="<?php echo ++$tabindex;?>"<?php echo !empty($active_repos[$repo])?"checked":""?>"/>
									<label for="<?php echo $repo?>_repo"><?php echo ucwords(_($repo)) ?></label>
								</td>
							<?php } ?>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	<?php } ?>
	<?php if(!$online) {?>
		<input type="submit" value="<?php echo _("Check Online") ?>" name="check_online" />
	<?php } ?>
	<?php echo $button_display ?>
</form>
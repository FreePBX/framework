<?php if(!empty($security_issues)) {?>
	<?php foreach($security_issues as $id => $issue) {?>
	<div class="module_security_description" id="security-<?php echo $id ?>" style="display: none;">
		<table>
			<tr>
				<td><?php echo _('ID') ?></td><td><?php echo $id ?></td>
			</tr>
			<tr>
				<td><?php echo _('Type') ?></td><td><?php echo $issue['type'] ?></td>
			</tr>
			<tr>
				<td><?php echo _('Severity') ?></td><td><?php echo $issue['severity'] ?></td>
			</tr>
			<tr>
				<td><?php echo _('Description') ?></td><td><?php echo $issue['description'] ?></td>
			</tr>
			<tr>
				<td><?php echo _('Date Reported') ?></td><td><?php echo $issue['reported'] ?></td>
			</tr>
			<tr>
				<td><?php echo _('Date Fixed') ?></td><td><?php echo $issue['fixed'] ?></td>
			</tr>
			<?php if(!empty($issue['related_urls']['url'])) {?>
				<?php foreach ($issue['related_urls']['url'] as $url) { ?>
					<tr>
						<td><?php echo $related_urls_text ?></td>
						<td><a href="<?php echo $url ?>" target="_security"><?php echo $url ?></a></td>
					</tr>
				<?php } ?>
			<?php } ?>
			<tr>
				<td><?php echo _('Related Tickets') ?></td>
				<td><?php echo $issue['tickets'] ?></td>
			</tr>
		</table>
	</div>
	<?php } ?>
<?php } ?>

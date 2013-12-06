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

<script type="text/javascript">
$(document).ready(function(){
	$('.repo_boxes').find('input[type=checkbox]').button();
	$('#show_auto_update').click(function() {
		autoupdate_box = $('#db_online').dialog({
			title: fpbx.msg.framework.updatenotifications,
			resizable: false,
			modal: true,
			position: ['center', 50],
			width: '400px',
			close: function (e) {
				//console.log('calling close');
				$('#update_email').val($('#update_email').attr('saved-value'));
			},
			open: function (e) {
				//console.log('calling open');
				$('#update_email').focus();
			},
			buttons: [ {
				text: fpbx.msg.framework.save,
				click: function() {
					if ($('#update_email')[0].validity.typeMismatch) {
						alert(fpbx.msg.framework.bademail + ' : ' + $('#update_email').focus().val());
						$('#update_email').focus();
					} else {
						online_updates = $('[name="online_updates"]:checked').val();
						update_email = $('#update_email').val();
						if (online_updates != 'yes') {
							if (!confirm(fpbx.msg.framework.noupdates)) {
								return false;
							}
						} else if (isEmpty(update_email)) {
							if (!confirm(fpbx.msg.framework.noupemail)) {
								return false;
							}
						}
					$.ajax({
  					type: 'POST',
  					url: "<?php echo $_SERVER["PHP_SELF"]; ?>",
  					//data: "quietmode=1&skip_astman=1&display=modules&update_email=" + $('#update_email').val() + "&online_updates=" + $('[name="online_updates"]:checked').val(),
  					data: "quietmode=1&skip_astman=1&display=modules&update_email=" + update_email + "&online_updates=" + online_updates,
  					dataType: 'json',
  					success: function(data) {
								if (data.status == true) {
									$('#update_email').attr('saved-value', $('#update_email').val());
									if ($('[name="online_updates"]:checked').val() == 'no') {
										$('#shield_link').attr('class', 'updates_off');
									} else {
										$('#shield_link').attr('class', (isEmpty($('#update_email').val()) ? 'updates_partial' : 'updates_full'));
									}
									autoupdate_box.dialog("close")
								} else {
									alert(data.status)
									$('#update_email').focus();
								}
  					},
  					error: function(data) {
								alert(fpbx.msg.framework.invalid_response);
  					}
					});
					}
				}
			}, {
				text: fpbx.msg.framework.cancel,
				click: function() {
					//console.log('pressed cancel button');
					$(this).dialog("close");
				}
			} ]
		});
	});
	$('.modulevul_tag').click(function(e) {
		e.preventDefault();
		$.each($(this).data('sec'), function(index, value) { 
			$('#security-' + value).dialog({
				title: fpbx.msg.framework.securityissue + ' ' + value,
				resizable: false,
				position: [50+20*index, 50+20*index],
				width: '450px',
				close: function (e) {
					//console.log('calling close');
				},
				open: function (e) {
					//console.log('calling open');
				},
				buttons: [ {
					text: fpbx.msg.framework.close,
					click: function() {
						//console.log('pressed cancel button');
						$(this).dialog("close");
					}
				} ]
			});
		});
	});
})
function toggleInfoPane(pane) {
	var style = document.getElementById(pane).style;
	if (style.display == 'none' || style.display == '') {
		style.display = 'block';
	} else {
		style.display = 'none';
	}
}
function check_upgrade_all() {
	var re = /^moduleaction\[([a-z0-9_\-]+)\]$/;
	for(i=0; i<document.modulesGUI.elements.length; i++) {
		if (document.modulesGUI.elements[i].value == 'upgrade') {
			if (match = document.modulesGUI.elements[i].name.match(re)) {
				// check the box
				document.modulesGUI.elements[i].checked = true;
				// expand info pane
				document.getElementById('infopane_'+match[1]).style.display = 'block';
			}
		}
	}
}
function check_download_all() {
	var re = /^moduleaction\[([a-z0-9_\-]+)\]$/;
	for(i=0; i<document.modulesGUI.elements.length; i++) {
		if (document.modulesGUI.elements[i].value == 'downloadinstall') {
			if (match = document.modulesGUI.elements[i].name.match(re)) {
				// check the box
				document.modulesGUI.elements[i].checked = true;
				// expand info pane
				document.getElementById('infopane_'+match[1]).style.display = 'block';
			}
		}
	}
}
function showhide_upgrades() {
	var upgradesonly = document.getElementById('show_upgradable_only').checked;
	var module_re = /^module_([a-z0-9_-]+)$/;   // regex to match a module element id
	var cat_re = /^category_([a-zA-Z0-9_]+)$/; // regex to match a category element id
	var elements = document.getElementById('modulelist').getElementsByTagName('li');
	// loop through all modules, check if there is an upgrade_<module> radio box 
	for(i=0; i<elements.length; i++) {
		if (match = elements[i].id.match(module_re)) {
			if (!document.getElementById('upgrade_'+match[1])) {
				// not upgradable
				document.getElementById('module_'+match[1]).style.display = upgradesonly ? 'none' : 'block';
			}
		}
	}
	// hide category headings that don't have any visible modules
	var elements = document.getElementById('modulelist').getElementsByTagName('div');
	// loop through category items
	for(i=0; i<elements.length; i++) {
		if (elements[i].id.match(cat_re)) {
			var subelements = elements[i].getElementsByTagName('li');
			var display = false;
			for(j=0; j<subelements.length; j++) {
				// loop through children <li>'s, find names that are module element id's 
				if (subelements[j].id.match(module_re) && subelements[j].style.display != 'none') {
					// if at least one is visible, we're displaying this element
					display = true;
					break; // no need to go further
				}
			}
			document.getElementById(elements[i].id).style.display = display ? 'block' : 'none';
		}
	}
}
var box;
function process_module_actions(actions) {
	urlStr = "config.php?display=modules&amp;extdisplay=process&amp;quietmode=1";
	for (var i in actions) {
		urlStr += "&amp;moduleaction["+i+"]="+actions[i];
	}
	 box = $('<div></div>')
		.html('<iframe frameBorder="0" src="'+urlStr+'"></iframe>')
		.dialog({
			title: 'Status',
			resizable: false,
			modal: true,
			position: ['center', 50],
			width: '400px',
			close: function (e) {
				close_module_actions(true);
				$(e.target).dialog("destroy").remove();
			}
		});
}
function close_module_actions(goback) {
	box.dialog("destroy").remove();
	if (goback) {
  		location.href = 'config.php?display=modules&amp;online=<?php echo $online; ?>';
	}
}
</script>
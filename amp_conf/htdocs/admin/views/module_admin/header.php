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
	$('.repo_boxes').find('input[type=checkbox]').click(function() {
		var id = $(this).attr('id');
		var selected = $(this).prop('checked') ? 1 : 0;
		
		$.ajax({
			type: 'POST',
			url: "<?php echo $_SERVER["PHP_SELF"]; ?>",
			data: {quietmode: 1, skip_astman: 1, display: "modules", action: "setrepo", "id": id,selected: selected},
			dataType: 'json',
			success: function(data) {
				$('#check_online').fadeIn('fast');
			},
			error: function(data) {
				alert(fpbx.msg.framework.invalid_response);
			}
		});
	})
	$('#show_auto_update').click(function() {
		autoupdate_box = $('#db_online').dialog({
			title: fpbx.msg.framework.updatenotifications,
			resizable: false,
			modal: true,
			position: ['center', 50],
			width: '400px',
			close: function (e) {
				$('#update_email').val($('#update_email').attr('saved-value'));
			},
			open: function (e) {
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
	if($('#'+pane).is(":visible")) {
		$('#'+pane).slideUp( "slow", function() {
		})
	} else {
		$('#'+pane).slideDown( "slow", function() {
		})
		$('#'+pane+' .modulefunctionradios').buttonset();
		$('#'+pane+' .moduletrackradios').buttonset();
	}
}
function check_upgrade_all() {
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'upgrade') {
			$(this).prop('checked',true);
			$(this).parents('.moduleinfopane').show();
			var pane = $(this).parents('.moduleinfopane').attr('id');
			$('#'+pane+' .modulefunctionradios').buttonset();
			$('#'+pane+' .moduletrackradios').buttonset();
		}
	});
}

function check_download_all() {
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'downloadinstall') {
			$(this).prop('checked',true);
			$(this).parents('.moduleinfopane').show();
			var pane = $(this).parents('.moduleinfopane').attr('id');
			$('#'+pane+' .modulefunctionradios').buttonset();
			$('#'+pane+' .moduletrackradios').buttonset();
		}
	});
}

function navigate_to_module(module) {
	$('#fullmodule_'+module).scrollMinimal(true);
	$('#infopane_'+module).slideDown( "slow", function() {})
	$('#infopane_'+module+' .modulefunctionradios').buttonset();
	$('#infopane_'+module+' .moduletrackradios').buttonset();
}

function showhide_upgrades() {
	var upgradesonly = $('#show_upgradable_only').prop('checked');

	// loop through all modules, check if there is an upgrade_<module> radio box 
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'upgrade') {

		}
	});

}
var box;
function process_module_actions(modules) {
	var urlStr = '';
	if(!jQuery.isEmptyObject(modules)) {
		urlStr = "config.php?display=modules&action=process&quietmode=1&online=1&"+$.param( {"modules":modules} );
	}
	
	box = $('<div id="moduledialogwrapper"></div>')
			.dialog({
				title: 'Status',
				resizable: false,
				modal: true,
				position: ['center', 50],
				width: '410px',
				open: function (e) {
					$('#moduledialogwrapper').html('');
				    var xhr = new XMLHttpRequest();
				    xhr.open('GET', urlStr, true);
				    xhr.send(null);
				    var timer;
				    timer = window.setInterval(function() {
				        if (xhr.readyState == XMLHttpRequest.DONE) {
				            window.clearTimeout(timer);
				        }
				        $('#moduledialogwrapper').html(xhr.responseText);
				    }, 100);
				},
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

var oldReleaseInfo = {};
function changeReleaseTrack(module,track) {
	//dont load info again if we are already showing it
	var info = {};
	if(track != 'stable') {
		info = modules[module].releasetracks[track];
		//force_upgrade_dahdiconfig
		oldReleaseInfo[module] = $('#infopane_'+module+' .modulefunctionradios label[for=force_upgrade_'+module+']').text();
		//$('#infopane_'+module+' .modulefunctionradios label[for=force_upgrade_'+module+']').text('Upgrade to '+info.version);
		$('#force_upgrade_'+module).button( "option", "label", 'Upgrade to '+info.version );
		$('#upgrade_'+module).button( "option", "label", 'Upgrade to '+info.version );
	} else {
		info = modules[module];
		if(typeof(oldReleaseInfo[module]) !== undefined) {
			//$('#infopane_'+module+' .modulefunctionradios label[for=force_upgrade_'+module+']').text(oldReleaseInfo[module]);
			$('#force_upgrade_'+module).button( "option", "label", oldReleaseInfo[module] );
			$('#upgrade_'+module).button( "option", "label", oldReleaseInfo[module] );
		}
	}
	
	$('#infopane_'+module+' .modulefunctionradios').buttonset("destroy").buttonset();
	
	var extra = (track != 'stable') ? '-<span style="color:red;">'+track.toUpperCase()+'</span>' : '';
	$('#fullmodule_'+module+' .moduleversion').html(info.version+extra);
	$('#changelog_'+module+' span').html(info.changelog);
	//I dont like this much please improve...someone
	$('#changelog_'+module+' h5').html('<?php echo _("Change Log for version")?>: '+info.version)
}
</script>
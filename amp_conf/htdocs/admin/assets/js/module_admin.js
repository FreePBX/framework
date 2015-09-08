$(document).ready(function(){
	$('.repo_boxes').find('input[type=checkbox]').click(function() {
		var id = $(this).attr('id');
		var selected = $(this).prop('checked') ? 1 : 0;

		$.ajax({
			type: 'POST',
			url: "config.php",
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
	$( "form[name='onlineRepo']" ).submit(function( event ) {
		toggleScreenDoor();
		//The following is a workaround hack for safari
		//if you submit a page and add something to it
		//safari rejects your add
		//so we stop submission of the page, wait 100 ms
		//then submit again
		event.preventDefault();
		var form = this;
		setTimeout(function() {
			form.submit();
		}, 200);
});
	$('#show_auto_update').click(function() {
		autoupdate_box = $('#db_online').dialog({
			title: fpbx.msg.framework.updatenotifications,
			resizable: false,
			modal: true,
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
						update_email = $('#update_email').val();
						machine_id = $('#machine_id').val();
						if (isEmpty(update_email)) {
							if (!confirm(fpbx.msg.framework.noupemail)) {
								return false;
							}
						}
						$.ajax({
	  						type: 'POST',
	  						url: "config.php",
							data: {quietmode: 1, skip_astman: 1, display: "modules", update_email: update_email, machine_id: machine_id },
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
					$(this).dialog("close");
				}
			} ]
		});
	});
	if(window.location.hash == '#email'){
		$('#show_auto_update').click();
	}
	$('.modulevul_tag').click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		$.each($(this).data('sec'), function(index, value) {
			$('#security-' + value).dialog({
				title: fpbx.msg.framework.securityissue + ' ' + value,
				resizable: false,
				width: '450px',
				close: function (e) {
				},
				open: function (e) {
				},
				buttons: [ {
					text: fpbx.msg.framework.close,
					click: function() {
						$(this).dialog("close");
					}
				} ]
			});
		});
	});
	$('.moduletrackradios').change(function(e) {
		var track = $(this).find('input:checked').val();
		var module = $(this).parents('.fullmodule').data('module');
		var previous_track = modules[module].track;
		var si = (track == 'stable') ? modules[module] : modules[module].releasetracks[track];
		var pi = (previous_track == 'stable') ? modules[module] : modules[module].releasetracks[previous_track];

		$('#fullmodule_'+module+' .moduletrack').html(track.capitalize());
		$('#fullmodule_'+module+' .moduletrack').removeClass(previous_track.toLowerCase());
		$('#fullmodule_'+module+' .moduletrack').addClass(track.toLowerCase());

		var label = $('#fullmodule_'+module+' .modulestatus span.text').html().replace(pi.version,si.version);
		$('#fullmodule_'+module+' .modulestatus span.text').html(label);

		$('#fullmodule_'+module+' .modulefunctionradios input[type=radio]').each(function( index ) {
			var label = '';
		});

		var llabel = $('#fullmodule_'+module+' .modulefunctionradios .installabel').text().replace(pi.version,si.version);
		$('#fullmodule_'+module+' .modulefunctionradios .installabel').text(llabel);

		$('#changelog_'+module+' span').html(si.changelog);
		//I dont like this much please improve...someone
		var cl = $('#changelog_'+module+' h5').html().replace(pi.version,si.version);
		$('#changelog_'+module+' h5').html(cl);

		modules[module].track = track;
	});
	$('#local-type').change(function(e) {
		if($(this).val() == 'download') {
			$.cookie('local-type','download');
			$('#download-group').show();
			$('#upload-group').hide();
		} else {
			$.cookie('local-type','upload');
			$('#download-group').hide();
			$('#upload-group').show();
		}
	});
	if($.cookie('local-type') == 'upload') {
		$('#local-type').val('upload');
		$('#download-group').hide();
		$('#upload-group').show();
	}
	$('.moduleheader').not('a').click(function(e) {
		if($(e.srcElement).hasClass("fpbx-buy") || $(e.srcElement).hasClass("fa-money")) {
			return true;
		}
		var module = $(this).data('module');
		if($('#infopane_'+module).is(":visible")) {
			$('#infopane_'+module).slideUp( "slow", function() {
			})
			$('#arrow_'+module).removeClass("fa-chevron-down").addClass("fa-chevron-right");
		} else {
			$('#infopane_'+module).slideDown( "slow", function() {
			})
			$('#arrow_'+module).removeClass("fa-chevron-right").addClass("fa-chevron-down");
			$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
			$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
		}
	});
	if(!fpbx.conf.AMPTRACKENABLE) {
		$('#modulelist .moduletrack').hide();
	}
})
function check_upgrade_all() {
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'upgrade') {
			$(this).prop('checked',true);
			$(this).parents('.moduleinfopane').show();
			var module = $(this).parents('.fullmodule').data('module');
			$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
			$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
		}
	});
	fpbxToast(_('All module upgrades marked. Click process to run update.'),_('Updates Selected'),'success');
}

function check_download_all() {
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'downloadinstall') {
			$(this).prop('checked',true);
			$(this).parents('.moduleinfopane').show();
			var module = $(this).parents('.fullmodule').data('module');
			$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
			$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
		}
	});
	fpbxToast(_('All modules selected to download. Press process to continue.'),_('Downloads Selected'),'success');

}

function navigate_to_module(module) {
	if($('#fullmodule_'+module).length) {
		$('#fullmodule_'+module).scrollMinimal(true, 100);
		$('#infopane_'+module).slideDown( "slow", function() {})
		$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
		$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
	} else {
		alert(sprintf(_('Required Module %s is not local'),module));
	}

}

function showhide_upgrades() {
	var upgradesonly = $('#show_upgradable_only').prop('checked');

	// loop through all modules, check if there is an upgrade_<module> radio box
	if(upgradesonly) {
		$('.fullmodule').hide();
		$('.category').hide();
		$( ".modulefunctionradios :radio" ).each(function( index ) {
			if($(this).val() == 'upgrade') {
				$(this).parents("td").show();
				$(this).parents("div .category").show();
			}
		});
	} else {
		$('.fullmodule').show();
		$('.category').show();
	}

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
				width: 410,
				height: 325,
				open: function (e) {
					$('#moduledialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
					var xhr = new XMLHttpRequest(),
					timer = null;
					xhr.open('POST', urlStr, true);
					xhr.send(null);
					timer = window.setInterval(function() {
						if (xhr.readyState == XMLHttpRequest.DONE) {
							window.clearTimeout(timer);
						}
						if (xhr.responseText.length > 0) {
							if ($('#moduledialogwrapper').html().trim() != xhr.responseText.trim()) {
								$('#moduledialogwrapper').html(xhr.responseText);
								$('#moduleprogress').scrollTop(1E10);
							}
						}
						if (xhr.readyState == XMLHttpRequest.DONE) {
							$("#moduleprogress").css("overflow", "auto");
							$('#moduleprogress').scrollTop(1E10);
							$("#moduleBoxContents a").focus();
						}
					}, 500);
				},
				close: function(e) {
					close_module_actions(true);
					$(e.target).dialog("destroy").remove();
				}
			});
}
function close_module_actions(goback) {
	box.dialog("destroy").remove();
	if (goback) {
  		location.href = 'config.php?display=modules';
	}
}

function toggleScreenDoor() {
	var h = $( document ).height();
	$('.screendoor').css('height', h);
	$('.screendoor').fadeToggle('fast');
}

String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

$(document).ready(function(){
	// Scheduler update button
	$("#saveschedule").on("click", saveUpdateScheduler);
	$("#check_online_button").click(function(e) {
		if(fpbx.conf.DEVEL === 1) {
			alert(_("Checking Online is disabled while 'Developer Mode' is enabled"))
			e.preventDefault();
			e.stopPropagation();
		}
	});
	$("#restartHttpd").click( function(){
		fpbxToast(_("Restarting Apache. Please, wait for the page to load."),'Action','info');
		$.ajax({
			url: 'config.php',
			type: 'POST',
			data: {quietmode: 1,  display: "modules", action: "restarthttpd", online: 1},
			timeout: 3000,
			error: function(){
				console.debug('reloading page');
				window.location.href = "./config.php?display=modules";
			}
		})
	});
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
	});
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
								if (data.status === true) {
									$('#update_email').attr('saved-value', $('#update_email').val());
									if ($('[name="online_updates"]:checked').val() == 'no') {
										$('#shield_link').attr('class', 'updates_off');
									} else {
										$('#shield_link').attr('class', (isEmpty($('#update_email').val()) ? 'updates_partial' : 'updates_full'));
									}
									autoupdate_box.dialog("close");
								} else {
									alert(data.status);
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
		//$('#fullmodule_'+module+' .modulequickinfo .packagedDate').text(si.packaged);

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
			Cookies.set('local-type','download');
			$('#download-group').show();
			$('#upload-group').hide();
		} else {
			Cookies.set('local-type','upload');
			$('#download-group').hide();
			$('#upload-group').show();
		}
	});
	if(Cookies.get('local-type') == 'upload') {
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
			});
			$('#arrow_'+module).removeClass("fa-chevron-down").addClass("fa-chevron-right");
		} else {
			$('#infopane_'+module).slideDown( "slow", function() {
			});
			$('#arrow_'+module).removeClass("fa-chevron-right").addClass("fa-chevron-down");
			$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
			$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
		}
	});
	if(!fpbx.conf.AMPTRACKENABLE) {
		$('#modulelist .moduletrack').hide();
	}

	// Tab 'summary' page javascript hooks
	// 'Modules with Upgrades': Display the update modal.
	$("#moduleupdatecount").on('click', show_modules_modal);

	// When the updatesmodal is shown, always set the scroll position to be top left
	$("#updatesmodal").on('shown.bs.modal', function() {
		$(".modal-body", "#updatesmodal").scrollTop(0).scrollLeft(0);
	});

	// When the updatesmodal is hidden, reload the sysupdate page, if we're ON
	// the sysupdates page.
	$("#updatesmodal").on('hide.bs.modal', function() {
		if ($("#systemupdatestab.active").length !== 1) {
			return;
		}
		reload_system_updates_tab();
	});

	$(".showsystemupdatestab").on('click', function(event) {
		event.preventDefault();
		$('#summarytab').removeClass('active');
		$('#systemupdatestab').addClass('active');
		$('a[href="#summarytab"]').removeClass('active');
		$('a[href="#systemupdatestab"]').addClass('active');
	});
})

function check_upgrade_all() {
	$( ".modulefunctionradios :radio" ).each(function( index ) {
		if($(this).val() == 'upgrade') {
			$(this).prop('checked',true);
			$(this).parents('.moduleinfopane').show();
			var module = $(this).parents('.fullmodule').data('module');
			$('#infopane_'+module+' .modulefunctionradios').addClass('radioset');
			$('#infopane_'+module+' .moduletrackradios').addClass('radioset');
			if($('#fullmodule_'+module).find(".notinstalled").length == 1){
				$('#noaction_'+module).click();
				$('#infopane_'+module).slideUp("slow", function() {});
			}
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
var httpdRestart
function process_module_actions(modules) {
	httpdRestart = false;
	if(modules.hasOwnProperty("sysadmin") || modules.hasOwnProperty("restapps") || modules.hasOwnProperty("sangomaconnect")){
		httpdRestart = true;
	}

	var urlStr = '';
	if(!jQuery.isEmptyObject(modules)) {
		urlStr = "config.php?display=modules&action=process&quietmode=1&online=1";
		content_data = $.param( {"modules":modules} )
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
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(content_data);
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
	htrestart = '';
	if (httpdRestart == true) {
	    htrestart = '&httpdRestart=check';
	}

	if (goback) {
		location.href = 'config.php?display=modules'+htrestart;
	}
}

function toggleScreenDoor() {
	var h = $( document ).height();
	h = h+'px';
	$('.screendoor').css('height', h);
	// $('.screendoor').css('display', 'block');
	$('.screendoor').css('justifiy-content', 'center');
	let starTime = performance.now();
	$('.screendoor').fadeToggle('fast',function(){
		let endTime = performance.now();
	});
}

String.prototype.capitalize = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}


function saveUpdateScheduler(e) {
	// There shouldn't be a default event, but...
	e.preventDefault();

	// Create our ajax request
	ajaxdata = {
		notification_emails: $("#notemail").val(),
		system_ident: $("#sysident").val(),
		auto_system_updates: $("input[name='auto_system_updates']:checked").val(),
		auto_module_updates: $("input[name='auto_module_updates']:checked").val(),
		auto_module_security_updates: $("input[name='auto_module_security_updates']:checked").val(),
		unsigned_module_emails: $("input[name='unsigned_module_emails']:checked").val(),
		update_every: $("#update_every").val(),
		update_period: $("#update_period").val(),
		module: "framework",
		command: "scheduler",
		action: "updatescheduler"
	};

	var s = $("#saveschedule");
	s.text(_("Saving...")).prop("disabled", true);

	$.ajax({
		url: FreePBX.ajaxurl,
		method: "POST",
		data: ajaxdata,
		success: function(data) {
			$.each(data, function(i,v) {
				switch($("input[name='auto_system_updates']").prop("type")) {
					case "radio":
						var selector = "input[name='"+i+"'][value='"+v+"']";
						$(selector).prop('checked', true);
					break;
					case "select":
					case "text":
						var selector = "input[name='"+i+"']";
						$(selector).val(v);
					break;
				}

			});
		},
		complete: function(data) { s.text("Save").prop("disabled", false); },
	});
}

/*
 * This resets the module admin modal to blank, and makes sure it's hidden
 */
function clean_modadmin_modal() {
	// Remove title and body
	$(".modal-title,.modal-body", "#updatesmodal").text("");
	// Make sure we don't think we've scrolled
	delete window.userhasscrolled;
}

function show_modules_modal(e) {
	e.preventDefault;
	clean_modadmin_modal();
	$(".modal-title", "#updatesmodal").text(_("Available Module Updates"));
	$(".modal-body", "#updatesmodal").text(_("Loading, please wait ..."));
	$("#updatesmodal").modal('show');
	// Run an ajax request to get the details of the modules that need upgrading.
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "scheduler", action: "getmoduleupdates" },
		success: function(data) {
			$(".modal-body", "#updatesmodal").html(data.result);
		},
	});
}


// This is triggered by an onclick tag generated in Builtin/SystemUpdates::getSystemUpdatesPage()
// Updates the systemupdate tab.
function reload_system_updates_tab() {
	// When we're reloading, set the 'Refresh' button to say 'Loading', so that
	// people see something happening.
	$("#refreshpagebutton").attr('disabled', true).text(_("Loading..."));

	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "getsysupdatepage" },
		success: function(data) {
			$("#systext").html(data.message);
		},
		complete: function() {
			$("#refreshpagebutton").attr('disabled', false).text(_("Refresh Page"));
			// If the systemupdate modal is being displayed, don't refresh, as that
			// modal is probably refreshing for us.
			if ($("#updatesmodal:visible").length == 1) {
				return;
			}
			// If we're not complete, AND we're visible, poll for updates in a second.
			if ($("#refreshpagebutton:visible").length == 1) {
				if ($("#pendingstatus").data('value') !== "complete" || $("#yumstatus").data('value') !== "complete") {
					window.setTimeout(reload_system_updates_tab, 1000);
				}
			}
		},
		error: function (error) {
			if (error.responseJSON && error.responseJSON.error.message != undefined) {
				alert(error.responseJSON.error.message)
			}
		},
	});
}

// This is triggered by an onclick tag generated in Builtin/SystemUpdates::getSystemUpdatesPage()
// For Debian systems, use refreshupgradablepackages instead of check-updates
function run_yum_checkonline() {
	// Use the new refreshupgradablepackages action for Debian/APT systems
	refreshUpgradablePackages();
}

// This is triggered by an onclick tag generated in Builtin/SystemUpdates::getSystemUpdatesPage()
// Loads and displays the system updates modal
function show_sysupdate_modal() {
	clean_modadmin_modal();
	$(".modal-title", "#updatesmodal").html(_("Operating System Updates")+" &nbsp; <span id='statusspan'></span>");
	$(".modal-body", "#updatesmodal").text(_("Loading, please wait ..."));
	// Try to render any updates that may already exist. 'window.currentupdate' may have been
	// generated with a <script> tag inside SystemUpdates
	render_updates_in_modal(false); // false == don't refresh, we're doing it two lines down.
	$("#updatesmodal").modal('show');
	// Now trigger an update, to try to refresh update the page
	update_sysupdate_modal();
}

function update_sysupdate_modal() {
	// Empty currentupdate
	delete window.currentupdate;
	$("#statusspan").text(_("(Updating...)"));
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "getsysupdatestatus" },
		success: function(data) { window.currentupdate = data; },
		error: function(d) {
			// Errored. Possibly because httpd is restarting?
			d.suppresserrors = true;
			$("#statusspan").text(_("(Error! Retrying...)"));
		       	window.setTimeout(update_sysupdate_modal, 1000);
	       	},
		complete: function() {
			if (typeof window.currentupdate === "undefined") {
				// It failed? Shouldn't have happened.
				return;
			}
			$("#statusspan").text("");
			render_updates_in_modal();
		}
	});
}

var lastUpdate = 0;
$(function() {
	lastUpdate = moment().unix();
});
// Dorefresh = false stops a reload from happening, as one is
// going to happen next in the code path.
function render_updates_in_modal(dorefresh) {
	if (dorefresh === undefined) {
		dorefresh = true;
	}
	// Do we have anything in currentupdate?
	if (typeof window.currentupdate === "undefined") {
		// Nope. Don't do anything
		return;
	}

	// Do we have any output?
	if (typeof window.currentupdate.currentlog === "undefined") {
		// No? How did that happen?
		return;
	}

	var output = window.currentupdate.currentlog;

	// If we don't have our wrapper div in modal-body, add it
	if ($("#modal-wrapper").length == 0) {
		$(".modal-body", "#updatesmodal").html("<div id='modal-wrapper'></div>");
	}

	// Have we got LESS lines than are in the modal wrapper? That means the old
	// update wasn't cleaned up correctly.  Nuke it.
	if ($("#modal-wrapper>.outputline").length > output.length) {
		$(".modal-body", "#updatesmodal").html("<div id='modal-wrapper'></div>");
		delete window.userhasscrolled;
	}

	// Autoscrolling manager
	var body = $(".modal-body");
	var autoscroll = false;

	// Has the user NOT scrolled at all? Then always autoscroll
	if (typeof window.userhasscrolled === "undefined" && body[0].scrollTop === 0) {
		autoscroll = true;
		// console.log("Autoscrolling because.");
	} else {
		// Autoscroll.
		window.userhasscrolled = true;
		// Are we at the bottom of the current scroll window? If we are, then autoscroll to the bottom.
		//
		// We figure this out by taking the scrollheight (How much scroll is AVAILABLE), and
		// then subtracting scrollTop from it. If this is less than the actual height of the
		// window+31 (padding) we are at the bottom of the viewport, and want to scroll.
		//
		// Debugging left in here, for anyone who can think of a better way.
		// console.log("scrollHeight", body[0].scrollHeight);
		// console.log("scrollTop", body.scrollTop());
		// console.log("sum", body[0].scrollHeight - body.scrollTop());
		// console.log("height+31", body.height()+31);
		if (body[0].scrollHeight - body.scrollTop() <= body.height()+31) {
			autoscroll = true;
			// console.log("autoscrolling");
		}
	}

	// Loop through output, and if there isn't a n'th element, append it.
	var wrapper = $("#modal-wrapper");

	for (var i = 0; i < output.length; i++) {
		// Avoiding jquery here, let's just use native
		var e = document.querySelector("#modal-wrapper>.outputline:nth-of-type("+(i+1)+")");
		if (e === null) {
			// Add this line to modal-wrapper
			wrapper.append("<tt class='outputline' style='white-space: pre'>"+output[i]+"</tt><br/>");
			lastUpdate = moment().unix();
		}
	}

	if((lastUpdate + 10) < moment().unix()) {
		var upquips = [
			_("The Upgrade Script is still alive"),
			_("Upgrades are still being run"),
			_("Progressing through upgrades"),
			_("Sometimes this takes a while, but the script is still alive"),
			_("Believe me, The script is still alive"),
			"I'm doing Science and I'm still alive",
			"I'm still alive",
			"I feel fantastic and I'm still alive",
			"Still alive",
		];
		wrapper.append("<tt class='quipline' style='white-space: pre'>"+upquips[Math.floor(Math.random() * upquips.length)]+"</tt><br/>");
		lastUpdate = moment().unix();
	}

	if (autoscroll) {
		body.scrollTop($("#modal-wrapper").height());
	}

	// Update the status
	$("#statusspan").text(window.currentupdate.i18nstatus);

	// Am I allowed to check for a refresh?
	if (!dorefresh) {
		return;
	}

	if (typeof window.currentupdate.retryafter !== "undefined") {
		// If there was, somehow, already a reload even pending, kill it.
		if (typeof window.refreshtimeout !== "undefined") {
			clearTimeout(window.refreshtimeout);
		}
		window.refreshtimeout = setTimeout(update_sysupdate_modal, window.currentupdate.retryafter);
	}
}

// This is triggered by an onclick tag generated in Builtin/SystemUpdates::getSystemUpdatesPage()
// Request that system update is run (legacy function, not used for Debian)
function update_rpms() {
	$("#updatesystembutton").text(_("Starting...")).prop("disabled", true);
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "startyumupdate" },
		success: function(data) {
			// If it worked, it's started. Clean and show our modal!
			clean_modadmin_modal();
			// This will auto-refresh until it's complete.
			show_sysupdate_modal();
		},
		complete: function() {
			$("#updatesystembutton").text(_("Update System")).prop("disabled", false);
		},
	});
}

// Preview Debian Bookworm configuration changes
function preview_debian_bookworm_config() {
	// Toggle functionality - if already visible, hide it
	if ($("#bookworm-preview").is(":visible")) {
		$("#bookworm-preview").hide();
		$("#previewbookwormbutton").text("Preview Changes");
		return;
	}
	
	$("#previewbookwormbutton").text("Loading...").prop("disabled", true);
	$("#bookworm-preview").show();
	$("#bookworm-preview").html("<div class='alert alert-info'>Loading preview...</div>");
	
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "getdebianbookwormpreview" },
		success: function(data) {
			var html = "<h5>Files to be modified:</h5>";
			
			if (data.files_to_change && data.files_to_change.length > 0) {
				html += "<ul>";
				data.files_to_change.forEach(function(file) {
					html += "<li><code>" + file + "</code></li>";
				});
				html += "</ul>";
			}
			
			if (data.new_files && data.new_files.length > 0) {
				html += "<h5>New files to be created:</h5>";
				html += "<ul>";
				data.new_files.forEach(function(file) {
					html += "<li><code>" + file + "</code></li>";
				});
				html += "</ul>";
			}
			
			if (data.changes && data.changes.length > 0) {
				html += "<h5>Detailed changes:</h5>";
				data.changes.forEach(function(change) {
					html += "<div class='panel panel-default' style='margin-top: 10px;'>";
					html += "<div class='panel-heading'><strong>" + change.file + "</strong> - " + change.description + "</div>";
					html += "<div class='panel-body'>";
					if (change.original) {
						html += "<h6>Original content:</h6>";
						html += "<pre style='background: #f5f5f5; padding: 10px; border-radius: 3px;'>" + escapeHtml(change.original) + "</pre>";
					}
					if (change.new) {
						html += "<h6>New content:</h6>";
						html += "<pre style='background: #e8f5e8; padding: 10px; border-radius: 3px;'>" + escapeHtml(change.new) + "</pre>";
					}
					html += "</div></div>";
				});
			}
			
			if (data.files_to_change.length === 0 && data.new_files.length === 0) {
				html = "<div class='alert alert-success'>No changes needed. System is already configured for Debian Bookworm.</div>";
			}
			
			$("#bookworm-preview").html(html);
		},
		error: function(xhr, status, error) {
			$("#bookworm-preview").html("<div class='alert alert-danger'>Error loading preview: " + error + "</div>");
		},
		complete: function() {
			$("#previewbookwormbutton").text("Preview Changes").prop("disabled", false);
		}
	});
}

// Configure Debian Bookworm repositories
function configure_debian_bookworm() {
	if (!confirm("This will modify your APT repository configuration. Are you sure you want to continue?")) {
		return;
	}
	
	$("#configurebookwormbutton").text("Configuring...").prop("disabled", true);
	$("#bookworm-results").show();
	$("#bookworm-results").html("<div class='alert alert-info'>Applying configuration...</div>");
	
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "configuredebianbookworm" },
		success: function(data) {
			var html = "";
			
			if (data.success) {
				html += "<div class='alert alert-success'><strong>Configuration completed successfully!</strong></div>";
			} else {
				html += "<div class='alert alert-danger'><strong>Configuration failed!</strong></div>";
			}
			
			if (data.messages && data.messages.length > 0) {
				html += "<h5>Messages:</h5>";
				html += "<ul>";
				data.messages.forEach(function(message) {
					html += "<li class='text-success'>" + escapeHtml(message) + "</li>";
				});
				html += "</ul>";
			}
			
			if (data.errors && data.errors.length > 0) {
				html += "<h5>Errors:</h5>";
				html += "<ul>";
				data.errors.forEach(function(error) {
					html += "<li class='text-danger'>" + escapeHtml(error) + "</li>";
				});
				html += "</ul>";
			}
			
			$("#bookworm-results").html(html);
		},
		error: function(xhr, status, error) {
			$("#bookworm-results").html("<div class='alert alert-danger'>Error applying configuration: " + error + "</div>");
		},
		complete: function() {
			$("#configurebookwormbutton").text("Apply Configuration").prop("disabled", false);
		}
	});
}

// Toggle Node.js packages details
function toggleNodejsPackages() {
	var details = document.getElementById('nodejs-details');
	var button = document.getElementById('nodejs-toggle');
	
	if (details.style.display === 'none') {
		details.style.display = 'table-row';
		button.textContent = 'Hide Details';
	} else {
		details.style.display = 'none';
		button.textContent = 'Show Details';
	}
}

// Refresh Debian configuration status via hook
function refreshDebianConfigStatus() {
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "getdebianconfigstatus" },
		success: function(data) {
			// Update the UI with the fresh data
			updateDebianConfigUI(data);
		},
		error: function(xhr, status, error) {
			console.log('Error refreshing Debian config status: ' + error);
		}
	});
}

// Refresh upgradable packages via hook
function refreshUpgradablePackages() {
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "refreshupgradablepackages" },
		success: function(data) {
			// Wait for apt command to complete, then reload the page to show fresh data
			setTimeout(function() {
				// Reload the entire page to show updated packages
				window.location.reload();
			}, 3000); // Increased timeout to allow apt command to complete
		},
		error: function(xhr, status, error) {
			console.log('Error refreshing upgradable packages: ' + error);
		}
	});
}

// Refresh held packages via hook
function refreshHeldPackages() {
	$.ajax({
		url: window.ajaxurl,
		data: { module: "framework", command: "sysupdate", action: "refreshheldpackages" },
		success: function(data) {
			// Wait for hook to complete, then reload the page to show fresh data
			setTimeout(function() {
				// Reload the entire page to show updated packages
				window.location.reload();
			}, 3000); // Increased timeout to allow hook to complete
		},
		error: function(xhr, status, error) {
			console.log('Error refreshing held packages: ' + error);
		}
	});
}

// Update Debian configuration UI with fresh data
function updateDebianConfigUI(configData) {
	if (!configData || !configData.is_debian) {
		return;
	}
	
	// Update the red alert visibility based on fresh data
	var alertDiv = $('.alert-danger').filter(function() {
		return $(this).text().indexOf('Debian 13 (Trixie) Upgrade Risk') !== -1;
	});
	
	if (configData.is_using_stable && !configData.is_trixie_blocked) {
		// Show the alert if it doesn't exist
		if (alertDiv.length === 0) {
			var alertHtml = '<div class="alert alert-danger" style="margin-top: 20px;">' +
				'<strong>Warning: Debian 13 (Trixie) Upgrade Risk</strong><br>' +
				'Your system is using the \'stable\' repository which may automatically upgrade to Debian 13 (Trixie) when it becomes available. ' +
				'We recommend configuring your system to use \'bookworm\' repositories and block Trixie upgrades to prevent compatibility issues.' +
				'</div>';
			$('#systemupdatestab .container-fluid').prepend(alertHtml);
		}
	} else {
		// Hide the alert if conditions are not met
		alertDiv.remove();
	}
	
	// Update the Debian configuration section status
	if (configData.is_trixie_blocked) {
		$('#debian-config-section .panel-body').html(
			'<div class="alert alert-success">' +
			'<strong>System is already configured!</strong><br>' +
			'This system is already blocked from upgrading to Debian 13 (Trixie).' +
			'</div>'
		);
	} else if (configData.is_debian_12) {
		// Keep the existing configuration section as is
	} else {
		$('#debian-config-section .panel-body').html(
			'<div class="alert alert-info">' +
			'<strong>Debian ' + configData.version + ' detected</strong><br>' +
			'This feature is designed for Debian 12 (Bookworm) systems to block upgrades to Debian 13 (Trixie).' +
			'</div>'
		);
	}
}

// Helper function to escape HTML
function escapeHtml(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Initialize tab click handlers when document is ready
$(document).ready(function() {
	// Listen for System Updates tab clicks
	$('a[href="#systemupdatestab"]').on('click', function() {
		// Trigger hooks to get fresh data
		refreshDebianConfigStatus();
		refreshUpgradablePackages();
		refreshHeldPackages();
	});
	
	// Also trigger when the tab is shown (in case it's activated programmatically)
	$('#systemupdatestab').on('shown.bs.tab', function() {
		refreshDebianConfigStatus();
		refreshUpgradablePackages();
		refreshHeldPackages();
	});
});

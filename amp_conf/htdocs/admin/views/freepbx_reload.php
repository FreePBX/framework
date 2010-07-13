<script type="text/javascript">
	function freepbx_show_reload(confirm) {
		/*
		$.blockUI($('#reloadBox')[0], { width: '400px' });
		$(document.body).append("<div style=\"width:100%; height:100%; background:#ccc; opacity:50%;\"><\/div>"); 
		*/
		
    if (confirm == 1) {
		  $("#reload_confirm").show();
    }
		$("#reload_reloading").hide();
		$("#reload_response").hide();
		
		freepbx_modal_show('reloadBox', function() {
			// have to use DOM method (not jquery), hence [0]
			$("#reload_confirm_continue_btn")[0].focus();
		});
		
    if (confirm == 0) {
		  $("#reload_confirm").hide();
      run_reload();
    }

		// add keyboard handler
		$('#reloadBox').keydown( function(e) {
			if ( $('#reload_confirm').css('display') == 'block') {
				// handler for when "confirm" screen is shown
				switch (e.keyCode) {
					case 13: case 10: case 32: // enter, spacebar = yes
						run_reload();
					break;
					case 27:
						freepbx_stop_reload(); // esc = cancel
					break;	
				}
			} else if ($('#reload_response').css('display') == 'block') {
				switch (e.keyCode) {
					case 27: case 13: case 10: case 32: // enter, esc, spacebar = close
						freepbx_stop_reload();
					break;
				}

			}
		});
	}
	function freepbx_stop_reload() {
		freepbx_modal_close('reloadBox');
	}
	
	function run_reload() {
		// figure out which div to slideup (hide): 
		// reload_confirm (normally), or reload_response (on a Retry)
		closeobj = $('#reload_confirm');
		if (closeobj.css('display') == 'none') {
			closeobj = $('#reload_response');
		}

		closeobj.slideUp(150, function() {
			$("#reload_reloading").slideDown(150, function() {
			
				$.ajax({
					type: 'POST',
					url: "<?php echo $_SERVER["PHP_SELF"]; ?>", 
					data: "handler=reload",
					dataType: 'json',
					success: function(data) {
						if (data.status) {
							// successful reload
<?php
  global $amp_conf;
  if (!$amp_conf['DEVELRELOAD']) {
?>
							$('#need_reload_block').fadeOut();
<?php
  }
?>
							freepbx_stop_reload();
						} else {
							// there was a problem
							var responsetext = '<h3>' + data.message + "<\/h3>" + '<div class="moreinfo">';

							responsetext += '<p><pre>' + data.retrieve_conf + "<\/pre><\/p>";
												
							if (data.num_errors) {
								responsetext += '<p>' + data.num_errors + " <?php echo _(" error(s) occurred, you should view the notification log on the dashboard or main screen to check for more details."); ?> " + "<\/p>";
							}
						
							responsetext += "<\/div>" +
							                '<div class="buttons"><a id="reload_response_close_btn" href="#" onclick="freepbx_stop_reload();"><img src="images/cancel.png" height="16" width="16" border="0" alt="<?php echo _('Close'); ?>" />&nbsp;<?php echo _('Close'); ?>' + "<\/a>" +
							                '&nbsp;&nbsp;&nbsp;<a id="reload_retry_btn" href="#" onclick="run_reload();"><img src="images/arrow_rotate_clockwise.png" height="16" width="16" border="0" alt="<?php echo _('Retry'); ?>" />&nbsp;<?php echo _('Retry'); ?>' + "<\/a> <\/div>";

							$('#reload_response').html(responsetext);
	
							$("#reload_reloading").slideUp(150, function() {
								$("#reload_response").slideDown(150);
								$("#reload_response_close_btn")[0].focus();
							});
						}
					},
					error: function(reqObj, status) {
						$('#reload_response').html(
							'<p>' + "<?php echo _("Error: Did not receive valid response from server"); ?>" + "<\/p>" + 
							'<div class="buttons"><a id="reload_response_close_btn" href="#" onclick="freepbx_stop_reload();"><img src="images/cancel.png" height="16" width="16" border="0" alt="<?php echo _('Close'); ?>" />&nbsp;<?php echo _('Close'); ?>' + "<\/a><\/div>"
						);
						$("#reload_reloading").slideUp(150, function() {
							$("#reload_response").slideDown(150);
							$("#reload_response_close_btn")[0].focus();
						});
					}
				});
				
			});
		});
		
	}


</script>
<div id="reloadBox" style="display:none;">
	<div id="reload_confirm">
		<h3><?php echo _('Apply Configuration Changes'); ?></h3>
		<?php echo _('Reloading will apply all configuration changes made in FreePBX to your PBX engine and make them active.'); ?>
		<ul>
		<li><a id="reload_confirm_continue_btn" href="#" onclick="run_reload();"><img src="images/accept.png" height="16" width="16" border="0" alt="<?php echo _('Accept'); ?>" /> <?php echo _('Continue with reload'); ?></a></li>
			<li><a href="#" onclick="freepbx_stop_reload();"><img src="images/cancel.png" height="16" width="16" border="0" alt="<?php echo _('Cancel'); ?>" /> <?php echo _('Cancel reload and go back to editing'); ?></a></li>
		</ul>
	</div>
	
	<div id="reload_reloading" style="display:none;">
		<h3><?php echo _('Please wait, reloading..'); ?></h3>
		<img src="images/loading.gif" alt="<?php echo _('Loading...'); ?>" />
	</div>
	
	<div id="reload_response" style="display:none;">
	</div>
</div>

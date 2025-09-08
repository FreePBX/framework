<?php if($errors) {?>
	<span class="obe_error">
		<?php echo _('Please correct the following errors:')?>
		<?php echo ul($errors);?>
	</span>
<?php } ?>

	<div id="login_form">
		<form id="loginform" method="post" role="form">
			<h3><?php echo _('To get started, please enter your credentials:')?></h3>
			<div class="form-group">
				<input type="text" name="username" class="form-control" value="" placeholder="username" autocomplete="off">
			</div>
			<div class="form-group">
				<input type="password" name="password" class="form-control" value="" placeholder="password" autocomplete="off">
			</div>
			<div class="" style="text-align:center">
				<button type="button" id="customContinue" class="ui-button ui-corner-all ui-widget btn">Continue</button>
				<button type="button" id="customCancel" class="ui-button ui-corner-all ui-widget btn">Cancel</button>
			</div>
			<?php
				if (\FreePBX::Modules()->checkStatus('pbxsaml')) {
			?>
				<div class="samlcheck" style="margin-bottom: 14px;margin-top:14px">
					<h6 style="text-align: center;font-size:14px" class="loginhead">Or sign in with</h6>
					<div class="samllink" style="text-align: center;">
						<?php
							$sql = "SELECT val FROM kvstore_FreePBX_modules_Pbxsaml WHERE `key` = 'samldriver'";
							$sth = FreePBX::Database()->prepare($sql);
							$sth->execute();
							$res = $sth->fetch(\PDO::FETCH_ASSOC);
							$image = '';
							$samlDriver = '';
							$disabled= '';
							$tooltip = _("SAML driver is not enabled. Please enable it to sign in");
							if(isset($res['val'])){
								$samlDriver = $res['val'];
								$sql = "SELECT val FROM kvstore_FreePBX_modules_Pbxsaml WHERE `key` = 'driver_details'";
								$sth = FreePBX::Database()->prepare($sql);
								$sth->execute();
								$getDetails = $sth->fetch(\PDO::FETCH_ASSOC);
								if(!empty($getDetails)){
									$driver = json_decode($getDetails['val'],true);
									$image = isset($driver['image'])?$driver['image']:'';
									$tooltip = isset($driver['tooltip'])?$driver['tooltip']:$tooltip;
								}
							}
							if($samlDriver == '' || $samlDriver == false){
								$disabled = "disabled";
							}
						?>
						<a class="loginsmal form-group" onclick="navigateToSaml(event)" data-toggle="tootip" title="<?php echo $tooltip ?>" style="width:100%;cursor:pointer" >
							<img src="<?php echo $image?>" style="width:110px" alt="Saml Login"></img>
						</a>
					</div>
				</div>
			<?php
				}
			?>
		</form>
	</div>

<?php
	if (\FreePBX::Modules()->checkStatus('pbxmfa') && $PBXMFA_LICENSED) {
		$webrootpath = \FreePBX::Config()->get('AMPWEBROOT');
		include $webrootpath . '/admin/modules/pbxmfa/views/mfa/otpModal.php';
	}
?>
<div id="login_icon_holder">
	<div class="login_item_title">
		<a href="#" class="login_item" id="login_admin" style="background-image: url(assets/images/sys-admin.png);"/>&nbsp;</a>
		<span class="login_item_text" style="display: block;width: 160px;text-align: center;">
			<?php echo _('FreePBX Administration')?>
		</span>
	</div>
	<div class="login_item_title">
		<a href="/ucp" class="login_item" id="login_ari" style="background-image: url(assets/images/user-control.png);"/>&nbsp;</a>
		<span class="login_item_text" style="display: block;width: 160px;text-align: center;">
			<?php echo _('User Control Panel')?>
		</span>
	</div>
	<?php if($panel) {?>
		<div class="login_item_title">
			<a href="<?php echo $panel?>" class="login_item" id="login_fop" style="background-image: url(assets/images/operator-panel.png);"/>&nbsp;</a>
			<span class="login_item_text" style="display: block;width: 160px;text-align: center;">
				<?php echo _('Operator Panel') ?>
			</span>
		</div>
	<?php } ?>
	<div class="login_item_title">
		<a href="https://help.sangoma.com/" target="_blank" class="login_item" id="login_support" style="background-image: url(assets/images/support.png);"/>&nbsp;</a>
		<span class="login_item_text" style="display: block;width: 160px;text-align: center;">
			<?php echo _('Get Support') ?>
		</span>
	</div>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<div id="key" style="color: white;font-size:small">
		<?php echo session_id();?>
	</div>
</div>
<script>
	function navigateToSaml(e) {
	e.preventDefault();
	const $dialog = $(e.target).closest('.ui-dialog, .ui-dialog-content');
	const $form = $dialog.find('#loginform').length
		? $dialog.find('#loginform')
		: $dialog.find('form').first();
	
		if($(".samlcheck")){
			$(".samlcheck").css("display","none");
		}
		if ($form.length) {
			$form.empty();
			let continuetext = loginBtn();
			$form.append(`
			<h3><?php echo _('To get started, please enter your username:')?></h3>
			<div class='form-group'>
				<input type='text' name='username' class='form-control' value='' placeholder='username' autocomplete='off'>
			</div>
			<div style="text-align:center;margin-bottom:8px">
				<button type="button" id="customContinue" class="ui-button ui-corner-all ui-widget btn">${continuetext}</button>
			</div>
		`);
		} 
	}

</script>
<script type="text/javascript" src="assets/js/views/login.js"></script>
<?php
	if (\FreePBX::Modules()->checkStatus('userman')) {
?>
	<script type="text/javascript" src='/admin/modules/userman/assets/js/adminPwdExpReminder.js'></script>
<?php
	}
?>
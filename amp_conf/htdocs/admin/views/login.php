<style>
.loginhead::before {
  content: '';
  width:100px;
  height:1px;
  display:inline-block;
  background:gray;
  vertical-align: middle;
}
.loginhead::after {
  content: '';
  width:100px;
  height:1px;
  display:inline-block;
  background:gray;
  vertical-align: middle;
}
.loginsmal{
	text-decoration:none !important;
	width:100%;
	border: 2px solid #cccccc; 
	border-radius:5px;
	display:flex;
	padding:5px 5px;
	justify-content: center;
    background: white;
	align-items:center;
}
</style>

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
				
			<?php
	if (\FreePBX::Modules()->checkStatus('pbxsaml')) {
?>
	<h6 style="text-align: center" class="loginhead"> or sigin in with</h6>
	<div class="samllink">
		<a href="javascript:void(0)" class="loginsmal form-group" onclick="navigateToSaml(event)">
			<img src="https://apps3.sangoma.com/microsoft-logo-with-signs.svg" style="width:110px"></img>
		</a>
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

		if ($form.length) {
			$form.empty()
			$form.append(`
			<h3><?php echo _('To get started, please enter your username:')?></h3>
			<div class='form-group'>
				<input type='text' name='username' class='form-control' value='' placeholder='username' autocomplete='off'>
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

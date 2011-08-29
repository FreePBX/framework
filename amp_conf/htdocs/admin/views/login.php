<?php
$html = '';
$html .= heading('Login', 3) . '<hr class="backup-hr"/>';
$html .= form_open($_SERVER['REQUEST_URI'], 'id="backup_form"');
$html .= '<div id="loginform_wrapper"><div id="loginform">';
$html .= _('To get started, please enter your credentials:');
$html .= br(2);
$data = array(
			'name' => 'username', 
			'placeholder' => _('username')
		);
$html .= form_input($data);
$html .= br(2);
$data = array(
			'name' => 'password',
			'type' => 'password',
			'placeholder' => _('password')
		);
$html .= form_input($data);
$html .= br(2);
$html .= form_submit('submit', _('Login'));
$html .= br(2);
$html .= form_close();
$html .= '</div></div>';
echo $html;

?>
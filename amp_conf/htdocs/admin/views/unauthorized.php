<?php

$template['content'] = 
	"<h2>"._("Unauthorized")."</h2>".
	"<p>"._("You are not authorized to access this page.")
	."</p><br><br>"
	. '<div style="color: white;font-size:small">'
	. session_id()
	. '</div>';
show_view($amp_conf['VIEW_FREEPBX'], $template);

?>

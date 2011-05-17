<?php

$template['content'] = 
	"<h2>"._("Not found")."</h2>".
	"<p>"._("The section you requested does not exist or you do not have access to it.")."</p>";
show_view($amp_conf['VIEW_FREEPBX'], $template);

?>

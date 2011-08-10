<?php
$template['content'] = 
	"<h2>"._("Logged out")."</h2>\n".
	"<p>"._("You have been successfully logged out.")."</p>\n".
	"<p><span style=\"background-color: #dddddd; margin: 6px; padding: 3px; border-style: solid; border-color: #777777; border-top-width: 0px; border-left-width: 0px; border-right-width: 2px; border-bottom-width: 2px;\">\n".
	"<a href=\"".$_SERVER['PHP_SELF']."\">"._("Log in")."</a></span></p>\n";
show_view($amp_conf['VIEW_FREEPBX'], $template);

?>

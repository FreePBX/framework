<?php

$template['content'] = 
	"<h2>"._("Logged out")."</h2>".
	"<p>"._("You have been succesfully logged out.")."</p>";
	"<p><a href=\"".$_SERVER['PHP_SELF']."\">"._("Log in")."</a></p>";
showview('freepbx', $template);

?>

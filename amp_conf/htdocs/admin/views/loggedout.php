<?php
// Until a real solution is found for #4680 tell them to close the browser.
if (strstr($_SERVER['HTTP_USER_AGENT'],'Chrome') !== false) {
$template['content'] = 
	"<h2>"._("Close Browser to Log Out")."</h2>\n".
	"<p>"._("You must close your browser to logout when using Chrome")."</p>\n";
} else {
$template['content'] = 
	"<h2>"._("Logged out")."</h2>\n".
	"<p>"._("You have been successfully logged out.")."</p>\n".
	"<p><span style=\"background-color: #dddddd; margin: 6px; padding: 3px; border-style: solid; border-color: #777777; border-top-width: 0px; border-left-width: 0px; border-right-width: 2px; border-bottom-width: 2px;\">\n".
	"<a href=\"".$_SERVER['PHP_SELF']."\">"._("Log in")."</a></span></p>\n";
}
showview('freepbx', $template);

?>

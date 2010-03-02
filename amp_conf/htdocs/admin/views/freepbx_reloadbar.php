<?php
global $amp_conf;
$reload_confirm = $amp_conf['RELOADCONFIRM'] ? 1 : 0;
echo "\n\t\t<div class=\"attention\" id=\"need_reload_block\"><a href=\"#\" onclick=\"freepbx_show_reload($reload_confirm);\" class=\"info\">";
echo '<img src="images/database_gear.png" height="16" width="16" border="0" alt="'._('Reload Required').'" title="'._('Reload Required').'" />&nbsp;';
echo _("Apply Configuration Changes");
echo "<span>".sprintf(_("You have made changes to the configuration that have not yet been applied. When you are finished making all changes, click on %s to put them into effect."), "<strong>"._("Apply Configuration Changes")."</strong>");
echo "</span></a></div>\n\n";
?>

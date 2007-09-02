<?php

$template['title'] = $title;
$template['content'] = 
	'<div id="panelframe">'.
	'<iframe width="97%" height="600" frameborder="0" align="top" src="../../panel/index_amp.php?context='.$deptname.'"></iframe>'.
	'</div>';
showview('freepbx', $template);

?>

<?php /* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$title="freePBX: Call Detail Reports";
$message="Call Detail Reports";

require_once('functions.inc.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");

// BUILD an SQL clause for any AMP User restrictions
session_register('AMP_SQL');
$low = $_SESSION["AMP_user"]->_extension_low;
$high = $_SESSION["AMP_user"]->_extension_high;
if ((!empty($low)) && (!empty($high))) {
	$channelfilter="OR (FIELD( SUBSTRING_INDEX( channel, '/', 1 ) , 'SIP', 'IAX2' ) > 0 AND SUBSTRING_INDEX(SUBSTRING(channel,2+LENGTH(SUBSTRING_INDEX( channel, '/', 1 ))),'-',1) BETWEEN $low and $high)";
	$channelfilter.="OR (dstchannel<>'' AND FIELD( SUBSTRING_INDEX( dstchannel, '/', 1 ) , 'SIP', 'IAX2' ) > 0 AND SUBSTRING_INDEX(SUBSTRING(dstchannel,2+LENGTH(SUBSTRING_INDEX( dstchannel, '/', 1 ))),'-',1) BETWEEN $low and $high)";

        $_SESSION["AMP_SQL"] = " AND ((src+0 BETWEEN $low AND $high) OR (dst+0 BETWEEN $low AND $high) OR (dst+0 BETWEEN 8$low AND 8$high) $channelfilter)";
} else {
	$_SESSION["AMP_SQL"] = "";
}

include 'header_auth.php';

$display=1;
if (isset($_REQUEST['display'])) {
	$display=$_REQUEST['display'];
}

// setup menu 
$amp_sections = array(
		1=>_("Call Logs"),
		2=>_("Compare Calls"),
		3=>_("Monthly Traffic"),
		4=>_("Daily load"),
	);

foreach ($amp_sections as $key=>$value) {
	echo "<div class=\"nav\" style=\"width=25%;text-align:center;\">";
	echo "<li><nobr><a id=\"".(($display==$key) ? 'current':'')."\" href=\"reports.php?display=".$key."\">".$value."</a><nobr></li>";
	echo "</div>";
}

// CDR viewer from www.areski.net.  
// Changes for -- AMP -- commented in:
// cdr.php, defines.php, call-log.php, call-comp.php, graph_hourdetail.php, graph_statbar.php, graph_pie.php
?>
<br><br>
</div>

<iframe width="97%" height="600" frameborder="0" align="top" src="cdr/cdr.php?s=<?php echo $display; echo ($display=='1' ? '&posted=1' : '');?>"></iframe>


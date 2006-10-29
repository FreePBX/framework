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

$quietmode = isset($_REQUEST['quietmode'])?$_REQUEST['quietmode']:'';

$title="freePBX: Flash Operator Panel";
$message="Flash Operator Panel";

require_once('functions.inc.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");

include 'header_auth.php';
?>
</div>
<iframe width="97%" height="600" frameborder="0" align="top" src="../panel/index_amp.php?context=<?php echo $_SESSION["AMP_user"]->_deptname?>"></iframe>

</body>
</html>

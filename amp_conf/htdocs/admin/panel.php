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

$title=_("FreePBX: Flash Operator Panel");
$message=_("Flash Operator Panel");

include 'header.php';

showview('panel', array('title'=>$title, 'deptname' => $_SESSION["AMP_user"]->_deptname));
?>
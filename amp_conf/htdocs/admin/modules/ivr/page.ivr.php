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
?>

<?php

// The Digital Receptionist code is a rat's nest.  If you are planning on making significant modifications, just re-write from scratch.
//if menu_id is being empty, or if we are requesting delete, just use ivr_action.php
if ((!isset($_REQUEST['menu_id']) || empty($_REQUEST['menu_id'])) || (isset($_REQUEST['ivr_action']) && $_REQUEST['ivr_action'] == 'delete'))
	include 'ivr_action.php'; 
else
	include 'ivr.php'; //wizard to create/edit a menu
			
?>

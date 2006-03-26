<?php 
//Copyright (C) 2006 Niklas Larsson
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

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';

$dispnum = "applications"; //used for switch on config.php

//if submitting form, update database
switch ($action) {
  case "save":
  	applications_update($_REQUEST);
  	needreload();
  break;
}
?>

</div>

<div class="content">
	<form autocomplete="off" name="editMM" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
  <input type="hidden" name="action" value="save">
	<table>
	<tr><td colspan="2"><h5>Change Access Number to Applications<hr></h5></td></tr>
<?php 
  $applications = applications_list();
  foreach($applications as $item){ ?>
	<tr>
		<td><?php echo $item['app'] ?></td>
		<td><input type="text" name="<?php echo $item['var'] ?>" value="<?php echo $item['exten'] ?>"></td>
	</tr>	
	<?php
	}
 ?>
	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
	</tr>
	</table>
	</form>

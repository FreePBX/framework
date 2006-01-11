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
//query for exisiting aa_N contexts
$unique_aas = ivr_list();

//check to see if we have any aa_ contexts
if (count($unique_aas) > 0) {
?>

	<h4><?php echo _("Voice Menu Map")?></h4>

<?php 
	//convert the customizable parts of each auto attendant to a user-readable format
	
	//create top-level for each voice menu
	foreach ($unique_aas as $unique_aa) {
		$menus[] = array($unique_aa[0],$unique_aa[1]);
		//Here we are looking for the largest menu_num in use, so that we can increment for a new menu
		//if we are not restricted to a dept (ie: admin), then count only non-dept specific menus
		if (!empty($dept)) {
			$num = (int) substr(strrchr($unique_aa[0],"_"),1);
			if ($num > $menu_num) $menu_num = $num;
		}
		else if (substr($unique_aa[0],0,3) == 'aa_') {
			$num = (int) substr(strrchr($unique_aa[0],"_"),1);
			if ($num > $menu_num) $menu_num = $num;
		}
	}
	foreach ($menus as $menu)
	{
?>
	<ul>
		<li>
			<span style="float:right;text-align:right;">
				&bull; <a href="config.php?display=ivr&menu_id=<?php  echo $menu[0] ?>&ivr_action=edit"><?php echo _("Modify this Menu")?></a><br>
				&bull; <a href="config.php?display=ivr&menu_id=<?php  echo $menu[0] ?>&ivr_action=delete"><?php echo _("Delete")?></a>
			</span>
			<?php echo _("Menu")?> <?php  echo $menu[0] ?>: <b><?php echo $menu[1]?></b>
			<ul>
<?php 
		//do another select for all parts in this aa_
		$aalines = ivr_get($menu[0]);
		
		//find relevant info in this context
		foreach ($aalines as $aaline) {
			$extension = $aaline[1];
			$application = $aaline[3];
			$args = explode(',',$aaline[4]);
			$argslen = count($args);
			//need to be backwards compatible, as we dial extensions by goto ext-local now
			if ($application == 'Macro' && $args[0] == 'exten-vm') {
					echo '<li>'._("dialing").' '.$extension.' <b>'._("dials extension #").$args[2].'</b>';
			}
			elseif ($application == 'Goto' && strpos($args[1],'VM_PREFIX')) {
				echo '<li>'._("dialing").' '.$extension.' <b>'._("sends to voicemail box #").ltrim($args[1],'${VM_PREFIX}').'</b>';
			}
			elseif ($application == 'Goto' && $args[0] == 'ext-local') {
				echo '<li>'._("dialing").' '.$extension.' <b>'._("dials extension #").$args[1].'</b>';
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'aa_') === false)) {
					echo '<li>'._("dialing").' '.$extension.' <b>'._("goes to Menu ID").' '.$args[0].'</b>';
					$menu_request[] = $args[0]; //we'll check to see if the aa_ target exists later
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-group') === false)) {
					echo '<li>'._("dialing").' '.$extension.' <b>'._("dials group #").$args[1].'</b>';
			}
			elseif ($application == 'Background') {
					$description = $aaline[5];
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'custom') === false)) {
				echo '<li>'._("dialing").' '.$extension.' <b>'._("goes to").' '.$args[0].','.$args[1].','.$args[2].'</b>';
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-queues') === false)) {
				echo '<li>'._("dialing").' '.$extension.' <b>'._("goes to Queue #").$args[1].'</b>';
			}
		}
?>
			</ul>
			<br>
			<?php echo _("Menu notes:")?> <b><i><?php  echo $description; ?></i></b>
	</ul>
	<hr>		
<?php 				
	} //end foreach ($unique_aas as $unique_aa) 
		
	//include a link to create an additional voice menu.
	echo '<ul><li>'._("Would you like to create another Menu?").'<ul><li><a href="config.php?display=ivr&menu_id='.$dept.'aa_'.++$menu_num.'">'._("Create a new Voice Menu").'</a></ul></ul><br>';
	
} //end if (count($unique_aas) > 0)
else {
	include 'ivr.php';
	
}
?>
	</ul>


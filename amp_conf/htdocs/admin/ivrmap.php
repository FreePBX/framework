<?
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

<?
//query for exisiting aa_N contexts
$unique_aas = getaas();

//check to see if we have any aa_ contexts
if (count($unique_aas) > 0) {
?>

	<h4>Voice Menu Map</h4>

<?
	//convert the customizable parts of each auto attendant to a user-readable format
	
	//create top-level for each voice menu
	foreach ($unique_aas as $unique_aa) {
		$num = (int) substr($unique_aa[0],3);
		$menu_nums[] = $num;
		$menu_names[$num] = $unique_aa[1];
		asort($menu_nums);
		$menus[] = $unique_aa[0];
	}
	foreach ($menu_nums as $menu_num)
	{
?>
	<ul>
		<li>
			<span style="float:right;text-align:right;">
				&bull; <a href="config.php?display=2&promptnum=<? echo $menu_num ?>&ivr_action=edit">Edit Menu #<? echo $menu_num ?></a><br>
				&bull; <a href="config.php?display=2&ivract_target=<? echo $menu_num ?>&ivr_action=delete">Delete</a>
			</span>
			Menu #<? echo $menu_num ?>: <b><?echo $menu_names[$menu_num]?></b>
			<ul>
<?
		//do another select for all parts in this aa_
		$aalines = aainfo($menu_num);
		
		//$description = $aalines[0][5];

		//find relevant info in this context
		foreach ($aalines as $aaline) {
			$extension = $aaline[1];
			$application = $aaline[3];
			$args = explode(',',$aaline[4]);
			$argslen = count($args);
			if ($application == 'Macro' && $args[0] == 'exten-vm') {
					echo '<li>dialling '.$extension.' <b>dials extension #'.$args[2].'</b>';
			}
			elseif ($application == 'Macro' && $args[0] == 'vm') {
					echo '<li>dialling '.$extension.' <b>sends to voicemail box #'.$args[1].'</b>';
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'aa_') === false)) {
					echo '<li>dialling '.$extension.' <b>goes to Menu #'.substr($args[0],3).'</b>';
					$menu_request[] = $args[0]; //we'll check to see if the aa_ target exists later
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'ext-group') === false)) {
					echo '<li>dialling '.$extension.' <b>dials group #'.$args[1].'</b>';
			}
			elseif ($application == 'Background') {
					$description = $aaline[5];
			}
			elseif ($application == 'Goto' && !(strpos($args[0],'custom') === false)) {
				echo '<li>dialling '.$extension.' <b>goes to '.$args[0].','.$args[1].','.$args[2].'</b>';
			}
		}
?>
			</ul>
			<br>
			Menu #<? echo $menu_num ?> notes: <b><i><? echo $description; ?></i></b>
	</ul>
	<hr>		
<?				
	} //end foreach ($unique_aas as $unique_aa) 
	
	//search the $menus[] for $menu_request[]
	//in other words - if a voice menu is requested that doesn't exist, then display a prompt to build the missing menu
	if (count($menu_request) > 0) {
		foreach ($menu_request as $mreq) {
			$found = false;
			foreach ($menus as $menu) {
				if ($mreq == $menu)
						$found = true;
			}
			if ($found == false) 
				echo '<ul><li>Menu #'.substr($mreq,3).' - <span style="color:red;">not yet created!</span><ul><li><a href="config.php?display=2&promptnum='.substr($mreq,3).'">Create Voice Menu #'.substr($mreq,3).'</a></ul></ul><br>';
		}
	}
	
	//include a link to create an additional voice menu.
	echo '<ul><li>Would you like to create another Menu?<ul><li><a href="config.php?display=2&promptnum='.++$menu_num.'">Create Voice Menu #'.$menu_num.'</a></ul></ul><br>';
	
} //end if (count($unique_aas) > 0)
else {
	include 'ivr.php';
	
}
?>
	</ul>


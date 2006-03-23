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

// The Digital Receptionist code is a rat's nest.  If you are planning on making significant modifications, just re-write from scratch.
// OK! You're the boss. --Rob


$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$dircontext = isset($_SESSION["AMP_user"]->_deptname)?$_SESSION["AMP_user"]->_deptname:'';

echo "</div>\n";
if (empty($dircontext))
        $dircontext = 'default';
// So. Lets check to make sure everything's happy
ivr_init();

switch ($action) {
	case "edit":
		ivr_sidebar($id);
		ivr_show_edit($id, $_POST);
		break;
	case "edited":
		ivr_do_edit($id, $_POST);
		ivr_sidebar($id);
		ivr_show_edit($id, $_POST);
		break;
	default:
		ivr_sidebar($id);
}


function ivr_sidebar($id)  {
?>
        <div class="rnav">
        <li><a id="<?php echo empty($id)?'current':'nul' ?>" href="config.php?display=ivr"><?php echo _("Add IVR")?></a></li>
<?php

        $tresults = ivr_list();
        if (isset($tresults)){
                foreach ($tresults as $tresult) {
                        echo "<li><a id=\"".($id==$tresult['ivr_id'] ? 'current':'nul')."\" href=\"config.php?display=ivr";
                        echo "&amp;action=edit&amp;id={$tresult['ivr_id']}\">{$tresult['displayname']}</a></li>\n";
                }
        }
        echo "</div>\n";
}

function ivr_show_edit($id, $post) {
	global $db;

	$ivr_details = ivr_get_details($id);
	$ivr_dests = ivr_get_dests($id);
	
	// Load up all the variables that may or may not have been posted
	$nbroptions = isset($_REQUEST['nbroptions'])?$_REQUEST['nbroptions']:'';
?>
	<div class="content">
        <h2><?php echo _("Digital Receptionist"); ?></h2>
        <h3><?php echo _("Edit Menu")." ".$ivr_details['displayname']; ?></h3>
<?php 
	echo "<a href='config.php?display=ivr&amp;action=delete&amp;id=$id' >"._("Delete")." "._("Digital Receptionist").'</a>';
?>
        <form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
        <input type="hidden" name="action" value="edited">
        <input type="hidden" name="display" value="ivr">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <table>
        <tr><td colspan=2><hr></td></tr>
        <tr>
                <td><a href="#" class="info">Change Name<span>This changes the short name, visible on the right, of this IVR</span></a></td>
                <td><input type="text" name="displayname" value="<?php echo $ivr_details['displayname'] ?>"></td>
        </tr>
        <tr>
                <td><a href="#" class="info">Timeout<span>The amount of time (in seconds) before the 't' option, if specified, is used</span></a></td>
                <td><input type="text" name="timeout" value="<?php echo $ivr_details['timeout'] ?>"></td>
        </tr>
        <tr>
                <td><a href="#" class="info">Enable Directory<span>Let callers into the IVR dial '#' to access the directory</span></a></td>
                <td><input type="checkbox" name="ena_directory" <? echo $ivr_details['enable_directory'] ?>></td>
        </tr>
        <tr>
                <td><a href="#" class="info">Enable Direct Dial<span>Let callers into the IVR dial an extension directly</span></a></td>
                <td><input type="checkbox" name="ena_directdial" <? echo $ivr_details['enable_directdial'] ?>></td>
        </tr>
        <tr><td colspan=2><hr></td></tr>
	<tr><td colspan=2>
	<input name="increase" type="submit" value="<?php echo _("Increase Options")?>"></h6>
	<input name="decrease" type="submit" value="<?php echo _("Decrease Options")?>"></h6>
	</td><tr>
        </table>
        <input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6>
	<input type="hidden" name="nbroptions" value="<?php echo $nbroptions ?>">
        </form>
        </div>


<?php

echo "</div>\n";
}




function runModuleSQL($moddir,$type){
        global $db;
        $data='';
        if (is_file("modules/{$moddir}/{$type}.sql")) {
                // run sql script
                $fd = fopen("modules/{$moddir}/{$type}.sql","r");
                while (!feof($fd)) {
                        $data .= fread($fd, 1024);
                }
                fclose($fd);

                preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);

                foreach ($matches[1] as $sql) {
                                $result = $db->query($sql);
                                if(DB::IsError($result)) {
                                        return false;
                                }
                }
                return true;
        }
                return true;
}


?>

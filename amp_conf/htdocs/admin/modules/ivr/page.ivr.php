<?php 
/* $Id$ */
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
// Re-written from the ground up by Rob Thomas <xrobau@gmail.com> 23rd March, 2006.


$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$dircontext = isset($_SESSION["AMP_user"]->_deptname)?$_SESSION["AMP_user"]->_deptname:'';
$nbroptions = isset($_REQUEST['nbroptions'])?$_REQUEST['nbroptions']:'3';

echo "</div>\n";
if (empty($dircontext))
        $dircontext = 'default';
// So. Lets check to make sure everything's happy
ivr_init();

switch ($action) {
	case "add":
		$id = ivr_get_ivr_id('Unnamed');
		// Set the defaults
		$def['timeout'] = 5;
		$def['ena_directdial'] = 'CHECKED';
		$def['ena_directory'] = 'CHECKED';
		ivr_sidebar($id);
		ivr_show_edit($id, 3,  $def);
		break;
	case "edit":
		ivr_sidebar($id);
		ivr_show_edit($id, $nbroptions, $_POST);
		break;
	case "edited":
		ivr_do_edit($id, $_POST);
		ivr_sidebar($id);
		if (isset($_REQUEST['increase'])) 
			$nbroptions++;
		if (isset($_REQUEST['decrease'])) {
			$nbroptions--;
		}
		if ($nbroptions < 1)
			$nbroptions = 1;
		ivr_show_edit($id, $nbroptions, $_POST);
		break;
	case "delete":
		sql("DELETE from ivr where ivr_id='$id'");
		sql("DELETE FROM ivr_dests where ivr_id='$id'");
		needreload();
	default:
		ivr_sidebar($id);
?>
<div class="content">
<h2><?php echo _("Digital Receptionist"); ?></h2>
<h3><?php 
echo _("Instructions")."</h3>";
echo _("You use the Digital Receptionist to make IVR's, Interactive Voice Responce systems.")."<br />\n";
echo _("When creating a menu option, apart from the standard options of 0-9,* and #, you can also use 'i' and 't' destinations.")."\n";
echo _("'i' is used when the caller pushes an invalid button, and 't' is used when there is no response.")."\n";
echo _("If those options aren't supplied, the default 't' is to replay the menu three times and then hang up,")."\n";
echo _("and the default 'i' is to say 'Invalid option, please try again' and replay the menu.")."\n";
echo _("After three invalid attempts, the line is hung up.")."\n"; ?>
</div>

<?php
}


function ivr_sidebar($id)  {
?>
        <div class="rnav">
        <li><a id="<?php echo empty($id)?'current':'nul' ?>" href="config.php?display=ivr&amp;action=add"><?php echo _("Add IVR")?></a></li>
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

function ivr_show_edit($id, $nbroptions, $post) {
	global $db;

	$ivr_details = ivr_get_details($id);
	$ivr_dests = ivr_get_dests($id);
?>
	<div class="content">
        <h2><?php echo _("Digital Receptionist"); ?></h2>
        <h3><?php echo _("Edit Menu")." ".$ivr_details['displayname']; ?></h3>
<?php 
	echo "<a href='config.php?display=ivr&amp;action=delete&amp;id=$id' >"._("Delete")." "._("Digital Receptionist")." {$ivr_details['displayname']}</a>";
?>
        <form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
        <input type="hidden" name="action" value="edited" />
        <input type="hidden" name="display" value="ivr" />
        <input type="hidden" name="id" value="<?php echo $id ?>" />
        <table>
        <tr><td colspan=2><hr /></td></tr>
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
                <td><input type="checkbox" name="ena_directory" <?php echo $ivr_details['enable_directory'] ?>></td>
        </tr>
        <tr>
                <td><a href="#" class="info">Enable Direct Dial<span>Let callers into the IVR dial an extension directly</span></a></td>
                <td><input type="checkbox" name="ena_directdial" <?php echo $ivr_details['enable_directdial'] ?>></td>
        </tr>
<?php
	if(function_exists('recordings_list')) { //only include if recordings is enabled ?>
        <tr>
                <td><a href="#" class="info"><?php echo _("Announcement")?><span><?php echo _("Message to be played to the caller. To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
                <td>&nbsp;
                        <select name="annmsg"/>
                        <?php
                                $tresults = recordings_list();
                                $annmsg = isset($ivr_details['announcement'])?$ivr_details['announcement']:'';
                                echo '<option value="">'._("None")."</option>";
                                if (isset($tresults[0])) {
                                        foreach ($tresults as $tresult) {
                                                echo '<option value="'.$tresult[0].'"'.($tresult[0] == $annmsg ? ' SELECTED' : '').'>'.$tresult[1]."</option>\n";
                                        }
                                }
                        ?>
                        </select>
                </td>
        </tr>
	
<?php
	}
?>

        <tr><td colspan=2><hr /></td></tr>
	<tr><td colspan=2>
	<input name="increase" type="submit" value="<?php echo _("Increase Options")?>"></h6>
	&nbsp;
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6>
	&nbsp;
	<input name="decrease" type="submit" value="<?php echo _("Decrease Options")?>"></h6>
	</td></tr>
        <tr><td colspan=2><hr /></td></tr>
<?php
	// Draw the destinations
	$dests = ivr_get_dests($id);
	$count = 0;
	if (!empty($dests)) {
		foreach ($dests as $dest) {
			drawdestinations($count, $dest['selection'], $dest['dest']);
			$count++;
		}
	}
	while ($count < $nbroptions) {
		drawdestinations($count, null, null);
		$count++;
	}
?>
	
        </table>
<?php
	if ($nbroptions < $count) { 
		echo "<input type='hidden' name='nbroptions' value=$count />\n";
	} else {
		echo "<input type='hidden' name='nbroptions' value=$nbroptions />\n";
	} 
?>
	<input name="increase" type="submit" value="<?php echo _("Increase Options")?>"></h6>
	&nbsp;
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6>
	&nbsp;
	<input name="decrease" type="submit" value="<?php echo _("Decrease Options")?>"></h6>
        </form>
        </div>


<?php

echo "</div>\n";
}

function drawdestinations($count, $sel,  $dest) { ?>
	<tr> <td style="text-align:right;">
		<input size="2" type="text" name="option<?php echo $count ?>" value="<?php echo $sel ?>"><br />
<?php if (strlen($sel)) {  ?>
		<i style='font-size: x-small'>Leave blank to remove</i>
<?php }  ?>
	</td>
		<td> <table> <?php echo drawselects($dest,$count); ?> </table> </td>
	</tr>
	<tr><td colspan=2><hr /></td></tr>
<?php
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

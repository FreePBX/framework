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

//used for switch on config.php
$dispnum = 'queues';

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the extension we are currently displaying
isset($_REQUEST['extdisplay'])?$extdisplay=$_REQUEST['extdisplay']:$extdisplay='';
isset($_REQUEST['account'])?$account = $_REQUEST['account']:$account='';
isset($_REQUEST['name'])?$name = $_REQUEST['name']:$name='';
isset($_REQUEST['password'])?$password = $_REQUEST['password']:$password='';
isset($_REQUEST['agentannounce'])?$agentannounce = $_REQUEST['agentannounce']:$agentannounce='';
isset($_REQUEST['prefix'])?$prefix = $_REQUEST['prefix']:$prefix='';
isset($_REQUEST['joinannounce'])?$joinannounce = $_REQUEST['joinannounce']:$joinannounce='';
$maxwait = (isset($_REQUEST['maxwait']) ? $_REQUEST['maxwait'] : '');

if (isset($_REQUEST['goto0']) && isset($_REQUEST[$_REQUEST['goto0']."0"])) {
	$goto = $_REQUEST[$_REQUEST['goto0']."0"];
} else {
	$goto = '';
}
$members = array();
if (isset($_REQUEST["members"])) {
	$members = explode("\n",$_REQUEST["members"]);

	if (!$members) {
		$members = array();
	}
	
	foreach (array_keys($members) as $key) {
		//trim it
		$members[$key] = trim($members[$key]);

		// remove invalid chars
		$members[$key] = preg_replace("/[^0-9#\,*]/", "", $members[$key]);

		$penalty_pos = strrpos($members[$key], ",");
		if ( $penalty_pos === false ) {
				$penalty_val = 0;
		} else {
				$penalty_val = substr($members[$key], $penalty_pos+1); // get penalty
				$members[$key] = substr($members[$key],0,$penalty_pos); // clean up ext
				$members[$key] = preg_replace("/[^0-9#*]/", "", $members[$key]); //clean out other ,'s
				$penalty_val = preg_replace("/[^0-9*]/", "", $penalty_val); // get rid of #'s if there
				$penalty_val = ($penalty_val == "") ? 0 : $penalty_val;
		}

		// remove blanks // prefix with the channel
		if ($members[$key] == "") unset($members[$key]);
		else $members[$key] = "Local/".$members[$key]."@from-internal,".$penalty_val;
	}
	
	// check for duplicates, and re-sequence
	$members = array_values(array_unique($members));
}

// do if we are submitting a form
if(isset($_POST['action'])){
	//check if the extension is within range for this user
	if (isset($account) && !checkRange($account)){
		echo "<script>javascript:alert('"._("Warning! Extension")." $account "._("is not allowed for your account.")."');</script>";
	} else {
		
		//if submitting form, update database
		switch ($action) {
			case "add":
				queues_add($account,$name,$password,$prefix,$goto,$agentannounce,$members,$joinannounce,$maxwait);
				needreload();
			break;
			case "delete":
				queues_del($account);
				needreload();
			break;
			case "edit":  //just delete and re-add
				queues_del($account);
				queues_add($account,$name,$password,$prefix,$goto,$agentannounce,$members,$joinannounce,$maxwait);
				needreload();
			break;
		}
	}
}

//get unique queues
$queues = queues_list();
	
?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add Queue")?></a></li>
<?php
if (isset($queues)) {
	foreach ($queues as $queue) {
		echo "<li><a id=\"".($extdisplay==$queue[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($dispnum)."&extdisplay=".urlencode($queue[0])."\">{$queue[0]}:{$queue[1]}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>'._("Queue").' '.$account.' '._("deleted").'!</h3><br><br><br><br><br><br><br><br>';
} else {
	$member = array();
	//get members in this queue
	$thisQ = queues_get($extdisplay);
	//create variables
	unset($maxwait);
	extract($thisQ);
	
	$delButton = "
				<form name=delete action=\"{$_SERVER['PHP_SELF']}\" method=POST>
					<input type=\"hidden\" name=\"display\" value=\"{$dispnum}\">
					<input type=\"hidden\" name=\"account\" value=\"{$extdisplay}\">
					<input type=\"hidden\" name=\"action\" value=\"delete\">
					<input type=submit value=\""._("Delete Queue")."\">
				</form>";
?>

<?php if ($extdisplay) { ?>
	<h2><?php echo _("Queue:")." ". $extdisplay; ?></h2>
<?php } else { ?>
	<h2><?php echo _("Add Queue"); ?></h2>
<?php } ?>

<?php		if ($extdisplay){ ?>
	<p><?php echo $delButton ?></p>
<?php		} ?>
	<form autocomplete="off" name="editQ" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($extdisplay ? _("Edit Queue") : _("Add Queue")) ?><hr></h5></td></tr>
	<tr>
<?php		if ($extdisplay){ ?>
		<input type="hidden" name="account" value="<?php echo $extdisplay; ?>">
<?php		} else { ?>
		<td><a href="#" class="info"><?php echo _("queue number:")?><span><?php echo _("Use this number to dial into the queue, or transfer callers to this number to put them into the queue.<br><br>Agents will dial this queue number plus * to log onto the queue, and this queue number plus ** to log out of the queue.<br><br>For example, if the queue number is 123:<br><br><b>123* = log in<br>123** = log out</b>")?></span></a></td>
		<td><input type="text" name="account" value=""></td>
<?php		} ?>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("queue name:")?><span><?php echo _("Give this queue a brief name to help you identify it.")?></span></a></td>
		<td><input type="text" name="name" value="<?php echo (isset($name) ? $name : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("queue password:")?><span><?php echo _("You can require agents to enter a password before they can log in to this queue.<br><br>This setting is optional.")?></span></a></td>
		<td><input type="text" name="password" value="<?php echo (isset($password) ? $password : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("CID name prefix:")?><span><?php echo _("You can optionally prefix the Caller ID name of callers to the queue. ie: If you prefix with \"Sales:\", a call from John Doe would display as \"Sales:John Doe\" on the extensions that ring.")?></span></a></td>
		<td><input size="4" type="text" name="prefix" value="<?php echo (isset($prefix) ? $prefix : ''); ?>"></td>
	</tr>
	<tr>
		<td valign="top"><a href="#" class="info"><?php echo _("static agents") ?>:<span><br><?php echo _("Static agents are extensions that are assumed to always be on the queue.  Static agents do not need to 'log in' to the queue, and cannot 'log out' of the queue.<br><br>List extensions to ring, one per line.<br><br>You can include an extension on a remote system, or an external number (Outbound Routing must contain a valid route for external numbers)") ?><br><br></span></a></td>
		<td valign="top">&nbsp;
			<textarea id="members" cols="15" rows="<?php  $rows = count($member)+1; echo (($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows) ); ?>" name="members"><?php foreach ($member as $mem) { $mem = rtrim(ltrim(strstr($mem,"/"),"/"),"@from-internal");echo substr($mem,0,strpos($mem, "@")).substr($mem,strrpos($mem, ","))."\n"; }?></textarea><br>
			<input type="submit" style="font-size:10px;" value="<?php echo _("Clean & Remove duplicates") ?>" />
		</td>
	</tr>

	<tr><td colspan="2"><br><h5><?php echo _("Queue Options")?><hr></h5></td></tr>
<?php if(function_exists('recordings_list')) { //only include if recordings is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Agent Announcement:")?><span><?php echo _("Announcement played to the Agent prior to bridging in the caller <br><br> Example: \"the Following call is from the Sales Queue\" or \"This call is from the Technical Support Queue\".<br><br>To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
		<td>
			<select name="agentannounce"/>
			<?php
				$tresults = recordings_list("/var/lib/asterisk/sounds/custom");
				$default = (isset($agentannounce) ? $agentannounce : None);
				echo '<option value="None">'._("None");
				if (isset($tresults)) {
					foreach ($tresults as $tresult) {
						$searchvalue="custom/$tresult";	
						echo '<option value="'.$tresult.'" '.($searchvalue == $default ? 'SELECTED' : '').'>'.$tresult;
					}
				}
			?>		
			</select>		
		</td>
	</tr>
<?php } if(function_exists('music_list')) { //only include if music module is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Hold Music Category:")?><span><?php echo _("Music (or Commercial) played to the caller while they wait in line for an available agent.<br><br>  This music is defined in the \"On Hold Music\" Menu to the left.")?></span></a></td>
		<td>
			<select name="music"/>
			<?php
				$tresults = music_list("/var/lib/asterisk/mohmp3");
				$default = (isset($music) ? $music : 'default');
				echo '<option value="default">'._("Default");
				if (isset($tresults)) {
					foreach ($tresults as $tresult) {
						$searchvalue="$tresult";	
						echo '<option value="'.$tresult.'" '.($searchvalue == $default ? 'SELECTED' : '').'>'.$tresult;
					}
				}
			?>		
			</select>		
		</td>
	</tr>
<?php } ?>
	<tr>
		<td><a href="#" class="info"><?php echo _("max wait time:")?><span><?php echo _("The maximum number of seconds a caller can wait in a queue before being pulled out.  (0 for unlimited).")?></span></a></td>
		<td>
			<select name="maxwait"/>
			<?php
				$default = (isset($maxwait) ? $maxwait : 0);
				for ($i=0; $i <= 1200; $i+=30) {
					if ($i == 0)
						echo '<option value="">'._("Unlimited").'</option>';
					else
						echo '<option value="'.$i.'"'.($i == $maxwait ? ' SELECTED' : '').'>'.queues_timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("max callers:")?><span><?php echo _("Maximum number of people waiting in the queue (0 for unlimited)")?></span></a></td>
		<td>
			<select name="maxlen"/>
			<?php 
				$default = (isset($maxlen) ? $maxlen : 0);
				for ($i=0; $i <= 50; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.$i.'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("join empty:")?><span><?php echo _("If you wish to allow callers to join queues that currently have no agents, set this to yes")?></span></a></td>
		<td>
			<select name="joinempty"/>
			<?php
				$default = (isset($joinempty) ? $joinempty : 'yes');
				$items = array('yes'=>_("Yes"),'no'=>_("No"));
				foreach ($items as $item) {
					echo '<option value="'.$item.'" '. ($default == $item ? 'SELECTED' : '').'>'.$item;
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("leave when empty:")?><span><?php echo _("If you wish to remove callers from the queue if there are no agents present, set this to yes")?></span></a></td>
		<td>
			<select name="leavewhenempty"/>
			<?php
				$default = (isset($leavewhenempty) ? $leavewhenempty : 'no');
				$items = array('yes'=>_("Yes"),'no'=>_("No"));
				foreach ($items as $item=>$val) {
					echo '<option value="'.$item.'" '. ($default == $item ? 'SELECTED' : '').'>'.$val;
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td>
			<a href="#" class="info"><?php echo _("ring strategy:")?>
				<span>
					<b><?php echo _("ringall")?></b>:  <?php echo _("ring all available agents until one answers (default)")?><br>
					<b><?php echo _("roundrobin")?></b>: <?php echo _("take turns ringing each available agent")?><br>
					<b><?php echo _("leastrecent")?></b>: <?php echo _("ring agent which was least recently called by this queue")?><br>
					<b><?php echo _("fewestcalls")?></b>: <?php echo _("ring the agent with fewest completed calls from this queue")?><br>
					<b><?php echo _("random")?></b>: <?php echo _("ring random agent")?><br>
					<b><?php echo _("rrmemory")?></b>: <?php echo _("round robin with memory, remember where we left off last ring pass")?><br>
				</span>
			</a>
		</td>
		<td>
			<select name="strategy"/>
			<?php
				$default = (isset($strategy) ? $strategy : 'ringall');
				$items = array('ringall','roundrobin','leastrecent','fewestcalls','random','rrmemory');
				foreach ($items as $item) {
					echo '<option value="'.$item.'" '.($default == $item ? 'SELECTED' : '').'>'._($item);
				}
			?>		
			</select>
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("agent timeout:")?><span><?php echo _("The number of seconds an agents phone can ring before we consider it a timeout.")?></span></a></td>
		<td>
			<select name="timeout"/>
			<?php
				$default = (isset($timeout) ? $timeout : 15);
				for ($i=0; $i <= 60; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.queues_timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("retry:")?><span><?php echo _("The number of seconds we wait before trying all the phones again")?></span></a></td>
		<td>
			<select name="retry"/>
			<?php
				$default = (isset($retry) ? $retry : 5);
				for ($i=0; $i <= 20; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.queues_timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("wrap-up-time:")?><span><?php echo _("After a successful call, how many seconds to wait before sending a potentially free agent another call (default is 0, or no delay)")?></span></a></td>
		<td>
			<select name="wrapuptime"/>
			<?php
				$default = (isset($wrapuptime) ? $wrapuptime : 0);
				for ($i=0; $i <= 60; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.queues_timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("call recording:")?><span><?php echo _("Incoming calls to agents can be recorded. (saved to /var/spool/asterisk/monitor)")?></span></a></td>
		<td>
			<select name="monitor-format"/>
			<?php
				$default = (empty($thisQ['monitor-format']) ? "no" : $thisQ['monitor-format']);  
				echo '<option value="wav49" '.($default == "wav49" ? 'SELECTED' : '').'>'._("wav49").'</option>';
				echo '<option value="wav" '.($default == "wav" ? 'SELECTED' : '').'>'._("wav").'</option>';
				echo '<option value="gsm" '.($default == "gsm" ? 'SELECTED' : '').'>'._("gsm").'</option>';
				echo '<option value="" '.($default == "no" ? 'SELECTED' : '').'>'._("No").'</option>';
			?>	
			</select>		
		</td>
	</tr>
	<tr><td colspan="2"><br><h5><?php echo _("Caller Announcements")?><hr></h5></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Frequency:")?><span><?php echo _("How often to announce queue position, estimated holdtime, and/or voice menu to the caller (0 to Disable Announcements).")?></span></a></td>
		<td>
			<select name="announcefreq"/>
			<?php
				$default = (isset($thisQ['announce-frequency']) ? $thisQ['announce-frequency'] : 0);
				for ($i=0; $i <= 1200; $i+=15) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.queues_timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Announce Position:")?><span><?php echo _("Announce position of caller in the queue?")?></span></a></td>
		<td>
			<select name="announceposition"/>
			<?php //setting to "no" will override sounds queue-youarenext, queue-thereare, queue-callswaitingÊ 
				$default = (isset($thisQ['announce-position']) ? $thisQ['announce-position'] : "no");  
					echo '<option value=yes '.($default == "yes" ? 'SELECTED' : '').'>'._("Yes").'</option>';
					echo '<option value=no '.($default == "no" ? 'SELECTED' : '').'>'._("No").'</option>';
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Announce Hold Time:")?><span><?php echo _("Should we include estimated hold time in position announcements?  Either yes, no, or only once; hold time will not be announced if <1 minute")?> </span></a></td>
		<td>
			<select name="announceholdtime">
			<?php
				$default = (isset($thisQ['announce-holdtime']) ? $thisQ['announce-holdtime'] : "no");
				echo '<option value=yes '.($default == "yes" ? 'SELECTED' : '').'>'._("Yes").'</option>';
				echo '<option value=no '.($default == "no" ? 'SELECTED' : '').'>'._("No").'</option>';
				echo '<option value=once '.($default == "once" ? 'SELECTED' : '').'>'._("Once").'</option>';
			?>		
			</select>		
		</td>
	</tr>
<?php if(function_exists('ivr_list')) { //only include if music module is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Voice Menu:")?><span> <?php echo _("After announcing Position and/or Hold Time, you can optionally present an existing Digital Receptionist Voice Menu.<br><br>This voicemenu must only contain single-digit 'dialed options'.")?> </span></a></td>
		<td>
			<select name="announcemenu">
			<?php // setting this will override the sound file queue-thankyou, and set the context= option
			$default = (isset(${announcemenu}) ? ${announcemenu} : "none");
			
			echo '<option value=none '.($default == "none" ? 'SELECTED' : '').'>'._("None").'</option>';
			
			//query for exisiting aa_N contexts
			$unique_aas = ivr_list();		
			
			if (isset($unique_aas)) {
				foreach ($unique_aas as $unique_aa) {
					$menu_id = $unique_aa[0];
					$menu_name = $unique_aa[1];
					echo '<option value="'.$menu_id.'" '.(strpos($default,$menu_id) === false ? '' : 'SELECTED').'>'.($menu_name ? $menu_name : _("Menu ID ").$menu_id);
				}
			}
		
			?>
			</select>
		</td>
	</tr>
	
<?php } if(function_exists('recordings_list')) { //only include if recordings is enabled?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Join Announcement:")?><span><?php echo _("Announcement played to callers once prior to joining the queue.<br><br>To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
		<td>
			<select name="joinannounce"/>
			<?php
				$tresults = recordings_list("/var/lib/asterisk/sounds/custom");
				$default = (isset($joinannounce) ? $joinannounce : None);
				echo '<option value="None">'._("None");
				if (isset($tresults)) {
					foreach ($tresults as $tresult) {
						$searchvalue="custom/$tresult";	
						echo '<option value="'.$tresult.'" '.($searchvalue == $default ? 'SELECTED' : '').'>'.$tresult;
					}
				}
			?>		
			</select>		
		</td>
	</tr>
<?php } ?>

	<tr><td colspan="2"><br><h5><?php echo _("Fail Over Destination")?><hr></h5></td></tr>

	<?php 
	//get goto for this group - note priority 6
	$goto = legacy_args_get($extdisplay,6,'ext-queues');
	echo drawselects($goto,0);
	
	?>
	
	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="button" value="<?php echo _("Submit Changes")?>" onclick="checkQ(editQ);"></h6></td>		
	</tr>
	</table>
	</form>
<?php		
} //end if action == delGRP
?>






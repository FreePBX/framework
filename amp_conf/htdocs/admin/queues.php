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


//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
$wScript2 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_queues_from_mysql.pl';

$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];  //the extension we are currently displaying
$dispnum = 11; //used for switch on config.php

$account = $_REQUEST['account'];
$name = $_REQUEST['name'];
$password = $_REQUEST['password'];
$goto = $_REQUEST['goto0'];

//if submitting form, update database
switch ($action) {
	case "add":
		addqueue($account,$name,$password,$goto);
		exec($wScript1);
		exec($wScript2);
		needreload();
	break;
	case "delete":
		delqueue($extdisplay);
		exec($wScript1);
		exec($wScript2);
		needreload();
	break;
	case "edit":  //just delete and re-add
		delqueue($account);
		addqueue($account,$name,$password,$goto);
		exec($wScript1);
		exec($wScript2);
		needreload();
	break;
}


//get unique extensions
$extens = getextens();
//get unique queues
$queues = getqueues();
	
?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>">Add Queue</a><br></li>
<?php
foreach ($queues as $queue) {
    echo "<li><a id=\"".($extdisplay==$queue[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$queue[0]}\">{$queue[0]}:{$queue[1]}</a></li>";
}
?>
</div>

<div class="content">
<?php
if ($action == 'delete') {
	echo '<br><h3>Queue '.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
} else {
	//get members in this queue
	$thisQ = getqueueinfo($extdisplay);
	//create variables
	extract($thisQ);
	

	$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
?>

	<h2>Queue: <?php echo $extdisplay; ?></h2>
<?php		if ($extdisplay){ ?>
	<p><a href="<?php echo $delURL ?>">Delete Queue <?php echo $extdisplay; ?></a></p>
<?php		} ?>
	<form name="editQ" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
	<table>
	<tr><td colspan="2"><h5><?php echo ($extdisplay ? 'Edit Queue' : 'Add Queue') ?><hr></h5></td></tr>
	<tr>
<?php		if ($extdisplay){ ?>
		<input type="hidden" name="account" value="<?php echo $extdisplay; ?>">
<?php		} else { ?>
		<td><a href="#" class="info">queue number:<span>Use this number to dial into the queue, or transfer callers to this number to put the into the queue.<br><br>Agents will dial this queue number plus * to log onto the queue, and this queue number plus ** to log out of the queue.<br><br>For example, if the queue number is 123:<br><br><b>123* = log in<br>123** = log out</b></span></a></td>
		<td><input type="text" name="account" value=""></td>
<?php		} ?>
	</tr>
	<tr>
		<td><a href="#" class="info">queue name:<span>Give this queue a brief name to help you identify it.</span></a></td>
		<td><input type="text" name="name" value="<?php echo (isset($name) ? $name : ''); ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info">queue password:<span>You can require agents to enter a password before they can log in to this queue.<br><br>This setting is optional.</span></a></td>
		<td><input type="text" name="password" value="<?php echo (isset($password) ? $password : ''); ?>"></td>
	</tr>
	<tr  valign="top">
		<td><a href="#" class="info">static agents:<span>Static agents are extensions that are assumed to always be on the queue.  Static agents do not need to 'log in' to the queue, and cannot 'log out' of the queue.<br><br>Hold <b>CTRL</b> to select multiple extensions</span></a></td>
		<td>
			<select multiple size="10" name="members[]"/>
			<?php
				foreach ($extens as $exten) { // (number,cid,tech)
					if ($exten[2] == 'zap') {
						$sql = "SELECT data FROM zap WHERE keyword = 'channel' AND id = '$exten[0]'";
						$channel = $db->getOne($sql);
						$device = 'zap/'.$channel;
					} else {
						$device = $exten[2].'/'.$exten[0];
					}
					
					echo '<option value="'.$device.'" '.(in_array($device,$member) ? 'SELECTED' : '').'>'.$exten[1];
				}
			?>		
			</select>
		</td>
	</tr>

	<tr><td colspan="2"><br><h5>Queue Options<hr></h5></td></tr>

	<tr>
		<td><a href="#" class="info">max wait time:<span>The maximum number of seconds a caller can wait in a queue before being pulled out.  (0 for unlimited).</span></a></td>
		<td>
			<select name="maxwait"/>
			<?php
				$default = (isset($maxwait) ? $maxwait : 0);
				for ($i=0; $i <= 1200; $i+=60) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info">max callers:<span>Maximum number of people waiting in the queue (0 for unlimited)</span></a></td>
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
		<td><a href="#" class="info">join empty:<span>If you wish to allow queues that have no members currently to be joined, set this to yes</span></a></td>
		<td>
			<select name="joinempty"/>
			<?php
				$default = (isset($joinempty) ? $joinempty : 'yes');
				$items = array('yes','no');
				foreach ($items as $item) {
					echo '<option value="'.$item.'" '. ($default == $item ? 'SELECTED' : '').'>'.$item;
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info">leave when empty:<span>If you wish to remove callers from the queue if there are no agents present, set this to yes</span></a></td>
		<td>
			<select name="leavewhenempty"/>
			<?php
				$default = (isset($leavewhenempty) ? $leavewhenempty : 'no');
				$items = array('yes','no');
				foreach ($items as $item) {
					echo '<option value="'.$item.'" '. ($default == $item ? 'SELECTED' : '').'>'.$item;
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td>
			<a href="#" class="info">ring strategy:
				<span>
					<b>ringall</b>:  ring all available channels until one answers (default)<br>
					<b>roundrobin</b>: take turns ringing each available interface<br>
					<b>leastrecent</b>: ring interface which was least recently called by this queue<br>
					<b>fewestcalls</b>: ring the one with fewest completed calls from this queue<br>
					<b>random</b>: ring random interface<br>
					<b>rrmemory</b>: round robin with memory, remember where we left off last ring pass<br>
				</span>
			</a>
		</td>
		<td>
			<select name="strategy"/>
			<?php
				$default = (isset($strategy) ? $strategy : 'ringall');
				$items = array('ringall','roundrobin','leastrecent','fewestcalls','random','rrmemory');
				foreach ($items as $item) {
					echo '<option value="'.$item.'" '.($default == $item ? 'SELECTED' : '').'>'.$item;
				}
			?>		
			</select>
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info">agent timeout:<span>The number of seconds a phone can ring before we consider it a timeout.</span></a></td>
		<td>
			<select name="timeout"/>
			<?php
				$default = (isset($timeout) ? $timeout : 15);
				for ($i=0; $i <= 60; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info">retry:<span>The number of seconds we wait before trying all the phones again?</span></a></td>
		<td>
			<select name="retry"/>
			<?php
				$default = (isset($retry) ? $retry : 5);
				for ($i=0; $i <= 20; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>
	<tr>
		<td><a href="#" class="info">wrap-up-time:<span>After a successful call, how many seconds to wait before sending a potentially free member another call (default is 0, or no delay)</span></a></td>
		<td>
			<select name="wrapuptime"/>
			<?php
				$default = (isset($wrapuptime) ? $wrapuptime : 0);
				for ($i=0; $i <= 60; $i++) {
					echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.timeString($i,true).'</option>';
				}
			?>		
			</select>		
		</td>
	</tr>

	<tr><td colspan="2"><br><h5>Fail Over Destination<hr></h5></td></tr>

	<?php echo drawselects('editQ',$goto,0);?>
	
	<tr>
		<td colspan="2"><br><h6><input name="Submit" type="button" value="Submit Changes" onclick="checkQ(editQ);"></h6></td>		
	</tr>
	</table>
	</form>
<?php		
} //end if action == delGRP
?>


<?php //Make sure the bottom border is low enuf
str_repeat('<br />',count($queues));
?>





<?php 
/* $Id$ */
// Module for offering IRC Support using PJIRC. 
//
// Original Release using PJIRC 2.2.0 on 17th Feb, 2006 by
// xrobau@gmail.com
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of version 2 of the GNU General Public
//License as published by the Free Software Foundation.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action = '';
$display='irc';
$type = 'tool';

?>

</div> 
<div class="rnav">
    <li><a href="config.php?type=tool&display=<?php echo urlencode($display)?>&action=start"><?php echo _("Start IRC")?></a></li>
</div>
<div class="content">

<h2>
<?php echo _("Online Support")?>
</h2>

<?php
switch ($action) {
	case "start":
	$vers=getversion();
?>

<p>
<?php echo _("When you connect, you will be automatically be named 'FreePBX' and a random 4 digit number, eg, FreePBX3486. If you wish to change this to your normal nickname, you can type '<b>/nick yournickname</b>', and your nick will change. This is an ENGLISH ONLY support channel. Sorry.")?>
</p>

<applet name=PJirc codebase=modules/irc/pjirc/ code=IRCApplet.class archive="irc.jar,pixx.jar" width=640 height=400>
<param name="CABINETS" value="irc.cab,securedirc.cab,pixx.cab">
<param name="nick" value="FreePBX????">
<param name="alternatenick" value="FreePBXU????">
<param name="host" value="irc.freenode.net">
<param name="gui" value="pixx">
<param name="command1" value="/join #freepbx">
<param name="command2" value="/notice #freepbx I am using <?php echo $vers[0][0]." on ".getversioninfo(); ?> ">
<param name="command3" value="/notice #freepbx My kernel is: <?php echo exec('uname -a'); ?> ">
</applet>
<?
		// Do IRC stuff
	break;
	case "":
?>

<?php echo _("This allows you to contact the FreePBX channel on IRC."); ?>

<?php echo _("As IRC is an un-moderated international medium, AMP, FreePBX, Coalescent Systems, or any other party can not be held responsible for the actions or behaviour of other people on the network"); ?>

<?php echo _("When you connect to IRC, to assist in support, the IRC client will automatically send the following information to everyone in the #freePBX channel:"); ?>

<ul>
<li> <?php echo _("Your Linux Distribution:");
           $ver=getversioninfo();
           echo " ($ver)"; ?>
<li> <?php echo _("Your FreePBX version:");
           $ver=getversion();
           echo " (".$ver[0][0].")"; ?>
<li> <?php echo _("Your Kernel version:");
           $ver=exec('uname -a');
           echo " ($ver)"; ?>
</ul>
<?php echo _("If you do not want this information to be made public, please use another IRC client, or contact a commercial support provider");
break;
}
?>



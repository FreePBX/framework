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
$title="Asterisk Management Portal";
$message="Administration";

require_once('functions.php');

// get settings
$amp_conf = parse_amportal_conf("/etc/amportal.conf");

// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

include 'header.php'; 

$display=$_REQUEST['display'];
?>

<div class="nav">
    <li><a id="<?php  echo ($display=='' ? 'current':'') ?>" href="index.php">Welcome</a></li>
</div>


<div class="content">

<?php
$display=$_REQUEST['display'];
switch($display) {
    default:
?>

    <h2>AMP</h2>
    <p>
        Welcome to the Asterisk Management Portal <?php $ver=getversion(); echo $ver[0][0];?>
        <br><br><br><br><br><br>
    </p>
    
<?php
    break;
    case '1':
?>


    
<?php
    break;
}
?>

</div>




<?php include 'footer.php' ?>

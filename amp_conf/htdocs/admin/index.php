<?
// Asterisk Management Portal (AMP)
// Copyright (C) 2004 Coalescent Systems Inc
?>

<?php 
$title="Asterisk Management Portal";
$message="Administration";
include 'header.php'; 

$display=$_REQUEST['display'];
?>

<div class="nav">
    <li><a id="<? echo ($display=='' ? 'current':'') ?>" href="index.php">Welcome</a></li>
</div>


<div class="content">

<?php
$display=$_REQUEST['display'];
switch($display) {
    default:
?>

    <h2>AMP</h2>
    <p>
        Welcome to the Asterisk Management Portal
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

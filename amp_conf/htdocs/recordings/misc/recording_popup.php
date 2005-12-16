<?php

/**
 * @file
 * popup window for playing recording
 */

chdir("..");
include_once("./includes/bootstrap.inc");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>ARI</TITLE>
    <link rel="stylesheet" href="popup.css" type="text/css">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  </head>
  <body>

<?php

  if (isset($_GET['recording'])) {
    if (isset($_GET['date'])) {
      echo($_GET['date'] . "<br>");
    }
    if (isset($_GET['time'])) {
      echo($_GET['time'] . "<br>");
    }
    echo("<br>");
    echo("<embed src='audio.php?recording=" . $_GET['recording'] . "' width=300, height=20 autostart=yes loop=false></embed><br>");
    echo("<a class='popup_download' href=/recordings/misc/audio.php?recording="  . $_GET['recording'] . ">" . _("download") . "</a><br>");
  }

?>

  </body>
</html>


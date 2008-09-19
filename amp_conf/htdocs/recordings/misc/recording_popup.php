<?php

/**
 * @file
 * popup window for playing recording
 */

chdir("..");
include_once("./includes/bootstrap.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>ARI</TITLE>
    <link rel="stylesheet" href="../theme/main.css" type="text/css">
    <link rel="stylesheet" href="popup.css" type="text/css">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  </head>
  <body>

<?php

  $path = urlencode($_GET['recording']);

  if (isset($path)) {
    if (isset($_GET['date'])) {
      echo("<small>" . $_GET['date'] . "</small><br>");
    }
    if (isset($_GET['time'])) {
      echo("<small>" . $_GET['time'] . "</small><br>");
    }

    echo("<br>");
    echo("<embed src='audio.php?recording=" . $path . "' width=300, height=25 autoplay=true loop=false></embed><br>");
    echo("<a class='popup_download' href=/recordings/misc/audio.php?recording="  . $path . ">" . _("download") . "</a><br>");
  }

?>

  </body>
</html>


<?php

/**
 * @file
 * popup window for playing recording
 */

if (isset($_GET['recording'])) {
  if (isset($_GET['date'])) {
    echo($_GET['date'] . "<br>");
  }
  if (isset($_GET['time'])) {
    echo($_GET['time'] . "<br>");
  }
  echo("<br>");
  echo("<embed src='audio.php?recording=" . $_GET['recording'] . "' width=300, height=20 autostart=yes loop=false></embed><br>");



echo ("<a style='color: #105D90; margin: 250px; font-size: 12px; text-align: right;' href=/recordings/misc/audio.php?recording="  . $_GET['recording'] . ">" . _("download") . "</a><br>");


}


?>
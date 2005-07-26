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
  echo("<embed src='audio.php?recording=" . $_REQUEST['recording'] . "' width=300, height=20 autostart=yes loop=false></embed>");
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>ARI</TITLE>
    <link rel="stylesheet" href="theme/style.css" type="text/css" />
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  </head>
  <div id="page">
    <div id="topheader">
      <div id="logo"><a href='<?=$_SERVER['PHP_SELF']?>' title="ARI">Asterisk Recording Interface</a></div>
    </div>
    <div id="topnav" class="topnav">
      <div class="spacer"></div>
      <span class="left">
        <form action="sector" method="post">
          <small>&nbsp;&nbsp;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;&middot;&nbsp;</small>
        </form>
      </span>
      <div class="spacer"></div>
    </div> 
    <div id="headerspacer"><img src="theme/spacer.gif" alt=""></div> 
    <div id="main">
      <div id="menu" class="nav">
        <div class="navimg">
          <img alt="" src="./theme/header_monitor.png">
	</div>
        <div class="navtext">
          <p><small><small><a href='<?=$_SERVER['PHP_SELF']?>'>Recordings</a></small></small></p>
        </div>
      </div>
      <div id="center">
        <div id="content">
          <!-- begin main content -->
          <?php print($content) ?>
          <!-- end main content -->
          <!--begin footer-->
          <div id="footer">
            <p class="footer">
              <small class="min">&copy; Copyright 2005 . All Rights Reserved.<br>
              <a href="Mailto:">L</a></small>
            </p>
          </div>
          <!-- end footer -->
        </div>
      </div>
    </div>
  </div>
  </body>
</html>


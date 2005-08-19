<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>ARI</TITLE>
    <link rel="stylesheet" href="theme/style.css" type="text/css" />
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  </head>
  <body>
  <div id="page">
  <div class="minwidth">
  <div class="container">
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
    <div class="minwidth">
    <div class="container">
      <div class="spacer"></div>
      <span class="left">
        <div id="menu">
          <div><img height=4 src="theme/spacer.gif" alt=""></div> 
          <div class="nav">
            <b class='nav_b1'></b><b class='nav_b2'></b><b class='nav_b3'></b><b class='nav_b4'></b>
            <div class='nav_items'>
              <?php if ($ari_no_login =='') { ?>
                <p><small><small><a href='<?=$_SERVER['PHP_SELF']?>?s=voicemail'>Voicemail</a></small></small></p>
              <?php } ?>
                <p><small><small><a href='<?=$_SERVER['PHP_SELF']?>?s=callmonitor'>Call Monitor</a></small></small></p>
                <?php if ($logout !='') { ?>
                  <br>
                  <p><small><small><a href='<?=$_SERVER['PHP_SELF']?>?logout=1'>Logout</a></small></small></p>
                <?php } ?>
            </div>
            <b class='nav_b4'></b><b class='nav_b3'></b><b class='nav_b2'></b><b class='nav_b1'></b>
          </div>
          <div><img height=14 src="theme/spacer.gif" alt=""></div> 
          <?php if ($navsub_menu !='') { ?>
            <div class="subnav">
              <div class="subnav_title">Folders:</div>
              <b class='subnav_b1'></b><b class='subnav_b2'></b><b class='subnav_b3'></b><b class='subnav_b4'></b>
              <div class='subnav_items'>
                <? echo $navsub_menu; ?>
              </div>
              <b class='subnav_b4'></b><b class='subnav_b3'></b><b class='subnav_b2'></b><b class='subnav_b1'></b>
            </div>
          <?php } ?>
        </div>
      </span>
      <span class="right">
        <div id="center">
          <?php if ($login != "") { ?>
            <?php print($login) ?>
          <?php } ?>
          <div id="content">
            <!-- begin main content -->
              <?php print($content) ?>
            <!-- end main content -->
          </div>
        </div>
      </span>
      <div class="spacer"></div>
    </div>
    </div>
    </div>
    <!--begin footer-->
    <div id="footer">
      <small>&middot;&nbsp;<a href="http://www.littlejohnconsulting.com">Littlejohn Consulting</a></small>
    </div>
    <!-- end footer -->
  </div>
  </div>
  </div>
  </body>
</html>


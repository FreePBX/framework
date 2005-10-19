<?php

/**
 * @file
 * Functions common to display
 */

/**
  * display constructor 
  */
class Display {

  /**
   * display constructor 
   */
  function Display() {
  }

  /**
   * display text header
   *
   * @param $text
   *   Header text to display
   */
  function DisplayHeaderText($text) {

    $ret = "<h2>" . $text . "</h2>
            <br>";

    return $ret;
  }

  /**
   * Displays header line 
   */
  function DisplayLine() {

    $ret = "
      <div id='line'>
        <div class='spacer'></div>
        <div class='spacer'></div>
      </div>
      <br>";

    return $ret;
  }
}  


class DisplaySearch extends Display {

  /**
   * Constructor
   */
  function DisplaySearch() {
  }

  /**
   * Displays search controls
   *
   * @param $align
   *   where to align the control
   * @param $q
   *   search query
   * @param $focus
   *   whether to focus control on this block
   */
  function DisplaySearchBlock($align,$m,$folder,$q,$focus) {

    if ($align=='center') {
      $alignText = "id='centerbar'";
    }

    $ret .= "<div " . $alignText . ">
               <form action='" . $_SERVER['PHP_SELF'] . "' method='GET' name='search'>
                 <input type=hidden name=m value=" . $m . ">	
                 <input type=hidden name=folder value=" . $folder . ">
                 <input type=text name=q size=40 value='" . $q . "' maxlength=256>
                 <input type=hidden name=start value=0>			
                 <input type=submit name=btnS value='Search'>
               </form>
             </div>";

    if ($focus=="true") {	// search block loaded twice usually so only allow javascript to be loaded on the top block
      $ret .= "<script type='text/javascript'> 
                 <!-- 
                 if (document.search) { 
                   document.search.q.focus(); 
                 } 
                 // -->                
               </script>";
    }

    return $ret;
  }

  /**
   * Displays info bar
   *
   * @param $controls
   *   controls for the page on the bar
   * @param $q
   *   search query
   * @param $start
   *   start number of current page
   * @param $span
   *   number of items on current page
   * @param $total
   *   total number of records found by current search
   */
  function DisplayInfoBarBlock($controls,$q,$start,$span,$total) {

    if ($total<$span) { 
      $span = $total; 
    }
    $startCount = ($total>0)?$start+1:$start;
    $spanCount = ($start+$span>$total)?$total:$start+$span;

    if ($controls) {
      $leftText = $controls;
    }
    elseif ($q != NULL) {
      $leftText = "<small><small>" . _("Searched for") . " <u>" . $q . "</u></small></small>";
    }

    if ($span<$total) {
      $rightText = "<small><small>" . _("Results") . " " . $startCount . " - " . $spanCount . " " . _("of") . " " . $total . "</small></small>";
    } else {
      $rightText = "<small><small>" . _("Results") . " " . $total . "</small></small>";
    }

    $ret .= "
      <table id='navbar' width='100%'>
        <tr>
          <td>
           " . $leftText . "
         </td>
         <td align='right'>
           " . $rightText ."
         </td>
       </tr>
     </table>";

    return $ret;
  }

  /**
   * Displays navigation bar
   *
   * @param $q
   *   search query
   * @param $start
   *   start number of current page
   * @param $span
   *   number of items on current page
   * @param $total
   *   total number of records found by current search
   */
  function DisplayNavigationBlock($m,$folder,$q,$start,$span,$total) {

    $start = $start=='' ? 0 : $start ;
    $span = $span=='' ? 15 : $span ;

    // add navigation to bottom of page
    $totalPages = ceil($total/$span);
    $startPage = floor($start/$span);

    // encode search string
    $q = urlencode($q);

    // if more than ten pages start at this page minus ten otherwise start at zero
    $begin = ($startPage>10)?($startPage-10):0;
    // if more than ten pages then stop at this page plus ten otherwise stop at last page
    $end = ($startPage>8)?($startPage+10):10;

    $url = $_SERVER['PHP_SELF'] . "?m=" . $m . "&folder=" . $folder . "&q=" . $q;

    if ($startPage!=0) {
      $startPageText = "<a href='" . $url . "&start=0'><small>" . _("First") . "</a>&nbsp;</small>
			<a href=" . $url . "&start=" . ($start-$span) . "><small><</a>&nbsp;</small>";
    }

    for($nextPage=$begin;($nextPage<$totalPages)&&($nextPage<$end);$nextPage++) {
      if ($nextPage == $startPage) {
          $middlePageText .= "<small>" . ($nextPage+1) . "&nbsp;</small>";
      } else {
          $middlePageText .= "<a href='" . $url . "&start=" . ($nextPage*$span) . "'><small>" . ($nextPage+1) . "</a>&nbsp;</small>";
      }
    }
    if ( ($startPage != $totalPages-1)  && ($total != 0) ) {
          $endPageText = "<a href='" . $url . "&start=" . ($start+$span) . "'><small>></a>&nbsp;</small>
                          <a href='" . $url . "&start=" . ($totalPages-1)*$span  . "'><small>" . _("Last") . "</a>&nbsp;</small>";
    }

    $ret .= "<div id='centerbar'>
              " . $startPageText . "
              " . $middlePageText . "
              " . $endPageText . "
             </div>";

    return $ret;
  }
}  


?>
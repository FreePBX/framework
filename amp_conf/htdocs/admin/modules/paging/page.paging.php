<?php 
/* $Id$ */
//Copyright (C) 2006 Rob Thomas (xrobau@gmail.com)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of version 2 of the GNU General Public
//License as published by the Free Software Foundation.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

//Both of these are used for switch on config.php
$display = isset($_REQUEST['display'])?$_REQUEST['display']:'paging';
$type = isset($_REQUEST['type'])?$_REQUEST['type']:'tool';

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$selection = isset($_REQUEST['selection'])?$_REQUEST['selection']:'';
$pagelist = isset($_REQUEST['pagelist'])?$_REQUEST['pagelist']:'';
$pagenbr = isset($_REQUEST['pagenbr'])?$_REQUEST['pagenbr']:'';

?>

</div>
<?php
// Check to make sure that the paging database is propogated and
// up to date.
paging_init();

switch ($action) {
	case "add":
		paging_sidebar($selection, $type, $display);
		paging_show(null, $display, $type);
		break;
	case "delete":
		paging_del($selection);
		paging_sidebar($selection, $type, $display);
		echo "Paging Group Deleted<br>\n";
		break;
	case "modify":
		paging_sidebar($selection, $type, $display);
		paging_show($selection, $display, $type);
		break;
	case "submit":
		paging_modify($pagenbr, $pagelist);
		paging_sidebar($selection, $type, $display);
		echo "<h5>Paging Group $pagenbr Modified</h5>\n";
		paging_text();
		break;
	default:
		paging_sidebar($selection, $type, $display);
		paging_text();
}

function paging_text() {
?>
<p>This module is for specific phones that are capable of Paging or Intercom. Presently, Intercom is <b>not</b>
supported, only group paging is. The current list of supported phones is GXP-2000 with firmware 1.0.13 or higher,
Snom phones with 'recent' firmware, and (someone fill in some more phone information here).</p>
<?php
}

function paging_show($xtn, $display, $type) {
	if ($xtn) {
		$rows = count(paging_get_devs($xtn))+1;
		if ($rows < 5) 
			$rows = 5;
		if ($rows > 20)
			$rows = 20;
		echo "<p><a href='".$_SERVER['PHP_SELF']."?type=${type}&amp;display=${display}&amp;action=delete";
		echo "&amp;selection=${xtn}'>"._("Delete Group")." $xtn</a></p>";
	} else {
		$rows = 5;
	}
	echo "<form name='page_edit' action='".$_SERVER['PHP_SELF']."' method='post' onsubmit='return page_edit_onsubmit();'>\n";
	echo "<input type='hidden' name='display' value='${display}'>\n";
	echo "<input type='hidden' name='type' value='${type}'>\n";
	echo "<input type='hidden' name='action' value='submit'>\n";
	echo "<table><tr><td colspan=2><h5>";
	echo ($xtn)?_("Modify Paging Group"):_("Add Paging Group")."</h5></td></tr>\n";  ?>
	<tr><td><a href='#' class='info'><?php echo _("Paging Extension") ?><span>
	<?php echo _("The number users will dial to page this group") ?></span></a></td>
	<td><input size='5' type='text' name='pagenbr' value='<?php echo $xtn ?>'></td>
	</tr><tr>
	<tr><td valign='top'><a href='#' class='info'><?php echo _("extension list:")."<span><br>"._("List extensions to page, one per line.") ?> 
	<br><br></span></a></td>
	<td valign="top"> <textarea id="xtnlist" cols="15" rows="<?php echo $rows ?>" name="pagelist"><?php 
		echo ($xtn)?implode("\n",paging_get_devs($xtn)):''; ?></textarea><br>
	<input type="submit" style="font-size:10px;" value="Clean & Remove duplicates" />
	</td></tr>
	<tr>
	<td colspan="2"><br><h6><input type="submit" name="Submit" type="button" value="Submit Changes"></h6></td>
	</tr>
	</table>
<script language="javascript">
<!--
var theForm = document.page_edit;
theForm.pagenbr.focus();

function page_edit_onsubmit() {
	defaultEmptyOK = false;
	if (!isInteger(theForm.pagenbr.value))
		return warnInvalid(theForm.pagenbr, "Please enter a valid Paging Extension");
	
	if (isEmpty(theForm.xtnlist.value) || isWhitespace(theForm.xtnlist.value))
		return warnInvalid(theForm.xtnlist, "Please enter the Extension List details");
		
	return true;
}

-->
</script>
	</form>
<?php
}

function paging_sidebar($selection, $type, $display) {
	echo "<div class='rnav'>\n";
	echo "<li><a id='".($selection==''?'current':'std')."' ";
	echo "href='config.php?type=${type}&amp;display=${display}&amp;action=add'>"._("Add Paging Group")."</a></li>"; 
	//get the list of paging groups
	$presults = paging_list();
	if ($presults) {
		foreach ($presults as $grouparr) {
			$group = $grouparr[0];
			echo "<li><a id=\"".($selection==$group ? 'current':'std');
			echo "\" href=\"config.php?type=${type}&amp;display=";
			echo "${display}&amp;selection=${group}&amp;action=modify\">";
			echo _("Page Group")." ${group}</a></li>";
		}
	} 
	echo "</div><div class='content'><h2>Paging and Intercom</h2>\n";
}
?>

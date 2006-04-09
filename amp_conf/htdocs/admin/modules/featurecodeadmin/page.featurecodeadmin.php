<?php 
// Original Copyright (C) 2006 Niklas Larsson
// Re-written 20060331, Rob Thomas <xrobau@gmail.com>
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

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$dispnum = "featurecodeadmin"; //used for switch on config.php

//if submitting form, update database
switch ($action) {
  case "save":
  	featurecodeadmin_update($_REQUEST);
  	needreload();
  break;
}

$featurecodes = featurecodes_getAllFeaturesDetailed();
?>

</div>

<div class="content">
	<form autocomplete="off" name="frmAdmin" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return frmAdmin_onsubmit();">
	<input type="hidden" name="display" value="<?php echo $dispnum?>">
  	<input type="hidden" name="action" value="save">
	<table>
	<tr><td colspan="4"><h3><?php echo _("Feature Code Admin"); ?><hr></h3></td></tr>
	<?php 
	$currentmodule = "(none)";
	foreach($featurecodes as $item) {
		$moduledesc = _($item['moduledescription']);
		$moduleena = ($item['moduleenabled'] == 1 ? true : false);

		$featuredesc = _($item['featuredescription']);
		$featureid = $item['modulename'] . '#' . $item['featurename'];
		$featureena = ($item['featureenabled'] == 1 ? true : false);
		$featurecodedefault = (isset($item['defaultcode']) ? $item['defaultcode'] : '');
		$featurecodecustom = (isset($item['customcode']) ? $item['customcode'] : '');
		
		if ($currentmodule != $moduledesc) {
			$currentmodule = $moduledesc;
			?>
			<tr>
				<td colspan="4">
					<font color="Black" size="4">
					<?php echo $currentmodule; ?>
					<?php if ($moduleena == false) {?>
					<i>(<?php echo _("Disabled"); ?>)</i>
					<?php } ?>
					</font>
				</td>
			</tr>
			<?php
		}
		?> 	
		<tr>
			<td> 
				<?php echo $featuredesc; ?>
			</td>
			<td>
				<input type="text" name="custom#<?php echo $featureid; ?>" value="<?php echo $featurecodecustom; ?>" size="4">
			</td>
			<td>
				<?php echo _("Default ?"); ?>&nbsp;<input type="checkbox" onclick="usedefault_onclick(this);" name="usedefault_<?php echo $featureid; ?>"<?php if ($featurecodecustom == '') echo "checked"; ?>>
				<input type="hidden" name="default_<?php echo $featureid; ?>" value="<?php echo $featurecodedefault; ?>">
				<input type="hidden" name="origcustom_<?php echo $featureid; ?>" value="<?php echo $featurecodecustom; ?>">
			</td>
			<td>
				<select name="ena#<?php echo $featureid; ?>">
				<option <?php if ($featureena == true) echo ("selected "); ?>value="1"><?php echo _("Enabled"); ?></option>
				<option <?php if ($featureena == false) echo ("selected "); ?>value="0"><?php echo _("Disabled"); ?></option>
				</select>
			</td>
		</tr>	
		<?php
	}
 ?>
	<tr>
		<td colspan="4"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
	</tr>
	</table>

		<script language="javascript">
	<!--
	
	var theForm = document.frmAdmin;
	
	callallusedefaults();
	
	// call the onclick function for all the Use Default boxes
	function callallusedefaults() {
		for (var i=0; i<theForm.elements.length; i++) {
			var theFld = theForm.elements[i];
			if (theFld.name.substring(0,11) == "usedefault_") {
				usedefault_onclick(theFld);
			}
		}
	}
		
	// disabled the custom code box if using default and also puts the default number in the box
	function usedefault_onclick(chk) {
		var featureid = chk.name.substring(11);
		if (chk.checked) {
			theForm.elements['origcustom_' + featureid].value = theForm.elements['custom#' + featureid].value;			
			theForm.elements['custom#' + featureid].value = theForm.elements['default_' + featureid].value;
		} else {
			theForm.elements['custom#' + featureid].value = theForm.elements['origcustom_' + featureid].value;
		}
		theForm.elements['custom#' + featureid].readOnly = chk.checked;
	}
	
	// onsubmit, check that every non default has a custom code
	function frmAdmin_onsubmit() {
		var msgError = "<?php echo _("Please enter a Feature Code or check Use Default for all Enabled Feature Codes"); ?>";

		for (var i=0; i<theForm.elements.length; i++) {
			var theFld = theForm.elements[i];
			if (theFld.name.substring(0,7) == "custom#") {
				var featureid = theFld.name.substring(7);
				if (!theForm.elements['usedefault_' + featureid].checked && theForm.elements['ena#' + featureid].value == 1) {
					defaultEmptyOK = false;
					if (!isDialDigits(theFld.value))
						return warnInvalid(theFld, msgError);
				}
			}
		}
		
		return true;
	}
	//-->
	</script>
	
	</form>

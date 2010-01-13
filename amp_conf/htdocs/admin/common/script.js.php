<?php
/*
$fstats = stat(__FILE__);
$mtime = $fstats['mtime'];
*/

header('Etag: "00002-0003-00000000"');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+3600).' GMT', true);
header ("Content-type: application/x-javascript");

if (!extension_loaded('gettext')) {
       function _($str) {
               return $str;
       }
} else {
    if (isset($_COOKIE['lang'])) {
    	setlocale(LC_MESSAGES,  $_COOKIE['lang']);
    } else { 
    	setlocale(LC_MESSAGES,  'en_US');
    }
    bindtextdomain('amp','../i18n');
    textdomain('amp');
}

/*
NOTE: this js function should not be in the php block. It is here only as a 
reference to the minifies version, located just out of the block
//this is called from validateDestinations to check each set
//you can call this directly if you have multiple sets and only
//require one to be selected, for example.
//formNum is the set number (0 indexed)
//bRequired true|false if user must select something
function validateSingleDestination(theForm,formNum,bRequired) {
	var gotoType = theForm.elements[ 'goto'+formNum ].value;
	
	if (bRequired && gotoType == '') {
		alert('<?php echo _("Please select a \"Destination\""); ?>');
		return false;
	} else {
		// check the 'custom' goto, if selected
		if (gotoType == 'custom') {
			var gotoFld = theForm.elements[ 'custom'+formNum ];
			var gotoVal = gotoFld.value;
			if (gotoVal.indexOf('custom-') == -1) {
				alert('<?php echo _("Custom Goto contexts must contain the string \"custom-\".  ie: custom-app,s,1"); ?>');
				gotoFld.focus();
				return false;
			}
		}
	}
	
	return true;
}
*/
?>
function validateSingleDestination(theForm,formNum,bRequired){var gotoType=theForm.elements['goto'+formNum].value;if(bRequired&&gotoType==''){alert('<?php echo _("Please select a \"Destination\""); ?>');return false;}else{if(gotoType=='custom'){var gotoFld=theForm.elements['custom'+formNum];var gotoVal=gotoFld.value;if(gotoVal.indexOf('custom-')==-1){alert('<?php echo _("Custom Goto contexts must contain the string \"custom-\".  ie: custom-app,s,1"); ?>');gotoFld.focus();return false;}}}
return true;}

// Used on the extensions/devices page to make sure a strong password is used
function weakSecret()
{
  var password = document.getElementById('devinfo_secret').value;
  var origional_password = document.getElementById('devinfo_secret_origional').value;

  if (password == origional_password)
  {
    return false;
  }

  if (password.length <= 5)
  {
    alert('<?php echo _("The secret must be at minimum six characters in length."); ?>');
    return true;
  }

  if (password.match(/[a-z].*[a-z]/i) == null || password.match(/\d\D*\d/) == null)
  {
    alert('<?php echo _("The secret must contain at least two numbers and two letters."); ?>');
    return true;
  }
  return false;
}

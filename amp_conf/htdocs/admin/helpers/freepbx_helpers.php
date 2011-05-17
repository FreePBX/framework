<?php

/**
 * short FreePBX Label generator
 * long Function  used to generate FreePBX 'labels' that can
 * show a help popup when moused over
 *
 * @author Moshe Brevda <mbrevda@gmail.com>
 * @param string $text
 * @param string $help
 * @return string
 * @todo change format to take advantage of html's data attribute. No need for spans!
 *
 * {@source } 
 */
function fpbx_label($text, $help = '') {
	if ($help) {
		$ret = '<a href="#" class="info">'
				. $text
				. '<span>'
				. $help
				. '</span></a>';
	} else {
		$ret = $text;
	}
	
	return $ret;
}

/*
 * $goto is the current goto destination setting
 * $i is the destination set number (used when drawing multiple destination sets in a single form ie: digital receptionist)
 * ensure that any form that includes this calls the setDestinations() javascript function on submit.
 * ie: if the form name is "edit", and drawselects has been called with $i=2 then use onsubmit="setDestinations(edit,2)"
 * $table specifies if the destinations will be drawn in a new <tr> and <td>
 * 
 */   
function drawselects($goto,$i,$show_custom=false, $table=true, $nodest_msg='') {
	global $tabindex, $active_modules, $drawselect_destinations, $drawselects_module_hash; 
	$html=$destmod=$errorclass=$errorstyle='';
  if ($nodest_msg == '') {
	  $nodest_msg = '== '._('choose one').' ==';
  }

	if($table){$html.='<tr><td colspan=2>';}//wrap in table tags if requested

	if(!isset($drawselect_destinations)){ 
		//check for module-specific destination functions
		foreach($active_modules as $rawmod => $module){
			$funct = strtolower($rawmod.'_destinations');
		
			//if the modulename_destinations() function exits, run it and display selections for it
			if (function_exists($funct)) {
				$destArray = $funct(); //returns an array with 'destination' and 'description', and optionally 'category'
				if(is_Array($destArray)) {
					foreach($destArray as $dest){
						$cat=(isset($dest['category'])?$dest['category']:$module['displayname']);
						$drawselect_destinations[$cat][] = $dest;
						$drawselects_module_hash[$cat] = $rawmod;
					}
				}
			}
		}
		//sort destination alphabetically		
		ksort($drawselect_destinations);
		ksort($drawselects_module_hash);
	}
	//set variables as arrays for the rare (impossible?) case where there are none
  if(!isset($drawselect_destinations)){$drawselect_destinations=array();}
  if(!isset($drawselects_module_hash)){$drawselects_module_hash = array();}

	$foundone=false;
	$tabindex_needed=true;
	//get the destination module name if we have a $goto, add custom if there is an issue
	if($goto){
		foreach($drawselects_module_hash as $mod => $description){
			foreach($drawselect_destinations[$mod] as $destination){
				if($goto==$destination['destination']){
					$destmod=$mod;
			  }
		  }
	  }
	  if($destmod==''){//if we haven't found a match, display error dest
		  $destmod='Error';
		  $drawselect_destinations['Error'][]=array('destination'=>$goto, 'description'=>'Bad Dest: '.$goto, 'class'=>'drawselect_error');
		  $drawselects_module_hash['Error']='error';
	  }
  }	

	//draw "parent" select box
	$style=' style="'.(($destmod=='Error')?'background-color:red;':'background-color:white;').'"';
	$html.='<select name="goto'.$i.'" class="destdropdown" '.$style.' tabindex="'.++$tabindex.'">';
	$html.='<option value="" style="background-color:white;">'.$nodest_msg.'</option>';
	foreach($drawselects_module_hash as $mod => $disc){
		/* We bind to the hosting module's domain. If we find the translation there we use it, if not
		 * we try the default 'amp' domain. If still no luck, we will try the _() which is the current
		 * module's display since some old translation code may have stored it locally but should migrate */
		bindtextdomain($drawselects_module_hash[$mod],"modules/".$drawselects_module_hash[$mod]."/i18n");
		bind_textdomain_codeset($drawselects_module_hash[$mod], 'utf8');
		$label_text=dgettext($drawselects_module_hash[$mod],$mod);
		if($label_text==$mod){$label_text=dgettext('amp',$label_text);}
		if($label_text==$mod){$label_text=_($label_text);}
		/* end i18n */
		$selected=($mod==$destmod)?' SELECTED ':' ';
		$style=' style="'.(($mod=='Error')?'background-color:red;':'background-color:white;').'"';
		$html.='<option value="'.str_replace(' ','_',$mod).'"'.$selected.$style.'>'.$label_text.'</option>';
	}
	$html.='</select> ';
	
	//draw "children" select boxes
	$tabindexhtml=' tabindex="'.++$tabindex.'"';//keep out of the foreach so that we don't increment it
	foreach($drawselect_destinations as $cat=>$destination){
		$style=(($cat==$destmod)?'':'display:none;');
		if($cat=='Error'){$style.=' '.$errorstyle;}//add error style
		$style=' style="'.(($cat=='Error')?'background-color:red;':$style).'"';
		$html.='<select name="'.str_replace(' ','_',$cat).$i.'" '.$tabindexhtml.$style.' class="destdropdown2">';
		foreach($destination as $dest){
			$selected=($goto==$dest['destination'])?'SELECTED ':' ';
		// This is ugly, but I can't think of another way to do localization for this child object
		    if(isset( $dest['category']) && dgettext('amp',"Terminate Call") == $dest['category']) {
    			$child_label_text = dgettext('amp',$dest['description']);
			}
		    else {
			$child_label_text=$dest['description'];
			}
			$style=' style="'.(($cat=='Error')?'background-color:red;':'background-color:white;').'"';
			$html.='<option value="'.$dest['destination'].'" '.$selected.$style.'>'.$child_label_text.'</option>';
		}
		$html.='</select>';
	}
	if(isset($drawselect_destinations['Error'])){unset($drawselect_destinations['Error']);}
	if(isset($drawselects_module_hash['Error'])){unset($drawselects_module_hash['Error']);}
	if($table){$html.='</td></tr>';}//wrap in table tags if requested
	
	return $html;
}


(function(factory){if(typeof define==='function'&&define.amd){define(['jquery'],factory);}else{factory(jQuery);}}(function($){var pluses=/\+/g;function encode(s){return config.raw?s:encodeURIComponent(s);}
function decode(s){return config.raw?s:decodeURIComponent(s);}
function stringifyCookieValue(value){return encode(config.json?JSON.stringify(value):String(value));}
function parseCookieValue(s){if(s.indexOf('"')===0){s=s.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,'\\');}
try{s=decodeURIComponent(s.replace(pluses,' '));return config.json?JSON.parse(s):s;}catch(e){}}
function read(s,converter){var value=config.raw?s:parseCookieValue(s);return $.isFunction(converter)?converter(value):value;}
var config=$.cookie=function(key,value,options){if(value!==undefined&&!$.isFunction(value)){options=$.extend({},config.defaults,options);if(typeof options.expires==='number'){var days=options.expires,t=options.expires=new Date();t.setTime(+t+days*864e+5);}
return(document.cookie=[encode(key),'=',stringifyCookieValue(value),options.expires?'; expires='+options.expires.toUTCString():'',options.path?'; path='+options.path:'',options.domain?'; domain='+options.domain:'',options.secure?'; secure':''].join(''));}
var result=key?undefined:{};var cookies=document.cookie?document.cookie.split('; '):[];for(var i=0,l=cookies.length;i<l;i++){var parts=cookies[i].split('=');var name=decode(parts.shift());var cookie=parts.join('=');if(key&&key===name){result=read(cookie,value);break;}
if(!key&&(cookie=read(cookie))!==undefined){result[name]=cookie;}}
return result;};config.defaults={};$.removeCookie=function(key,options){if($.cookie(key)===undefined){return false;}
$.cookie(key,'',$.extend({},options,{expires:-1}));return!$.cookie(key);};}));
function hideSelects(b){var allelems=document.all.tags('SELECT');if(allelems!=null){var i;for(i=0;i<allelems.length;i++){allelems[i].style.visibility=(b?'hidden':'inherit');}}}
function doHideSelects(event)
{hideSelects(true);}
function doShowSelects(event){hideSelects(false);}
function setDestinations(theForm,numForms){for(var formNum=0;formNum<numForms;formNum++){var whichitem=0;while(whichitem<theForm['goto'+formNum].length){if(theForm['goto'+formNum][whichitem].checked){theForm['goto'+formNum].value=theForm['goto'+formNum][whichitem].value;}
whichitem++;}}}
var whitespace=" \t\n\r";var decimalPointDelimiter=".";var defaultEmptyOK=false;function validateDestinations(theForm,numForms,bRequired){var valid=true;for(var formNum=0;formNum<numForms&&valid==true;formNum++){valid=validateSingleDestination(theForm,formNum,bRequired);}
return valid;}
function warnInvalid(theField,s){if(theField){theField.focus();theField.select();}
alert(s);return false;}
function isAlphanumeric(s){var i;if(isEmpty(s))
if(isAlphanumeric.arguments.length==1)return defaultEmptyOK;else return(isAlphanumeric.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!(isLetter(c)||isDigit(c)))
return false;}
return true;}
function isInteger(s){var i;if(isEmpty(s))
if(isInteger.arguments.length==1)return defaultEmptyOK;else return(isInteger.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isDigit(c)){return false;}}
return true;}
function isFloat(s){var i;var seenDecimalPoint=false;if(isEmpty(s))
if(isFloat.arguments.length==1)return defaultEmptyOK;else return(isFloat.arguments[1]==true);if(s==decimalPointDelimiter)return false;for(i=0;i<s.length;i++){var c=s.charAt(i);if((c==decimalPointDelimiter)&&!seenDecimalPoint)seenDecimalPoint=true;else if(!isDigit(c))return false;}
return true;}
function checkNumber(object_value){if(object_value.length==0)
return true;var start_format=" .+-0123456789";var number_format=" .0123456789";var check_char;var decimal=false;var trailing_blank=false;var digits=false;check_char=start_format.indexOf(object_value.charAt(0))
if(check_char==1)
decimal=true;else if(check_char<1)
return false;for(var i=1;i<object_value.length;i++)
{check_char=number_format.indexOf(object_value.charAt(i))
if(check_char<0)
return false;else if(check_char==1)
{if(decimal)
return false;else
decimal=true;}
else if(check_char==0)
{if(decimal||digits)
trailing_blank=true;}
else if(trailing_blank)
return false;else
digits=true;}
return true}
function isEmpty(s){return((s==null)||(s.length==0));}
function isWhitespace(s){var i;if(isEmpty(s))return true;for(i=0;i<s.length;i++){var c=s.charAt(i);if(whitespace.indexOf(c)==-1){return false;}}
return true;}
function isURL(s){var i;if(isEmpty(s))
if(isURL.arguments.length==1)return defaultEmptyOK;else return(isURL.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!(isURLChar(c)||isDigit(c)))
return false;}
return true;}
function isPINList(s)
{var i;if(isEmpty(s))
if(isPINList.arguments.length==1)return defaultEmptyOK;else return(isPINList.arguments[1]==true);for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isDigit(c)&&c!=",")return false;}
return true;}
function isCallerID(s){var i;if(isEmpty(s))
if(isCallerID.arguments.length==1)return defaultEmptyOK;else return(isCallerID.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!(isCallerIDChar(c)))
return false;}
return true;}
function isDialpattern(s){var i;if(isEmpty(s))
if(isDialpattern.arguments.length==1)return defaultEmptyOK;else return(isDialpattern.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isDialpatternChar(c)){if(c.charCodeAt(0)!=13&&c.charCodeAt(0)!=10){return false;}}}
return true;}
function isDialrule(s){var i;if(isEmpty(s))
if(isDialrule.arguments.length==1)return defaultEmptyOK;else return(isDialrule.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isDialruleChar(c)){if(c.charCodeAt(0)!=13&&c.charCodeAt(0)!=10){return false;}}}
return true;}
function isDialIdentifier(s)
{var i;if(isEmpty(s))
if(isDialIdentifier.arguments.length==1)return defaultEmptyOK;else return(isDialIdentifier.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isDialDigitChar(c)&&(c!="w")&&(c!="W"))return false;}
return true;}
function isDialDigits(s){var i;if(isEmpty(s))
if(isDialDigits.arguments.length==1)return defaultEmptyOK;else return(isDialDigits.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isDialDigitChar(c))return false;}
return true;}
function isIVROption(s)
{var i;if(isEmpty(s))
if(isIVROption.arguments.length==1)return defaultEmptyOK;else return(isIVROption.arguments[1]==true);if(s.length==1){var c=s.charAt(0);if((!isDialDigitChar(c))&&(c!="i")&&(c!="t"))
return false;}else{for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isDialDigitChar(c))return false;}}
return true;}
function isFilename(s)
{var i;if(isEmpty(s))
if(isFilename.arguments.length==1)return defaultEmptyOK;else return(isFilename.arguments[1]==true);for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isFilenameChar(c))return false;}
return true;}
function isInside(s,c)
{var i;if(isEmpty(s)){return false;}
for(i=0;i<s.length;i++)
{var t=s.charAt(i);if(t==c)return true;}
return false;}
function isEmail(s){if(isEmpty(s)){if(isEmail.arguments.length==1){return defaultEmptyOK;}else{return(isEmail.arguments[1]==true)}}
var emailAddresses=s.split(",");var pattern=/(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/i;var emailCount=0;for(e in emailAddresses){emailCount+=(pattern.test(emailAddresses[e])===true)?1:0;}
if(emailAddresses.length==emailCount){return true;}
return false;}
function isDigit(c){return new RegExp(/[0-9]/).test(c);}
function isLetter(c){return new RegExp(/[ a-zA-Z'\&\(\)\-\/]/).test(c);}
function isURLChar(c){return new RegExp(/[a-zA-Z=:,%#\.\-\/\?\&]/).test(c);}
function isCallerIDChar(c){return new RegExp(/[ a-zA-Z0-9:_,-<>\(\)\"&@\.\+]/).test(c);}
function isDialpatternChar(c){return new RegExp(/[-0-9\[\]\+\.\|ZzXxNn\*\#_!\/]/).test(c);}
function isDialruleChar(c){return new RegExp(/[0-9\[\]\+\.\|ZzXxNnWw\*\#\_\/]/).test(c);}
function isDialDigitChar(c){return new RegExp(/[0-9\*#]/).test(c);}
function isFilenameChar(c){return new RegExp(/[-0-9a-zA-Z\_]/).test(c);}
function validateSingleDestination(theForm,formNum,bRequired){var gotoType=theForm.elements['goto'+formNum].value;if(bRequired&&gotoType==''){alert(fpbx.msg.framework.validateSingleDestination.required);return false;}else{if(gotoType=='custom'){var gotoFld=theForm.elements['custom'+formNum];var gotoVal=gotoFld.value;if(gotoVal.indexOf('custom-')==-1){alert(fpbx.msg.framework.validateSingleDestination.error);gotoFld.focus();return false;}}}
return true;}
function weakSecret(){var password=document.getElementById('devinfo_secret').value;var origional_password=document.getElementById('devinfo_secret_origional').value;if(password==origional_password){return false;}
if(password.length<=5){alert(fpbx.msg.framework.weakSecret.length);return true;}
if(password.match(/[a-z].*[a-z]/i)==null||password.match(/\d\D*\d/)==null){alert(fpbx.msg.framework.weakSecret.types);return true;}
return false;}
$.urlParam=function(name){var match=new RegExp('[\\?&]'+name+'=([^&#]*)').exec(window.location.search);return match&&decodeURIComponent(match[1].replace(/\+/g,' '));}
var popover_box;var popover_box_class;var popover_box_mod;var popover_select_id;function bind_dests_double_selects(){$('.destdropdown').unbind().bind('change',function(e){var id=$(this).data('id');var id=typeof id=='undefined'?'':id;var dest=$(this).val();$('[data-id='+id+'].destdropdown2').hide();dd2=$('#'+dest+id+'.destdropdown2');cur_val=dd2.show().val();if(dd2.children().length>1&&cur_val=='popover'){dd2.val('');cur_val='';}
if(cur_val=='popover'){dd2.trigger('change');}});$('.destdropdown2').unbind().bind('change',function(){var dest=$(this).val();if(dest=="popover"){var urlStr=$(this).data('url')+'&fw_popover=1';var id=$(this).data('id');popover_select_id=this.id;popover_box_class=$(this).data('class');popover_box_mod=$(this).data('mod');popover_box=$('<div id="popover-box-id" data-id="'+id+'"></div>').html('<iframe data-popover-class="'+popover_box_class+'" id="popover-frame" frameBorder="0" src="'+urlStr+'" width="100%" height="95%"></iframe>').dialog({title:'Add',resizable:false,modal:true,position:['center',50],width:window.innerWidth-(window.innerWidth*.10),height:window.innerHeight-(window.innerHeight*.10),create:function(){$("body").scrollTop(0).css({overflow:'hidden'});},close:function(e){var id=$(this).data('id');var par=$('#goto'+id).data('last');$('#goto'+id).val(par).change();if(par!=''){var par_id=par.concat(id);$('#'+par_id).val($('#'+par_id).data('last')).change();}
$('#popover-frame').contents().find('body').remove();$('#popover-box-id').html('');$("body").css({overflow:'inherit'});$(e.target).dialog("destroy").remove();},buttons:[{text:fpbx.msg.framework.save,click:function(){pform=$('#popover-frame').contents().find('.popover-form').first();if(pform.length==0){pform=$('#popover-frame').contents().find('form').first();}
pform.submit();}},{text:fpbx.msg.framework.cancel,click:function(){$(this).dialog("close");}}]});}else{var last=$.data(this,'last',dest);}});$('.destdropdown').bind('change',function(){if($(this).find('option:selected').val()=='Error'){$(this).css('background-color','red');}else{$(this).css('background-color','white');}});}
function closePopOver(drawselects){var options=$('.'+popover_box_class+' option',$('<div>'+drawselects+'</div>'));$('.'+popover_box_class).each(function(){if(this.id==popover_select_id){$(this).empty().append(options.clone());}else{dv=$(this).val();$(this).empty().append(options.clone()).val(dv);}});if(popover_box_class!=popover_box_mod){var options={};$('.'+popover_box_mod).each(function(){var data_class=$(this).data('class');if(data_class!=popover_box_class){if(typeof options[data_class]=='undefined'){options[data_class]=$('.'+data_class+' option',$('<div>'+drawselects+'</div>'));}
dv=$(this).val();$(this).empty().append(options[data_class].clone()).val(dv);}});}
$("body").css({overflow:'inherit'});$('#popover-box-id').html('');popover_box.dialog("destroy");}
function popOverDisplay(){$('.rnav').hide();pform=$('.popover-form').first();if(pform.length==0){pform=$('form').first();}
$('[type="submit"]',pform).hide();$('<input>').attr({type:'hidden',name:'fw_popover_process'}).val(parent.$('#popover-frame').data('popover-class')).appendTo(pform);}
function fpbx_reload_confirm(){if(!fpbx.conf.RELOADCONFIRM){fpbx_reload();}
$('<div></div>').html('Reloading will apply all configuration changes made '
+'in FreePBX to your PBX engine and make them active.').dialog({title:'Confirm reload',resizable:false,modal:true,position:['center',50],close:function(e){$(e.target).dialog("destroy").remove();},buttons:[{text:fpbx.msg.framework.continuemsg,click:function(){$(this).dialog("destroy").remove();fpbx_reload();}},{text:fpbx.msg.framework.cancel,click:function(){$(this).dialog("destroy").remove();}}]});}
function fpbx_reload(){$('<div></div>').progressbar({value:100})
var box=$('<div id="reloadbox"></div>').html('<progress style="width: 100%">'
+'Please wait...'
+'</progress>').dialog({title:'Reloading...',resizable:false,modal:true,height:52,position:['center',50],closeOnEscape:false,open:function(event,ui){$(".ui-dialog-titlebar-close",$(this).parent()).hide();},close:function(e){$(e.target).dialog("destroy").remove();}});$.ajax({type:'POST',url:document.location.pathname,data:"handler=reload",dataType:'json',success:function(data){box.dialog('destroy').remove();if(!data.status){var r='<h3>'+data.message+'<\/h3>'
+'<a href="#" id="error_more_info">click here for more info</a>'
+'<pre style="display:none">'+data.retrieve_conf+"<\/pre>";if(data.num_errors){r+='<p>'+data.num_errors+fpbx.msg.framework.reload_unidentified_error+"<\/p>";}
freepbx_reload_error(r);}else{if(fpbx.conf.DEVELRELOAD!='true'){toggle_reload_button('hide');}}},error:function(reqObj,status){box.dialog("destroy").remove();var r='<p>'+fpbx.msg.framework.invalid_responce+'<\/p>'
+"<p>XHR response code: "+reqObj.status
+" XHR responseText: "+reqObj.resonseText
+" jQuery status: "+status+"<\/p>";freepbx_reload_error(r);}});}
function freepbx_reload_error(txt){var box=$('<div></div>').html(txt).dialog({title:'Error!',resizable:false,modal:true,minWidth:600,position:['center',50],close:function(e){$(e.target).dialog("destroy").remove();},buttons:[{text:fpbx.msg.framework.retry,click:function(){$(this).dialog("destroy").remove();fpbx_reload();}},{text:fpbx.msg.framework.cancel,click:function(){$(this).dialog("destroy").remove();}}]});$('#error_more_info').click(function(){$(this).next('pre').show();$(this).hide();return false;})}
function toggle_reload_button(action){switch(action){case'show':$('#button_reload').show().css('display','inline-block');break;case'hide':$('#button_reload').hide();break;}}
$(document).ready(function(){bind_dests_double_selects();$("a.info").each(function(){$(this).after('<span class="help">?<span>'+$(this).find('span').html()+'</span></span>');$(this).find('span').remove();$(this).replaceWith($(this).html())})
$(".help").on('mouseenter',function(){side=fpbx.conf.text_dir=='lrt'?'left':'right';var pos=$(this).offset();var offset=(200-pos.side)+"px";$(this).find("span").css(side,offset).stop(true,true).delay(500).animate({opacity:"show"},750);}).on('mouseleave',function(){$(this).find("span").stop(true,true).animate({opacity:"hide"},"fast");});$('.guielToggle').click(function(){var txt=$(this).find('.guielToggleBut');var el=$(this).data('toggle_class');var section=$.urlParam('display')+'#'+el;switch(txt.text().replace(/ /g,'')){case'-':txt.text('+ ');$('.'+el).hide();guielToggle=$.parseJSON($.cookie('guielToggle'))||{};guielToggle[section]=false;$.cookie('guielToggle',JSON.stringify(guielToggle));break;case'+':txt.text('-  ');$('.'+el).show();guielToggle=$.parseJSON($.cookie('guielToggle'))||{};if(guielToggle.hasOwnProperty(section)){guielToggle[section]=true;$.cookie('guielToggle',JSON.stringify(guielToggle));}
break;}})
$('#fpbx_lang > li').click(function(){$.cookie('lang',$(this).data('lang'));window.location.reload();})
$('.rnav > ul').menu();$('.radioset').buttonset();$('.menubar').menubar().hide().show();$('.module_menu_button').hover(function(){$(this).click();var sh=$(window).height();$('.ui-menu').each(function(){if($(this).css('display')=='block'){$(this).css('max-height','');if($(this).height()>sh){$(this).css('max-height',sh-50+'px');}}});});if(fpbx.conf.reload_needed){toggle_reload_button('show');}
$('.sortable').menu().find('input[type="checkbox"]').parent('a').click(function(event){var checkbox=$(this).find('input');checkbox.prop('checked',!checkbox[0].checked);return false;});$('.ui-menu-item').click(function(){go=$(this).find('a').attr('href');if(go&&!$(this).find('a').hasClass('ui-state-disabled')){document.location.href=go;}})
$('#button_reload').click(function(){if(fpbx.conf.RELOADCONFIRM=='true'){fpbx_reload_confirm();}else{fpbx_reload();}});$('#MENU_BRAND_IMAGE_TANGO_LEFT').click(function(){window.open($(this).data('brand_image_freepbx_link_left'),'_newtab');});$('input[type=submit],input[type=button], button, input[type=reset]').each(function(){var prim=(typeof $(this).data('button-icon-primary')=='undefined')?'':($(this).data('button-icon-primary'));var sec=(typeof $(this).data('button-icon-secondary')=='undefined')?'':($(this).data('button-icon-secondary'));var txt=(typeof $(this).data('button-text')=='undefined')?'true':($(this).data('button-text'));var txt=(txt=='true')?true:false;$(this).button({icons:{primary:prim,secondary:sec},text:txt});});var extselector=$('input.extdisplay,input[type=text][name=extension],input[type=text][name=extdisplay],input[type=text][name=account]').not('input.noextmap');if(extselector.length>0){extselector.after(" <span style='display:none'><a href='#'><img src='images/notify_critical.png'/></a></span>").keyup(function(){if(typeof extmap[this.value]=="undefined"||$(this).data('extdisplay')==this.value){$(this).removeClass('duplicate-exten').next('span').hide();}else{$(this).addClass('duplicate-exten').next('span').show().children('a').attr('title',extmap[this.value]);}}).each(function(){if(typeof $(this).data('extdisplay')=="undefined"){$(this).data('extdisplay',this.value);}else if(typeof extmap[this.value]!="undefined"){this.value++;while(typeof extmap[this.value]!="undefined"){this.value++;}}}).parents('form').submit(function(e){if(e.isDefaultPrevented()){return false;}
exten=$('.duplicate-exten',this);if(exten.length>0){extnum=exten.val();alert(extnum+fpbx.msg.framework.validation.duplicate+extmap[extnum]);return false;}
return true;});}
$(document).bind('keydown','meta+shift+a',function(){$('#modules_button').trigger('click');});$(document).bind('keydown','ctrl+shift+s',function(){$('input[type=submit][name=Submit]').click();});$(document).bind('keydown','ctrl+shift+a',function(){fpbx_reload();});$('#user_logout').click(function(){url=window.location.pathname;$.get(url+'?logout=true',function(){$.cookie('PHPSESSID',null);window.location=url;});});$(".input_checkbox_toggle_true, .input_checkbox_toggle_false").click(function(){checked=$(this).hasClass('input_checkbox_toggle_true')?this.checked:!this.checked;$(this).prev().prop('disabled',checked);if(checked){$(this).data('saved',$(this).prev().val());$(this).prev().val($(this).data('disabled'));}else{$(this).prev().val($(this).data('saved'))}});$(document).ajaxStart(function(){$('#ajax_spinner').show()});$(document).ajaxStop(function(){$('#ajax_spinner').hide()});$('#login_admin').click(function(){var form=$('#login_form').html();$('<div></div>').html(form).dialog({title:'Login',resizable:false,modal:true,position:['center','center'],close:function(e){$(e.target).dialog("destroy").remove();},buttons:[{text:fpbx.msg.framework.continuemsg,click:function(){$(this).find('form').trigger('submit');}},{text:fpbx.msg.framework.cancel,click:function(){$(this).dialog("destroy").remove();}}],focus:function(){$(':input',this).keyup(function(event){if(event.keyCode==13){$('.ui-dialog-buttonpane button:first').click();}})}});});$('form').submit(function(e){if(!e.isDefaultPrevented()){$('.destdropdown2').filter(':hidden').remove();}});jQuery.fn.scrollMinimal=function(smooth,offset){var cTop=this.offset().top-offset;var cHeight=this.outerHeight(true);var windowTop=$(window).scrollTop();var visibleHeight=$(window).height();if(cTop<windowTop){if(smooth){$('body').animate({'scrollTop':cTop},'slow','swing');}else{$(window).scrollTop(cTop);}}else if(cTop+cHeight>windowTop+visibleHeight){if(smooth){$('body').animate({'scrollTop':cTop-visibleHeight+cHeight},'slow','swing');}else{$(window).scrollTop(cTop-visibleHeight+cHeight);}}};});
(function(jQuery){jQuery.hotkeys={version:"0.8",specialKeys:{8:"backspace",9:"tab",10:"return",13:"return",16:"shift",17:"ctrl",18:"alt",19:"pause",20:"capslock",27:"esc",32:"space",33:"pageup",34:"pagedown",35:"end",36:"home",37:"left",38:"up",39:"right",40:"down",45:"insert",46:"del",96:"0",97:"1",98:"2",99:"3",100:"4",101:"5",102:"6",103:"7",104:"8",105:"9",106:"*",107:"+",109:"-",110:".",111:"/",112:"f1",113:"f2",114:"f3",115:"f4",116:"f5",117:"f6",118:"f7",119:"f8",120:"f9",121:"f10",122:"f11",123:"f12",144:"numlock",145:"scroll",186:";",191:"/",220:"\\",222:"'",224:"meta"},shiftNums:{"`":"~","1":"!","2":"@","3":"#","4":"$","5":"%","6":"^","7":"&","8":"*","9":"(","0":")","-":"_","=":"+",";":": ","'":"\"",",":"<",".":">","/":"?","\\":"|"}};function keyHandler(handleObj){if(typeof handleObj.data==="string"){handleObj.data={keys:handleObj.data};}
if(!handleObj.data||!handleObj.data.keys||typeof handleObj.data.keys!=="string"){return;}
var origHandler=handleObj.handler,keys=handleObj.data.keys.toLowerCase().split(" "),textAcceptingInputTypes=["text","password","number","email","url","range","date","month","week","time","datetime","datetime-local","search","color","tel"];handleObj.handler=function(event){if(this!==event.target&&(/textarea|select/i.test(event.target.nodeName)||jQuery.inArray(event.target.type,textAcceptingInputTypes)>-1)){return;}
var special=jQuery.hotkeys.specialKeys[event.keyCode],character=event.type==="keypress"&&String.fromCharCode(event.which).toLowerCase(),modif="",possible={};if(event.altKey&&special!=="alt"){modif+="alt+";}
if(event.ctrlKey&&special!=="ctrl"){modif+="ctrl+";}
if(event.metaKey&&!event.ctrlKey&&special!=="meta"){modif+="meta+";}
if(event.shiftKey&&special!=="shift"){modif+="shift+";}
if(special){possible[modif+special]=true;}
if(character){possible[modif+character]=true;possible[modif+jQuery.hotkeys.shiftNums[character]]=true;if(modif==="shift+"){possible[jQuery.hotkeys.shiftNums[character]]=true;}}
for(var i=0,l=keys.length;i<l;i++){if(possible[keys[i]]){return origHandler.apply(this,arguments);}}};}
jQuery.each(["keydown","keyup","keypress"],function(){jQuery.event.special[this]={add:keyHandler};});})(this.jQuery);
(function($){$.fn.toggleVal=function(theOptions){if(!theOptions||typeof theOptions=='object'){theOptions=$.extend({},$.fn.toggleVal.defaults,theOptions);}
else if(typeof theOptions=='string'&&theOptions.toLowerCase()=='destroy'){var destroy=true;}
return this.each(function(){if(destroy){$(this).unbind('focus.toggleval').unbind('blur.toggleval').removeData('defText');return false;}
var defText='';switch(theOptions.populateFrom){case'title':if($(this).attr('title')){defText=$(this).attr('title');$(this).val(defText);}
break;case'label':if($(this).attr('id')){defText=$('label[for="'+$(this).attr('id')+'"]').text();$(this).val(defText);}
break;case'custom':defText=theOptions.text;$(this).val(defText);break;default:defText=$(this).val();}
$(this).addClass('toggleval').data('defText',defText);if(theOptions.removeLabels==true&&$(this).attr('id')){$('label[for="'+$(this).attr('id')+'"]').remove();}
$(this).bind('focus.toggleval',function(){if($(this).val()==$(this).data('defText')){$(this).val('');}
$(this).addClass(theOptions.focusClass);}).bind('blur.toggleval',function(){if($(this).val()==''&&!theOptions.sticky){$(this).val($(this).data('defText'));}
$(this).removeClass(theOptions.focusClass);if($(this).val()!=''&&$(this).val()!=$(this).data('defText')){$(this).addClass(theOptions.changedClass);}
else{$(this).removeClass(theOptions.changedClass);}});});};$.fn.toggleVal.defaults={focusClass:'tv-focused',changedClass:'tv-changed',populateFrom:'default',text:null,removeLabels:false,sticky:false};$.extend($.expr[':'],{toggleval:function(elem){return $(elem).data('defText')||false;},changed:function(elem){if($(elem).data('defText')&&$(elem).val()!=$(elem).data('defText')){return true;}
return false;}});})(jQuery);
(function($){}(jQuery));(function($){$.widget("ui.menubar",{_create:function(){var self=this;this.element.children("button, a").next("ul").each(function(i,elm){$(elm).flyoutmenu({select:self.options.select,input:$(elm).prev()}).hide().addClass("ui-menu-flyout");});this.element.children("button, a").each(function(i,elm){$(elm).click(function(event){$(document).find(".ui-menu-flyout").hide();if($(this).next().is("ul")){$(this).next().flyoutmenu("show");$(this).next().css({position:"absolute",top:0,left:0}).position({my:"left top",at:"left bottom",of:$(this),collision:"fit none"});}
event.stopPropagation();}).button({icons:{secondary:($(elm).next("ul").length>0)?'ui-icon-triangle-1-s':''}});});}});}(jQuery));(function($){$.widget("ui.flyoutmenu",{_create:function(){var self=this;this.active=this.element;this.activeItem=this.element.children("li").first();this.element.find("ul").addClass("ui-menu-flyout").hide().prev("a").prepend('<span class="ui-icon ui-icon-carat-1-e"></span>');this.element.find("ul").addBack().menu({input:(!this.options.input)?$():this.options.input,select:this.options.select,focus:function(event,ui){self.active=ui.item.parent();self.activeItem=ui.item;ui.item.parent().find("ul").hide();var nested=$(">ul",ui.item);if(nested.length&&/^mouse/.test(event.originalEvent.type)){self._open(nested);}}}).keydown(function(event){if(self.element.is(":hidden"))
return;event.stopPropagation();switch(event.keyCode){case $.ui.keyCode.PAGE_UP:self.pageup(event);break;case $.ui.keyCode.PAGE_DOWN:self.pagedown(event);break;case $.ui.keyCode.UP:self.up(event);break;case $.ui.keyCode.LEFT:self.left(event);break;case $.ui.keyCode.RIGHT:self.right(event);break;case $.ui.keyCode.DOWN:self.down(event);break;case $.ui.keyCode.ENTER:case $.ui.keyCode.TAB:self._select(event);event.preventDefault();break;case $.ui.keyCode.ESCAPE:self.hide();break;default:clearTimeout(self.filterTimer);var prev=self.previousFilter||"";var character=String.fromCharCode(event.keyCode);var skip=false;if(character==prev){skip=true;}else{character=prev+character;}
var match=self.activeItem.parent("ul").children("li").filter(function(){return new RegExp("^"+character,"i").test($("a",this).text());});var match=skip&&match.index(self.active.next())!=-1?match.next():match;if(!match.length){character=String.fromCharCode(event.keyCode);match=self.widget().children("li").filter(function(){return new RegExp("^"+character,"i").test($(this).text());});}
if(match.length){self.activate(event,match);if(match.length>1){self.previousFilter=character;self.filterTimer=setTimeout(function(){delete self.previousFilter;},1000);}else{delete self.previousFilter;}}else{delete self.previousFilter;}}});},_open:function(submenu){$(document).find(".ui-menu-flyout").not(submenu.parents()).hide();submenu.show().css({top:0,left:0}).position({my:"left top",at:"right top",of:this.activeItem,collision:"fit none"});$(document).one("click",function(){$(document).find(".ui-menu-flyout").hide();})},_select:function(event){this.activeItem.parent().data("menu").select(event);$(document).find(".ui-menu-flyout").hide();activate(event,self.element.children("li").first());},left:function(event){this.activate(event,this.activeItem.parents("li").first());},right:function(event){this.activate(event,this.activeItem.children("ul").children("li").first());},up:function(event){if(this.activeItem.prev("li").length>0){this.activate(event,this.activeItem.prev("li"));}else{this.activate(event,this.activeItem.parent("ul").children("li:last"));}},down:function(event){if(this.activeItem.next("li").length>0){this.activate(event,this.activeItem.next("li"));}else{this.activate(event,this.activeItem.parent("ul").children("li:first"));}},pageup:function(event){if(this.activeItem.prev("li").length>0){this.activate(event,this.activeItem.parent("ul").children("li:first"));}else{this.activate(event,this.activeItem.parent("ul").children("li:last"));}},pagedown:function(event){if(this.activeItem.next("li").length>0){this.activate(event,this.activeItem.parent("ul").children("li:last"));}else{this.activate(event,this.activeItem.parent("ul").children("li:first"));}},activate:function(event,item){if(item){item.parent().data("menu").widget().show();item.parent().data("menu").activate(event,item);}
this.activeItem=item;this.active=item.parent("ul");},show:function(){this.active=this.element;this.element.show();if(this.element.hasClass("ui-menu-flyout")){$(document).one("click",function(){$(document).find(".ui-menu-flyout").hide();})
this.element.one("mouseleave",function(){$(document).find(".ui-menu-flyout").hide();})}},hide:function(){this.activeItem=this.element.children("li").first();this.element.find("ul").addBack().menu("deactivate").hide();}});}(jQuery));
function tabberObj(argsObj)
{var arg;this.div=null;this.classMain="tabber";this.classMainLive="tabberlive";this.classTab="tabbertab";this.classTabDefault="tabbertabdefault";this.classNav="tabbernav";this.classTabHide="tabbertabhide";this.classNavActive="tabberactive";this.titleElements=['h2','h3','h4','h5','h6'];this.titleElementsStripHTML=true;this.removeTitle=true;this.addLinkId=false;this.linkIdFormat='<tabberid>nav<tabnumberone>';for(arg in argsObj){this[arg]=argsObj[arg];}
this.REclassMain=new RegExp('\\b'+this.classMain+'\\b','gi');this.REclassMainLive=new RegExp('\\b'+this.classMainLive+'\\b','gi');this.REclassTab=new RegExp('\\b'+this.classTab+'\\b','gi');this.REclassTabDefault=new RegExp('\\b'+this.classTabDefault+'\\b','gi');this.REclassTabHide=new RegExp('\\b'+this.classTabHide+'\\b','gi');this.tabs=new Array();if(this.div){this.init(this.div);this.div=null;}}
tabberObj.prototype.init=function(e)
{var
childNodes,i,i2,t,defaultTab=0,DOM_ul,DOM_li,DOM_a,aId,headingElement;if(!document.getElementsByTagName){return false;}
if(e.id){this.id=e.id;}
this.tabs.length=0;childNodes=e.childNodes;for(i=0;i<childNodes.length;i++){if(childNodes[i].className&&childNodes[i].className.match(this.REclassTab)){t=new Object();t.div=childNodes[i];this.tabs[this.tabs.length]=t;if(childNodes[i].className.match(this.REclassTabDefault)){defaultTab=this.tabs.length-1;}}}
DOM_ul=document.createElement("ul");DOM_ul.className=this.classNav;for(i=0;i<this.tabs.length;i++){t=this.tabs[i];t.headingText=t.div.title;if(this.removeTitle){t.div.title='';}
if(!t.headingText){for(i2=0;i2<this.titleElements.length;i2++){headingElement=t.div.getElementsByTagName(this.titleElements[i2])[0];if(headingElement){t.headingText=headingElement.innerHTML;if(this.titleElementsStripHTML){t.headingText.replace(/<br>/gi," ");t.headingText=t.headingText.replace(/<[^>]+>/g,"");}
break;}}}
if(!t.headingText){t.headingText=i+1;}
DOM_li=document.createElement("li");t.li=DOM_li;DOM_a=document.createElement("a");DOM_a.appendChild(document.createTextNode(t.headingText));DOM_a.href="javascript:void(null);";DOM_a.title=t.headingText;DOM_a.onclick=this.navClick;DOM_a.tabber=this;DOM_a.tabberIndex=i;if(this.addLinkId&&this.linkIdFormat){aId=this.linkIdFormat;aId=aId.replace(/<tabberid>/gi,this.id);aId=aId.replace(/<tabnumberzero>/gi,i);aId=aId.replace(/<tabnumberone>/gi,i+1);aId=aId.replace(/<tabtitle>/gi,t.headingText.replace(/[^a-zA-Z0-9\-]/gi,''));DOM_a.id=aId;}
DOM_li.appendChild(DOM_a);DOM_ul.appendChild(DOM_li);}
e.insertBefore(DOM_ul,e.firstChild);e.className=e.className.replace(this.REclassMain,this.classMainLive);this.tabShow(defaultTab);if(typeof this.onLoad=='function'){this.onLoad({tabber:this});}
return this;};tabberObj.prototype.navClick=function(event)
{var
rVal,a,self,tabberIndex,onClickArgs;a=this;if(!a.tabber){return false;}
self=a.tabber;tabberIndex=a.tabberIndex;a.blur();if(typeof self.onClick=='function'){onClickArgs={'tabber':self,'index':tabberIndex,'event':event};if(!event){onClickArgs.event=window.event;}
rVal=self.onClick(onClickArgs);if(rVal===false){return false;}}
self.tabShow(tabberIndex);return false;};tabberObj.prototype.tabHideAll=function()
{var i;for(i=0;i<this.tabs.length;i++){this.tabHide(i);}};tabberObj.prototype.tabHide=function(tabberIndex)
{var div;if(!this.tabs[tabberIndex]){return false;}
div=this.tabs[tabberIndex].div;if(!div.className.match(this.REclassTabHide)){div.className+=' '+this.classTabHide;}
this.navClearActive(tabberIndex);return this;};tabberObj.prototype.tabShow=function(tabberIndex)
{var div;if(!this.tabs[tabberIndex]){return false;}
this.tabHideAll();div=this.tabs[tabberIndex].div;div.className=div.className.replace(this.REclassTabHide,'');this.navSetActive(tabberIndex);if(typeof this.onTabDisplay=='function'){this.onTabDisplay({'tabber':this,'index':tabberIndex});}
return this;};tabberObj.prototype.navSetActive=function(tabberIndex)
{this.tabs[tabberIndex].li.className=this.classNavActive;return this;};tabberObj.prototype.navClearActive=function(tabberIndex)
{this.tabs[tabberIndex].li.className='';return this;};function tabberAutomatic(tabberArgs)
{var
tempObj,divs,i;if(!tabberArgs){tabberArgs={};}
tempObj=new tabberObj(tabberArgs);divs=document.getElementsByTagName("div");for(i=0;i<divs.length;i++){if(divs[i].className&&divs[i].className.match(tempObj.REclassMain)){tabberArgs.div=divs[i];divs[i].tabber=new tabberObj(tabberArgs);}}
return this;}
function tabberAutomaticOnLoad(tabberArgs)
{var oldOnLoad;if(!tabberArgs){tabberArgs={};}
oldOnLoad=window.onload;if(typeof window.onload!='function'){window.onload=function(){tabberAutomatic(tabberArgs);};}else{window.onload=function(){oldOnLoad();tabberAutomatic(tabberArgs);};}}
if(typeof tabberOptions=='undefined'){tabberAutomaticOnLoad();}else{if(!tabberOptions['manualStartup']){tabberAutomaticOnLoad(tabberOptions);}}
(function(){var oXMLHttpRequest=window.XMLHttpRequest;var bGecko=!!window.controllers;var bIE=!!window.document.namespaces;var bIE7=bIE&&window.navigator.userAgent.match(/MSIE 7.0/);function fXMLHttpRequest(){if(!window.XMLHttpRequest||bIE7){this._object=new window.ActiveXObject("Microsoft.XMLHTTP");}
else if(window.XMLHttpRequest.isNormalizedObject){this._object=new oXMLHttpRequest();}
else{this._object=new window.XMLHttpRequest();}
this._listeners=[];}
function cXMLHttpRequest(){return new fXMLHttpRequest;}
cXMLHttpRequest.prototype=fXMLHttpRequest.prototype;if(bGecko&&oXMLHttpRequest.wrapped){cXMLHttpRequest.wrapped=oXMLHttpRequest.wrapped;}
cXMLHttpRequest.isNormalizedObject=true;cXMLHttpRequest.UNSENT=0;cXMLHttpRequest.OPENED=1;cXMLHttpRequest.HEADERS_RECEIVED=2;cXMLHttpRequest.LOADING=3;cXMLHttpRequest.DONE=4;cXMLHttpRequest.prototype.UNSENT=cXMLHttpRequest.UNSENT;cXMLHttpRequest.prototype.OPENED=cXMLHttpRequest.OPENED;cXMLHttpRequest.prototype.HEADERS_RECEIVED=cXMLHttpRequest.HEADERS_RECEIVED;cXMLHttpRequest.prototype.LOADING=cXMLHttpRequest.LOADING;cXMLHttpRequest.prototype.DONE=cXMLHttpRequest.DONE;cXMLHttpRequest.prototype.readyState=cXMLHttpRequest.UNSENT;cXMLHttpRequest.prototype.responseText='';cXMLHttpRequest.prototype.responseXML=null;cXMLHttpRequest.prototype.status=0;cXMLHttpRequest.prototype.statusText='';cXMLHttpRequest.prototype.priority="NORMAL";cXMLHttpRequest.prototype.onreadystatechange=null;cXMLHttpRequest.onreadystatechange=null;cXMLHttpRequest.onopen=null;cXMLHttpRequest.onsend=null;cXMLHttpRequest.onabort=null;cXMLHttpRequest.prototype.open=function(sMethod,sUrl,bAsync,sUser,sPassword){var sLowerCaseMethod=sMethod.toLowerCase();if(sLowerCaseMethod=="connect"||sLowerCaseMethod=="trace"||sLowerCaseMethod=="track"){throw new Error(18);}
delete this._headers;if(arguments.length<3){bAsync=true;}
this._async=bAsync;var oRequest=this;var nState=this.readyState;var fOnUnload=null;if(bIE&&bAsync){fOnUnload=function(){if(nState!=cXMLHttpRequest.DONE){fCleanTransport(oRequest);oRequest.abort();}};window.attachEvent("onunload",fOnUnload);}
if(cXMLHttpRequest.onopen){cXMLHttpRequest.onopen.apply(this,arguments);}
if(arguments.length>4){this._object.open(sMethod,sUrl,bAsync,sUser,sPassword);}else if(arguments.length>3){this._object.open(sMethod,sUrl,bAsync,sUser);}else{this._object.open(sMethod,sUrl,bAsync);}
this.readyState=cXMLHttpRequest.OPENED;fReadyStateChange(this);this._object.onreadystatechange=function(){if(bGecko&&!bAsync){return;}
oRequest.readyState=oRequest._object.readyState;fSynchronizeValues(oRequest);if(oRequest._aborted){oRequest.readyState=cXMLHttpRequest.UNSENT;return;}
if(oRequest.readyState==cXMLHttpRequest.DONE){delete oRequest._data;fCleanTransport(oRequest);if(bIE&&bAsync){window.detachEvent("onunload",fOnUnload);}
if(nState!=oRequest.readyState){fReadyStateChange(oRequest);}
nState=oRequest.readyState;}};};cXMLHttpRequest.prototype.send=function(vData){if(cXMLHttpRequest.onsend){cXMLHttpRequest.onsend.apply(this,arguments);}
if(!arguments.length){vData=null;}
if(vData&&vData.nodeType){vData=window.XMLSerializer?new window.XMLSerializer().serializeToString(vData):vData.xml;if(!this._headers["Content-Type"]){this._object.setRequestHeader("Content-Type","application/xml");}}
this._data=vData;fXMLHttpRequest_send(this);};cXMLHttpRequest.prototype.abort=function(){if(cXMLHttpRequest.onabort){cXMLHttpRequest.onabort.apply(this,arguments);}
if(this.readyState>cXMLHttpRequest.UNSENT){this._aborted=true;}
this._object.abort();fCleanTransport(this);this.readyState=cXMLHttpRequest.UNSENT;delete this._data;};cXMLHttpRequest.prototype.getAllResponseHeaders=function(){return this._object.getAllResponseHeaders();};cXMLHttpRequest.prototype.getResponseHeader=function(sName){return this._object.getResponseHeader(sName);};cXMLHttpRequest.prototype.setRequestHeader=function(sName,sValue){if(!this._headers){this._headers={};}
this._headers[sName]=sValue;return this._object.setRequestHeader(sName,sValue);};cXMLHttpRequest.prototype.addEventListener=function(sName,fHandler,bUseCapture){for(var nIndex=0,oListener;oListener=this._listeners[nIndex];nIndex++){if(oListener[0]==sName&&oListener[1]==fHandler&&oListener[2]==bUseCapture){return;}}
this._listeners.push([sName,fHandler,bUseCapture]);};cXMLHttpRequest.prototype.removeEventListener=function(sName,fHandler,bUseCapture){for(var nIndex=0,oListener;oListener=this._listeners[nIndex];nIndex++){if(oListener[0]==sName&&oListener[1]==fHandler&&oListener[2]==bUseCapture){break;}}
if(oListener){this._listeners.splice(nIndex,1);}};cXMLHttpRequest.prototype.dispatchEvent=function(oEvent){var oEventPseudo={'type':oEvent.type,'target':this,'currentTarget':this,'eventPhase':2,'bubbles':oEvent.bubbles,'cancelable':oEvent.cancelable,'timeStamp':oEvent.timeStamp,'stopPropagation':function(){},'preventDefault':function(){},'initEvent':function(){}};if(oEventPseudo.type=="readystatechange"&&this.onreadystatechange){(this.onreadystatechange.handleEvent||this.onreadystatechange).apply(this,[oEventPseudo]);}
for(var nIndex=0,oListener;oListener=this._listeners[nIndex];nIndex++){if(oListener[0]==oEventPseudo.type&&!oListener[2]){(oListener[1].handleEvent||oListener[1]).apply(this,[oEventPseudo]);}}};cXMLHttpRequest.prototype.toString=function(){return'['+"object"+' '+"XMLHttpRequest"+']';};cXMLHttpRequest.toString=function(){return'['+"XMLHttpRequest"+']';};function fXMLHttpRequest_send(oRequest){oRequest._object.send(oRequest._data);if(bGecko&&!oRequest._async){oRequest.readyState=cXMLHttpRequest.OPENED;fSynchronizeValues(oRequest);while(oRequest.readyState<cXMLHttpRequest.DONE){oRequest.readyState++;fReadyStateChange(oRequest);if(oRequest._aborted){return;}}}}
function fReadyStateChange(oRequest){if(cXMLHttpRequest.onreadystatechange){cXMLHttpRequest.onreadystatechange.apply(oRequest);}
oRequest.dispatchEvent({'type':"readystatechange",'bubbles':false,'cancelable':false,'timeStamp':new Date().getTime()});}
function fGetDocument(oRequest){var oDocument=oRequest.responseXML;var sResponse=oRequest.responseText;if(bIE&&sResponse&&oDocument&&!oDocument.documentElement&&oRequest.getResponseHeader("Content-Type").match(/[^\/]+\/[^\+]+\+xml/)){oDocument=new window.ActiveXObject("Microsoft.XMLDOM");oDocument.async=false;oDocument.validateOnParse=false;oDocument.loadXML(sResponse);}
if(oDocument){if((bIE&&oDocument.parseError!=0)||!oDocument.documentElement||(oDocument.documentElement&&oDocument.documentElement.tagName=="parsererror")){return null;}}
return oDocument;}
function fSynchronizeValues(oRequest){try{oRequest.responseText=oRequest._object.responseText;}catch(e){}
try{oRequest.responseXML=fGetDocument(oRequest._object);}catch(e){}
try{oRequest.status=oRequest._object.status;}catch(e){}
try{oRequest.statusText=oRequest._object.statusText;}catch(e){}}
function fCleanTransport(oRequest){oRequest._object.onreadystatechange=new window.Function;}
if(!window.Function.prototype.apply){window.Function.prototype.apply=function(oRequest,oArguments){if(!oArguments){oArguments=[];}
oRequest.__func=this;oRequest.__func(oArguments[0],oArguments[1],oArguments[2],oArguments[3],oArguments[4]);delete oRequest.__func;};}
window.XMLHttpRequest=cXMLHttpRequest;})();
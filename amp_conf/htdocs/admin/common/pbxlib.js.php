<?php 
header('Content-type: text/javascript');
header('Cache-Control: public, max-age=3153600');
header('Expires: ' . date('r', strtotime('+1 year')));
header('Last-Modified: ' . date('r', strtotime('-1 year')));
ob_start('ob_gzhandler');
?>

jQuery.cookie=function(name,value,options){if(typeof value!='undefined'){options=options||{};if(value===null){value='';options.expires=-1;}
var expires='';if(options.expires&&(typeof options.expires=='number'||options.expires.toUTCString)){var date;if(typeof options.expires=='number'){date=new Date();date.setTime(date.getTime()+(options.expires*24*60*60*1000));}else{date=options.expires;}
expires='; expires='+date.toUTCString();}
var path=options.path?'; path='+(options.path):'';var domain=options.domain?'; domain='+(options.domain):'';var secure=options.secure?'; secure':'';document.cookie=[name,'=',encodeURIComponent(value),expires,path,domain,secure].join('');}else{var cookieValue=null;if(document.cookie&&document.cookie!=''){var cookies=document.cookie.split(';');for(var i=0;i<cookies.length;i++){var cookie=jQuery.trim(cookies[i]);if(cookie.substring(0,name.length+1)==(name+'=')){cookieValue=decodeURIComponent(cookie.substring(name.length+1));break;}}}
return cookieValue;}};
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
function isDigit(c){return new RegExp(/[0-9]/).test(c);}
function isLetter(c){return new RegExp(/[a-zA-Z'\&\(\)\-\/]/).test(c);}
function isURLChar(c){return new RegExp(/[a-zA-Z=:,%#\.\-\/\?\&]/).test(c);}
function isCallerIDChar(c){return new RegExp(/[ a-zA-Z0-9:_,-<>\(\)\"&@\.\+]/).test(c);}
function isDialpatternChar(c){return new RegExp(/[-0-9\[\]\+\.\|ZzXxNn\*\#_!\/]/).test(c);}
function isDialruleChar(c){return new RegExp(/[0-9\[\]\+\.\|ZzXxNnWw\*\#\_\/]/).test(c);}
function isDialDigitChar(c){return new RegExp(/[0-9\*#]/).test(c);}
function isFilenameChar(c){return new RegExp(/[-0-9a-zA-Z\_]/).test(c);}
function bind_dests_double_selects(){$('.destdropdown').unbind().bind('blur click change keypress',function(){var name=$(this).attr('name');var id=name.replace('goto','');var dest=$(this).val();$('[name$='+id+'].destdropdown2').hide();$('[name='+dest+id+'].destdropdown2').show();});$('.destdropdown').bind('change',function(){if($(this).find('option:selected').val()=='Error'){$(this).css('background-color','red');}else{$(this).css('background-color','white');}});}
$(document).ready(function(){bind_dests_double_selects();$("a.info").hover(function(){var pos=$(this).offset();var left=(200-pos.left)+"px";$(this).find("span").css("left",left).stop(true,true).delay(500).animate({opacity:"show"},750);},function(){$(this).find("span").stop(true,true).animate({opacity:"hide"},"fast");});$('#nav').tabs({cookie:{expires:30}});$(".category-header").each(function(){if($.cookie(this.id)=='collapsed'){$(".id-"+this.id).hide();$(this).removeClass("toggle-minus").addClass("toggle-plus")
$.cookie(this.id,'collapsed',{expires:365});}else{$(".id-"+this.id).show();$(this).removeClass("toggle-plus").addClass("toggle-minus")
$.cookie(this.id,'expanded',{expires:365});}});$(".category-header").click(function(){if($.cookie(this.id)=='expanded'){$(".id-"+this.id).slideUp();$.cookie(this.id,'collapsed',{expires:365});$(this).removeClass("toggle-minus").addClass("toggle-plus")}else{$(".id-"+this.id).slideDown();$.cookie(this.id,'expanded',{expires:365});$(this).removeClass("toggle-plus").addClass("toggle-minus")}});$('.guielToggle').click(function(){var txt=$(this).find('.guielToggleBut');var el=$(this).data('toggleClass')
switch(txt.text().replace(/ /g,'')){case'-':txt.text('+ ');$('.'+el).hide()
break;case'+':txt.text('-  ');$('.'+el).show();break;}})});
var realHeight;function freepbx_modal_show(divID,callback){obj=$('#'+divID);navHeight=$('#nav').height();wrapperHeight=$('#wrapper').height()
realHeight=$('#header').height()+(wrapperHeight>navHeight?wrapperHeight:navHeight);if($(window).height()>realHeight){realHeight=$(window).height();}
obj.css({top:(Math.floor($(window).height()/2-($('#'+divID).height()/2))),left:(Math.floor($(window).width()/2-($('#'+divID).width()/2)))});if($.browser.msie){hideSelects(true);}
$.dimScreen(50,0.7,function(){if($.browser.safari){obj[0].style.display="block";}
obj.fadeIn(50);if(callback){callback();}});}
function freepbx_modal_hide(divID,callback){$('#__dimScreen').css('cursor','wait');$('#'+divID).fadeOut(50,callback);}
function freepbx_modal_close(divID,callback){obj=$('#'+divID);if(obj.css('display')!='block'){return;}
obj.fadeOut(50,function(){$.dimScreenStop(50);if($.browser.msie){hideSelects(false);}
if(callback){callback();}});}
jQuery.extend({dimScreen:function(speed,opacity,callback){if(jQuery('#__dimScreen').size()>0)return;if(typeof speed=='function'){callback=speed;speed=null;}
if(typeof opacity=='function'){callback=opacity;opacity=null;}
if(speed<1){var placeholder=opacity;opacity=speed;speed=placeholder;}
if(opacity>=1){var placeholder=speed;speed=opacity;opacity=placeholder;}
dimHeight=(realHeight>0)?realHeight:$(window).height();speed=(speed>0)?speed:50;opacity=(opacity>0)?opacity:0.5;return jQuery('<div></div>').attr({id:'__dimScreen',fade_opacity:opacity,speed:speed}).css({background:'#000',height:dimHeight+'px',left:'0px',opacity:0,position:'absolute',top:'0px',width:$(window).width()+'px',zIndex:999}).appendTo(document.body).fadeTo(speed,0.7,callback);},dimScreenStop:function(callback){var x=jQuery('#__dimScreen');var opacity=x.attr('fade_opacity');var speed=x.attr('speed');x.fadeOut(speed,function(){x.remove();if(typeof callback=='function')callback();});}});
(function(jQuery){jQuery.hotkeys={version:"0.8",specialKeys:{8:"backspace",9:"tab",13:"return",16:"shift",17:"ctrl",18:"alt",19:"pause",20:"capslock",27:"esc",32:"space",33:"pageup",34:"pagedown",35:"end",36:"home",37:"left",38:"up",39:"right",40:"down",45:"insert",46:"del",96:"0",97:"1",98:"2",99:"3",100:"4",101:"5",102:"6",103:"7",104:"8",105:"9",106:"*",107:"+",109:"-",110:".",111:"/",112:"f1",113:"f2",114:"f3",115:"f4",116:"f5",117:"f6",118:"f7",119:"f8",120:"f9",121:"f10",122:"f11",123:"f12",144:"numlock",145:"scroll",191:"/",224:"meta"},shiftNums:{"`":"~","1":"!","2":"@","3":"#","4":"$","5":"%","6":"^","7":"&","8":"*","9":"(","0":")","-":"_","=":"+",";":": ","'":"\"",",":"<",".":">","/":"?","\\":"|"}};function keyHandler(handleObj){if(typeof handleObj.data!=="string"){return;}
var origHandler=handleObj.handler,keys=handleObj.data.toLowerCase().split(" ");handleObj.handler=function(event){if(this!==event.target&&(/textarea|select/i.test(event.target.nodeName)||event.target.type==="text")){return;}
var special=event.type!=="keypress"&&jQuery.hotkeys.specialKeys[event.which],character=String.fromCharCode(event.which).toLowerCase(),key,modif="",possible={};if(event.altKey&&special!=="alt"){modif+="alt+";}
if(event.ctrlKey&&special!=="ctrl"){modif+="ctrl+";}
if(event.metaKey&&!event.ctrlKey&&special!=="meta"){modif+="meta+";}
if(event.shiftKey&&special!=="shift"){modif+="shift+";}
if(special){possible[modif+special]=true;}else{possible[modif+character]=true;possible[modif+jQuery.hotkeys.shiftNums[character]]=true;if(modif==="shift+"){possible[jQuery.hotkeys.shiftNums[character]]=true;}}
for(var i=0,l=keys.length;i<l;i++){if(possible[keys[i]]){return origHandler.apply(this,arguments);}}};}
jQuery.each(["keydown","keyup","keypress"],function(){jQuery.event.special[this]={add:keyHandler};});})(jQuery);
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
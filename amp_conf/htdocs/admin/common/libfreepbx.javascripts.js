
Is_DOM=(document.getElementById)?true:false;Is_NS4=(document.layers)?true:false;Is_IE=(document.all)?true:false;var detect=navigator.userAgent.toLowerCase();if(checkIt('konqueror'))
Is_IE=false;function checkIt(string)
{place=detect.indexOf(string)+1;thestring=string;return place;}
Is_IE4=Is_IE&&!Is_DOM;Is_Mac=(navigator.appVersion.indexOf("Mac")!=-1);Is_IE4M=Is_IE4&&Is_Mac;function ie_getElementsByTagName(str){if(str=="*")
return document.all
else
return document.all.tags(str)}
if(document.all)
document.getElementsByTagName=ie_getElementsByTagName
function menu_popup(mylink,windowname)
{if(!window.focus)return true;var href;if(typeof(mylink)=='string')
href=mylink;else
href=mylink.href;window.open(href,windowname,'scrollbars=yes, resizable');return false;}
function decision(message,url){if(confirm(message))
location.href=url;}
function hideSelects(b)
{var allelems=document.all.tags('SELECT');if(allelems!=null)
{var i;for(i=0;i<allelems.length;i++)
allelems[i].style.visibility=(b?'hidden':'inherit');}}
function doHideSelects(event)
{hideSelects(true);}
function doShowSelects(event)
{hideSelects(false);}
function setAllInfoToHideSelects()
{if(Is_IE)
{var allelems=document.all.tags('A');if(allelems!=null)
{var i,elem;for(i=0;elem=allelems[i];i++)
{if(elem.className=='info'&&elem.onmouseover==null&&elem.onmouseout==null)
{elem.onmouseover=doHideSelects;elem.onmouseout=doShowSelects;}}}}}
function updateInfoTargets(){links=document.getElementsByTagName('a');var i,elem;for(i=0;elem=links[i];i++){if(elem.className=='info'&&elem.href.charAt(elem.href.length-1)=='#'){elem.href='javascript:void(null)';}}}
function setDestinations(theForm,numForms){for(var formNum=0;formNum<numForms;formNum++){var whichitem=0;while(whichitem<theForm['goto_indicate'+formNum].length){if(theForm['goto_indicate'+formNum][whichitem].checked){theForm['goto'+formNum].value=theForm['goto_indicate'+formNum][whichitem].value;}
whichitem++;}}}
function body_loaded(){setAllInfoToHideSelects();updateInfoTargets();}
function amp_apply_changes(){if(confirm("About to reload backend configuration. This applies all outstanding changes to the live server.")){var newlocation;if(document.all){window.event.returnValue=false;newlocation=window.location.href;}else{newlocation=location.href;}
if(location.href.indexOf('?')==-1){newlocation=location+'?clk_reload=true';}else{newlocation=location+'&clk_reload=true';}
location=newlocation.replace(/action/,"var-disabled");}}
var whitespace=" \t\n\r";var decimalPointDelimiter=".";var defaultEmptyOK=false;function validateDestinations(theForm,numForms,bRequired){var valid=true;for(var formNum=0;formNum<numForms&&valid==true;formNum++){valid=validateSingleDestination(theForm,formNum,bRequired);}
return valid;}
function warnInvalid(theField,s){theField.focus();theField.select();alert(s);return false;}
function isEmail(s){if(isEmpty(s))
if(isEmail.arguments.length==1)return defaultEmptyOK;else return(isEmail.arguments[1]==true);if(isWhitespace(s))return false;var i=1;var sLength=s.length;while((i<sLength)&&(s.charAt(i)!="@")){i++;}
if((i>=sLength)||(s.charAt(i)!="@"))return false;else i+=2;if(i>sLength)return false;return true;}
function isAlphabetic(s){var i;if(isEmpty(s))
if(isAlphabetic.arguments.length==1)return defaultEmptyOK;else return(isAlphabetic.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!isLetter(c))
return false;}
return true;}
function isAlphanumeric(s){var i;if(isEmpty(s))
if(isAlphanumeric.arguments.length==1)return defaultEmptyOK;else return(isAlphanumeric.arguments[1]==true);for(i=0;i<s.length;i++){var c=s.charAt(i);if(!(isLetter(c)||isDigit(c)))
return false;}
return true;}
function isInteger(s){var i;if(isEmpty(s))
if(isInteger.arguments.length==1)return defaultEmptyOK;else return(isInteger.arguments[1]==true);for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isDigit(c))return false;}
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
function isEmpty(s)
{return((s==null)||(s.length==0));}
function isWhitespace(s)
{var i;if(isEmpty(s))return true;for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(whitespace.indexOf(c)==-1)return false;}
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
if(isDialIdentifier.arguments.length==1)return defaultEmptyOK;else return(isDialIdentifier.arguments[1]==true);for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isDialDigitChar(c)&&(c!="w")&&(c!="W"))return false;}
return true;}
function isDialDigits(s)
{var i;if(isEmpty(s))
if(isDialDigits.arguments.length==1)return defaultEmptyOK;else return(isDialDigits.arguments[1]==true);for(i=0;i<s.length;i++)
{var c=s.charAt(i);if(!isDialDigitChar(c))return false;}
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
function isDigit(c)
{return((c>="0")&&(c<="9"))}
function isLetter(c)
{return(((c>="a")&&(c<="z"))||((c>="A")&&(c<="Z"))||(c==" ")||(c=="&")||(c=="'")||(c=="(")||(c==")")||(c=="-")||(c=="/"))}
function isURLChar(c)
{return(((c>="a")&&(c<="z"))||((c>="A")&&(c<="Z"))||(c==":")||(c==",")||(c==".")||(c=="%")||(c=="#")||(c=="-")||(c=="/")||(c=="?")||(c=="&")||(c=="="))}
function isCallerIDChar(c)
{return(((c>="a")&&(c<="z"))||((c>="A")&&(c<="Z"))||((c>="0")&&(c<="9"))||(c==":")||(c=="_")||(c=="-")||(c=="<")||(c==">")||(c=="(")||(c==")")||(c==" ")||(c=="\"")||(c=="&")||(c=="@")||(c=="."))}
function isDialpatternChar(c)
{return(((c>="0")&&(c<="9"))||(c=="[")||(c=="]")||(c=="-")||(c=="+")||(c==".")||(c=="|")||(c=="Z"||c=="z")||(c=="X"||c=="x")||(c=="N"||c=="n")||(c=="*")||(c=="#")||(c=="_")||(c=="!"))}
function isDialruleChar(c)
{return(((c>="0")&&(c<="9"))||(c=="[")||(c=="]")||(c=="-")||(c=="+")||(c==".")||(c=="|")||(c=="Z"||c=="z")||(c=="X"||c=="x")||(c=="N"||c=="n")||(c=="*")||(c=="#")||(c=="_")||(c=="!")||(c=="w")||(c=="W"))}
function isDialDigitChar(c)
{return(((c>="0")&&(c<="9"))||(c=="*")||(c=="#"))}
function isFilenameChar(c)
{return(((c>="0")&&(c<="9"))||((c>="a")&&(c<="z"))||((c>="A")&&(c<="Z"))||(c=="_")||(c=="-"))}
if(typeof window.jQuery=="undefined"){window.undefined=window.undefined;var jQuery=function(a,c){if(window==this||!this.init)
return new jQuery(a,c);return this.init(a,c);};if(typeof $!="undefined")
jQuery._$=$;var $=jQuery;jQuery.fn=jQuery.prototype={init:function(a,c){a=a||document;if(jQuery.isFunction(a))
return new jQuery(document)[jQuery.fn.ready?"ready":"load"](a);if(typeof a=="string"){var m=/^[^<]*(<(.|\s)+>)[^>]*$/.exec(a);if(m)
a=jQuery.clean([m[1]]);else
return new jQuery(c).find(a);}
return this.setArray(a.constructor==Array&&a||(a.jquery||a.length&&a!=window&&!a.nodeType&&a[0]!=undefined&&a[0].nodeType)&&jQuery.makeArray(a)||[a]);},jquery:"1.1.3.1",size:function(){return this.length;},length:0,get:function(num){return num==undefined?jQuery.makeArray(this):this[num];},pushStack:function(a){var ret=jQuery(a);ret.prevObject=this;return ret;},setArray:function(a){this.length=0;[].push.apply(this,a);return this;},each:function(fn,args){return jQuery.each(this,fn,args);},index:function(obj){var pos=-1;this.each(function(i){if(this==obj)pos=i;});return pos;},attr:function(key,value,type){var obj=key;if(key.constructor==String)
if(value==undefined)
return this.length&&jQuery[type||"attr"](this[0],key)||undefined;else{obj={};obj[key]=value;}
return this.each(function(index){for(var prop in obj)
jQuery.attr(type?this.style:this,prop,jQuery.prop(this,obj[prop],type,index,prop));});},css:function(key,value){return this.attr(key,value,"curCSS");},text:function(e){if(typeof e=="string")
return this.empty().append(document.createTextNode(e));var t="";jQuery.each(e||this,function(){jQuery.each(this.childNodes,function(){if(this.nodeType!=8)
t+=this.nodeType!=1?this.nodeValue:jQuery.fn.text([this]);});});return t;},wrap:function(){var a,args=arguments;return this.each(function(){if(!a)
a=jQuery.clean(args,this.ownerDocument);var b=a[0].cloneNode(true);this.parentNode.insertBefore(b,this);while(b.firstChild)
b=b.firstChild;b.appendChild(this);});},append:function(){return this.domManip(arguments,true,1,function(a){this.appendChild(a);});},prepend:function(){return this.domManip(arguments,true,-1,function(a){this.insertBefore(a,this.firstChild);});},before:function(){return this.domManip(arguments,false,1,function(a){this.parentNode.insertBefore(a,this);});},after:function(){return this.domManip(arguments,false,-1,function(a){this.parentNode.insertBefore(a,this.nextSibling);});},end:function(){return this.prevObject||jQuery([]);},find:function(t){var data=jQuery.map(this,function(a){return jQuery.find(t,a);});return this.pushStack(/[^+>] [^+>]/.test(t)||t.indexOf("..")>-1?jQuery.unique(data):data);},clone:function(deep){var $this=this.add(this.find("*"));$this.each(function(){this._$events={};for(var type in this.$events)
this._$events[type]=jQuery.extend({},this.$events[type]);}).unbind();var r=this.pushStack(jQuery.map(this,function(a){return a.cloneNode(deep!=undefined?deep:true);}));$this.each(function(){var events=this._$events;for(var type in events)
for(var handler in events[type])
jQuery.event.add(this,type,events[type][handler],events[type][handler].data);this._$events=null;});return r;},filter:function(t){return this.pushStack(jQuery.isFunction(t)&&jQuery.grep(this,function(el,index){return t.apply(el,[index])})||jQuery.multiFilter(t,this));},not:function(t){return this.pushStack(t.constructor==String&&jQuery.multiFilter(t,this,true)||jQuery.grep(this,function(a){return(t.constructor==Array||t.jquery)?jQuery.inArray(a,t)<0:a!=t;}));},add:function(t){return this.pushStack(jQuery.merge(this.get(),t.constructor==String?jQuery(t).get():t.length!=undefined&&(!t.nodeName||t.nodeName=="FORM")?t:[t]));},is:function(expr){return expr?jQuery.multiFilter(expr,this).length>0:false;},val:function(val){return val==undefined?(this.length?this[0].value:null):this.attr("value",val);},html:function(val){return val==undefined?(this.length?this[0].innerHTML:null):this.empty().append(val);},domManip:function(args,table,dir,fn){var clone=this.length>1,a;return this.each(function(){if(!a){a=jQuery.clean(args,this.ownerDocument);if(dir<0)
a.reverse();}
var obj=this;if(table&&jQuery.nodeName(this,"table")&&jQuery.nodeName(a[0],"tr"))
obj=this.getElementsByTagName("tbody")[0]||this.appendChild(document.createElement("tbody"));jQuery.each(a,function(){fn.apply(obj,[clone?this.cloneNode(true):this]);});});}};jQuery.extend=jQuery.fn.extend=function(){var target=arguments[0],a=1;if(arguments.length==1){target=this;a=0;}
var prop;while((prop=arguments[a++])!=null)
for(var i in prop)target[i]=prop[i];return target;};jQuery.extend({noConflict:function(){if(jQuery._$)
$=jQuery._$;return jQuery;},isFunction:function(fn){return!!fn&&typeof fn!="string"&&!fn.nodeName&&fn.constructor!=Array&&/function/i.test(fn+"");},isXMLDoc:function(elem){return elem.tagName&&elem.ownerDocument&&!elem.ownerDocument.body;},nodeName:function(elem,name){return elem.nodeName&&elem.nodeName.toUpperCase()==name.toUpperCase();},each:function(obj,fn,args){if(obj.length==undefined)
for(var i in obj)
fn.apply(obj[i],args||[i,obj[i]]);else
for(var i=0,ol=obj.length;i<ol;i++)
if(fn.apply(obj[i],args||[i,obj[i]])===false)break;return obj;},prop:function(elem,value,type,index,prop){if(jQuery.isFunction(value))
value=value.call(elem,[index]);var exclude=/z-?index|font-?weight|opacity|zoom|line-?height/i;return value&&value.constructor==Number&&type=="curCSS"&&!exclude.test(prop)?value+"px":value;},className:{add:function(elem,c){jQuery.each(c.split(/\s+/),function(i,cur){if(!jQuery.className.has(elem.className,cur))
elem.className+=(elem.className?" ":"")+cur;});},remove:function(elem,c){elem.className=c!=undefined?jQuery.grep(elem.className.split(/\s+/),function(cur){return!jQuery.className.has(c,cur);}).join(" "):"";},has:function(t,c){return jQuery.inArray(c,(t.className||t).toString().split(/\s+/))>-1;}},swap:function(e,o,f){for(var i in o){e.style["old"+i]=e.style[i];e.style[i]=o[i];}
f.apply(e,[]);for(var i in o)
e.style[i]=e.style["old"+i];},css:function(e,p){if(p=="height"||p=="width"){var old={},oHeight,oWidth,d=["Top","Bottom","Right","Left"];jQuery.each(d,function(){old["padding"+this]=0;old["border"+this+"Width"]=0;});jQuery.swap(e,old,function(){if(jQuery(e).is(':visible')){oHeight=e.offsetHeight;oWidth=e.offsetWidth;}else{e=jQuery(e.cloneNode(true)).find(":radio").removeAttr("checked").end().css({visibility:"hidden",position:"absolute",display:"block",right:"0",left:"0"}).appendTo(e.parentNode)[0];var parPos=jQuery.css(e.parentNode,"position")||"static";if(parPos=="static")
e.parentNode.style.position="relative";oHeight=e.clientHeight;oWidth=e.clientWidth;if(parPos=="static")
e.parentNode.style.position="static";e.parentNode.removeChild(e);}});return p=="height"?oHeight:oWidth;}
return jQuery.curCSS(e,p);},curCSS:function(elem,prop,force){var ret;if(prop=="opacity"&&jQuery.browser.msie){ret=jQuery.attr(elem.style,"opacity");return ret==""?"1":ret;}
if(prop.match(/float/i))
prop=jQuery.styleFloat;if(!force&&elem.style[prop])
ret=elem.style[prop];else if(document.defaultView&&document.defaultView.getComputedStyle){if(prop.match(/float/i))
prop="float";prop=prop.replace(/([A-Z])/g,"-$1").toLowerCase();var cur=document.defaultView.getComputedStyle(elem,null);if(cur)
ret=cur.getPropertyValue(prop);else if(prop=="display")
ret="none";else
jQuery.swap(elem,{display:"block"},function(){var c=document.defaultView.getComputedStyle(this,"");ret=c&&c.getPropertyValue(prop)||"";});}else if(elem.currentStyle){var newProp=prop.replace(/\-(\w)/g,function(m,c){return c.toUpperCase();});ret=elem.currentStyle[prop]||elem.currentStyle[newProp];}
return ret;},clean:function(a,doc){var r=[];doc=doc||document;jQuery.each(a,function(i,arg){if(!arg)return;if(arg.constructor==Number)
arg=arg.toString();if(typeof arg=="string"){var s=jQuery.trim(arg).toLowerCase(),div=doc.createElement("div"),tb=[];var wrap=!s.indexOf("<opt")&&[1,"<select>","</select>"]||!s.indexOf("<leg")&&[1,"<fieldset>","</fieldset>"]||(!s.indexOf("<thead")||!s.indexOf("<tbody")||!s.indexOf("<tfoot")||!s.indexOf("<colg"))&&[1,"<table>","</table>"]||!s.indexOf("<tr")&&[2,"<table><tbody>","</tbody></table>"]||(!s.indexOf("<td")||!s.indexOf("<th"))&&[3,"<table><tbody><tr>","</tr></tbody></table>"]||!s.indexOf("<col")&&[2,"<table><colgroup>","</colgroup></table>"]||[0,"",""];div.innerHTML=wrap[1]+arg+wrap[2];while(wrap[0]--)
div=div.firstChild;if(jQuery.browser.msie){if(!s.indexOf("<table")&&s.indexOf("<tbody")<0)
tb=div.firstChild&&div.firstChild.childNodes;else if(wrap[1]=="<table>"&&s.indexOf("<tbody")<0)
tb=div.childNodes;for(var n=tb.length-1;n>=0;--n)
if(jQuery.nodeName(tb[n],"tbody")&&!tb[n].childNodes.length)
tb[n].parentNode.removeChild(tb[n]);}
arg=jQuery.makeArray(div.childNodes);}
if(0===arg.length&&(!jQuery.nodeName(arg,"form")&&!jQuery.nodeName(arg,"select")))
return;if(arg[0]==undefined||jQuery.nodeName(arg,"form")||arg.options)
r.push(arg);else
r=jQuery.merge(r,arg);});return r;},attr:function(elem,name,value){var fix=jQuery.isXMLDoc(elem)?{}:jQuery.props;if(fix[name]){if(value!=undefined)elem[fix[name]]=value;return elem[fix[name]];}else if(value==undefined&&jQuery.browser.msie&&jQuery.nodeName(elem,"form")&&(name=="action"||name=="method"))
return elem.getAttributeNode(name).nodeValue;else if(elem.tagName){if(value!=undefined)elem.setAttribute(name,value);if(jQuery.browser.msie&&/href|src/.test(name)&&!jQuery.isXMLDoc(elem))
return elem.getAttribute(name,2);return elem.getAttribute(name);}else{if(name=="opacity"&&jQuery.browser.msie){if(value!=undefined){elem.zoom=1;elem.filter=(elem.filter||"").replace(/alpha\([^)]*\)/,"")+
(parseFloat(value).toString()=="NaN"?"":"alpha(opacity="+value*100+")");}
return elem.filter?(parseFloat(elem.filter.match(/opacity=([^)]*)/)[1])/100).toString():"";}
name=name.replace(/-([a-z])/ig,function(z,b){return b.toUpperCase();});if(value!=undefined)elem[name]=value;return elem[name];}},trim:function(t){return t.replace(/^\s+|\s+$/g,"");},makeArray:function(a){var r=[];if(typeof a!="array")
for(var i=0,al=a.length;i<al;i++)
r.push(a[i]);else
r=a.slice(0);return r;},inArray:function(b,a){for(var i=0,al=a.length;i<al;i++)
if(a[i]==b)
return i;return-1;},merge:function(first,second){for(var i=0;second[i];i++)
first.push(second[i]);return first;},unique:function(first){var r=[],num=jQuery.mergeNum++;for(var i=0,fl=first.length;i<fl;i++)
if(num!=first[i].mergeNum){first[i].mergeNum=num;r.push(first[i]);}
return r;},mergeNum:0,grep:function(elems,fn,inv){if(typeof fn=="string")
fn=new Function("a","i","return "+fn);var result=[];for(var i=0,el=elems.length;i<el;i++)
if(!inv&&fn(elems[i],i)||inv&&!fn(elems[i],i))
result.push(elems[i]);return result;},map:function(elems,fn){if(typeof fn=="string")
fn=new Function("a","return "+fn);var result=[];for(var i=0,el=elems.length;i<el;i++){var val=fn(elems[i],i);if(val!==null&&val!=undefined){if(val.constructor!=Array)val=[val];result=result.concat(val);}}
return result;}});new function(){var b=navigator.userAgent.toLowerCase();jQuery.browser={version:(b.match(/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/)||[])[1],safari:/webkit/.test(b),opera:/opera/.test(b),msie:/msie/.test(b)&&!/opera/.test(b),mozilla:/mozilla/.test(b)&&!/(compatible|webkit)/.test(b)};jQuery.boxModel=!jQuery.browser.msie||document.compatMode=="CSS1Compat";jQuery.styleFloat=jQuery.browser.msie?"styleFloat":"cssFloat",jQuery.props={"for":"htmlFor","class":"className","float":jQuery.styleFloat,cssFloat:jQuery.styleFloat,styleFloat:jQuery.styleFloat,innerHTML:"innerHTML",className:"className",value:"value",disabled:"disabled",checked:"checked",readonly:"readOnly",selected:"selected",maxlength:"maxLength"};};jQuery.each({parent:"a.parentNode",parents:"jQuery.parents(a)",next:"jQuery.nth(a,2,'nextSibling')",prev:"jQuery.nth(a,2,'previousSibling')",siblings:"jQuery.sibling(a.parentNode.firstChild,a)",children:"jQuery.sibling(a.firstChild)"},function(i,n){jQuery.fn[i]=function(a){var ret=jQuery.map(this,n);if(a&&typeof a=="string")
ret=jQuery.multiFilter(a,ret);return this.pushStack(ret);};});jQuery.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after"},function(i,n){jQuery.fn[i]=function(){var a=arguments;return this.each(function(){for(var j=0,al=a.length;j<al;j++)
jQuery(a[j])[n](this);});};});jQuery.each({removeAttr:function(key){jQuery.attr(this,key,"");this.removeAttribute(key);},addClass:function(c){jQuery.className.add(this,c);},removeClass:function(c){jQuery.className.remove(this,c);},toggleClass:function(c){jQuery.className[jQuery.className.has(this,c)?"remove":"add"](this,c);},remove:function(a){if(!a||jQuery.filter(a,[this]).r.length)
this.parentNode.removeChild(this);},empty:function(){while(this.firstChild)
this.removeChild(this.firstChild);}},function(i,n){jQuery.fn[i]=function(){return this.each(n,arguments);};});jQuery.each(["eq","lt","gt","contains"],function(i,n){jQuery.fn[n]=function(num,fn){return this.filter(":"+n+"("+num+")",fn);};});jQuery.each(["height","width"],function(i,n){jQuery.fn[n]=function(h){return h==undefined?(this.length?jQuery.css(this[0],n):null):this.css(n,h.constructor==String?h:h+"px");};});jQuery.extend({expr:{"":"m[2]=='*'||jQuery.nodeName(a,m[2])","#":"a.getAttribute('id')==m[2]",":":{lt:"i<m[3]-0",gt:"i>m[3]-0",nth:"m[3]-0==i",eq:"m[3]-0==i",first:"i==0",last:"i==r.length-1",even:"i%2==0",odd:"i%2","first-child":"a.parentNode.getElementsByTagName('*')[0]==a","last-child":"jQuery.nth(a.parentNode.lastChild,1,'previousSibling')==a","only-child":"!jQuery.nth(a.parentNode.lastChild,2,'previousSibling')",parent:"a.firstChild",empty:"!a.firstChild",contains:"(a.textContent||a.innerText||'').indexOf(m[3])>=0",visible:'"hidden"!=a.type&&jQuery.css(a,"display")!="none"&&jQuery.css(a,"visibility")!="hidden"',hidden:'"hidden"==a.type||jQuery.css(a,"display")=="none"||jQuery.css(a,"visibility")=="hidden"',enabled:"!a.disabled",disabled:"a.disabled",checked:"a.checked",selected:"a.selected||jQuery.attr(a,'selected')",text:"'text'==a.type",radio:"'radio'==a.type",checkbox:"'checkbox'==a.type",file:"'file'==a.type",password:"'password'==a.type",submit:"'submit'==a.type",image:"'image'==a.type",reset:"'reset'==a.type",button:'"button"==a.type||jQuery.nodeName(a,"button")',input:"/input|select|textarea|button/i.test(a.nodeName)"},"[":"jQuery.find(m[2],a).length"},parse:[/^\[ *(@)([\w-]+) *([!*$^~=]*) *('?"?)(.*?)\4 *\]/,/^(\[)\s*(.*?(\[.*?\])?[^[]*?)\s*\]/,/^(:)([\w-]+)\("?'?(.*?(\(.*?\))?[^(]*?)"?'?\)/,new RegExp("^([:.#]*)("+
(jQuery.chars=jQuery.browser.safari&&jQuery.browser.version<"3.0.0"?"\\w":"(?:[\\w\u0128-\uFFFF*_-]|\\\\.)")+"+)")],multiFilter:function(expr,elems,not){var old,cur=[];while(expr&&expr!=old){old=expr;var f=jQuery.filter(expr,elems,not);expr=f.t.replace(/^\s*,\s*/,"");cur=not?elems=f.r:jQuery.merge(cur,f.r);}
return cur;},find:function(t,context){if(typeof t!="string")
return[t];if(context&&!context.nodeType)
context=null;context=context||document;if(!t.indexOf("//")){context=context.documentElement;t=t.substr(2,t.length);}else if(!t.indexOf("/")&&!context.ownerDocument){context=context.documentElement;t=t.substr(1,t.length);if(t.indexOf("/")>=1)
t=t.substr(t.indexOf("/"),t.length);}
var ret=[context],done=[],last;while(t&&last!=t){var r=[];last=t;t=jQuery.trim(t).replace(/^\/\//,"");var foundToken=false;var re=new RegExp("^[/>]\\s*("+jQuery.chars+"+)");var m=re.exec(t);if(m){var nodeName=m[1].toUpperCase();for(var i=0;ret[i];i++)
for(var c=ret[i].firstChild;c;c=c.nextSibling)
if(c.nodeType==1&&(nodeName=="*"||c.nodeName.toUpperCase()==nodeName.toUpperCase()))
r.push(c);ret=r;t=t.replace(re,"");if(t.indexOf(" ")==0)continue;foundToken=true;}else{re=/^((\/?\.\.)|([>\/+~]))\s*([a-z]*)/i;if((m=re.exec(t))!=null){r=[];var nodeName=m[4],mergeNum=jQuery.mergeNum++;m=m[1];for(var j=0,rl=ret.length;j<rl;j++)
if(m.indexOf("..")<0){var n=m=="~"||m=="+"?ret[j].nextSibling:ret[j].firstChild;for(;n;n=n.nextSibling)
if(n.nodeType==1){if(m=="~"&&n.mergeNum==mergeNum)break;if(!nodeName||n.nodeName.toUpperCase()==nodeName.toUpperCase()){if(m=="~")n.mergeNum=mergeNum;r.push(n);}
if(m=="+")break;}}else
r.push(ret[j].parentNode);ret=r;t=jQuery.trim(t.replace(re,""));foundToken=true;}}
if(t&&!foundToken){if(!t.indexOf(",")){if(context==ret[0])ret.shift();done=jQuery.merge(done,ret);r=ret=[context];t=" "+t.substr(1,t.length);}else{var re2=new RegExp("^("+jQuery.chars+"+)(#)("+jQuery.chars+"+)");var m=re2.exec(t);if(m){m=[0,m[2],m[3],m[1]];}else{re2=new RegExp("^([#.]?)("+jQuery.chars+"*)");m=re2.exec(t);}
m[2]=m[2].replace(/\\/g,"");var elem=ret[ret.length-1];if(m[1]=="#"&&elem&&elem.getElementById){var oid=elem.getElementById(m[2]);if((jQuery.browser.msie||jQuery.browser.opera)&&oid&&typeof oid.id=="string"&&oid.id!=m[2])
oid=jQuery('[@id="'+m[2]+'"]',elem)[0];ret=r=oid&&(!m[3]||jQuery.nodeName(oid,m[3]))?[oid]:[];}else{for(var i=0;ret[i];i++){var tag=m[1]!=""||m[0]==""?"*":m[2];if(tag=="*"&&ret[i].nodeName.toLowerCase()=="object")
tag="param";r=jQuery.merge(r,ret[i].getElementsByTagName(tag));}
if(m[1]==".")
r=jQuery.classFilter(r,m[2]);if(m[1]=="#"){var tmp=[];for(var i=0;r[i];i++)
if(r[i].getAttribute("id")==m[2]){tmp=[r[i]];break;}
r=tmp;}
ret=r;}
t=t.replace(re2,"");}}
if(t){var val=jQuery.filter(t,r);ret=r=val.r;t=jQuery.trim(val.t);}}
if(t)
ret=[];if(ret&&context==ret[0])
ret.shift();done=jQuery.merge(done,ret);return done;},classFilter:function(r,m,not){m=" "+m+" ";var tmp=[];for(var i=0;r[i];i++){var pass=(" "+r[i].className+" ").indexOf(m)>=0;if(!not&&pass||not&&!pass)
tmp.push(r[i]);}
return tmp;},filter:function(t,r,not){var last;while(t&&t!=last){last=t;var p=jQuery.parse,m;for(var i=0;p[i];i++){m=p[i].exec(t);if(m){t=t.substring(m[0].length);m[2]=m[2].replace(/\\/g,"");break;}}
if(!m)
break;if(m[1]==":"&&m[2]=="not")
r=jQuery.filter(m[3],r,true).r;else if(m[1]==".")
r=jQuery.classFilter(r,m[2],not);else if(m[1]=="@"){var tmp=[],type=m[3];for(var i=0,rl=r.length;i<rl;i++){var a=r[i],z=a[jQuery.props[m[2]]||m[2]];if(z==null||/href|src/.test(m[2]))
z=jQuery.attr(a,m[2])||'';if((type==""&&!!z||type=="="&&z==m[5]||type=="!="&&z!=m[5]||type=="^="&&z&&!z.indexOf(m[5])||type=="$="&&z.substr(z.length-m[5].length)==m[5]||(type=="*="||type=="~=")&&z.indexOf(m[5])>=0)^not)
tmp.push(a);}
r=tmp;}else if(m[1]==":"&&m[2]=="nth-child"){var num=jQuery.mergeNum++,tmp=[],test=/(\d*)n\+?(\d*)/.exec(m[3]=="even"&&"2n"||m[3]=="odd"&&"2n+1"||!/\D/.test(m[3])&&"n+"+m[3]||m[3]),first=(test[1]||1)-0,last=test[2]-0;for(var i=0,rl=r.length;i<rl;i++){var node=r[i],parentNode=node.parentNode;if(num!=parentNode.mergeNum){var c=1;for(var n=parentNode.firstChild;n;n=n.nextSibling)
if(n.nodeType==1)
n.nodeIndex=c++;parentNode.mergeNum=num;}
var add=false;if(first==1){if(last==0||node.nodeIndex==last)
add=true;}else if((node.nodeIndex+last)%first==0)
add=true;if(add^not)
tmp.push(node);}
r=tmp;}else{var f=jQuery.expr[m[1]];if(typeof f!="string")
f=jQuery.expr[m[1]][m[2]];eval("f = function(a,i){return "+f+"}");r=jQuery.grep(r,f,not);}}
return{r:r,t:t};},parents:function(elem){var matched=[];var cur=elem.parentNode;while(cur&&cur!=document){matched.push(cur);cur=cur.parentNode;}
return matched;},nth:function(cur,result,dir,elem){result=result||1;var num=0;for(;cur;cur=cur[dir])
if(cur.nodeType==1&&++num==result)
break;return cur;},sibling:function(n,elem){var r=[];for(;n;n=n.nextSibling){if(n.nodeType==1&&(!elem||n!=elem))
r.push(n);}
return r;}});jQuery.event={add:function(element,type,handler,data){if(jQuery.browser.msie&&element.setInterval!=undefined)
element=window;if(!handler.guid)
handler.guid=this.guid++;if(data!=undefined){var fn=handler;handler=function(){return fn.apply(this,arguments);};handler.data=data;handler.guid=fn.guid;}
if(!element.$events)
element.$events={};if(!element.$handle)
element.$handle=function(){var val;if(typeof jQuery=="undefined"||jQuery.event.triggered)
return val;val=jQuery.event.handle.apply(element,arguments);return val;};var handlers=element.$events[type];if(!handlers){handlers=element.$events[type]={};if(element.addEventListener)
element.addEventListener(type,element.$handle,false);else
element.attachEvent("on"+type,element.$handle);}
handlers[handler.guid]=handler;if(!this.global[type])
this.global[type]=[];if(jQuery.inArray(element,this.global[type])==-1)
this.global[type].push(element);},guid:1,global:{},remove:function(element,type,handler){var events=element.$events,ret,index;if(events){if(type&&type.type){handler=type.handler;type=type.type;}
if(!type){for(type in events)
this.remove(element,type);}else if(events[type]){if(handler)
delete events[type][handler.guid];else
for(handler in element.$events[type])
delete events[type][handler];for(ret in events[type])break;if(!ret){if(element.removeEventListener)
element.removeEventListener(type,element.$handle,false);else
element.detachEvent("on"+type,element.$handle);ret=null;delete events[type];while(this.global[type]&&((index=jQuery.inArray(element,this.global[type]))>=0))
delete this.global[type][index];}}
for(ret in events)break;if(!ret)
element.$handle=element.$events=null;}},trigger:function(type,data,element){data=jQuery.makeArray(data||[]);if(!element)
jQuery.each(this.global[type]||[],function(){jQuery.event.trigger(type,data,this);});else{var val,ret,fn=jQuery.isFunction(element[type]||null);data.unshift(this.fix({type:type,target:element}));if(jQuery.isFunction(element.$handle)&&(val=element.$handle.apply(element,data))!==false)
this.triggered=true;if(fn&&val!==false&&!jQuery.nodeName(element,'a'))
element[type]();this.triggered=false;}},handle:function(event){var val;event=jQuery.event.fix(event||window.event||{});var c=this.$events&&this.$events[event.type],args=[].slice.call(arguments,1);args.unshift(event);for(var j in c){args[0].handler=c[j];args[0].data=c[j].data;if(c[j].apply(this,args)===false){event.preventDefault();event.stopPropagation();val=false;}}
if(jQuery.browser.msie)
event.target=event.preventDefault=event.stopPropagation=event.handler=event.data=null;return val;},fix:function(event){var originalEvent=event;event=jQuery.extend({},originalEvent);event.preventDefault=function(){if(originalEvent.preventDefault)
return originalEvent.preventDefault();originalEvent.returnValue=false;};event.stopPropagation=function(){if(originalEvent.stopPropagation)
return originalEvent.stopPropagation();originalEvent.cancelBubble=true;};if(!event.target&&event.srcElement)
event.target=event.srcElement;if(jQuery.browser.safari&&event.target.nodeType==3)
event.target=originalEvent.target.parentNode;if(!event.relatedTarget&&event.fromElement)
event.relatedTarget=event.fromElement==event.target?event.toElement:event.fromElement;if(event.pageX==null&&event.clientX!=null){var e=document.documentElement,b=document.body;event.pageX=event.clientX+(e&&e.scrollLeft||b.scrollLeft);event.pageY=event.clientY+(e&&e.scrollTop||b.scrollTop);}
if(!event.which&&(event.charCode||event.keyCode))
event.which=event.charCode||event.keyCode;if(!event.metaKey&&event.ctrlKey)
event.metaKey=event.ctrlKey;if(!event.which&&event.button)
event.which=(event.button&1?1:(event.button&2?3:(event.button&4?2:0)));return event;}};jQuery.fn.extend({bind:function(type,data,fn){return type=="unload"?this.one(type,data,fn):this.each(function(){jQuery.event.add(this,type,fn||data,fn&&data);});},one:function(type,data,fn){return this.each(function(){jQuery.event.add(this,type,function(event){jQuery(this).unbind(event);return(fn||data).apply(this,arguments);},fn&&data);});},unbind:function(type,fn){return this.each(function(){jQuery.event.remove(this,type,fn);});},trigger:function(type,data){return this.each(function(){jQuery.event.trigger(type,data,this);});},toggle:function(){var a=arguments;return this.click(function(e){this.lastToggle=0==this.lastToggle?1:0;e.preventDefault();return a[this.lastToggle].apply(this,[e])||false;});},hover:function(f,g){function handleHover(e){var p=e.relatedTarget;while(p&&p!=this)try{p=p.parentNode}catch(e){p=this;};if(p==this)return false;return(e.type=="mouseover"?f:g).apply(this,[e]);}
return this.mouseover(handleHover).mouseout(handleHover);},ready:function(f){if(jQuery.isReady)
f.apply(document,[jQuery]);else
jQuery.readyList.push(function(){return f.apply(this,[jQuery])});return this;}});jQuery.extend({isReady:false,readyList:[],ready:function(){if(!jQuery.isReady){jQuery.isReady=true;if(jQuery.readyList){jQuery.each(jQuery.readyList,function(){this.apply(document);});jQuery.readyList=null;}
if(jQuery.browser.mozilla||jQuery.browser.opera)
document.removeEventListener("DOMContentLoaded",jQuery.ready,false);if(!window.frames.length)
jQuery(window).load(function(){jQuery("#__ie_init").remove();});}}});new function(){jQuery.each(("blur,focus,load,resize,scroll,unload,click,dblclick,"+"mousedown,mouseup,mousemove,mouseover,mouseout,change,select,"+"submit,keydown,keypress,keyup,error").split(","),function(i,o){jQuery.fn[o]=function(f){return f?this.bind(o,f):this.trigger(o);};});if(jQuery.browser.mozilla||jQuery.browser.opera)
document.addEventListener("DOMContentLoaded",jQuery.ready,false);else if(jQuery.browser.msie){document.write("<scr"+"ipt id=__ie_init defer=true "+"src=//:><\/script>");var script=document.getElementById("__ie_init");if(script)
script.onreadystatechange=function(){if(this.readyState!="complete")return;jQuery.ready();};script=null;}else if(jQuery.browser.safari)
jQuery.safariTimer=setInterval(function(){if(document.readyState=="loaded"||document.readyState=="complete"){clearInterval(jQuery.safariTimer);jQuery.safariTimer=null;jQuery.ready();}},10);jQuery.event.add(window,"load",jQuery.ready);};if(jQuery.browser.msie)
jQuery(window).one("unload",function(){var global=jQuery.event.global;for(var type in global){var els=global[type],i=els.length;if(i&&type!='unload')
do
els[i-1]&&jQuery.event.remove(els[i-1],type);while(--i);}});jQuery.fn.extend({loadIfModified:function(url,params,callback){this.load(url,params,callback,1);},load:function(url,params,callback,ifModified){if(jQuery.isFunction(url))
return this.bind("load",url);callback=callback||function(){};var type="GET";if(params)
if(jQuery.isFunction(params)){callback=params;params=null;}else{params=jQuery.param(params);type="POST";}
var self=this;jQuery.ajax({url:url,type:type,data:params,ifModified:ifModified,complete:function(res,status){if(status=="success"||!ifModified&&status=="notmodified")
self.attr("innerHTML",res.responseText).evalScripts().each(callback,[res.responseText,status,res]);else
callback.apply(self,[res.responseText,status,res]);}});return this;},serialize:function(){return jQuery.param(this);},evalScripts:function(){return this.find("script").each(function(){if(this.src)
jQuery.getScript(this.src);else
jQuery.globalEval(this.text||this.textContent||this.innerHTML||"");}).end();}});jQuery.each("ajaxStart,ajaxStop,ajaxComplete,ajaxError,ajaxSuccess,ajaxSend".split(","),function(i,o){jQuery.fn[o]=function(f){return this.bind(o,f);};});jQuery.extend({get:function(url,data,callback,type,ifModified){if(jQuery.isFunction(data)){callback=data;data=null;}
return jQuery.ajax({type:"GET",url:url,data:data,success:callback,dataType:type,ifModified:ifModified});},getIfModified:function(url,data,callback,type){return jQuery.get(url,data,callback,type,1);},getScript:function(url,callback){return jQuery.get(url,null,callback,"script");},getJSON:function(url,data,callback){return jQuery.get(url,data,callback,"json");},post:function(url,data,callback,type){if(jQuery.isFunction(data)){callback=data;data={};}
return jQuery.ajax({type:"POST",url:url,data:data,success:callback,dataType:type});},ajaxTimeout:function(timeout){jQuery.ajaxSettings.timeout=timeout;},ajaxSetup:function(settings){jQuery.extend(jQuery.ajaxSettings,settings);},ajaxSettings:{global:true,type:"GET",timeout:0,contentType:"application/x-www-form-urlencoded",processData:true,async:true,data:null},lastModified:{},ajax:function(s){s=jQuery.extend({},jQuery.ajaxSettings,s);if(s.data){if(s.processData&&typeof s.data!="string")
s.data=jQuery.param(s.data);if(s.type.toLowerCase()=="get"){s.url+=((s.url.indexOf("?")>-1)?"&":"?")+s.data;s.data=null;}}
if(s.global&&!jQuery.active++)
jQuery.event.trigger("ajaxStart");var requestDone=false;var xml=window.ActiveXObject?new ActiveXObject("Microsoft.XMLHTTP"):new XMLHttpRequest();xml.open(s.type,s.url,s.async);if(s.data)
xml.setRequestHeader("Content-Type",s.contentType);if(s.ifModified)
xml.setRequestHeader("If-Modified-Since",jQuery.lastModified[s.url]||"Thu, 01 Jan 1970 00:00:00 GMT");xml.setRequestHeader("X-Requested-With","XMLHttpRequest");if(s.beforeSend)
s.beforeSend(xml);if(s.global)
jQuery.event.trigger("ajaxSend",[xml,s]);var onreadystatechange=function(isTimeout){if(xml&&(xml.readyState==4||isTimeout=="timeout")){requestDone=true;if(ival){clearInterval(ival);ival=null;}
var status;try{status=jQuery.httpSuccess(xml)&&isTimeout!="timeout"?s.ifModified&&jQuery.httpNotModified(xml,s.url)?"notmodified":"success":"error";if(status!="error"){var modRes;try{modRes=xml.getResponseHeader("Last-Modified");}catch(e){}
if(s.ifModified&&modRes)
jQuery.lastModified[s.url]=modRes;var data=jQuery.httpData(xml,s.dataType);if(s.success)
s.success(data,status);if(s.global)
jQuery.event.trigger("ajaxSuccess",[xml,s]);}else
jQuery.handleError(s,xml,status);}catch(e){status="error";jQuery.handleError(s,xml,status,e);}
if(s.global)
jQuery.event.trigger("ajaxComplete",[xml,s]);if(s.global&&!--jQuery.active)
jQuery.event.trigger("ajaxStop");if(s.complete)
s.complete(xml,status);if(s.async)
xml=null;}};var ival=setInterval(onreadystatechange,13);if(s.timeout>0)
setTimeout(function(){if(xml){xml.abort();if(!requestDone)
onreadystatechange("timeout");}},s.timeout);try{xml.send(s.data);}catch(e){jQuery.handleError(s,xml,null,e);}
if(!s.async)
onreadystatechange();return xml;},handleError:function(s,xml,status,e){if(s.error)s.error(xml,status,e);if(s.global)
jQuery.event.trigger("ajaxError",[xml,s,e]);},active:0,httpSuccess:function(r){try{return!r.status&&location.protocol=="file:"||(r.status>=200&&r.status<300)||r.status==304||jQuery.browser.safari&&r.status==undefined;}catch(e){}
return false;},httpNotModified:function(xml,url){try{var xmlRes=xml.getResponseHeader("Last-Modified");return xml.status==304||xmlRes==jQuery.lastModified[url]||jQuery.browser.safari&&xml.status==undefined;}catch(e){}
return false;},httpData:function(r,type){var ct=r.getResponseHeader("content-type");var data=!type&&ct&&ct.indexOf("xml")>=0;data=type=="xml"||data?r.responseXML:r.responseText;if(type=="script")
jQuery.globalEval(data);if(type=="json")
data=eval("("+data+")");if(type=="html")
jQuery("<div>").html(data).evalScripts();return data;},param:function(a){var s=[];if(a.constructor==Array||a.jquery)
jQuery.each(a,function(){s.push(encodeURIComponent(this.name)+"="+encodeURIComponent(this.value));});else
for(var j in a)
if(a[j]&&a[j].constructor==Array)
jQuery.each(a[j],function(){s.push(encodeURIComponent(j)+"="+encodeURIComponent(this));});else
s.push(encodeURIComponent(j)+"="+encodeURIComponent(a[j]));return s.join("&");},globalEval:function(data){if(window.execScript)
window.execScript(data);else if(jQuery.browser.safari)
window.setTimeout(data,0);else
eval.call(window,data);}});jQuery.fn.extend({show:function(speed,callback){return speed?this.animate({height:"show",width:"show",opacity:"show"},speed,callback):this.filter(":hidden").each(function(){this.style.display=this.oldblock?this.oldblock:"";if(jQuery.css(this,"display")=="none")
this.style.display="block";}).end();},hide:function(speed,callback){return speed?this.animate({height:"hide",width:"hide",opacity:"hide"},speed,callback):this.filter(":visible").each(function(){this.oldblock=this.oldblock||jQuery.css(this,"display");if(this.oldblock=="none")
this.oldblock="block";this.style.display="none";}).end();},_toggle:jQuery.fn.toggle,toggle:function(fn,fn2){return jQuery.isFunction(fn)&&jQuery.isFunction(fn2)?this._toggle(fn,fn2):fn?this.animate({height:"toggle",width:"toggle",opacity:"toggle"},fn,fn2):this.each(function(){jQuery(this)[jQuery(this).is(":hidden")?"show":"hide"]();});},slideDown:function(speed,callback){return this.animate({height:"show"},speed,callback);},slideUp:function(speed,callback){return this.animate({height:"hide"},speed,callback);},slideToggle:function(speed,callback){return this.animate({height:"toggle"},speed,callback);},fadeIn:function(speed,callback){return this.animate({opacity:"show"},speed,callback);},fadeOut:function(speed,callback){return this.animate({opacity:"hide"},speed,callback);},fadeTo:function(speed,to,callback){return this.animate({opacity:to},speed,callback);},animate:function(prop,speed,easing,callback){return this.queue(function(){var hidden=jQuery(this).is(":hidden"),opt=jQuery.speed(speed,easing,callback),self=this;for(var p in prop){if(prop[p]=="hide"&&hidden||prop[p]=="show"&&!hidden)
return jQuery.isFunction(opt.complete)&&opt.complete.apply(this);if(p=="height"||p=="width"){opt.display=jQuery.css(this,"display");opt.overflow=this.style.overflow;}}
if(opt.overflow!=null)
this.style.overflow="hidden";this.curAnim=jQuery.extend({},prop);jQuery.each(prop,function(name,val){var e=new jQuery.fx(self,opt,name);if(val.constructor==Number)
e.custom(e.cur(),val);else
e[val=="toggle"?hidden?"show":"hide":val](prop);});});},queue:function(type,fn){if(!fn){fn=type;type="fx";}
return this.each(function(){if(!this.queue)
this.queue={};if(!this.queue[type])
this.queue[type]=[];this.queue[type].push(fn);if(this.queue[type].length==1)
fn.apply(this);});}});jQuery.extend({speed:function(speed,easing,fn){var opt=speed&&speed.constructor==Object?speed:{complete:fn||!fn&&easing||jQuery.isFunction(speed)&&speed,duration:speed,easing:fn&&easing||easing&&easing.constructor!=Function&&easing||(jQuery.easing.swing?"swing":"linear")};opt.duration=(opt.duration&&opt.duration.constructor==Number?opt.duration:{slow:600,fast:200}[opt.duration])||400;opt.old=opt.complete;opt.complete=function(){jQuery.dequeue(this,"fx");if(jQuery.isFunction(opt.old))
opt.old.apply(this);};return opt;},easing:{linear:function(p,n,firstNum,diff){return firstNum+diff*p;},swing:function(p,n,firstNum,diff){return((-Math.cos(p*Math.PI)/2)+0.5)*diff+firstNum;}},queue:{},dequeue:function(elem,type){type=type||"fx";if(elem.queue&&elem.queue[type]){elem.queue[type].shift();var f=elem.queue[type][0];if(f)f.apply(elem);}},timers:[],fx:function(elem,options,prop){var z=this;var y=elem.style;z.a=function(){if(options.step)
options.step.apply(elem,[z.now]);if(prop=="opacity")
jQuery.attr(y,"opacity",z.now);else{y[prop]=parseInt(z.now)+"px";y.display="block";}};z.max=function(){return parseFloat(jQuery.css(elem,prop));};z.cur=function(){var r=parseFloat(jQuery.curCSS(elem,prop));return r&&r>-10000?r:z.max();};z.custom=function(from,to){z.startTime=(new Date()).getTime();z.now=from;z.a();jQuery.timers.push(function(){return z.step(from,to);});if(jQuery.timers.length==1){var timer=setInterval(function(){var timers=jQuery.timers;for(var i=0;i<timers.length;i++)
if(!timers[i]())
timers.splice(i--,1);if(!timers.length)
clearInterval(timer);},13);}};z.show=function(){if(!elem.orig)elem.orig={};elem.orig[prop]=jQuery.attr(elem.style,prop);options.show=true;z.custom(0,this.cur());if(prop!="opacity")
y[prop]="1px";jQuery(elem).show();};z.hide=function(){if(!elem.orig)elem.orig={};elem.orig[prop]=jQuery.attr(elem.style,prop);options.hide=true;z.custom(this.cur(),0);};z.step=function(firstNum,lastNum){var t=(new Date()).getTime();if(t>options.duration+z.startTime){z.now=lastNum;z.a();if(elem.curAnim)elem.curAnim[prop]=true;var done=true;for(var i in elem.curAnim)
if(elem.curAnim[i]!==true)
done=false;if(done){if(options.display!=null){y.overflow=options.overflow;y.display=options.display;if(jQuery.css(elem,"display")=="none")
y.display="block";}
if(options.hide)
y.display="none";if(options.hide||options.show)
for(var p in elem.curAnim)
jQuery.attr(y,p,elem.orig[p]);}
if(done&&jQuery.isFunction(options.complete))
options.complete.apply(elem);return false;}else{var n=t-this.startTime;var p=n/options.duration;z.now=jQuery.easing[options.easing](p,n,firstNum,(lastNum-firstNum),options.duration);z.a();}
return true;};}});}
(function($){$.extend({tabs:{remoteCount:0}});$.fn.tabs=function(initial,settings){if(typeof initial=='object')settings=initial;settings=$.extend({initial:(initial&&typeof initial=='number'&&initial>0)?--initial:0,disabled:null,bookmarkable:$.ajaxHistory?true:false,remote:false,spinner:'Loading&#8230;',hashPrefix:'remote-tab-',fxFade:null,fxSlide:null,fxShow:null,fxHide:null,fxSpeed:'normal',fxShowSpeed:null,fxHideSpeed:null,fxAutoHeight:false,onClick:null,onHide:null,onShow:null,navClass:'tabs-nav',selectedClass:'tabs-selected',disabledClass:'tabs-disabled',containerClass:'tabs-container',hideClass:'tabs-hide',loadingClass:'tabs-loading',tabStruct:'div'},settings||{});$.browser.msie6=$.browser.msie&&($.browser.version&&$.browser.version<7||/6.0/.test(navigator.userAgent));function unFocus(){scrollTo(0,0);}
return this.each(function(){var container=this;var nav=$('ul.'+settings.navClass,container);nav=nav.size()&&nav||$('>ul:eq(0)',container);var tabs=$('a',nav);if(settings.remote){tabs.each(function(){var id=settings.hashPrefix+(++$.tabs.remoteCount),hash='#'+id,url=this.href;this.href=hash;$('<div id="'+id+'" class="'+settings.containerClass+'"></div>').appendTo(container);$(this).bind('loadRemoteTab',function(e,callback){var $$=$(this).addClass(settings.loadingClass),span=$('span',this)[0],tabTitle=span.innerHTML;if(settings.spinner){span.innerHTML='<em>'+settings.spinner+'</em>';}
setTimeout(function(){$(hash).load(url,function(){if(settings.spinner){span.innerHTML=tabTitle;}
$$.removeClass(settings.loadingClass);callback&&callback();});},0);});});}
var containers=$('div.'+settings.containerClass,container);containers=containers.size()&&containers||$('>'+settings.tabStruct,container);nav.is('.'+settings.navClass)||nav.addClass(settings.navClass);containers.each(function(){var $$=$(this);$$.is('.'+settings.containerClass)||$$.addClass(settings.containerClass);});var hasSelectedClass=$('li',nav).index($('li.'+settings.selectedClass,nav)[0]);if(hasSelectedClass>=0){settings.initial=hasSelectedClass;}
if(location.hash){tabs.each(function(i){if(this.hash==location.hash){settings.initial=i;if(($.browser.msie||$.browser.opera)&&!settings.remote){var toShow=$(location.hash);var toShowId=toShow.attr('id');toShow.attr('id','');setTimeout(function(){toShow.attr('id',toShowId);},500);}
unFocus();return false;}});}
if($.browser.msie){unFocus();}
containers.filter(':eq('+settings.initial+')').show().end().not(':eq('+settings.initial+')').addClass(settings.hideClass);$('li',nav).removeClass(settings.selectedClass).eq(settings.initial).addClass(settings.selectedClass);tabs.eq(settings.initial).trigger('loadRemoteTab').end();if(settings.fxAutoHeight){var _setAutoHeight=function(reset){var heights=$.map(containers.get(),function(el){var h,jq=$(el);if(reset){if($.browser.msie6){el.style.removeExpression('behaviour');el.style.height='';el.minHeight=null;}
h=jq.css({'min-height':''}).height();}else{h=jq.height();}
return h;}).sort(function(a,b){return b-a;});if($.browser.msie6){containers.each(function(){this.minHeight=heights[0]+'px';this.style.setExpression('behaviour','this.style.height = this.minHeight ? this.minHeight : "1px"');});}else{containers.css({'min-height':heights[0]+'px'});}};_setAutoHeight();var cachedWidth=container.offsetWidth;var cachedHeight=container.offsetHeight;var watchFontSize=$('#tabs-watch-font-size').get(0)||$('<span id="tabs-watch-font-size">M</span>').css({display:'block',position:'absolute',visibility:'hidden'}).appendTo(document.body).get(0);var cachedFontSize=watchFontSize.offsetHeight;setInterval(function(){var currentWidth=container.offsetWidth;var currentHeight=container.offsetHeight;var currentFontSize=watchFontSize.offsetHeight;if(currentHeight>cachedHeight||currentWidth!=cachedWidth||currentFontSize!=cachedFontSize){_setAutoHeight((currentWidth>cachedWidth||currentFontSize<cachedFontSize));cachedWidth=currentWidth;cachedHeight=currentHeight;cachedFontSize=currentFontSize;}},50);}
var showAnim={},hideAnim={},showSpeed=settings.fxShowSpeed||settings.fxSpeed,hideSpeed=settings.fxHideSpeed||settings.fxSpeed;if(settings.fxSlide||settings.fxFade){if(settings.fxSlide){showAnim['height']='show';hideAnim['height']='hide';}
if(settings.fxFade){showAnim['opacity']='show';hideAnim['opacity']='hide';}}else{if(settings.fxShow){showAnim=settings.fxShow;}else{showAnim['min-width']=0;showSpeed=1;}
if(settings.fxHide){hideAnim=settings.fxHide;}else{hideAnim['min-width']=0;hideSpeed=1;}}
var onClick=settings.onClick,onHide=settings.onHide,onShow=settings.onShow;tabs.bind('triggerTab',function(){var li=$(this).parents('li:eq(0)');if(container.locked||li.is('.'+settings.selectedClass)||li.is('.'+settings.disabledClass)){return false;}
var hash=this.hash;if($.browser.msie){$(this).trigger('click');if(settings.bookmarkable){$.ajaxHistory.update(hash);location.hash=hash.replace('#','');}}else if($.browser.safari){var tempForm=$('<form action="'+hash+'"><div><input type="submit" value="h" /></div></form>').get(0);tempForm.submit();$(this).trigger('click');if(settings.bookmarkable){$.ajaxHistory.update(hash);}}else{if(settings.bookmarkable){location.hash=hash.replace('#','');}else{$(this).trigger('click');}}});tabs.bind('disableTab',function(){var li=$(this).parents('li:eq(0)');if($.browser.safari){li.animate({opacity:0},1,function(){li.css({opacity:''});});}
li.addClass(settings.disabledClass);});if(settings.disabled&&settings.disabled.length){for(var i=0,k=settings.disabled.length;i<k;i++){tabs.eq(--settings.disabled[i]).trigger('disableTab').end();}};tabs.bind('enableTab',function(){var li=$(this).parents('li:eq(0)');li.removeClass(settings.disabledClass);if($.browser.safari){li.animate({opacity:1},1,function(){li.css({opacity:''});});}});tabs.bind('click',function(e){var trueClick=e.clientX;var clicked=this,li=$(this).parents('li:eq(0)'),toShow=$(this.hash),toHide=containers.filter(':visible');if(container['locked']||li.is('.'+settings.selectedClass)||li.is('.'+settings.disabledClass)||typeof onClick=='function'&&onClick(this,toShow[0],toHide[0])===false){this.blur();return false;}
container['locked']=true;if(toShow.size()){if($.browser.msie&&settings.bookmarkable){var toShowId=this.hash.replace('#','');toShow.attr('id','');setTimeout(function(){toShow.attr('id',toShowId);},0);}
var resetCSS={display:'',overflow:'',height:''};if(!$.browser.msie){resetCSS['opacity']='';}
function switchTab(){if(settings.bookmarkable&&trueClick){$.ajaxHistory.update(clicked.hash);}
toHide.animate(hideAnim,hideSpeed,function(){$(clicked).parents('li:eq(0)').addClass(settings.selectedClass).siblings().removeClass(settings.selectedClass);toHide.addClass(settings.hideClass).css(resetCSS);if(typeof onHide=='function'){onHide(clicked,toShow[0],toHide[0]);}
if(!(settings.fxSlide||settings.fxFade||settings.fxShow)){toShow.css('display','block');}
toShow.animate(showAnim,showSpeed,function(){toShow.removeClass(settings.hideClass).css(resetCSS);if($.browser.msie){toHide[0].style.filter='';toShow[0].style.filter='';}
if(typeof onShow=='function'){onShow(clicked,toShow[0],toHide[0]);}
container['locked']=null;});});}
if(!settings.remote){switchTab();}else{$(clicked).trigger('loadRemoteTab',[switchTab]);}}else{alert('There is no such container.');}
var scrollX=window.pageXOffset||document.documentElement&&document.documentElement.scrollLeft||document.body.scrollLeft||0;var scrollY=window.pageYOffset||document.documentElement&&document.documentElement.scrollTop||document.body.scrollTop||0;setTimeout(function(){window.scrollTo(scrollX,scrollY);},0);this.blur();return settings.bookmarkable&&!!trueClick;});if(settings.bookmarkable){$.ajaxHistory.initialize(function(){tabs.eq(settings.initial).trigger('click').end();});}});};var tabEvents=['triggerTab','disableTab','enableTab'];for(var i=0;i<tabEvents.length;i++){$.fn[tabEvents[i]]=(function(tabEvent){return function(tab){return this.each(function(){var nav=$('ul.tabs-nav',this);nav=nav.size()&&nav||$('>ul:eq(0)',this);var a;if(!tab||typeof tab=='number'){a=$('li a',nav).eq((tab&&tab>0&&tab-1||0));}else if(typeof tab=='string'){a=$('li a[@href$="#'+tab+'"]',nav);}
a.trigger(tabEvent);});};})(tabEvents[i]);}
$.fn.activeTab=function(){var selectedTabs=[];this.each(function(){var nav=$('ul.tabs-nav',this);nav=nav.size()&&nav||$('>ul:eq(0)',this);var lis=$('li',nav);selectedTabs.push(lis.index(lis.filter('.tabs-selected')[0])+1);});return selectedTabs[0];};})(jQuery);(function($){var height=$.fn.height,width=$.fn.width;$.fn.extend({height:function(){if(this[0]==window)
if(($.browser.mozilla||$.browser.opera)&&$(document).width()>self.innerWidth)
return self.innerHeight-getScrollbarWidth();else
return self.innerHeight||$.boxModel&&document.documentElement.clientHeight||document.body.clientHeight;if(this[0]==document)
return Math.max(document.body.scrollHeight,document.body.offsetHeight);return height.apply(this,arguments);},width:function(){if(this[0]==window)
if(($.browser.mozilla||$.browser.opera)&&$(document).width()>self.innerWidth)
return self.innerWidth-getScrollbarWidth();else
return self.innerWidth||$.boxModel&&document.documentElement.clientWidth||document.body.clientWidth;if(this[0]==document)
if($.browser.mozilla){var scrollLeft=self.pageXOffset;self.scrollTo(99999999,self.pageYOffset);var scrollWidth=self.pageXOffset;self.scrollTo(scrollLeft,self.pageYOffset);return document.body.offsetWidth+scrollWidth;}
else
return Math.max(document.body.scrollWidth,document.body.offsetWidth);return width.apply(this,arguments);},innerHeight:function(){return this[0]==window||this[0]==document?this.height():this.is(':visible')?this[0].offsetHeight-num(this,'borderTopWidth')-num(this,'borderBottomWidth'):this.height()+num(this,'paddingTop')+num(this,'paddingBottom');},innerWidth:function(){return this[0]==window||this[0]==document?this.width():this.is(':visible')?this[0].offsetWidth-num(this,'borderLeftWidth')-num(this,'borderRightWidth'):this.width()+num(this,'paddingLeft')+num(this,'paddingRight');},outerHeight:function(){return this[0]==window||this[0]==document?this.height():this.is(':visible')?this[0].offsetHeight:this.height()+num(this,'borderTopWidth')+num(this,'borderBottomWidth')+num(this,'paddingTop')+num(this,'paddingBottom');},outerWidth:function(){return this[0]==window||this[0]==document?this.width():this.is(':visible')?this[0].offsetWidth:this.width()+num(this,'borderLeftWidth')+num(this,'borderRightWidth')+num(this,'paddingLeft')+num(this,'paddingRight');},scrollLeft:function(val){if(val!=undefined)
return this.each(function(){if(this==window||this==document)
window.scrollTo(val,$(window).scrollTop());else
this.scrollLeft=val;});if(this[0]==window||this[0]==document)
return self.pageXOffset||$.boxModel&&document.documentElement.scrollLeft||document.body.scrollLeft;return this[0].scrollLeft;},scrollTop:function(val){if(val!=undefined)
return this.each(function(){if(this==window||this==document)
window.scrollTo($(window).scrollLeft(),val);else
this.scrollTop=val;});if(this[0]==window||this[0]==document)
return self.pageYOffset||$.boxModel&&document.documentElement.scrollTop||document.body.scrollTop;return this[0].scrollTop;},position:function(returnObject){var offsetParent=this[0].offsetParent;if($.browser.msie&&(offsetParent.tagName!='BODY'&&$.css(offsetParent,'position')=='static')){do{offsetParent=offsetParent.offsetParent;}while(offsetParent&&(offsetParent.tagName!='BODY'&&$.css(offsetParent,'position')=='static'));}
return this.offset({margin:false,scroll:false,relativeTo:offsetParent},returnObject);},offset:function(options,returnObject){var x=0,y=0,sl=0,st=0,elem=this[0],parent=this[0],op,parPos,elemPos=$.css(elem,'position'),mo=$.browser.mozilla,ie=$.browser.msie,sf=$.browser.safari,oa=$.browser.opera,absparent=false,relparent=false,options=$.extend({margin:true,border:false,padding:false,scroll:true,lite:false,relativeTo:document.body},options||{});if(options.lite)return this.offsetLite(options,returnObject);if(elem.tagName=='BODY'){x=elem.offsetLeft;y=elem.offsetTop;if(mo){x+=num(elem,'marginLeft')+(num(elem,'borderLeftWidth')*2);y+=num(elem,'marginTop')+(num(elem,'borderTopWidth')*2);}else
if(oa){x+=num(elem,'marginLeft');y+=num(elem,'marginTop');}else
if(ie&&jQuery.boxModel){x+=num(elem,'borderLeftWidth');y+=num(elem,'borderTopWidth');}}else{do{parPos=$.css(parent,'position');x+=parent.offsetLeft;y+=parent.offsetTop;if(mo||ie){x+=num(parent,'borderLeftWidth');y+=num(parent,'borderTopWidth');if(mo&&parPos=='absolute')absparent=true;if(ie&&parPos=='relative')relparent=true;}
op=parent.offsetParent;if(options.scroll||mo){do{if(options.scroll){sl+=parent.scrollLeft;st+=parent.scrollTop;}
if(mo&&parent!=elem&&$.css(parent,'overflow')!='visible'){x+=num(parent,'borderLeftWidth');y+=num(parent,'borderTopWidth');}
parent=parent.parentNode;}while(parent!=op);}
parent=op;if(parent==options.relativeTo&&!(parent.tagName=='BODY'||parent.tagName=='HTML')){if(mo&&parent!=elem&&$.css(parent,'overflow')!='visible'){x+=num(parent,'borderLeftWidth');y+=num(parent,'borderTopWidth');}
if(($.browser.safari||$.browser.opera)&&$.css(op,'position')!='static'){x-=num(op,'borderLeftWidth');y-=num(op,'borderTopWidth');}
break;}
if(parent.tagName=='BODY'||parent.tagName=='HTML'){if((sf||(ie&&$.boxModel))&&elemPos!='absolute'&&elemPos!='fixed'){x+=num(parent,'marginLeft');y+=num(parent,'marginTop');}
if((mo&&!absparent&&elemPos!='fixed')||(ie&&elemPos=='static'&&!relparent)){x+=num(parent,'borderLeftWidth');y+=num(parent,'borderTopWidth');}
break;}}while(parent);}
var returnValue=handleOffsetReturn(elem,options,x,y,sl,st);if(returnObject){$.extend(returnObject,returnValue);return this;}
else{return returnValue;}},offsetLite:function(options,returnObject){var x=0,y=0,sl=0,st=0,parent=this[0],offsetParent,options=$.extend({margin:true,border:false,padding:false,scroll:true,relativeTo:document.body},options||{});do{x+=parent.offsetLeft;y+=parent.offsetTop;offsetParent=parent.offsetParent;if(options.scroll){do{sl+=parent.scrollLeft;st+=parent.scrollTop;parent=parent.parentNode;}while(parent!=offsetParent);}
parent=offsetParent;}while(parent&&parent.tagName!='BODY'&&parent.tagName!='HTML'&&parent!=options.relativeTo);var returnValue=handleOffsetReturn(this[0],options,x,y,sl,st);if(returnObject){$.extend(returnObject,returnValue);return this;}
else{return returnValue;}}});var num=function(el,prop){return parseInt($.css(el.jquery?el[0]:el,prop))||0;};var handleOffsetReturn=function(elem,options,x,y,sl,st){if(!options.margin){x-=num(elem,'marginLeft');y-=num(elem,'marginTop');}
if(options.border&&($.browser.safari||$.browser.opera)){x+=num(elem,'borderLeftWidth');y+=num(elem,'borderTopWidth');}else if(!options.border&&!($.browser.safari||$.browser.opera)){x-=num(elem,'borderLeftWidth');y-=num(elem,'borderTopWidth');}
if(options.padding){x+=num(elem,'paddingLeft');y+=num(elem,'paddingTop');}
if(options.scroll){sl-=elem.scrollLeft;st-=elem.scrollTop;}
return options.scroll?{top:y-st,left:x-sl,scrollTop:st,scrollLeft:sl}:{top:y,left:x};};var scrollbarWidth=0;var getScrollbarWidth=function(){if(!scrollbarWidth){var testEl=$('<div>').css({width:100,height:100,overflow:'auto',position:'absolute',top:-1000,left:-1000}).appendTo('body');scrollbarWidth=100-testEl.append('<div>').find('div').css({width:'100%',height:200}).width();testEl.remove();}
return scrollbarWidth;};})(jQuery);var realHeight;function freepbx_modal_show(divID,callback){obj=$('#'+divID);navHeight=$('#nav').height();wrapperHeight=$('#wrapper').height()
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
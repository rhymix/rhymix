/**
 * @projectDescription NHN UI Javascript Framework - codename Jindo
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.2.9
 *
 * Get some idea from prototype.js - http://prototype.conio.net
 * and Dean's work, Base.js - http://dean.edwards.name
 */

/** @id JINDO */
var JINDO = {
 /** @id JINDO.clone */
 clone : function(source) {
 var obj = new source.constructor;
 for(var x in source) obj[x] = source[x];

 return obj;
 },
 /** @id JINDO.extend */
 extend : function(source, append) {
 var obj = source;
 for(var x in append) obj[x] = append[x];

 return obj;
 }
}

/** @id Class */
var Class = function(){
 var constructor = function() {
 if (this.__init) this.__init.apply(this,arguments);
 }
 if (arguments[0]) constructor.prototype = arguments[0];

 return constructor;
}

/** @id Class.extend */
Class.extend = function(superClass) {
 var obj = Class();
 obj.prototype = new superClass;
 for(var i=1; i < arguments.length; i++) {
 if (arguments[i]) JINDO.extend(obj.prototype, arguments[i]);
 }

 return obj;
}
/**
 * @projectDescription Jindo Ajax Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */

/** @id Ajax */
var Ajax = Class({
 _url : '',
 _request : null,
 _response : null,
 _headers : {},
 _crossDom : false, // cross domain?
 _loaded : false,
 /** @id Ajax.__init */
 __init : function(url) {
 this.options = JINDO.extend({
 method : 'GET',
 async : true,
 interval : 0,
 params : null,
 suspend : false,
 charset : 'utf-8', // incoming charset
 preventCache : false,
 ignoreWhiteSpace : false,
 contentType : 'application/x-www-form-urlencoded',
 headers : {},
 onLoad : function(req){},
 onError : function(){}
 }, arguments[1]||{});

 this._url = url;

 // check cross domain
 var domain = String(document.location.href).match(/https?:\/\/([a-z0-9_\-\.]+)/i)[1];

 if (!(/^https?:\/\//i.test(url))) this._crossDom = false;
 else this._crossDom = !(new RegExp("^https?://"+domain+"(:[0-9]+)?(?:/|$)","i")).test(url);

 // get request
 this._request = this._getXMLHTTP();

 // set headers
 this._headers = this.options.headers;

 // start to request
 if (!this.options.suspend) this.request();
 },
 /** @id Ajax._getXMLHTTP */
 _getXMLHTTP : function() {
 if (this._crossDom) {
 return new Ajax.swfHTTPRequest();
 } else if (window.XMLHttpRequest) {
 return new XMLHttpRequest();
 } else if (ActiveXObject) {
 try { return new ActiveXObject('MSXML2.XMLHTTP'); }
 catch(e) { return new ActiveXObject('Microsoft.XMLHTTP'); }
 return null;
 }
 },
 /** @id Ajax._onReadyStateChange */
 _onReadyStateChange : function() {
 if (this._request.readyState == 4 && !this._loaded) {
 this._loaded = true;

 this.options.onLoad(this._request);

 // repeat
 if(this.options.interval > 0) {
 this._timer = setTimeout(this.request.bind(this), this.options.interval*1000);
 }
 }
 },
 /** @id Ajax._getData */
 _getData : function() {
 var i, a = [], t=this;

 $H(this.options.params).each(function(v,k){
 if (v instanceof Function) v = v();
 if (v instanceof Array) {
 for(i=0; i < v.length; i++) {
 a.push(k+'='+encodeURIComponent(v[i]));
 }
 return;
 }
 v = encodeURIComponent(v);
 a.push(k+'='+v);
 });

 return a.length?a.join('&'):null;
 },
 /** @id Ajax.request */
 request : function() {
 var o = this.options; // options
 var m = o.method.toUpperCase(); // method

 var req = this._request;
 if (this._crossDom) req.charset = this.options.charset;
 req.open(o.method, this._url, o.async);
 req.setRequestHeader('Content-Type', o.contentType+'; charset=utf-8');
 req.setRequestHeader('charset', 'utf-8');
 for (var x in this._headers) {
 // Fix Firefox Ajax Bug - shgraph
 if(this._headers.propertyIsEnumerable && typeof(this._headers[x]) != 'function') {
 req.setRequestHeader(x, this._headers[x]);
 }
 }

 // Somtimes FF ignore 'onreadystatechange' event handler.
 // So, if XMLHTTPRequest object support onload event,
 // use it instead of onreadystatechange
 if (typeof req.onload != 'undefined') {
 req.onload = this._onReadyStateChange.bind(this);
 } else {
 req.onreadystatechange = this._onReadyStateChange.bind(this);
 }

 this._loaded = false;

 req.send(this._getData());
 },
 /** @id Ajax.abort */
 abort : function() {
 this._request.abort();
 },
 /** @id Ajax.setRequestHeader */
 setRequestHeader : function(key, val) {
 this._headers[key] = val;
 },
 /** @id Ajax.getResponseHeader */
 getResponseHeader : function(key) {
 return this._request.getResponseHeader(key);
 }
});

/** @id Ajax.swfHTTPRequest */
Ajax.swfHTTPRequest = Class({
 _url : '',
 _method : 'GET',
 _headers : {},

 charset : 'utf-8',
 readyState : 1,
 responseText : '',

 /** @id Ajax.swfHTTPRequest.onreadystatechange */
 onreadystatechange : function(){},

 /** @id Ajax.swfHTTPRequest.__init */
 __init : function() {
 this._tmpId = 'tmpSwf'+(new Date).getMilliseconds()+Math.floor(Math.random()*100000);
 },
 /** @id Ajax.swfHTTPRequest._onLoad */
 _onLoad : function(success, data) {
 if (success) {
 if (typeof data == 'undefined') {
 // TODO : raise exception

 throw new Error('Access denied. please check crossdomain.xml and application domain');

 }

 this.responseText = decodeURIComponent(data);
 this.responseXML = null;

 try {
 this.responseXML = this._parseXML(this.responseText);
 } catch(e){}

 this.readyState = 4;
 this.onreadystatechange();
 }
 },
 _parseXML : function(xmlStr) {
 var xdoc = null;
 try {
 xdoc = new ActiveXObject("Microsoft.XMLDOM");
 xdoc.async = "false";
 xdoc.loadXML(xmlStr);
 } catch(e) {
 var parser = new DOMParser();
 xdoc=parser.parseFromString(xmlStr,"text/xml");
 }

 return xdoc;
 },
 /** @id Ajax.swfHTTPRequest.abort */
 abort : function() {
 // TODO : abort
 },
 /** @id Ajax.swfHTTPRequest.open */
 open : function(method, url) {
 this._method = method;
 this._url = url;
 },
 /** @id Ajax.swfHTTPRequest.send */
 send : function(data) {
 var t = this;
 var dat = {}, key, val, pos;
 var swf = window.document[Ajax._tmpId];
 data = (data || '').split(/&/);

 for(var i=0; i < data.length; i++) {
 pos = data[i].indexOf('=');
 key = data[i].substring(0,pos);
 val = data[i].substring(pos+1);

 dat[key] = decodeURIComponent(val);
 }

 // create callback
 Ajax.swfHTTPRequest['callback_'+this._tmpId] = function() {
 t._onLoad.apply(t,arguments);
 };

 // request via flash
 swf.requestViaFlash(({
 'url' : this._url,
 'type' : this._method,
 'data' : dat,
 'charset' : this.charset.toUpperCase(),
 'callback' : 'Ajax.swfHTTPRequest.callback_'+this._tmpId,
 'headers_json' : this._headers
 }).toJSON());
 },
 /** @id Ajax.swfHTTPRequest.setRequestHeader */
 setRequestHeader : function(key, val) {
 this._headers[key] = val;
 },
 /** @id Ajax.swfHTTPRequest.getResponseHeader */
 getResponseHeader : function(key) {
 return '';
 }
});

// write flash module
Ajax.initFlash = function(swf_path) {
 if(typeof swf_path == "undefined") swf_path = "./ajax.swf";
 Ajax._tmpId = 'tmpSwf'+(new Date).getMilliseconds()+Math.floor(Math.random()*100000);

 document.write('<div style="position:absolute;top:-1000px;left:-1000px"><object id="'+Ajax._tmpId+'" width="1" height="1" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"><param name="movie" value="'+swf_path+'"><param name = "allowScriptAccess" value = "always" /><embed name="'+Ajax._tmpId+'" src="'+swf_path+'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="1" height="1" allowScriptAccess="always" swLiveConnect="true"></embed></object></div>');
}
/**
 * @projectDescription Jindo Array Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */


/** Extend Protoype of Array */
JINDO.extend(Array.prototype, {
 /** @id Array.prototype.has */
 has : function(needle) {
 return (this.indexOf(needle) > -1);
 },
 /** @id Array.prototype.load */
 load : function(obj) {
 for(var i=0; i<obj.length; i++) {
 this.push(obj[i]);
 }
 return this;
 },
 /** @id Array.prototype.each */
 each : function(iter) {
 for(var i=0; i<this.length; i++) {
 iter.call(this, this[i],i);
 }
 },
 /** @id Array.prototype.refuse */
 refuse : function(value) {
 return this.filter(function(v){ return v!=value });
 },
 size : function() {
 return this.length;
 }
});

/** If This Browser Supports "forEach", Replace "each" */
if (Array.prototype.forEach) Array.prototype.each = Array.prototype.forEach;
/**
 * @projectDescription Jindo DOM Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */

/** @id Element */
var Element = {
 _blockTags : /^(div|p|ul|ol|li|pre|xmp|hr|blockquote|center|br|h[1-6]|fieldset|dl|dt|dd)$/i,

 /** @id Element.show */
 show : function() {
 [].load(arguments).each(function(v){
 v = $(v);
 if (!Element.visible(v))
 v.style.display = Element._blockTags.test(v.tagName) ? 'block' : 'inline';
 });
 },
 /** @id Element.hide */
 hide : function() {
 [].load(arguments).each(function(v){ $(v).style.display='none'; });
 },
 /** @id Element.toggle */
 toggle : function() {
 [].load(arguments).each(function(v){ Element[Element.visible(v)?'hide':'show'](v) });
 },
 /** @id Element.visible */
 visible : function(oEl) {
 return (this.getCSS($(oEl), 'display')!='none');
 },
 /** @id Element.has */
 has : function(oParent, oChild) {
 for (; oChild; oChild = oChild.parentNode)
 if (oChild == oParent) return true;

 return false;
 },
 /** @id Element.realPos */
 realPos : function(oEl) {
 if (oEl.offsetParent) {
 var p = this.realPos(oEl.offsetParent);
 return { top: oEl.offsetTop+p.top, left: oEl.offsetLeft+p.left };
 } else {
 return { top: oEl.offsetTop, left:oEl.offsetLeft };
 }
 },
 /** @id Element.getCSS */
 getCSS : function(oEl, name) {
 var doc = oEl.ownerDocument;
 var view = doc.defaultView;

 try {

 if (doc && view) {
 var sRetval;
 var oSafariBug = $Agent().Safari && name == "marginRight";

 if (oSafariBug) {
 oSafariBug = [ oEl.style.position ];
 oEl.style.position = "absolute";
 }

 name = name.replace(/([A-Z])/g, function(s) { return "-" + s.toLowerCase(); });
 sRetval = view.getComputedStyle(oEl, null).getPropertyValue(name);

 if (oSafariBug)
 oEl.style.position = oSafariBug[0];

 return sRetval;
 }

 } catch(e){}

 if (oEl.currentStyle)
 return oEl.currentStyle[name];

 return Element.getInlineCSS(oEl, name);
 },
 /** @id Element.setCSS */
 setCSS : function(oEl, css) {
 $H(css).each(function(val, key) {
 oEl.style[key] = val;
 });
 },
 /** @id Element.hasClass */
 hasClass : function(oEl, className) {
 if (!oEl) return false;
 return (" " + oEl.className + " ").indexOf(" " + className + " ") != -1;
 },
 /** @id Element.addClass */
 addClass : function(oEl, className) {
 if (!this.hasClass(oEl, className)) $(oEl).className = ($(oEl).className+' '+className).replace(/^\s+/,'');
 },
 /** @id Element.removeClass */
 removeClass : function(oEl, className) {
 for (var i = 1, className; className = arguments[i]; i++)
 $(oEl).className = $(oEl).className.replace(new RegExp('\\b' + className + '(\\s+|$)', 'g'), '');
 },
 /** @id Element.getInlineCSS */
 getInlineCSS : function(oEl, name) {
 return oEl.style[name];
 },

 /** @id Element.firstChild */
 firstChild : function(oEl) {
 for (oEl = oEl.firstChild; oEl && oEl.nodeType != 1; oEl = oEl.nextSibling);
 return oEl;
 },

 /** @id Element.previousSibling */
 previousSibling : function(oEl) {
 for (oEl = oEl.previousSibling; oEl && oEl.nodeType != 1; oEl = oEl.previousSibling);
 return oEl;
 },

 /** @id Element.nextSibling */
 nextSibling : function(oEl) {
 for (oEl = oEl.nextSibling; oEl && oEl.nodeType != 1; oEl = oEl.nextSibling);
 return oEl;
 },

 /**
 * CSS Query Engine
 * @author Hooriza
 */
 _cache : {},

 _regexp : {
 bparse : /([^>|\+|\s]+)\s*([>|\+]?)\s*/g,
 fparse : /\s*([>|\+]?)\s*([^>|\+|\s]+)/g,

 tag : /^([\w-]+)/,
 id : /#([\w-]+)/g,
 cls : /\.([\w-]+)/g,

 pseudo : /:([\w-]+(\(.*\))?)/g,
 pseudoparse : /([\w-]+)(\((.*)\))?/,

 attr : /\[([^\]]+)]/g,
 attrparse : /^(\[)([\w-]+)([!|\^|\$|\*]?=)(.*)(\])$/
 },

 _agent : $Agent(),

 _getElementsByTagName : function(oEl, sTagName) {

 if (this._agent.IE55 && sTagName == "*")
 return oEl.all;

 return oEl.getElementsByTagName(sTagName);

 },

 _previousNode : function(o) {
 while (o && (o = o.previousSibling) && o.nodeType != 1);
 return o;
 },

 _nextNode : function(o) {
 while (o && (o = o.nextSibling) && o.nodeType != 1);
 return o;
 },

 _nodeIndex : function(o) {
 var idx = 1;
 for (var child = o.parentNode.firstChild; child && child != o; child = this._nextNode(child), idx++);
 return idx;
 },

 _pseudo : {

 /*
 nth_child : function(o, arg) {
 var idx = this._nodeIndex(o);

 if (/^[0-9]+$/.test(arg))
 return (idx == parseInt(arg));

 if (arg == "even")
 return (idx % 2 == 0);
 else if (arg == "odd")
 return (idx % 2 == 1);

 return false;
 },
 */

 first_child : function(o) {
 return Element._previousNode(o) ? false : true;
 },

 last_child : function(o) {
 return Element._nextNode(o) ? false : true;
 },

 empty : function(o) {
 return o.firstChild ? false : true;
 },

 contains : function(o, arg) {
 return o.innerHTML.indexOf(arg) > -1;
 }

 },

 _filter : function(selector, backward) {

 var filter = {};

 var declare = {}, cond = [ 'true' ];
 var varname, func = '';

 var id, tag, classes, pseudoes, attres;

 // id

 if (id = selector.match(this._regexp.id)) {
 if (id[1]) return false;
 filter.id = id[0].substr(1);
 }

 // tagName
 tag = selector.match(this._regexp.tag);
 filter.tag = (tag && tag[1]) || '*';

 // class
 if (classes = selector.match(this._regexp.cls)) {
 for (var cls, i = 0; cls = classes[i]; i++)
 cond.push('Element.hasClass(o,"' + cls.substr(1) + '")');
 }

 // pseudo
 if (pseudoes = selector.match(this._regexp.pseudo)) {
 for (var pseudo, i = 0; pseudo = pseudoes[i]; i++) {
 pseudo = pseudo.substr(1).match(this._regexp.pseudoparse);
 cond.push('Element._pseudo.' + pseudo[1].replace('-', '_') + '(o, "' + pseudo[3] + '")');
 }
 }

 // attribute
 if (attres = selector.match(this._regexp.attr)) {
 for (var attr, i = 0; attr = attres[i]; i++) {

 attr = attr.match(this._regexp.attrparse); // 2, 3, 4
 varname = 'v_' + attr[2];

 if (!declare[attr[2]]) {
 func += 'var ' + varname + ' = o.getAttribute("' + attr[2] + '") || "";\n';
 declare[attr[2]] = true;
 }

 switch (attr[3]) {
 case '=':
 cond.push(varname + ' == "' + attr[4] + '"');
 break;
 case '!=':
 cond.push(varname + ' != "' + attr[4] + '"');
 break;
 case '^=':
 cond.push(varname + '.indexOf("' + attr[4] + '") == 0');
 break;
 case '$=':
 cond.push(varname + '.substr(' + varname + '.length - "' + attr[4] + '".length) == "' + attr[4] + '"');
 break;
 case '*=':
 cond.push(varname + '.indexOf("' + attr[4] + '") > -1');
 break;
 default:
 cond.push(varname + ' != null');
 }

 }
 }

 if (backward) {
 if (filter.tag && filter.tag != '*')
 cond.push('(casei ? o.tagName.toLowerCase() == "' + filter.tag.toLowerCase() + '" : o.tagName == "' + filter.tag + '")');

 if (filter.id) cond.push('o.id == "' + filter.id + '"');
 }

 filter.func = new Function('o', 'casei', func + '\nreturn (' + cond.join(" && ") + ')');
 // alert(filter.func);

 return filter;
 },

 _traceNode : function(root, o, selectors, idx, casei) {

 if (idx == -1) return true;

 var selector = selectors[idx];

 switch (selector.type) {
 case '':
 for (o = o.parentNode; o != root; o = o.parentNode)
 if (selector.filter.func(o, casei))
 if (this._traceNode(root, o, selectors, idx - 1, casei))
 return true;

 break;

 case '>':
 o = o.parentNode;
 if (o && o != root && selector.filter.func(o, casei))
 if (this._traceNode(root, o, selectors, idx - 1, casei))
 return true;

 break;

 case '+':
 o = this._previousNode(o);

 if (o && selector.filter.func(o, casei))
 if (this._traceNode(root, o, selectors, idx - 1, casei))
 return true;

 break;

 }

 return false;

 },

 _compile : function(query, bquery, fquery) {

 if (!this._cache[query]) {

 var cache = { backward : [], forward : [] };
 var self = this;

 bquery.replace(this._regexp.bparse, function(all, selector, type) {

 cache.backward.push({
 'type' : type,
 'filter' : self._filter(selector, true)
 });

 });

 var i = 0, method;
 var code = [];

 fquery.replace(this._regexp.fparse, function(all, type, selector) {

 cache.forward.push({
 'type' : type,
 'filter' : self._filter(selector)
 });

 switch (type) {
 case '>':
 method = "_getChildren";
 break;
 case '':
 method = "_getOffspring";
 break;
 case '+':
 method = "_getBrother";
 break;
 }

 code.push('result = Element.' + method + '(result, forward[' + (i++) + '].filter, casei);');

 });

 code.push('return result;');
 cache.filter = new Function('result', 'forward', 'casei', code.join('\n'));

 this._cache[query] = cache;

 }

 return this._cache[query];

 },

 _getChildren : function(objs, filter, casei) {

 var ret = [];
 var child;

 for (var i = 0, obj; obj = objs[i]; i++) {

 for (child = obj.firstChild; child; child = this._nextNode(child))
 if (filter.func(child, casei)) ret.push(child);

 }

 return ret;

 },

 _getOffspring : function(objs, filter, casei) {

 var ret = [];
 var childs;

 for (var i = 0, obj; obj = objs[i]; i++) {

 childs = this._getElementsByTagName(obj, filter.tag);
 for (var j = 0, child; child = childs[j]; j++)
 if (filter.func(child, casei)) ret.push(child);

 }

 return ret;

 },

 _getBrother : function(objs, filter, casei) {

 var ret = [];
 var child;

 for (var i = 0, obj; obj = objs[i]; i++) {

 if (child = this._nextNode(obj))
 if (filter.func(child, casei)) ret.push(child);

 }

 return ret;

 },

 query : function(query, root) {

 root = root || document;

 var parts = query.match(/(.*#[\w]+[^>|\+|\s]*)([>|\+|\s].*)/) || [];

 var cache = Element._compile(query, parts[1] || query, parts[2] || '');

 var idx = cache.backward.length - 1;
 var filter = cache.backward[idx].filter;

 var sands = [];
 var result = [];

 var casei = (root == document || (root.ownerDocument || root.document) == document);

 if (filter.id)
 sands.push(document.getElementById(filter.id));
 else
 sands = Element._getElementsByTagName(root, casei ? filter.tag.toLowerCase() : filter.tag);

 for (var i = 0, sand; sand = sands[i]; i++) {

 if (filter.func(sand, casei))
 if (Element._traceNode(root, sand, cache.backward, idx - 1, casei))
 result.push(sand);

 }

 return cache.filter(result, cache.forward, casei);

 }

}

$$ = Element.query;

/**
 * @projectDescription Jindo Event Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */


/** @id Event */
var Event = {
 /** @id Event.register */
 register : function(oEl, sEvent, pFunc) {

 oEl = $(oEl);
 if (oEl.addEventListener) {
 if (sEvent == "mousewheel") sEvent = "DOMMouseScroll";
 oEl.addEventListener(sEvent, pFunc, false);
 } else if(oEl.attachEvent) {
 oEl.attachEvent('on'+sEvent, pFunc);
 }
 },
 /** @id Event.unregister */
 unregister : function(oEl, sEvent, pFunc) {
 oEl = $(oEl);
 if (oEl.removeEventListener) {
 if (sEvent == "mousewheel") sEvent = "DOMMouseScroll";
 oEl.removeEventListener(sEvent, pFunc, false);
 } else if(oEl.detachEvent) {
 oEl.detachEvent('on'+sEvent, pFunc);
 }
 },
 /** @id Event.ready */
 ready : function(evt) {
 var e = evt || window.event;
 var b = document.body;

 switch (e.type.toUpperCase()) {
 case "DOMMOUSESCROLL":
 e.delta = -e.detail;
 break;

 case "MOUSEWHEEL":
 e.delta = e.wheelDelta / 40;
 break;
 }

 var scrollPos = [
 b.scrollLeft || document.documentElement.scrollLeft,
 b.scrollTop || document.documentElement.scrollTop
 ];

 /** Extend For Browser Event */
 JINDO.extend(e, {
 element : e.target || e.srcElement,
 related_element : e.relatedTarget || (e.type == "mouseover" ? e.fromElement : e.toElement),
 page_x : e.pageX || e.clientX+scrollPos[0]-b.clientLeft,
 page_y : e.pageY || e.clientY+scrollPos[1]-b.clientTop,
 layer_x : e.offsetX || e.layerX - 1,
 layer_y : e.offsetY || e.layerY - 1,
 key : {
 alt : e.altKey,
 ctrl : e.ctrlKey,
 shift : e.shiftKey,
 up : [38,104].has(e.keyCode),
 down : [40,98].has(e.keyCode),
 left : [37,100].has(e.keyCode),
 right : [39,102].has(e.keyCode),
 enter : (e.keyCode==13)
 },
 mouse : {
 left : (e.which&&e.button==0)||!!(e.button&1),
 middle : (e.which&&e.button==1)||!!(e.button&4),
 right : (e.which&&e.button==2)||!!(e.button&2)
 },
 of : function(parent) {

 var el;
 var ret = true;

 if (el = this.element)
 ret &= Element.has(parent, el);

 if (el = this.related_element)
 ret &= Element.has(parent, el);

 return ret;
 },
 stop : function() { Event.stop(this); }
 });

 return e;
 },
 /** @id Event.stop */
 stop : function(e) {
 if (e.preventDefault) e.preventDefault();
 if (e.stopPropagation) e.stopPropagation();

 e.returnValue = false;
 e.cancelBubble = true;
 }
}

/** @id Event.stopProc */
Event.stopProc = function(e) {
 Event.stop(e || window.event);
}
/**
 * @projectDescription Jindo Base Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */

/** @id document.getElementByClassName */
document.getElementsByClassName = function(className, oParent) {
 var a = [].load(($(oParent) || document.body).getElementsByTagName('*'));
 var r = new RegExp('(^|\\s)'+className+'($|\\s)','gi');
 return a.filter(function(v){ return r.test(v.className); });
}


/** @id $ */
function $() {
 var ret = [];
 for(var i=0; i < arguments.length; i++) {
 if (typeof arguments[i] == 'string') {
 ret[ret.length] = document.getElementById(arguments[i]);
 } else {
 ret[ret.length] = arguments[i];
 }
 }
 return ret[1]?ret:ret[0];
}

/** @id $A */
function $A(collection) {
 var ret = [];
 for(var i=0; i < collection.length; i++) ret[ret.length] = collection[i];
 return ret;
}

/** @id $C */
function $C(tag) {
 return document.createElement(tag);
}

/** @id $H */
function $H(obj) {
 var oHash, hashType = function(){};

 JINDO.extend(hashType.prototype, {
 keys : function() {
 var ret = new Array;
 for(var k in this) {
 if (this.propertyIsEnumerable(k) && typeof Object.prototype[k] == 'undefined') ret.push(k);
 }
 return ret;
 },
 values : function() {
 var ret = new Array;
 var keys = this.keys();
 for(var i=0; i < keys.length; i++) {
 ret.push(this[keys[i]]);
 }
 return ret;
 },
 each : function(iter) {
 var keys = this.keys();
 for(var i=0; i < keys.length; i++) {
 iter(this[keys[i]], keys[i]);
 }
 },
 size : function() {
 return this.keys().length;
 }
 });

 oHash = new hashType;

 try { JINDO.extend(oHash, obj) }catch(e){alert(e)};

 return oHash;
}

/** @id $Agent */
function $Agent() {
 var isOpera = !!(window.opera);
 var nu = navigator.userAgent;
 var isIE = !isOpera && /MSIE/.test(nu), ie5=false, ie55=false, ie6=false, ie7=false, macIE=false;

 if (isIE) {
 /MSIE ([0-9\.]+)/.exec(nu);
 var ver = parseFloat(RegExp.$1);
 switch (ver) {
 case 5 : ie5 =true; break;
 case 5.5 : ie55=true; break;
 case 6 : ie6=true; break;
 case 7 : ie7=true; break;
 default :
 }
 }

 return {
 IE : isIE,
 IE5 : isIE && ie5,
 IE55 : isIE && ie55,
 IE6 : isIE && ie6,
 IE7 : isIE && ie7,
 macIE : isIE && macIE,
 Gecko : /Gecko/.test(nu),
 Opera : isOpera,
 Safari : /WebKit/.test(nu),
 KHTML : /KHTML/.test(nu)
 };
}

/**
 * legacy comportability
 */
(function(){
 var a = {
 push : function() {
 for(var i=0; i < arguments.length; i++) {
 this[this.length] = arguments[i];
 }
 return this.length;
 },
 pop : function() {
 var el = this[Math.max(this.length-1,0)];
 if (this.length) this.length--;
 return el;
 },
 shift : function() {
 var el = this[0];
 for(var i=0; i < this.length-1; i++) {
 this[i] = this[i+1];
 }
 if (this.length) this.length--;
 return el;
 },
 unshift : function() {
 var a = new Array(arguments.length+this.length);
 for(var i=a.length-1; i > -1; i--) {
 a[i] = (i < arguments.length)?arguments[i]:this[i-arguments.length];
 }
 for(var i=0; i < a.length; i++) {
 this[i] = a[i];
 }
 },
 filter : function(func /*, obj*/) {
 var l = this.length, a=[], obj=arguments[1],v;
 for(var i=0; i < l; i++) {
 v = this[i];
 if (obj) { if (func.call(obj,v,i,this)) a.push(v); }
 else { if (func(v,i,this)) a.push(v); }
 }
 return a;
 },
 map : function(func /*, obj*/) {
 var l = this.length, a=new Array(l), obj=arguments[1];
 /* fixed variable name : len -> l By TarauS */
 for (var i = 0; i < l; i++) {
 a[i] = obj?func.call(obj,this[i],i,this):func(this[i],i,this);
 }
 return a;
 },
 indexOf : function(el/*, from*/) {
 var from = arguments[1] || 0;
 if (from < 0) from += this.length;
 for(var i=from; i < this.length; i++) {
 if (this[i] == el) return i;
 }
 return -1;
 },
 lastIndexOf : function(el/*, from*/) {
 var from = (typeof arguments[1] == 'undefined')?this.length:arguments[1];
 if (from < 0) from += this.length;
 for(var i=from; i >= 0; i--) {
 if (this[i] == el) return i;
 }
 return -1;
 }
 };
 var f = {
 call : function(obj) {
 var r,a = [];
 ( obj||{} ).prototype['__jindo_call__'] = this;

 switch (arguments.length) {
 case 1: return obj.__jindo_call__(); break;
 case 2: return obj.__jindo_call__(arguments[1]); break;
 case 3: return obj.__jindo_call__(arguments[1],arguments[2]); break;
 default:
 for(var i=1;i<arguments.length;i++) a.push('arguments['+i+']');
 return eval('obj.__jindo_call__('+a.join(',')+')');
 }
 },
 apply : function(obj,arg) {
 var r,a = []; arg = arg || [];
 ( obj||{} ).prototype['__jindo_apply__'] = this;

 switch(arg.length) {
 case 0: return obj.__jindo_apply__();
 case 1: return obj.__jindo_apply__(arg[0]);
 case 2: return obj.__jindo_apply__(arg[0],arg[1]);
 default:
 for(var i=0;i<arg.length;i++) a.push('arg['+i+']');
 return eval('obj.__jindo_apply__('+a.join(',')+')');
 }
 }
 };
 var o = {
 // JScript 5.0 and earlier version can't support this method
 propertyIsEnumerable : function(k) {
 return (typeof Object[k] == 'undefined') && (typeof Object.prototype[k] == 'undefined');
 }
 };
 for(var x in a) { if (typeof Array.prototype[x] == 'undefined') Array.prototype[x] = a[x] };
 for(var x in f) { if (typeof Function.prototype[x] == 'undefined') Function.prototype[x] = f[x] };
 for(var x in o) { if (typeof Object.prototype[x] == 'undefined') Object.prototype[x] = o[x] };
})();

/** avoid error */
if (typeof window.debugg == 'undefined') {
 (function(){
 window.debugg = {};
 var names = ['log', 'assert', 'time', 'timeEnd'];
 for(var i=0; i < names.length; i++) {
 window.debugg[names[i]] = function(){};
 }
 })();
}
/**
 * @projectDescription Jindo Function Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 */

/** Extend Protoype of Function */
JINDO.extend(Function.prototype, {
 /** @id Function.prototype.bind */
 bind : function(obj) {
 var f=this, a=$A(arguments);a.shift();
 return function() {
 return f.apply(obj, a);
 }
 },
 /** @id Function.prototype.bindForEvent */
 bindForEvent : function(obj) {
 var f=this, a=$A(arguments);
 return function(e) {
 a[0] = Event.ready(e);
 return f.apply(obj, a);
 }
 },
 /** @id Function.prototype.owner */
 owner : function(thisobj) {
 var f=this;
 return function() {
 return f.apply(thisobj, $A(arguments));
 }
 }
});
/**
 * @projectDescription Jindo Json Extend
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.1
 * @since 0.2.9 <jindo.do.js>
 *
 * @import extend.$H
 */

/** @id Object.toJSON */
Object.prototype.toJSON = function() {
 return JINDO.obj2json(this);
};

/** @id Array.toJSON */
Array.prototype.toJSON = function() {
 return JINDO.obj2json(this);
};

/** @id JINDO.obj2xml */
JINDO.obj2json = function(obj) {
 switch (typeof obj) {
 case 'boolean':
 case 'number':
 return obj.toString();
 case 'string':
 obj = obj.replace(/(["|\\])/g, '\\$1');
 /*
 obj = obj.replace(new RegExp('(\\|"|\\/)','g'), '\\$1');
 obj = obj.replace(new RegExp('\n','g'), '\\n');
 obj = obj.replace(new RegExp('\r','g'), '\\r');
 obj = obj.replace(new RegExp('\t','g'), '\\t');
 */
 return '"' + obj + '"';
 case 'object':
 if (obj === null) return 'null';
 if (obj instanceof Function) return JINDO.obj2json(obj());
 if (obj instanceof Array) {
 var a = new Array;
 for(var i=0; i < obj.length; i++) {
 a.push(JINDO.obj2json(obj[i]));
 };
 return '['+a.join(',')+']';
 }
 if (obj instanceof Object) {
 var a = new Array;
 $H(obj).each(function(v,k){
 if (v instanceof Function) v = obj[k]();
 a.push(JINDO.obj2json(k)+':'+JINDO.obj2json(v));
 });
 return '{'+a.join(',')+'}';
 }
 case 'undefined':
 default:
 return '""';
 }
};

/** @id JINDO.xml2obj */
JINDO.xml2obj = function(xml) {
 var obj = {}, que = [], depth = 0;
 var reTrim = /^\s+|\s+$/g;
 var reNum = /^[0-9]+(\.[0-9]+)?$/;

 var parse_value = function(val) {
 val = val || "";
 if (val.substr(0,9) == '<![CDATA[' && val.substr(val.length-3,3) == ']]>') return val.substring(9, val.length-3);

 val = val.replace(reTrim, '');
 if (reNum.test(val)) return ((RegExp.$1).length?parseFloat:parseInt)(val);
 if (val == 'true' || val == 'false') return new Boolean(val);

 return val.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&');
 };

 var parse_attr = function(oobj, str) {
 var parsed = false;
 str.replace(/([^=\s]+)\s*=\s*"([^\"]*)"/g, function(a0,a1,a2) {
 parsed = true;
 oobj[a1] = parse_value(a2);
 });

 return parsed;
 };

 xml = xml.replace(/<(\?|\!-)[^>]*>/g,'').replace(/>\s+</g, '><'); // remove meanless code and white space
 xml = xml.replace(/<([0-9a-zA-Z:_\-]+)([^>]*?)>(<\!\[CDATA\[(?:.|\s)*?\]\]>|\s*[^<>]*\s*)?<\/\1>|<([0-9a-zA-Z:_\-]+)([^>]*?)>|<\/([0-9a-zA-Z:_\-]+)>/ig, function(m0,m1,m2,m3,m4,m5,m6) {
 var parsed = false;
 var is_closed = false;
 var node = {};
 var tag = '';

 // close tag
 if ((m6||'').length) {
 depth--;
 return '';
 }

 if (que.length == 0) {
 que[depth] = obj;
 if ((m1||'').length) {
 if ((m2||'').length) parsed = parse_attr(obj, m2);
 if ((m3||'').length) {
 if (parsed) obj['_nodeValue'] = parse_value(m3);
 else obj = parse_value(m3);
 }
 } else if((m5||'').length) {
 parse_attr(obj, m5);
 }

 return '';
 }

 // append new object
 tag = m1 || m4;
 if (typeof que[depth][tag] == 'undefined') {
 que[depth][tag] = node;
 } else {
 if (que[depth][tag].constructor == Array) que[depth][tag].push(node);
 else que[depth][tag] = [que[depth][tag], node];
 }

 if ((m1||'').length) {
 // node with value
 parsed = parse_attr(node, m2||'');

 if (m3.length && parsed) {
 node['_nodeValue'] = parse_value(m3);
 } else {
 var q = que[depth][tag];
 if (q.constructor == Array) q[q.length-1] = parse_value(m3);
 else que[depth][tag] = parse_value(m3);
 }

 return '';
 }

 if ((m4||'').length) {
 // open tag
 m5 = m5 || '';
 if (m5.substr(m5.length-1,1) == '/') {
 is_closed = true;
 m5 = m5.substr(0, m5.length-1);
 }

 parsed = parse_attr(node, m5);
 }

 if (!is_closed) que[++depth] = node;

 return '';
 });

 return obj;
}

/** @id JINDO.xml2json */
JINDO.xml2json = function(xml) {
 return JINDO.xml2obj(xml).toJSON();
}
/**
 * @projectDescription NHN Effect Project - Ju
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.2.6
 */

 /** @id Ju */
var Ju = {};

/**
 * general purpose animate class
 */
Ju.Animate = Class({
 _timer : null,
 _stopped : false,
 duration : 0.3, // seconds
 interval : 0.01, // seconds
 start : function() {
 this.onStart();
 this._stopped = false;

 // event start
 this.__animate = this._animate.bind(this);
 this._animate();
 },
 _animate : function() {
 var result = this.onProgress();

 if (result && !stopped) {
 this._timer = setTimeout(this.__animate, this.interval);
 }
 },
 stop : function() {
 clearTimeout(this._timer);
 this._stopped = true;

 this.onFinish();
 },
 // abstract
 onStart : function(){},
 onProgress : function(){},
 onFinish : function(){}
});
/**
 * Blind effect
 * @author gony
 * @version 0.2.1
 */
Ju.Blind = Class({
 __init : function(id) {
 this.options = JINDO.extend({
 duration : 0.3
 }, arguments[1]||{})

 this.element = $(id);
 this._timer = null;

 this.element.style.display = (this.element.offsetHeight > 0)?'block':'none';
 },
 toggle : function(){
 var h = 0;
 var hidden = (this.element.style.display == 'none');

 // no-action during animating
 if (this._timer != null) return;
 if (hidden) {
 var old_pos = this.element.style.position;
 var old_vis = this.element.style.visibility;

 this.element.style.visibility = 'hidden';
 this.element.style.position = 'absolute';
 Element.show(this.element);
 }

 h = this.element.offsetHeight;
 this.element.style.height = h+'px';

 // correct size
 if (this.element.offsetHeight != this.element.style.height) h += parseInt(this.element.style.height)-this.element.offsetHeight;

 if (hidden) {
 this.element.style.height = '0px';
 this._animateCur = 0;
 this._animateTo = h;
 } else {
 this.element.style.height = h+'px';
 this._animateCur = h;
 this._animateTo = 0;
 }
 this._animateStep = (this._animateTo-this._animateCur)/(this.options.duration*50);

 this.element.style.overflow = 'hidden';

 if (hidden) {
 this.element.style.visibility = old_vis;
 this.element.style.position = old_pos;
 }

 this.animate();
 },
 animate : function(){
 var sign = this._animateStep/Math.abs(this._animateStep);
 var nextVal = Math.floor(this._animateCur+this._animateStep);

 this._animateCur += this._animateStep;
 if (sign < 0 && nextVal < 0) nextVal = 0;
 else if (sign > 0 && nextVal > this._animateTo) nextVal = this._animateTo;

 this.element.style.height = nextVal+'px';

 if (nextVal != this._animateTo) {
 this._timer = setTimeout(this.animate.bind(this), 20);
 } else {
 this._animateCur = nextVal;
 this.element.style.display = this._animateTo?'block':'none';
 this.element.style.overflow = '';
 this.element.style.height = '';
 clearTimeout(this._timer);
 this._timer = null;
 }
 }
});
/**
 * Static banner
 *
 * @author gony
 * @changelog
 * 2006. 11. 16 release
 */

Ju.Static = Class({
 __init : function(id) {
 this.obj = $(id);
 this.options = JINDO.extend({
 enable : true,
 slowDown : false,
 topTrack : false,
 topMargin : 0,
 division : 50,
 limitArea : null
 }, arguments[1] || {});

 var offset = this.getPos(this.obj)[0];

 if (this.obj.style.position == '') {
 this.obj.style.position = 'absolute';
 if (offset != this.getPos(this.obj)[0]) {
 this.obj.style.position = 'relative';
 }
 }

 if (this.obj.style.position == 'absolute') {
 this.obj.style.top = this.obj.offsetTop+'px';
 } else {
 if (offset == this.getPos(this.obj)[0]) this.obj.style.top = '0px';
 else this.obj.style.top = (this.getPos(this.obj)[0] - offset) + 'px';
 }

 this._scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
 this._startTop = parseInt(this.obj.style.top);
 this._startPosY = this.getPos(this.obj)[0];

 // event binding
 this.onscroll = this.onScroll.bindForEvent(this);
 Event.register(window, 'scroll',this.onscroll);
 },
 onScroll : function(e) {
 if (!this.options.enable) return;

 var scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
 var diff = scrollTop - this._scrollTop;

 if (this._timer != null) {
 clearTimeout(this._timer);
 this._timer = null;
 }

 this._targetTop = this._startTop + diff;

 if (this.options.topTrack) {
 if (scrollTop + this.options.topMargin > this._startPosY) {
 this._targetTop -= this._startPosY - this.options.topMargin;
 } else {
 this._targetTop = this._startTop;
 }
 diff = this._targetTop - this._startTop;
 }

 if (this.options.limitArea) {
 var objY = this.getPos(this.obj)[0];
 var area = $(this.options.limitArea);
 var posY = this.getPos(area)[0];

 if (this._startPosY + diff + this.obj.offsetHeight > posY + area.offsetHeight) {
 diff = posY + area.offsetHeight - this._startPosY - this.obj.offsetHeight;
 this._targetTop = this._startTop + diff;
 }
 }

 this._timer = setTimeout(this.animate.bind(this), 1);
 },
 animate : function() {
 var currTop = parseInt(this.obj.style.top);
 var unit = (this._targetTop-currTop)<0?-1:1;
 var step = Math.ceil(this._targetTop - this._startTop / 100);

 if (this.options.slowDown) {
 step = parseInt((this._targetTop - currTop) / this.options.division);
 if (step == 0) step = unit;
 }
 if ((step > 0 && currTop + step > this._targetTop) || (step < 0 && currTop + step < this._targetTop)) {
 step = this._targetTop - currTop;
 }
 if (this._targetTop != currTop) {
 this.obj.style.top = (currTop+step)+'px';
 setTimeout(this.animate.bind(this), 10);
 }
 },
 getPos : function(obj) {
 var top=0, left=0;
 while(obj.offsetParent) {
 top += obj.offsetTop;
 left += obj.offsetLeft;
 obj = obj.offsetParent;
 }

 return [top, left];
 },
 setEnable : function(enable) {
 this.options.enable = enable;
 }
});
/**
 * @projectDescription Control Layer
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team <TarauS>
 * @version 0.5
 */

Ju.controlLayer = Class({
 _bCursorOver : false,
 _bBlockReOpen : false,
 _oElement : null,
 _sElementID : '',

 __init : function(){
 this.options = JINDO.extend({
 onShow : function(oElement){},
 onHide : function(){}
 }, arguments[0]||{});

 this._checkFunction = this._checkLayer.bind(this);
 this._onMouseOverFunction = this._onMouseOver.bind(this);
 this._onMouseOutFunction = this._onMouseOut.bind(this);
 },
 getRealPosition : function(oElement) {
 var oOriginalElement = oElement;
 var oPosition = { top : 0, left : 0 };

 for (oElement = oOriginalElement; oElement; oElement = oElement.offsetParent) {
 oPosition.top += oElement.offsetTop;
 oPosition.left += oElement.offsetLeft;
 }

 for(oElement = oOriginalElement.parentNode; oElement; oElement = oElement.parentNode) {
 if(oElement.tagName.toLowerCase() == "body") return oPosition;
 oPosition.top -= oElement.scrollTop || 0;
 oPosition.left -= oElement.scrollLeft || 0;
 }

 return oPosition;
 },
 show : function(sLayerID, eEvent, nTop, nLeft, nBaseAlign){
 if(this._oElement == null || this._sElementID != sLayerID) this._bBlockReOpen = false;
 this._sElementID = sLayerID
 this._oElement = $(sLayerID);

 if(this._bBlockReOpen == false){
 if(eEvent){
 this._oElement.style.position = "absolute";
 this._oElement.style.zIndex = "1000";
 var eEvent = eEvent || window.event;
 var oTarget = eEvent.target || eEvent.srcElement;
 var oPosition = this.getRealPosition(oTarget);

 if(!nBaseAlign){
 if(nTop || nLeft) nBaseAlign = 2;
 else nBaseAlign = 3;
 }

 nTop = nTop ? (oPosition.top + nTop) : oPosition.top;
 nLeft = nLeft ? (oPosition.left + nLeft) : oPosition.left;
 this._oElement.style.display = "block"
 switch(nBaseAlign){
 case 1:
 this._oElement.style.top = nTop - oTarget.offsetHeight + "px";
 this._oElement.style.left = nLeft + "px";
 break;
 case 2:
 this._oElement.style.top = nTop + "px";
 this._oElement.style.left = nLeft + "px";
 break;
 case 3:
 this._oElement.style.top = nTop + oTarget.offsetHeight + "px";
 this._oElement.style.left = nLeft + "px";
 break;
 case 4:
 this._oElement.style.top = nTop - oTarget.offsetHeight + "px";
 this._oElement.style.left = nLeft - (this._oElement.offsetWidth - oTarget.offsetWidth) + "px";
 break;
 case 5:
 this._oElement.style.top = nTop + "px";
 this._oElement.style.left = nLeft - (this._oElement.offsetWidth - oTarget.offsetWidth) + "px";
 break;
 case 6:
 this._oElement.style.top = nTop + oTarget.offsetHeight + "px";
 this._oElement.style.left = nLeft - (this._oElement.offsetWidth - oTarget.offsetWidth) + "px";
 break;
 default:
 break;
 }
 }else{
 if(nTop && nLeft){
 this._oElement.style.position = "absolute";
 this._oElement.style.zIndex = "1000";
 this._oElement.style.top = nTop+'px';
 this._oElement.style.left = nLeft+'px';
 this._oElement.style.display = "block";
 }
 }
 Event.register(this._oElement, "mouseover", this._onMouseOverFunction);
 Event.register(this._oElement, "mouseout", this._onMouseOutFunction);
 Event.register(document, "mousedown", this._checkFunction);
 this.options.onShow(this._oElement);
 }
 },
 hide : function(sLayerID){
 if(sLayerID) this._oElement = $(sLayerID);
 this._oElement.style.display = "none";
 Event.unregister(this._oElement, "mouseover", this._onMouseOverFunction);
 Event.unregister(this._oElement, "mouseout", this._onMouseOutFunction);
 Event.unregister(document, "mousedown", this._checkFunction);
 this.options.onHide(this._oElement);
 },
 _checkLayer : function(){
 if(this._bCursorOver == false){
 this.hide();
 this._bBlockReOpen = true;
 setTimeout(function(){this._bBlockReOpen=false;}.bind(this),300);
 }
 },
 _onMouseOver : function(){
 this._bCursorOver = true;
 },
 _onMouseOut : function(){
 this._bCursorOver = false;
 }
});
/**
 * @projectDescription NHN Jindo Widget Project - Ku
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 * @version 0.2.6
 */

/** @id Ku */
Ku = {};
/**
 * Color picker widget
 *
 * @author gony
 * @version 0.6
 */

Ku.Colorpicker = Class({
 __init : function(id) {
 var e = this._element = $(id);
 this.useFilter = !!(this._element.filters);
 this.useCanvas = !!($C('canvas').getContext);
 this.options = JINDO.extend({
 width : 201,
 huePanelHeight : 12,
 colPanelHeight : 170,
 onchange : function(color) {}
 }, arguments[1]||{});
 var o = this.options;

 // for non-supported browser
 if (!this.useFilter && !this.useCanvas) {
 Element.hide(this._element);
 return false;
 }

 e.style.width = o.width+'px';

 // create panels
 var c = this._col_panel = this.useCanvas?$C('canvas'):$C('div');
 var h = this._hue_panel = this.useCanvas?$C('canvas'):$C('div');

 Element.setCSS(e.appendChild(c), {
 border : '1px solid #C5CAD0',
 background : 'white',
 cursor : 'crosshair',
 zIndex : 0
 });
 Element.setCSS(e.appendChild(h), {
 marginTop : '6px',
 border : '1px solid #C5CAD0',
 background : 'white',
 cursor : 'crosshair',
 position : 'relative',
 zIndex : 0
 });

 if (this.useCanvas) {
 c.width = h.width = o.width;
 c.height = o.colPanelHeight;
 h.height = o.huePanelHeight;
 } else {
 c.style.width = h.style.width = o.width+'px';
 c.style.height = o.colPanelHeight+'px';
 h.style.height = o.huePanelHeight+'px';
 c.style.position = h.style.position = 'relative';
 h.style.overflow = 'hidden';
 }

 // event binding
 this.ondown_hue = this.ondownHue.bindForEvent(this);
 this.onmove_hue = this.onmoveHue.bindForEvent(this);
 this.ondown_col = this.ondownCol.bindForEvent(this);
 this.onmove_col = this.onmoveCol.bindForEvent(this);

 Event.register(c, 'mousedown', this.ondown_col);
 Event.register(c, 'mousemove', this.onmove_col);
 Event.register(h, 'mousedown', this.ondown_hue);
 Event.register(h, 'mousemove', this.onmove_hue);
 Event.register(document.body, 'mouseup', this.onup.bind(this));

 // draw hue bar
 this.drawHueBar();

 // set initial value
 this.setHSV(0,100,100);
 },
 setRGB : function(r,g,b) {
 var hsv = this.rgb2hsv(r,g,b);
 this.setHSV(hsv.h, hsv.s, hsv.v);
 },
 setHSV : function(h,s,v) {
 var rgb = this.hsv2rgb(h, s, v);
 var sRGB = this.rgb2hex(rgb.r, rgb.g, rgb.b);

 this.clear();
 this.gradFill('#'+sRGB,1);
 this.gradFill('#000000',0);

 this._curHSV = {h:h,s:s,v:v};
 },
 ondownHue : function(e) {
 if (e.mouse.left) {
 this.movingHue = true;

 // fire onmousemove event
 this.onmoveHue(e);
 }
 },
 onmoveHue : function(e) {
 if (!this.movingHue) return;
 var pos = this._getOffsetXY(e);

 // calculate
 var h = (pos.x/(this._hue_panel.offsetWidth-1))*360;
 h = Math.min(Math.max(h, 0), 360);

 this.setHSV(h, this._curHSV.s, this._curHSV.v);
 },
 ondownCol : function(e) {
 if (e.mouse.left) {
 this.movingCol = true;

 // fire onmousemove event
 this.onmoveCol(e);
 }
 },
 onmoveCol : function(e) {
 if (!this.movingCol) return;
 var pos = this._getOffsetXY(e);

 // calculate
 var s = (pos.x/(this._col_panel.offsetWidth-1))*100;
 var v = (1 - pos.y/(this._col_panel.offsetWidth-1))*100;

 s = Math.min(Math.max(s, 0), 100);
 v = Math.min(Math.max(v, 0), 100);

 var rgb = this.hsv2rgb(this._curHSV.h, s, v);
 var sRGB = this.rgb2hex(rgb.r, rgb.g, rgb.b);

 this.options.onChange(sRGB);
 },
 onup : function() {
 this.movingCol = false;
 this.movingHue = false;
 },
 _getOffsetXY : function(e) {
 if (!e.offsetX && e.layerX) e.offsetX = e.layerX - e.element.offsetLeft;
 if (!e.offsetY && e.layerY) e.offsetY = e.layerY - e.element.offsetTop;

 var x = e.offsetX, y = e.offsetY;

 if (this.useFilter || /Opera/.test(navigator.userAgent)) {
 x++; y++;
 } else { // for firefox
 x--; y--;
 }

 x = Math.min(Math.max(x,0), e.element.offsetWidth-1);
 y = Math.min(Math.max(y,0), e.element.offsetHeight-1);

 return {x : x, y : y};
 },
 clear : function() {
 if (this.useCanvas) {
 var ctx = this._col_panel.getContext('2d');
 ctx.fillStyle = '#FFFFFF';
 ctx.fillRect(0,0,this._col_panel.width,this._col_panel.height);
 } else {
 this._col_panel.innerHTML = '';
 }
 },
 gradFill : function(rgb,orient) { // orient : (0)top->down, (1)left->right
 var p = this._col_panel, o = this.options;
 var w = p.offsetWidth, h = p.offsetHeight;

 if (this.useCanvas) {
 var ctx = p.getContext('2d');
 var l = ctx.createLinearGradient(0,0,orient?w:0,orient?0:h);
 rgb = this.hex2rgb(rgb);
 l.addColorStop(0, 'rgba('+rgb.r+','+rgb.g+','+rgb.b+',0)');
 l.addColorStop(1, 'rgba('+rgb.r+','+rgb.g+','+rgb.b+',1)');
 ctx.fillStyle = l;
 ctx.fillRect(0,0,w,h);
 } else {
 var m = rgb.match(/#?([0-9a-f]{6})/); rgb = m[1];
 Element.setCSS(p.appendChild($C('div')), {
 top : 0,
 left : 0,
 width : '100%',
 height : p.offsetHeight+'px',
 position : 'absolute',
 filter : "progid:DXImageTransform.Microsoft.Gradient(GradientType="+orient+", StartColorStr='#00"+rgb+"', EndColorStr='#FF"+rgb+"')"
 });
 }
 },
 drawHueBar : function() {
 var r=0, g=0, b=0, rgb, p;
 var h = this._hue_panel;

 if (this.useCanvas) {
 var ctx = h.getContext('2d');
 var l = ctx.createLinearGradient(0,0,h.offsetWidth,0);
 for (var i=0; i < 7; i++) {
 rgb = this.hsv2rgb(i/6*360, 100, 100);
 l.addColorStop(i/6, '#'+this.rgb2hex(rgb.r, rgb.g, rgb.b));
 }
 ctx.fillStyle = l;
 ctx.fillRect(0,0,h.offsetWidth,h.offsetHeight);
 } else {
 var div, sp, ep, s_rgb, e_rgb;
 for (var i=1; i < 7; i++) {
 sp = Math.floor(((i-1)/6) * (h.offsetWidth-1));
 ep = Math.floor(i/6 * (h.offsetWidth-1));
 s_rgb = this.hsv2rgb((i-1)/6*360, 100, 100);
 e_rgb = this.hsv2rgb(i/6*360, 100, 100);
 div = $C('div');

 Element.setCSS(div, {
 position : 'absolute',
 height : '100%',
 top : '0',
 left : sp+'px',
 width : ep-sp,
 filter : 'progid:DXImageTransform.Microsoft.Gradient(GradientType=1,StartColorStr="#FF'+this.rgb2hex(s_rgb.r,s_rgb.g,s_rgb.b)+'",EndColorStr="#FF'+this.rgb2hex(e_rgb.r,e_rgb.g,e_rgb.b)+'")'
 });
 this._hue_panel.appendChild(div);
 }
 }
 },
 rgb2hsv : function(r, g, b) {
 r /= 0xff; g /= 0xff; v /= 0xff;
 var h = 0, s = 0, v = 0, c = 0;
 var cmax, cmin;

 cmax = b>(cmax=(r>=g)?r:g)?b:cmax;
 cmin = b<(cmin=(r<=g)?r:g)?b:cmin;

 v = cmax; c = cmax - cmin;
 s = cmax?0:c/cmax;
 if (s) {
 if (r == cmax) {
 h = (g-b)/c;
 } else if (g == cmax) {
 h = 2 + (b-r)/c;
 } else if (b == cmax) {
 h = 4 + (4-g)/c;
 }
 if (h*=60 < 0) h += 360;
 }
 return {h : h, s : s, v : v};
 },
 hsv2rgb : function(h, s, v) {
 var r=0, g=0, b=0;
 h = h % 360;
 s /= 100;
 v /= 100;
 h /= 60;
 var i = Math.floor(h);
 var f = h-i;
 var p = v*(1-s);
 var q = v*(1-s*f);
 var t = v*(1-s*(1-f));
 switch (i) {
 case 0: r=v; g=t; b=p; break;
 case 1: r=q; g=v; b=p; break;
 case 2: r=p; g=v; b=t; break;
 case 3: r=p; g=q; b=v; break;
 case 4: r=t; g=p; b=v; break;
 case 5: r=v; g=p; b=q;break;
 case 6: break;
 }
 r = Math.floor(r*255);
 g = Math.floor(g*255);
 b = Math.floor(b*255);
 return {r:r, g:g, b:b};
 },
 rgb2hex : function(r, g, b) {
 r = r.toString(16); if (r.length==1) r = '0'+r;
 g = g.toString(16); if (g.length==1) g = '0'+g;
 b = b.toString(16); if (b.length==1) b = '0'+b;
 return r+g+b;
 },
 hex2rgb : function(hex) {
 var m = hex.match(/#?([0-9a-f]{6}|[0-9a-f]{3})/i);
 if (m[1].length == 3) {
 m = m[1].match(/./g).filter(function(c){return c+c});
 } else {
 m = m[1].match(/../g);
 }
 return {
 r : Number("0x" + m[0]),
 g : Number("0x" + m[1]),
 b : Number("0x" + m[2])
 }
 }
});
function toInt(v){
 var i = parseInt(v);
 if(!i) i=0;

 return i;
}

/**
 * Auto expand textarea (hasn't been tested on Safari and Opera)
 * @author nagoon, gony
 */
Ku.AutoExpand = Class({
 __init : function(id) {
 this.obj = $(id);
 this.gripHolder = document.createElement("DIV");

 this.options = JINDO.extend({
 maxRows : 10,
 stopAutoExpandAt : 3,
 gripImg : 'btn_resize.gif',
 gripImg_x_spacing : 0,
 gripImg_y_spacing : 0,
 width : '400px',
 scrollbarWidth : 17,
 onRowChange : function(){}
 }, arguments[1]||{});

 this.obj.style.width = this.options.width;

 this.obj.parentNode.insertBefore(this.gripHolder, this.obj);
 this.gripHolder.appendChild(this.obj);

 if($Agent().Safari || $Agent().Opera) return;

 if(!$Agent().IE){
 this.options.stopAutoExpandAt-=1;
 }

 // clone textarea
 this.clone = this.obj.cloneNode(true);
 this.clone.removeAttribute("name");
 this.clone.removeAttribute("id");
 this.clone.name = "";
 this.clone.id = "";
 this.clone.style.position = 'absolute';
 this.clone.style.visibility = 'hidden';

 this.obj.parentNode.insertBefore(this.clone, this.obj);
 // FF requires the value to be specifically assigned
 this.clone.value = this.obj.value;

 this.imgDiv = $C("DIV");

 // create the resize grip
 this.imgGrip = $C("IMG");
 this.imgGrip.src = this.options.gripImg;
 this.imgGrip.style.position = 'absolute';
 this.imgGrip.style.cursor = 'n-resize';

 this.imgDiv.style.width = this.obj.style.width;
 this.imgDiv.style.height = this.obj.style.height;
 this.imgDiv.style.position = 'relative';

 this.imgDiv.appendChild(this.imgGrip);

 // insert the grip image
 this.gripHolder.appendChild(this.imgDiv);

 // save information
 this.options.minRows = this.obj.rows;
 this.obj.style.overflow = 'hidden';

 // calculate lineHeight
 if($Agent().IE)
 this.clone.value += '\n';
 else
 this.clone.rows = this.obj.rows + 1;
 var temp = this.clone.scrollHeight;
 this.lineHeight = (this.clone.scrollHeight - this.obj.scrollHeight);
 // set the original value back in
 if($Agent().IE)
 this.clone.value = this.obj.value;
 else
 this.clone.rows = 1; //this.obj.rows;

 // register events
 var pFunc = this.execute.bind(this);
 var pDraw = this.execute.bind(this);
 Event.register(this.obj, 'keyup', pFunc);
 Event.register(this.obj, 'keydown', pFunc);

 this.rowHeight = this.lineHeight;;

 this.evalFunc = this.evaluate.bind(this);
 this.evalFunc();

 this.bfOnGripMouseDown = this.onGripMouseDown.bindForEvent(this);
 this.bfOnGripMouseMove = this.onGripMouseMove.bindForEvent(this);
 this.bfOnGripMouseUp = this.onGripMouseUp.bindForEvent(this);

 this.imgGrip.onmousedown = this.bfOnGripMouseDown;

 this.obj.style.overflow = 'hidden';

 this.execute();
 },
 execute : function() {
 clearTimeout(this._timer);

 this._timer = setTimeout(this.evalFunc, 50);
 },
 evaluate : function() {
 this.clone.value = this.obj.value;

 var rows = this.obj.rows;

 // this line refreshes this.clone.scrollHeight value, IE6.0 bug
 var temp = this.clone.scrollHeight;

 var targetRows = toInt(this.clone.scrollHeight / this.lineHeight) + (!$Agent().IE?-1:0);

 if (targetRows > this.obj.rows) {
 while(targetRows > this.obj.rows && this.obj.rows < this.options.maxRows && this.obj.rows < this.options.stopAutoExpandAt) {
 this.setRows(this.obj.rows+1);
 }
 }else {
 while(targetRows > this.obj.rows && this.obj.rows > this.options.minRows && this.obj.rows < this.options.stopAutoExpandAt) {
 this.setRows(this.obj.rows-1);
 }
 }

 if(toInt(this.clone.scrollHeight/this.lineHeight) > toInt(this.obj.clientHeight/this.lineHeight)){
 this.obj.style.overflowY = 'scroll';
 }else{
 this.obj.style.overflowY = 'hidden';
 }
 if (rows != this.obj.rows) this.obj.focus();

 this.draw();
 },
 onGripMouseDown:function(e){
 this.imgGrip_offsetY = e.clientY - toInt(Element.realPos(this.imgGrip).top);

 Event.register(document, 'mousemove', this.bfOnGripMouseMove);
 Event.register(document, 'mouseup', this.bfOnGripMouseUp);

 e.stop();
 },
 onGripMouseUp:function(e){
 Event.unregister(document, 'mousemove', this.bfOnGripMouseMove);
 Event.unregister(document, 'mouseup', this.bfOnGripMouseUp);

 if(toInt(this.clone.scrollHeight/this.lineHeight) > toInt(this.obj.clientHeight/this.lineHeight)){
 this.obj.style.overflowY = 'scroll';
 }else{
 this.obj.style.overflowY = 'hidden';
 }

 this.execute();

 this.draw();

 e.stop();
 },
 onGripMouseMove:function(e){
 var pos = Element.realPos(this.obj);
 var rows = Math.floor((e.clientY-pos.top+(this.imgDiv.style.height-this.imgGrip_offsetY))/this.rowHeight);
 if(rows < 1) rows = 1;
 if(rows > this.options.maxRows) rows = this.options.maxRows;

 this.setRows(rows);
 this.draw();

 e.stop();
 },
 draw:function(){
 var hScrollbarSpacing = (this.obj.style.overflowY != 'hidden')?this.options.scrollbarWidth:0;

 this.imgGrip.style.bottom = this.options.gripImg_y_spacing + "px";
 this.imgGrip.style.right = this.options.gripImg_x_spacing + hScrollbarSpacing + "px";
 },
 setRows:function(nRows){
 this.obj.rows = nRows;
 this.options.onRowChange();
 }
});
/**
 * Project description here
 * @author gony
 */
Ku.Rating = Class({
 _value : 0,
 _pressed : false,
 __init : function(id) {
 this.obj = $(id);
 this.options = JINDO.extend({
 step : 1,
 range : [0,100],
 hover : false,
 vertical : false,
 onChange : function(val){},
 onChanging : function(val){},
 onHoverChanging : function(val){}
 }, arguments[1]||{});

 // value
 this._value = this.options.range[0];

 this.gauge = this.obj.firstChild;
 this.gauge.style[this.options.vertical?'height':'width'] = '0px';

 // event binding
 this.onmousedown = this.MouseDown.bindForEvent(this);
 this.onmousemove = this.MouseMove.bindForEvent(this);
 this.onmouseup = this.MouseUp.bindForEvent(this);

 this.onhoverout = this.HoverOut.bindForEvent(this);
 this.onhoverover = this.HoverOver.bindForEvent(this);
 this.onhovermove = this.HoverMove.bindForEvent(this);

 // assign event handler
 Event.register(this.obj, 'mousedown', this.onmousedown);

 if (this.options.hover) {
 Event.register(this.obj, 'mouseover', this.onhoverover);
 Event.register(this.obj, 'mousemove', this.onhovermove);
 Event.register(this.obj, 'mouseout' , this.onhoverout);

 Event.register(this.gauge, 'mouseover', this.onhoverover);
 }
 },
 MouseDown : function(e) {
 var vert = this.options.vertical;
 var pos = Element.realPos(this.gauge);

 this._pressed = true;
 this._startPos = e[vert?'page_y':'page_x'];
 this._startLength = vert?(parseInt(this.gauge.style.height)-this._startPos+pos.top):(this._startPos - pos.left);
 this.setValue(this._len2val(this._startLength));

 // register event
 Event.register(document, 'mousemove', this.onmousemove);
 Event.register(document, 'mouseup', this.onmouseup);

 // stop event
 e.stop();

 // fire change event
 this.options.onChange(this._value);
 },
 MouseMove : function(e) {
 var vert = this.options.vertical;
 var diff = (this._startPos - e[vert?'page_y':'page_x']) * (vert?1:-1);

 this.setValue(this._len2val(this._startLength+diff));

 // fire changing event
 this.options.onChanging(this._value);
 },
 MouseUp : function(e) {
 var vert = this.options.vertical;
 var diff = (this._startPos - e[vert?'page_y':'page_x']) * (vert?1:-1);

 this._pressed = false;
 this.setValue(this._len2val(this._startLength+diff));

 // unregister event
 Event.unregister(document, 'mousemove', this.onmousemove);
 Event.unregister(document, 'mouseup', this.onmouseup);

 // stop event
 e.stop();

 // fire change event
 this.options.onChange(this._value);
 },
 HoverOver : function(e) {
 if (this._pressed) return;

 clearTimeout(this._restoreTimer);

 this._gaugePos = Element.realPos(this.gauge);

 var vert = this.options.vertical;
 this._startPos_h = e[vert?'page_y':'page_x'];
 this._startLen_h = vert?(parseInt(this.gauge.style.height)-this._startPos_h+this._gaugePos.top):(this._startPos_h - this._gaugePos.left);

 this.gauge.style[vert?'height':'width'] = this._startLen_h+'px';

 // fire event
 this.options.onHoverChanging(this._len2val(this._startLen_h));
 },
 HoverOut : function(e) {
 if (this._pressed) return;

 var t = this;
 var restore = function() { t.setValue(t._value); }

 this._restoreTimer = setTimeout(restore, 50);

 this._startLen_h = false;
 this._startPos_h = false;
 },
 HoverMove : function(e) {
 if (this._pressed) return;

 if (!this._startLen_h || !this._startPos_h) this.HoverOver(e);

 var vert = this.options.vertical;
 var diff = (this._startPos_h - e[vert?'page_y':'page_x']) * (vert?1:-1);

 this.gauge.style[vert?'height':'width'] = (this._startLen_h+diff)+'px';

 // fire event
 this.options.onHoverChanging(this._len2val(this._startLen_h+diff));
 },
 setValue : function(val) {
 var opt = this.options;
 var vert = this.options.vertical;
 var val = Math.min(Math.max(opt.range[0],Math.round(val/opt.step)*opt.step),opt.range[1]);

 this._value = val;
 this.gauge.style[vert?'height':'width'] = this._val2len(val) + 'px';
 },
 getValue : function() {
 return this._value;
 },
 _len2val : function(l) {
 var opt = this.options;
 var vert = this.options.vertical;
 var len = this.obj[vert?'clientHeight':'clientWidth'];
 var val = ((l / len) * (opt.range[1] - opt.range[0])) + opt.range[0];

 return Math.min(Math.max(Math.round(val),opt.range[0]),opt.range[1]);
 },
 _val2len : function(val) {
 var opt = this.options;
 var vert = this.options.vertical;
 var len = this.obj[vert?'clientHeight':'clientWidth'];
 var ratio = (val-opt.range[0]) / (opt.range[1]-opt.range[0]);

 return Math.min(Math.max(Math.round(ratio*len),0),len);
 }
});
/**
 * @projectDescription Multiple or single file upload component
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team
 */
Ku.Upload = Class({
 __init : function(id) {
 this._obj = $(id);
 this.options = JINDO.extend({
 url : '', // upload url
 meta : '', // meta url
 data : {},
 multiple : true,
 autosend : false,
 filetype : '*.*', // support only for multiple (flash)
 onSelect : function(/*file1, file2, file3, ...*/){},
 onSuccess : function(){},
 onError : function(){},
 onBeforeSelect : function(self){ return '' },
 msgNotAllowedExt : 'Extension %s is not allowed'
 }, arguments[1]||{});

 var t = this;
 var o = this.options;
 var v = this._getFlashVer();

 if (o.multiple && v >= 8) {
 // flash embed
 } else {
 // turn-off multiple option
 o.multiple = false;
 this._div = $C('div');
 this._file = this._createFileForm();

 var oow = this._obj.offsetWidth;
 var ooh = this._obj.offsetHeight;

 this._obj.parentNode.insertBefore(this._div, this._obj);
 Element.setCSS(this._div, {
 position : 'absolute',
 opacity : 0.0, // opera(for osx) does not support this property
 filter : 'alpha(opacity=0)',
 width : oow+'px',
 height : ooh+'px',
 overflow : 'hidden'
 });

 this._div.appendChild(this._file);

 // fixed moving button bug on firefox
 if (['relative','fixed','absolute'].has(this._div.offsetParent.style.position)) {
 this._div.style.left = '0px';
 }
 }

 // event binding
 this.onmouseover = this.MouseOver.bindForEvent(this);
 this.onmouseout = this.MouseOut.bindForEvent(this);
 this.onmousedown = this.MouseDown.bindForEvent(this);
 this.onmouseup = this.MouseUp.bindForEvent(this);

 if (o.multiple) {
 Event.register(this._obj, 'click', this.MouseClick.bind(this));
 } else {
 this._div.onmouseover = this.onmouseover;
 this._div.onmouseout = this.onmouseout;
 this._div.onmousedown = this.onmousedown;
 this._div.onmouseup = this.onmouseup;
 }
 },
 _getTmpId : function() {
 return (new Date).getMilliseconds()+Math.floor(Math.random()*100000);
 },
 _createElement : function(name, attributes) {
 var element = $C(name);
 for (var k in attributes) {
 element.setAttribute(k, attributes[k]);
 }
 return element;
 },
 _checkExtension : function(sFile) {
 var ext = /\.([^\.\/]*)$/.test(sFile)?RegExp.$1:'';
 var types = this.options.filetype.split(';');

 for(var i=0; i < types.length; i++) {
 types[i] = types[i].replace(/^\s+|\s+$/, '');
 types[i] = types[i].replace(/\./g,'\\.');
 types[i] = types[i].replace(/\*/g, '[^\\.\\/\\\\]+');
 if ((new RegExp(types[i]+'$','gi')).test(sFile)) return true;
 }

 return false;
 },
 send : function() {
 var f, r, t = this;
 var x = 'tmpFrame_'+this._getTmpId();
 var func = x+'_func';

 // iframe
 try {
 r = $C('<iframe name="'+x+'"></iframe>')
 } catch(e) {
 r = $C('iframe');
 r.setAttribute('name', x);
 }
 r.style.position = 'absolute';
 r.style.width = '1px';
 r.style.height = '1px';
 r.style.left = '-100px';
 r.style.top = '-100px';
 document.body.appendChild(r);

 // form
 try {
 f = $C('<form enctype="multipart/form-data"></form>');
 } catch(e) {
 f = $C('form');
 f.setAttribute('enctype', 'multipart/form-data');
 }
 f.style.position = 'absolute';
 f.style.width = '1px';
 f.style.height = '1px';
 f.style.left = '-100px';
 f.style.overflow = 'hidden';
 f.setAttribute('target', x);
 f.setAttribute('action', this.options.url+'?callback='+encodeURIComponent(this.options.callback)+'&callback_func='+func);
 f.setAttribute('method', 'POST');
 f.appendChild(this._file);
 f.appendChild(this._createElement('input', {'type':'hidden', 'name':'callback', 'value':this.options.callback}));
 f.appendChild(this._createElement('input', {'type':'hidden', 'name':'callback_func', 'value':func}));
 for (var k in this.options.data) {
 f.appendChild(this._createElement('input', {'type':'hidden', 'name':k, 'value':this.options.data[k]}));
 }
 document.body.appendChild(f);

 // temporary function - on success
 Ku.Upload.__tmpFunc[func+'_success'] = function(pairs) {
 t.options.onSuccess(pairs);
 setTimeout(function(){r.parentNode.removeChild(r);document.body.removeChild(f)},10);
 };

 // temporary function - on error
 Ku.Upload.__tmpFunc[func+'_error'] = function(pairs) {
 t.options.onError(pairs);
 setTimeout(function(){r.parentNode.removeChild(r);document.body.removeChild(f)},10);
 };

 // form submit and reset
 f.submit();

 // reset file form
 this.reset();
 },
 reset : function() {
 this._file.onchange = null;
 this._file.parentNode.removeChild(this._file);

 this._file = this._div.appendChild(this._createFileForm());
 },
 MouseClick : function() {
 var swf = window.document[Ku.Upload._tmpId];
 var tmpid = 'f'+this._getTmpId();
 var t = this, sResult = '';

 if (this.options.onBeforeSelect) sResult = this.options.onBeforeSelect(this);
 if (sResult) {
 alert(sResult);
 return;
 }

 Ku.Upload.__tmpFunc[tmpid+'_success'] = this.options.onSuccess;
 Ku.Upload.__tmpFunc[tmpid+'_error'] = this.options.onError;
 Ku.Upload.__tmpFunc[tmpid+'_select'] = function(fileStr) {
 var files = fileStr.split('#');
 t.options.onSelect.apply(t.options.onSelect, files);
 }

 swf.uploadWithFlash(this.options.url, this.options.meta, '{"filter":"*.*","onselect":"Ku.Upload.__tmpFunc.'+tmpid+'_select","onsuccess":"Ku.Upload.__tmpFunc.'+tmpid+'_success","onerror":"Ku.Upload.__tmpFunc.'+tmpid+'_error"}');
 },
 MouseOver : function(e) {
 if (this._obj.onmouseover) this._obj.onmouseover(e);
 },
 MouseOut : function(e) {
 if (this._obj.onmouseout) this._obj.onmouseout(e);
 },
 MouseDown : function(e) {
 var t = this, sResult = '';
 if (this.options.onBeforeSelect) sResult = this.options.onBeforeSelect(this);
 if (this._obj.onmousedown) this._obj.onmousedown(e);

 if (sResult) {
 this._file.disabled = true;
 alert(sResult);
 setTimeout(function(){ t._file.disabled = false; }, 100);
 }
 },
 MouseUp : function(e) {
 if (this._obj.onmouseup) this._obj.onmouseup(e);
 },
 _createFileForm : function() {
 var t = this;
 var f = null;
 var fs = parseInt(Math.max(this._obj.offsetHeight, this._obj.offsetWidth/5));

 try {
 f = $C('<input type="file" name="Filedata" style="font-size:'+fs+'px" />');
 } catch(e) {
 f = $C('input');
 f.setAttribute('type', 'file');
 f.setAttribute('name', 'Filedata');
 f.style.fontSize = fs+'px';
 }

 Element.setCSS(f, {
 position : 'absolute',
 top : '0px',
 right : '0px'
 });

 try { f.style.cursor = 'pointer'; }
 catch(err){ f.style.cursor = 'hand'; }

 // event binding
 f.onchange = function() {
 if (!t._checkExtension(this.value)) {
 var ext = /\.([^\.\/]*)$/.test(this.value)?RegExp.$1:'';
 alert(t.options.msgNotAllowedExt.replace('%s', ext));
 return false;
 }
 t.options.onSelect(this.value);
 if(t.options.autosend) t.send();
 };

 return f;
 },
 _getFlashVer : function() {
 if (navigator.plugins && navigator.plugins.length) {
 var x = navigator.plugins["Shockwave Flash"];
 if (x && x.description) {
 var y = x.description;
 return parseInt(y.charAt(y.indexOf('.')-1));
 }
 } else if (ActiveXObject){
 try {
 new ActiveXObject('ShockwaveFlash.ShockwaveFlash.9');
 return 9;
 } catch(e1) {
 try {
 new ActiveXObject('ShockwaveFlash.ShockwaveFlash.8');
 return 8;
 } catch(e2) {
 return -1;
 }
 }
 }

 return -1;
 },
 free : function() {
 var d = this._div;

 d.onmouseover = null;
 d.onmouseout = null;
 d.onmousedown = null;
 d.onmouseup = null;
 d.parentNode.removeChild(d);
 this._div = d = null;
 }
});

Ku.Upload.__tmpFunc = {};

// write flash module
Ku.Upload.initFlash = function(swf_path) {
 if(typeof swf_path == "undefined") swf_path = "./uploader.swf";
 Ku.Upload._tmpId = 'tmpSwf'+(new Date).getMilliseconds()+Math.floor(Math.random()*100000);

 document.write('<div style="position:absolute;top:300px;left:-1000px"><object id="'+Ku.Upload._tmpId+'" width="200" height="200" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"><param name="movie" value="'+swf_path+'"><param name = "allowScriptAccess" value = "always" /><embed name="'+Ku.Upload._tmpId+'" src="'+swf_path+'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="200" height="200" allowScriptAccess="always" swLiveConnect="true"></embed></object></div>');
}
/**
 * Replacement Component for HTML Element
 * @author gony
 */
Ku.Checkbox = Class({
 __init : function(id) {
 this._source = $(id);
 this.options = JINDO.extend({
 skinFormat : './images/check_%s.gif', // on, off
 style : {} // {'border' : 'solid 1px red', 'margin' : '1px', ...}
 }, arguments[1]);

 // for image cache
 var o = this.options;
 $C('img').src = o.onImg = o.skinFormat.replace('%s', 'on');
 $C('img').src = o.offImg = o.skinFormat.replace('%s', 'off');

 // image element
 var e = this._element = $C('img');
 $H(this.options.style).each(function(v,k){e.style[k] = v;});
 e.onclick = this.onclick.bind(this);
 this.paint();

 // replace html element
 this._source.parentNode.insertBefore(e, this._source);
 this._source.style.display = 'none';
 },
 onclick : function() {
 this._source.checked = !this._source.checked;
 this.paint();
 if (this._source.onclick) this._source.onclick();
 },
 paint : function() {
 this._element.src = this._source.checked?this.options.onImg:this.options.offImg;
 }
});

Ku.Radiobox = Class({
 __init : function(name) {
 var s = this._sources = $A(document.getElementsByName(name));
 this.options = JINDO.extend({
 onChange : function(v,i){},
 skinFormat : './images/radio_%s.gif', // on, off
 style : {} // {'border' : 'solid 1px red', 'margin' : '1px', ...}
 }, arguments[1]);

 // for image cache
 var o = this.options;
 $C('img').src = o.onImg = o.skinFormat.replace('%s', 'on');
 $C('img').src = o.offImg = o.skinFormat.replace('%s', 'off');

 // image elemnts
 var e = this._elements = [];
 for(var i=0; i < s.length; i++) {
 var img = $C('img');
 $H(this.options.style).each(function(v,k){img.style[k] = v;});
 img.onclick = this.onclick.bind(this,i);
 e.push(img);
 }
 this.paint();

 // replace html elements
 s.each(function(v,i) {
 v.parentNode.insertBefore(e[i],v);
 Element.hide(v);
 });
 },
 onclick : function(idx) {
 var s = this._sources[idx];
 var c = s.checked;
 s.checked = true;

 this.paint();
 if (s.onclick) s.onclick();
 if (c != s.checked) this.options.onChange(s.value,idx);
 },
 setIndex : function(i) {
 this._sources[i].checked = true;
 this.paint();
 },
 setValue : function(val) {
 this._sources.each(function(v){ if(v.value == val)v.checked=true });
 this.paint();
 },
 getValue : function() {
 var val;
 this._sources.each(function(v){ if(v.checked)val=v.value });
 return val;
 },
 getIndex : function() {
 var idx;
 this._sources.each(function(v,i){ if(v.checked)idx=i });
 return idx;
 },
 paint : function() {
 var t = this;
 this._elements.each(function(v,i) {
 v.src = t._sources[i].checked?t.options.onImg:t.options.offImg;
 });
 }
});

Ku.Selectbox = Class({
 __init : function(id) {
 var s = this._source = $(id);
 this.options = JINDO.extend({
 height : s.offsetHeight,
 width : s.offsetWidth,
 fontSize : 12, // px
 listSize : 8,
 skinActive : false,
 opacity : 3,
 skinFormat : './images/sbox_%s.gif', // left, right, up, down, bt
 mouseAct : false,
 borderActive: true,
 borderColor : '#CCCCCC',
 borderHColor: '#FFFFFF', //hidden color
 borderTColor: false,
 borderRColor: false,
 borderBColor: false,
 borderLColor: false,
 optTxtColor : 'black',
 optBgColor : 'white',
 optTxtHover : 'white',
 optBgHover : 'gray',
 optAlign : 'left',
 useImage : false
 }, arguments[1]);

 var o = this.options;
 var e = this._element = $C('div');
 var c = this._container = e.appendChild($C('div')); // container
 var p = Element.realPos(this._source);

 // replace html element
 this._source.parentNode.insertBefore(e, s);
 this._source.style.display = 'none';

 // border
 this.borderact();

 Element.setCSS(e, {
 top : p.top+'px',
 left : p.left+'px',
 width : o.width+'px',
 height : (o.height-2)+'px',
 color : o.optTxtColor,
 background : o.optBgColor
 });

 Element.setCSS(c, {
 width : o.width+'px',
 height : (o.height-2)+'px',
 fontSize : o.fontSize+'px',
 overflow : 'hidden',
 cursor : 'default'
 });

 this.disable();

 // button layer
 var b = c.appendChild($C('div'));
 Element.setCSS(b, {
 height : '100%',
 background : 'no-repeat url('+o.skinFormat.replace('%s', 'bt')+') 50% 50%',
 cssFloat : 'right',
 styleFloat : 'right'
 });

 // text box
 this._txt_element = $C('div');
 this._source.parentNode.insertBefore(this._txt_element, s);
 with(this._txt_element) {
 if (this.options.useImage) {
 var img = $C('img');
 img.onload = function() {
 this.parentNode.style.height = o.height+'px';
 this.parentNode.style.background = 'url('+this.src+') '+o.optBgColor+' no-repeat 0px 0px';
 this.parentNode.removeChild(this);
 }
 appendChild(img).src = s.options[s.selectedIndex].text;
 } else {
 appendChild(document.createTextNode(s.options[s.selectedIndex].text));
 style.color = o.optTxtColor;
 style.fontSize = o.fontSize + 'px';
 style.height = o.fontSize+'px';
 }
 style.overflow = 'hidden';
 style.marginTop = Math.max(Math.floor((o.height-offsetHeight)/2),0)+'px';
 style.marginLeft = style.marginTop;
 }
 c.appendChild(this._txt_element);
 c.appendChild($C('div')).style.clear = 'both';

 // re-margin
 var im = $C('img');
 im.onload = function(){b.style.width=im.width+'px'}
 im.src = o.skinFormat.replace('%s', 'bt');

 // options
 this._list_element = e.appendChild($C('div'));
 Element.setCSS(this._list_element, {
 position : 'absolute',
 zIndex : 1000
 });

 var el = this._list_element.appendChild($C('div'));
 Element.setCSS(el, {
 position : 'absolute',
 top : '1px',
 left : '-1px',
 overflow : 'auto',
 zIndex : 1000
 });
 if (o.borderActive) {
 el.style.borderTop = '0px';
 el.style.borderRight = '1px solid '+this.ColorR;
 el.style.borderBottom = '1px solid '+this.ColorB;
 el.style.borderLeft = '1px solid '+this.ColorL;
 }

 el.onmousedown = this.onscrollbar.bindForEvent(this);
 if(o.mouseAct) el.onmouseover = this.onmouseover.bind(this);
 this.paint();
 Element.hide(this._list_element);

 // event binding
 this._event_onmousedown = this.onmousedown.bindForEvent(this);
 },
 disable : function() {
 var s = this._source, o = this.options, e = this._element;
 var c = this._container;

 Element.setCSS(e, {
 filter : (s.disabled)?'alpha(opacity='+o.opacity+'0)':'alpha(opacity=100)',
 opacity : (s.disabled)?'0.'+o.opacity:'1'
 });

 c.onmousedown = (s.disabled)?'':this.onmousedown.bindForEvent(this);
 c.onmouseup = (s.disabled)?'':this.onmouseup.bind(this);
 if(o.mouseAct) c.onmouseover = (s.disabled)?'':this.onmouseover.bindForEvent(this);
 if(o.mouseAct) c.onmouseout = (s.disabled)?'':this.onmouseout.bind(this);
 },
 borderact : function() {
 var s = this._source, o = this.options, e = this._element;

 this.ColorT = (o.borderTColor)?o.borderTColor:o.borderColor;
 this.ColorR = (o.borderRColor)?o.borderRColor:o.borderColor;
 this.ColorB = (o.borderBColor)?o.borderBColor:o.borderColor;
 this.ColorL = (o.borderLColor)?o.borderLColor:o.borderColor;

 if (o.borderActive) {
 e.style.borderTop = (o.mouseAct)?'1px solid '+ o.borderHColor:'1px solid '+ this.ColorT;
 e.style.borderRight = (o.mouseAct)?'1px solid '+ o.borderHColor:'1px solid '+ this.ColorR;
 e.style.borderBottom = (o.mouseAct)?'1px solid '+ o.borderHColor:'1px solid '+ this.ColorB;
 e.style.borderLeft = (o.mouseAct)?'1px solid '+ o.borderHColor:'1px solid '+ this.ColorL;
 }
 },
 onmouseover : function(e) {
 var e = this._element;

 Element.setCSS(e, {
 borderTop : '1px solid '+ this.ColorT,
 borderRight : '1px solid '+ this.ColorR,
 borderBottom : '1px solid '+ this.ColorB,
 borderLeft : '1px solid '+ this.ColorL
 });
 },
 onmouseout : function(e) {
 var e = this._element;

 Element.setCSS(e, {
 border : '1px #FFFFFF solid'
 });
 },
 onmousedown : function(e) {
 if (!Element.visible(this._list_element)) {
 Element.show(this._list_element);

 this.paint();
 } else {
 Element.hide(this._list_element);
 Event.unregister(document.body, 'mousedown', this._event_onmousedown);
 }
 },
 onmouseup : function() {
 if (Element.visible(this._list_element)) {
 Event.register(document.body, 'mousedown', this._event_onmousedown);
 }
 },
 onselect : function(e) {
 var el=e.element, o=this.options;
 var idx = o.useImage?el.parentNode._index:el._index;

 this.setIndex(idx);

 this.onmousedown();
 this.borderact();
 if (this._source.onchange) this._source.onchange();
 },
 onover : function(e) {
 var el=e.element, o=this.options;

 if (o.useImage) {
 var c=$A(el.parentNode.parentNode.childNodes), idx=el.parentNode._index;
 } else {
 var c=$A(el.parentNode.childNodes), idx=el._index;
 }

 c.map(function(v,i) {
 if (v.className != 'nhn_ajaxui_gony_selectbox_option') return v;
 if (o.useImage) {
 v.firstChild.style.backgroundColor = 'transparent'; // fix IE bug
 } else {

 v.style.color = (i==idx)?o.optTxtHover:o.optTxtColor;
 }
 v.style.backgroundColor = (i==idx)?o.optBgHover:o.optBgColor;

 return v;
 });
 },
 onscrollbar : function(e) {
 e.stop();
 },
 paint : function() {
 var o=this.options,s=this._source,op,le=this._list_element.firstChild,el=this._element,css,oh;
 le.innerHTML = '';
 le.style.width = '';
 le.style.height = '';
 this._maxImageWidth = 0;

 // default option style
 css = {
 padding : '4px 5px 2px 5px',
 cursor : 'default',
 fontSize : o.fontSize+'px',
 color : o.optTxtColor,
 background : o.optBgColor,
 display : 'block'
 };

 // make temporary option to calcurate height of element
 op = this._makeOption('',unescape('%uAC00'));
 Element.setCSS(op, css);
 le.appendChild(op);
 oh = op.offsetHeight;
 le.removeChild(op);

 // set height
 css.overflow = 'hidden';
 if ($Agent().IE) css.height = oh+'px';
 else css.height = (oh-6)+'px';

 for(var i=0; i < s.options.length; i++) {
 op = this._makeOption(s.options[i].value, s.options[i].text);
 op._index = i;
 Element.setCSS(op, css);
 le.appendChild(op);
 if (i == this._source.selectedIndex) {
 op.style.color = o.optTxtHover;
 op.style.backgroundColor = o.optBgHover;
 }
 }

 // set width
 if (le.offsetWidth < el.offsetWidth) {
 if($Agent().IE) le.style.width = (el.clientWidth+2)+'px';
 else le.style.width = el.clientWidth+'px';
 };

 le.style.height = oh*Math.min(o.listSize,s.options.length)+'px';

 if (o.listSize > s.options.length) le.style.overflow = 'hidden';

 // re-index selected option
 this.setIndex(s.selectedIndex);
 },
 setValue : function(val) {
 this._source.value = val;
 this.setIndex(this._source.selectedIndex);
 },
 setIndex : function(idx) {
 var s = this._source, o = this.options, e = this._txt_element;
 s.selectedIndex = idx;
 if (o.useImage) {
 e.style.backgroundImage = 'url("'+s.options[s.selectedIndex].text+'")';
 } else {
 e.firstChild.nodeValue = s.options[s.selectedIndex].text;
 }
 Element.setCSS(e, {
 fontSize : o.fontSize+'px',
 color : o.optTxtColor
 });
 },
 _makeOption : function(value, text) {
 var o = $C('div'), t = this;
 o.className = 'nhn_ajaxui_gony_selectbox_option';
 o._value = value;

 // support image option - 2006. 11. 14
 if (this.options.useImage) {
 var img = $C('IMG');
 o.appendChild(img).src = text;

 Event.register(img, 'mousedown', this.onselect.bindForEvent(this));
 Event.register(img, 'mouseover', this.onover.bindForEvent(this));
 } else {
 o.appendChild(document.createTextNode(text));
 o.style.textAlign = this.options.optAlign;
 Event.register(o, 'mousedown', this.onselect.bindForEvent(this));
 Event.register(o, 'mouseover', this.onover.bindForEvent(this));
 }

 return o;
 }
});

/**
 * convert file button to image button
 */
Ku.Filebutton = Class({
 __init : function(id) {
 this.obj = $(id);
 this.options = JINDO.extend({
 skinFormat : 'images/filebutton_%s.gif', // normal, over, down
 onchange : function(file){}
 },arguments[1]||{});

 // re-assign onchange event
 this.obj.__onchange = this.obj.onchange||(new Function);
 this.obj.onchange = this.onChange.bind(this);
 this.obj.onmouseover = this.onMouseOver.bind(this);
 this.obj.onmouseout = this.onMouseOut.bind(this);
 this.obj.onmouseup = this.onMouseUp.bind(this);
 this.obj.onmousedown = this.onMouseDown.bind(this);

 // file button
 this._btn = $C('IMG');
 this._btn.onload = this._paint.bind(this)
 this.obj.parentNode.insertBefore(this._btn, this.obj);

 // create filebutton layer
 this._div = $C('div');
 this._btn.parentNode.insertBefore(this._div, this._btn);
 with(this._div) {
 appendChild(this.obj);
 style.position = 'absolute';
 style.overflow = 'hidden';
 style.opacity = 0; // opera(for osx) does not support this property
 style.filter = 'alpha(opacity=0)';
 };
 with(this.obj) {
 style.position = 'absolute';
 style.right = style.top = '0';
 style.fontSize = '100pt';
 }

 // load and cache file button images
 this._btn.src = this.options.skinFormat.replace('%s', 'normal');
 $C('IMG').src = this.options.skinFormat.replace('%s', 'over');
 $C('IMG').src = this.options.skinFormat.replace('%s', 'down');
 },
 _paint : function() {
 this._div.style.width = this._btn.width+'px';
 this._div.style.height = this._btn.height+'px';
 },
 onChange : function() {
 this.options.onchange(this.obj.value);
 this.obj.__onchange();
 },
 onMouseOver : function() {
 this._btn.src = this.options.skinFormat.replace('%s', 'over');
 },
 onMouseOut : function() {
 this._btn.src = this.options.skinFormat.replace('%s', 'normal');
 },
 onMouseDown : function() {
 this._btn.src = this.options.skinFormat.replace('%s', 'down');
 },
 onMouseUp : function() {
 this._btn.src = this.options.skinFormat.replace('%s', 'over');
 }
});

/**
 * CSS Designed SelectBox
 *
 * @author hooriza
 * @version 0.9.070513.3
 *
 * Compatibility with IE5.5+, FF 2, Opera 9
 */

Ku.Selectbox2 = new Class({
 obj : null,
 item : null,

 options : null,
 wrap : null,

 scrollbox : null,

 __init : function(id, options) {

 this.obj = $(id);

 this.options = JINDO.extend({
 width : this.obj.offsetWidth,
 skinClass : "selectbox"
 }, options);

 this._createDesignedObject();

 this._fillList();
 this._refreshLabel();

 if (this.scrollbox) Element.hide(this.scrollbox.wrap.skin);
 },
 _createDesignedObject : function() {

 var wrap = {};
 var options = this.options;

 this.wrap = wrap;

 wrap.obj = $C("div");
 this.obj.parentNode.insertBefore(wrap.obj, this.obj);
 wrap.obj.className = options.skinClass;

 Element.setCSS(wrap.obj, {
 width : options.width + "px"
 });

 // Create a Box
 wrap.box = $C("a");
 wrap.box.href = "javascript:void(0)";
 wrap.box.className = "box";

 Element.setCSS(wrap.box, {
 display : "block",
 overflow : "hidden"
 });

 wrap.obj.appendChild(wrap.box);

 // Create Buttons
 wrap.button = $C("div");
 wrap.box.appendChild(wrap.button);
 wrap.button.className = "button";
 wrap.button.innerHTML = "&nbsp;";
 Element.setCSS(wrap.button, {
 position : "relative",
 zIndex : "998"
 });

 // Create Label
 wrap.label = $C("div");
 wrap.box.appendChild(wrap.label);
 wrap.label.className = "label";
 wrap.label.innerHTML = "&nbsp;";

 // Create Listbox
 wrap.listbox = $C("ul");
 wrap.listbox.className = "listbox";

 Element.setCSS(wrap.listbox, {
 listStyle : "none"
 });

 if (options.scrollbox) {

 this.scrollbox = new Ku.Scrollbox(options.scrollbox);
 var obj = this.scrollbox.createObject();
 Element.setCSS(obj, {
 position : "absolute"
 });

 wrap.obj.insertBefore(obj, wrap.box);
 this.scrollbox.bindWrapObjects();

 this.scrollbox.wrap.content.appendChild(wrap.listbox);

 } else {

 Element.setCSS(wrap.listbox, {
 position : "absolute",
 display : "none",
 zIndex : "999",
 overflow : "hidden",
 overflowY : "auto"
 });

 wrap.obj.insertBefore(wrap.listbox, wrap.box);

 }

 this._resetDesign();

 if (options.height) {

 if (this.scrollbox)
 this.scrollbox.setHeight(options.height);
 else
 Element.setCSS(wrap.listbox, { height : options.height + "px" });

 }

 // Create clear:both Element
 wrap.clear = $C("div");
 Element.setCSS(wrap.clear, { clear : "both" });
 wrap.box.appendChild(wrap.clear);

 Element.hide(this.obj);

 this._bindEvent();
 Event.register(window, "load", this._refreshLabel.owner(this));

 this._resetStatus();
 },
 _resetDesign : function() {

 var wrap = this.wrap;
 var options = this.options;

 Element.setCSS(wrap.listbox, { marginTop : "" });

 var orgMarginTop = parseInt(Element.getCSS(wrap.listbox, "marginTop")) || 0;
 var width = wrap.box.offsetWidth;

 var styleobj = this.scrollbox ? this.scrollbox.wrap.box : wrap.listbox;

 if (document.compatMode) {
 width -= parseInt(Element.getCSS(styleobj, "borderLeftWidth")) || 0;
 width -= parseInt(Element.getCSS(styleobj, "borderRightWidth")) || 0;
 }

 if (this.scrollbox) {

 // alert(Element.getCSS(this.scrollbox.wrap.box, "borderRightWidth"));

 Element.setCSS(this.scrollbox.wrap.skin, {
 marginTop : wrap.box.offsetHeight + orgMarginTop + "px"
 });

 wrap.listbox.style.margin = "0px";
 this.scrollbox.setWidth(width);

 } else {

 Element.setCSS(wrap.listbox, {
 marginTop : wrap.box.offsetHeight + orgMarginTop + "px",
 width : width + "px"
 });

 }

 },
 _resetStatus : function() {

 var disabled = this.isDisabled();
 this.wrap.obj.disabled = disabled;

 Element[disabled ? "addClass" : "removeClass"](this.wrap.obj, "selectbox_disabled");

 },
 _refreshLabel : function() {

 var wrap = this.wrap;
 var option = this.obj.options[this.obj.selectedIndex];

 var innerText = option.innerHTML.replace(/&lt;.+&gt;/g, "").replace(/(^\s+|\s+$)/g, "");
 wrap.label.innerHTML = innerText;
 },
 _refreshItem : function() {

 var wrap = this.wrap;
 var idx = this.obj.selectedIndex;
 var li = wrap.listbox.childNodes[idx];

 this._highlightItem(li);
 },
 _selectItem : function(li) {

 if (!li) return;

 var nodeIndex = parseInt(li.getAttribute("nodeIndex"));
 if (this.obj.selectedIndex != nodeIndex) {

 this.obj.selectedIndex = nodeIndex;
 this._highlightItem(li);
 this._refreshLabel();

 if (this.obj.onchange)
 this.obj.onchange();

 }

 this.wrap.box.focus();

 },
 _highlightItem : function(li) {

 // TODO
 var wrap = this.wrap;
 var itemrange = [ li.offsetTop, li.offsetTop + li.offsetHeight ];
 var listrange;

 if (this.scrollbox)
 listrange = [ this.scrollbox.getScrollTop(), this.scrollbox.getScrollTop() + this.scrollbox.getClientHeight() ];
 else
 listrange = [ wrap.listbox.scrollTop, wrap.listbox.scrollTop + wrap.listbox.clientHeight ];

 if (itemrange[0] < listrange[0]) {

 if (this.scrollbox)
 this.scrollbox.scrollTo(itemrange[0]);
 else
 wrap.listbox.scrollTop = itemrange[0];

 }

 if (itemrange[1] > listrange[1]) {

 if (this.scrollbox)
 this.scrollbox.scrollTo(itemrange[1] - this.scrollbox.getClientHeight());
 else
 wrap.listbox.scrollTop = itemrange[1] - wrap.listbox.clientHeight;

 }

 if (this.item) {
 Element.removeClass(this.item, "over");
 this.item = null;
 }

 Element.addClass(li, "over");
 this.item = li;

 },
 _fillList : function() {

 var wrap = this.wrap;
 var options = this.obj.options;
 var code = "";

 for (var i = 0, option; option = options[i]; i++) {

 var html = option.innerHTML.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">");
 code += '<li nodeIndex="' + i + '">' + html + '</li>';
 }

 var listbox = wrap.listbox;

 listbox.innerHTML = code;

 if ($Agent().IE7) {
 listbox.style.width = listbox.firstChild.offsetWidth + "px";
 }

 if ($Agent().IE && !$Agent().IE7 && this.scrollbox) {

 var width = listbox.offsetWidth - this.scrollbox.wrap.track.offsetWidth;

 if (document.compatMode) {
 width -= parseInt(Element.getCSS(listbox, "borderLeftWidth")) || 0;
 width -= parseInt(Element.getCSS(listbox, "borderRightWidth")) || 0;
 }

 Element.setCSS(listbox, {
 position : "absolute",
 width : width + "px"
 });

 }

 this.item = listbox.childNodes[this.obj.selectedIndex];
 },
 _showListBox : function(flag) {

 var wrap = this.wrap;
 var show_obj = this.scrollbox ? this.scrollbox.wrap.skin : wrap.listbox;

 if (flag == undefined) Element.toggle(show_obj);
 else Element[flag ? "show" : "hide"](show_obj);

 var visible = Element.visible(show_obj);
 Element[visible ? "addClass" : "removeClass"](wrap.obj, "selectbox_expanded");

 },
 _bindEvent : function() {

 var wrap = this.wrap;

 Event.register(document, "mousedown", function(e) {

 for (var el = e.element; el; el = el.parentNode)
 if (el == this.wrap.obj) return;

 this._showListBox(false);

 }.bindForEvent(this));

 Event.register(wrap.box, "focus", function(e, self, box) {

 if (!this.isDisabled()) return;

 box.blur();
 e.stop();

 }.bindForEvent(this, wrap.box));

 Event.register(wrap.box, "click", function(e) {

 if (this.isDisabled()) return;

 var wrap = this.wrap;
 var show_obj = this.scrollbox ? this.scrollbox.wrap.skin : wrap.listbox;

 this._showListBox();
 if (Element.visible(wrap.listbox)) {

 if (this.scrollbox) {

 this.scrollbox._resetThumb();

 } else {

 Element.setCSS(wrap.listbox, { visibility : "hidden" });
 Element.setCSS(wrap.listbox, { height : "auto" });

 if (this.options.height && wrap.listbox.offsetHeight > this.options.height)
 Element.setCSS(wrap.listbox, { height : this.options.height + "px" });

 Element.setCSS(wrap.listbox, { visibility : "visible" });

 }

 this._refreshItem();

 }

 // e.stop();

 }.bindForEvent(this));

 Event.register(wrap.box, $Agent().IE ? "keydown" : "keypress", function(e) {

 switch (e.keyCode) {
 case 38: // up
 this._selectItem(this.item.previousSibling);
 break;

 case 40: // down
 this._selectItem(this.item.nextSibling);
 break;
 }

 }.bindForEvent(this));

 this._bindEventForButton();
 this._bindEventForListbox();
 },
 _bindEventForButton : function() {

 var wrap = this.wrap;

 Event.register(wrap.box, "mouseover", function(self) {
 if (this.isDisabled()) return;
 Element.addClass(this.wrap.box, "box_over");
 }.bind(this));

 Event.register(wrap.box, "mouseout", function() {
 if (this.isDisabled()) return;
 Element.removeClass(this.wrap.box, "box_over", "box_down");
 }.bind(this));

 Event.register(wrap.box, "mousedown", function() {
 if (this.isDisabled()) return;
 Element.addClass(this.wrap.box, "box_down");
 }.bind(this));

 Event.register(wrap.box, "mouseup", function() {
 if (this.isDisabled()) return;
 Element.removeClass(this.wrap.box, "box_down");
 }.bind(this));

 },
 _bindEventForListbox : function() {

 var wrap = this.wrap;
 var getOwnLi = function(el, listbox, method, arg) {
 for (; el && el.parentNode != listbox; el = el.parentNode);
 return el;
 };

 Event.register(wrap.listbox, "mouseover", function(e) {
 var li = getOwnLi(e.element, this.wrap.listbox);
 if (li) this._highlightItem(li);
 }.bindForEvent(this));

 Event.register(wrap.listbox, "click", function(e) {

 var el = getOwnLi(e.element, this.wrap.listbox);
 if (!el) return;

 this._selectItem(el);
 this._showListBox();

 this.wrap.box.focus();

 }.bindForEvent(this));

 },
 refresh : function() {

 with (this) {
 _fillList();
 _refreshItem();
 _refreshLabel();
 }

 },
 setSkinClass : function(className) {
 this.options.skinClass = className;
 this.wrap.obj.className = this.options.skinClass;
 this._resetDesign();
 this._resetStatus();
 },
 isDisabled : function() {
 return (this.obj.disabled == true);
 },
 setDisabled : function(flag) {

 if (flag == undefined) flag = !this.isDisabled(); // Toggle when there's no argument
 this.obj.disabled = flag;

 this._resetStatus();

 }
});

/**
 * CSS Designed ScrollBox
 *
 * @author hooriza
 * @version 0.9.070513.1
 *
 * Compatibility with IE5.5+, FF 2, Opera 9, Shiira 1.2.2, Safari 2.0.4
 */

Ku.Scrollbox = Class({

 rand : null,

 options : null,
 wrap : null,

 timer : null,
 draging : null,

 // private methods
 __init : function(options) {

 this.options = JINDO.extend({
 skinClass : "template"
 }, options);

 },
 transform : function(userwrap) {

 if (this.wrap) throw "The Scrollbox already has been created";
 this.wrap = {};

 $H(userwrap).each(function(val, key) {
 this.wrap[key] = $(val);
 }.owner(this));

 this._createDesignedObject();

 },
 createObject : function() {

 if (this.wrap) throw "The Scrollbox already has been created";
 this.wrap = {};

 var rand = new Date().getTime();
 var inner_code = this._getStartCode(rand) + this._getEndCode(rand);

 var skin = $C("div");
 skin.id = "skin" + rand;
 skin.className = this.options.skinClass;

 skin.innerHTML = inner_code;

 return skin;
 },
 bindWrapObjects : function() {

 $A([ "skin", "box", "track", "up", "down", "thumb", "thumbtop", "thumbbtm", "content" ]).each(function(prefix) {

 var obj = $(prefix + this.rand);
 if (!obj) return;
 obj.removeAttribute("id");
 this.wrap[prefix] = obj;

 }.owner(this));

 this._createDesignedObject();

 },
 _getStartCode : function(rand) {

 var except_outer = rand ? true : false;

 rand = rand || new Date().getTime();
 this.rand = rand;

 var code = '';

 if (!except_outer)
 code += '<div id="skin' + rand + '" class="' + this.options.skinClass + '">';

 code +=
 '<div id="box' + rand + '" class="scrollbox" style="position:relative; overflow:hidden;">' +
 '<div id="track' + rand + '" style="height:100%; float:right;" class="track">' +
 '<div id="up' + rand + '" style="position:absolute; top:0; right:0;" class="button button_up"></div>' +
 '<div id="down' + rand + '" style="position:absolute; bottom:0; right:0;" class="button button_dn"></div>' +
 '<div id="thumb' + rand + '" style="position:relative;" class="thumb">' + // top, height
 '<div id="thumbtop' + rand + '" style="overflow:hidden; position:absolute; top:0; left:0;" class="thumbtop"></div>' +
 '<div id="thumbbtm' + rand + '" style="overflow:hidden; position:absolute; bottom:0; left:0;" class="thumbbtm"></div>' +
 '</div>' +
 '</div>' +
 '<div id="content' + rand + '" style="position:relative; top:0;">'; // margin-right

 return code;
 },
 start : function() {

 if (this.wrap) throw "The Scrollbox already has been created";
 this.wrap = {};

 document.write(this._getStartCode());
 },
 _getEndCode : function(rand) {

 var except_outer = rand ? true : false;

 var code =
 '</div>' +
 '</div>';

 if (!except_outer)
 code += '</div>';

 return code;
 },
 end : function() {

 var wrap = this.wrap;

 document.write(this._getEndCode());
 this.bindWrapObjects();

 },
 _createDesignedObject : function() {

 this._resetDesign();
 this._bindEvents();
 },
 _resetDesign : function() {

 var wrap = this.wrap;

 wrap.skin.className = this.options.skinClass;

 wrap.box.style.width = (this.options.width ? this.options.width + "px" : "auto");
 wrap.box.style.height = (this.options.height ? this.options.height + "px" : "auto");

 if (wrap.content.offsetWidth == wrap.box.clientWidth)
 wrap.content.style.marginRight = wrap.thumb.offsetWidth + "px";

 this._resetThumb();

 },
 _getTrackHeight : function() {
 return this.getClientHeight() - this.wrap.up.offsetHeight - this.wrap.down.offsetHeight;
 },
 _setThumbHeight : function(px) {
 Element[px > this._getTrackHeight() ? "hide" : "show"](this.wrap.thumb);
 if (!isNaN(px)) this.wrap.thumb.style.height = px + "px";
 },
 _setThumbTop : function(px) {

 var limit = [ 0, this._getTrackHeight() - this.wrap.thumb.offsetHeight ];

 if (px < limit[0]) px = limit[0];
 if (px > limit[1]) px = limit[1];

 if (!isNaN(px)) this.wrap.thumb.style.top = this.wrap.up.offsetHeight + px + "px";

 return px;
 },
 // Calculate Thumb Position
 _resetThumb : function() {
 var wrap = this.wrap;

 var scrollTop = this.getScrollTop();
 var scrollHeight = this.getScrollHeight();
 var clientHeight = this.getClientHeight();
 var trackHeight = this._getTrackHeight();

 // clientHeight : scrollHeight = thumbHeight : trackHeight
 var thumbHeight = Math.round(clientHeight * trackHeight / scrollHeight);
 var minHeight = wrap.thumbtop.offsetHeight + wrap.thumbbtm.offsetHeight;

 if (minHeight + 10 > thumbHeight) thumbHeight = minHeight + 10;

 // scrollTop : (scrollHeight - clientHeight) = thumbTop : (trackHeight - thumbHeight)
 var thumbTop = Math.round(scrollTop * (trackHeight - thumbHeight) / (scrollHeight - clientHeight));

 this._setThumbHeight(thumbHeight);
 this._setThumbTop(thumbTop);
 },
 _setTimer : function(func, interval) {
 this._clearTimer();
 this.timer = window.setInterval(func.owner(this), interval);
 },
 _clearTimer : function() {
 if (this.timer) window.clearInterval(this.timer);
 this.timer = null;
 },
 _bindButtonEvents : function() {

 var wrap = this.wrap;

 // Up Button
 Event.register(wrap.up, "mouseover", function() {
 if (this.draging) return;
 Element.addClass(this.wrap.up, "button_up_over");
 }.bind(this));

 Event.register(wrap.up, "mouseout", function() {
 Element.removeClass(this.wrap.up, "button_up_over", "button_up_down");
 this._clearTimer();
 }.bind(this));

 Event.register(wrap.up, "mousedown", function() {
 Element.addClass(this.wrap.up, "button_up_down");
 this._setTimer(function() { this.scrollBy(-20); }, 100);
 this.scrollBy(-20);
 }.bind(this));

 Event.register(wrap.up, "mouseup", function() {
 Element.removeClass(this.wrap.up, "button_up_down");
 this._clearTimer();
 }.bind(this));

 // Down Button
 Event.register(wrap.down, "mouseover", function() {
 if (this.draging) return;
 Element.addClass(this.wrap.down, "button_dn_over");
 }.bind(this));

 Event.register(wrap.down, "mouseout", function() {
 Element.removeClass(this.wrap.down, "button_dn_over", "button_dn_down");
 this._clearTimer();
 }.bind(this));

 Event.register(wrap.down, "mousedown", function() {
 Element.addClass(this.wrap.down, "button_dn_down");
 this._setTimer(function() { this.scrollBy(20); }, 100);
 this.scrollBy(20);
 }.bind(this));

 Event.register(wrap.down, "mouseup", function() {
 Element.removeClass(this.wrap.down, "button_dn_down");
 this._clearTimer();
 }.bind(this));

 },
 _bindThumbEvents : function() {

 var wrap = this.wrap;

 Event.register(wrap.thumb, "mouseover", function(e) {
 if (e.of(this.wrap.thumb)) return;
 Element.addClass(this.wrap.thumb, "thumb_over");
 }.bindForEvent(this));

 Event.register(wrap.thumb, "mouseout", function(e) {
 if (e.of(this.wrap.thumb)) return;
 Element.removeClass(this.wrap.thumb, "thumb_over");
 }.bindForEvent(this));

 // Thumb Drag Start
 Event.register(wrap.thumb, "mousedown", function(e) {
 Element.addClass(this.wrap.thumb, "thumb_down");
 this.draging = e.page_y - parseInt(this.wrap.thumb.style.top) + wrap.up.offsetHeight;
 e.stop();
 }.bindForEvent(this));

 Event.register(wrap.thumb, "selectstart", function(e) {
 e.stop();
 }.bindForEvent(this));

 // Draging
 Event.register(document, "mousemove", function(e) {
 if (this.draging == null) return;

 var thumbTop = e.page_y - this.draging;
 var thumbHeight = this.wrap.thumb.offsetHeight;
 var scrollHeight = this.getScrollHeight();
 var clientHeight = this.getClientHeight();
 var trackHeight = this._getTrackHeight();

 // alert(trackHeight - thumbHeight);

 thumbTop = this._setThumbTop(thumbTop);

 // scrollTop : (scrollHeight - clientHeight) = thumbTop : (trackHeight - thumbHeight)
 var scrollTop = (scrollHeight - clientHeight) * thumbTop / (trackHeight - thumbHeight);
 this.scrollTo(scrollTop);

 }.bindForEvent(this));

 // Thumb Drag End
 Event.register(document, "mouseup", function() {
 if (this.draging)
 Element.removeClass(this.wrap.thumb, "thumb_down");

 this.draging = null;
 }.bind(this));

 },
 _bindEvents : function() {

 var wrap = this.wrap;

 this._bindButtonEvents();
 this._bindThumbEvents();

 // Rolling Wheel
 Event.register(wrap.box, "mousewheel", function(e) {
 this.scrollBy(e.delta * -10);
 e.stop();
 }.bindForEvent(this));

 },
 // public methods
 getScrollTop : function() {
 return -parseInt(this.wrap.content.style.top); // scrollTop of Content
 },
 getScrollHeight : function() {
 var obj = this.wrap.content.firstChild || this.wrap.content;
 return obj.offsetHeight;
 },
 getClientHeight : function() {
 return this.wrap.track.offsetHeight;
 },
 scrollTo : function(px) {

 var limit = [ 0, this.getScrollHeight() - this.getClientHeight() ]
 if (limit[1] < 0) limit[1] = 0;

 if (px < limit[0]) px = limit[0];
 if (px > limit[1]) px = limit[1];

 this.wrap.content.style.top = -px + "px";

 this._resetThumb();
 },
 scrollBy : function(px) {
 var top = this.getScrollTop();
 this.scrollTo(top + px);
 },
 setSkinClass : function(className) {
 this.options.skinClass = className;
 this._resetDesign();
 },
 setWidth : function(px) {
 this.options.width = px;
 this._resetDesign();
 },
 setHeight : function(px) {
 this.options.height = px;
 this._resetDesign();
 }
});
 /**
 * @projectDescription Copy string to clipboard component
 * @copyright NHN corp. <http://www.nhncorp.com>
 * @author AjaxUI Team <TarauS>
 */

 Ku.SetClipboard = Class({
 /**
 *
 */
 __init : function(){
 },

 /**
 *
 * @return {Object}
 */
 _getModule : function(){
 return window.document[Ku.SetClipboard._sModuleId];
 },

 /**
 * setClipboard
 * @return {Boolean}
 */
 checkFunction : function(){
 var oModule = this._getModule();
 if(oModule) return typeof(oModule.setClipboard) == 'function' ? true : false;
 },

 /**
 * sString
 * @param {String} sString
 * @param {String} sMessage
 */
 setClipboard : function(sString, sMessage){
 var bResult;
 try{
 var oModule = this._getModule();
 if(oModule){
 if(sString) bResult = oModule.setClipboard(sString);
 if(sMessage && bResult) alert(sMessage);
 }
 return bResult;
 }catch(e){
 return false;
 }
 }
 });

 Ku.SetClipboard.initFlash = function(sSwfPath){
 if(sSwfPath == null) sSwfPath = './clipboard.swf';
 Ku.SetClipboard._sModuleId = 'SetClipboardModule'+(new Date).getMilliseconds()+Math.floor(Math.random()*100000);
 document.write('<object id="'+Ku.SetClipboard._sModuleId+'" width="0" height="0" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">');
 document.write('<param name="movie" value="'+sSwfPath+'">');
 document.write('<param name="allowScriptAccess" value = "always" />');
 document.write('<embed name="'+Ku.SetClipboard._sModuleId+'" src="'+sSwfPath+'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="0" height="0" allowScriptAccess="always"></embed></object>');
 }
/**
 * Slider component
 * @author gony
 */
Ku.Slider = Class({
 _applying : false,
 __init : function(id) {
 this.options = JINDO.extend({
 unit : null, // id of draggable unit
 minValue : 0,
 maxValue : 100,
 defValue : 0, // default value
 step : 10,
 maxPos : 86, // position related to parent (px)
 initPos : {x:1, y:12}, // initial position (minimun value)
 vertical : false,
 onChange : function(v){},
 onChanging : function(v){}
 }, arguments[1]);

 var e = this._element = $(id);
 var o = this.options;

 this._value = o.minValue;

 // event binding
 this.onmousedown = this.onDown.bindForEvent(this);
 this.onmousemove = this.onMove.bindForEvent(this);
 this.onmouseup = this.onUp.bind(this);
 this.onclick = this.onClick.bindForEvent(this);

 Event.register(this._element, 'click', this.onclick);

 // positining draggable unit
 var u = this._unit_element = $(o.unit);
 u.ondragstart = u.ondrag = function(){ return false }
 u.onmousedown = this.onmousedown;

 Element.setCSS(u, {
 cursor : 'pointer',
 top : o.initPos.y+'px',
 left : o.initPos.x+'px'
 });
 },
 getValue : function() {
 return this._value;
 },
 setValue : function(v) {
 if (this._applying) return;

 var o = this.options; v = Math.min(Math.max(v,o.minValue),o.maxValue);
 var p = this._val2pos(v);

 if (this.options.vertical) {
 this._unit_element.style.top = p+'px';
 } else {
 this._unit_element.style.left = p+'px';
 }

 this._value = v;
 this.options.onChange(v);
 },
 setValueBy : function(diff) {
 var o = this.options;
 var v = Math.min(Math.max(this._value+diff,o.minValue),o.maxValue);

 this.setValue(v);
 },
 onDown : function(e) {
 var u = this._unit_element;
 if (e.mouse.left) {
 this._moving = true;
 this._startPos = [parseInt(u.style.left), parseInt(u.style.top)];
 this._startXY = [e.clientX, e.clientY];

 // register event
 Event.register(document, 'mouseup', this.onmouseup);
 Event.register(document, 'mousemove', this.onmousemove);
 Event.register(document, 'keypress', this.onmousemove);

 e.stop();
 }
 },
 onMove : function(e) {
 var newX, newY;
 var o = this.options;
 var u = this._unit_element;

 if (!this._moving || !e.mouse.left) return;
 newX = this._startPos[0] + e.clientX - this._startXY[0];
 newY = this._startPos[1] + e.clientY - this._startXY[1];

 if (o.vertical) {
 newY = Math.max(Math.min(newY,o.maxPos),o.initPos.y);
 u.style.top = newY + 'px';
 } else {
 newX = Math.max(Math.min(newX,o.maxPos),o.initPos.x);
 u.style.left = newX + 'px';
 }

 this._value = this._pos2val(o.vertical?newY:newX);

 this.options.onChanging(this._value);
 },
 onUp : function() {
 var t =this;

 this._moving = false;

 // unregister event
 Event.unregister(document, 'mouseup', this.onmouseup);
 Event.unregister(document, 'mousemove', this.onmousemove);
 Event.unregister(document, 'keypress', this.onmousemove);

 this.setValue(this._value);
 this.options.onChange(this._value);

 // prevent onclick event
 this._applying = true;
 setTimeout(function(){t._applying=false}, 50);
 },
 onClick : function(e) {
 var pos = Element.realPos(this._element);
 this.setValue(this._pos2val(e.page_x-pos.left-this._unit_element.offsetWidth/2));
 },
 _pos2val : function(p) {
 var o = this.options,s=o.step;
 var m = o.vertical?o.initPos.y:o.initPos.x;
 var v = (p-m)*(o.maxValue-o.minValue)/(o.maxPos-m);
 var q = Math.floor(v/s), r=v-q*s, v=q*s;

 return Math.max(Math.min(Math.round((v+o.minValue+((s/2)>r?0:s))*100)/100, o.maxValue), o.minValue);
 },
 _val2pos : function(v) {
 var o = this.options;
 var m = o.vertical?o.initPos.y:o.initPos.x;
 var p = (v-o.minValue)*(o.maxPos-m)/(o.maxValue-o.minValue);

 return Math.max(Math.min(Math.round((p+m)*100)/100, o.maxPos), m);
 }
});

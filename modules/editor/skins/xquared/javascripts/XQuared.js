/*! Xquared is copyrighted free software by Alan Kang <jania902@gmail.com>.
 *  For more information, see http://xquared.springbook.playmaru.net/
 */
if(!window.xq) {
	/**
	 * @namespace Contains all variables.
	 */
	var xq = {};
}

xq.majorVersion = '0.7';
xq.minorVersion = '20080402';



/**
 * Compiles regular expression pattern if possible.
 *
 * @param {String} p Regular expression.
 * @param {String} f Flags.
 */
xq.compilePattern = function(p, f) {
	if(!RegExp.prototype.compile) return new RegExp(p, f);
	
	var r = new RegExp();
	r.compile(p, f);
	return r;
}



/**
 * @class Simple class based OOP framework
 */
xq.Class = function() {
	var parent = null, properties = xq.$A(arguments), key;
	if (typeof properties[0] === "function") {
		parent = properties.shift();
	}
	
	function klass() {
		this.initialize.apply(this, arguments);
	}
	
	if(parent) {
		for (key in parent.prototype) {
			klass.prototype[key] = parent.prototype[key];
		}
	}
	
	for (key in properties[0]) if(properties[0].hasOwnProperty(key)){
		klass.prototype[key] = properties[0][key];
	}
	
	if (!klass.prototype.initialize) {
		klass.prototype.initialize = function() {};
	}
	
	klass.prototype.constructor = klass;
	
	return klass;
};

/**
 * Registers event handler
 *
 * @param {Element} element Target element.
 * @param {String} eventName Name of event. For example "keydown".
 * @param {Function} handler Event handler.
 */
xq.observe = function(element, eventName, handler) {
	if (element.addEventListener) {
		element.addEventListener(eventName, handler, false);
	} else {
		element.attachEvent('on' + eventName, handler);
	}
	element = null;
};

/**
 * Unregisters event handler
 */
xq.stopObserving = function(element, eventName, handler) {
	if (element.removeEventListener) {
		element.removeEventListener(eventName, handler, false);
	} else {
		element.detachEvent("on" + eventName, handler);
	}
	element = null;
};

/**
 * Predefined event handler which simply cancels given event
 *
 * @param {Event} e Event to cancel.
 */
xq.cancelHandler = function(e) {xq.stopEvent(e); return false;};

/**
 * Stops event propagation.
 *
 * @param {Event} e Event to stop.
 */
xq.stopEvent = function(e) {
      if(e.preventDefault) {
    	  e.preventDefault();
      }
      if(e.stopPropagation) {
    	  e.stopPropagation();
      }
      e.returnValue = false;
      e.cancelBubble = true;
      e.stopped = true;
};

xq.isButton = function(event, code) {
     return event.which ? (event.which === code + 1) : (event.button === code);
};
xq.isLeftClick = function(event) {return xq.isButton(event, 0);};
xq.isMiddleClick = function(event) {return xq.isButton(event, 1);};
xq.isRightClick = function(event) {return xq.isButton(event, 2);};

xq.getEventPoint = function(event) {
	return {
		x: event.pageX || (event.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft)),
		y: event.pageY || (event.clientY + (document.documentElement.scrollTop || document.body.scrollTop))
	};
};

xq.getCumulativeOffset = function(element, until) {
	var top = 0, left = 0;
	
	do {
		top += element.offsetTop  || 0;
		left += element.offsetLeft || 0;
		element = element.offsetParent;
	} while (element && element != until);
	
	return {top:top, left:left};
};

xq.$ = function(id) {
	return document.getElementById(id);
};

xq.isEmptyHash = function(h) {
	for(var key in h) if(h.hasOwnProperty(key)){
		return false;
	}
	return true;
};

xq.emptyFunction = function() {};

xq.$A = function(arraylike) {
	var len = arraylike.length, a = [];
	while (len--) {
		a[len] = arraylike[len];
	}
	return a;
};

xq.addClassName = function(element, className) {
	if (!xq.hasClassName(element, className)) {
		element.className += (element.className ? ' ' : '') + className;
	}
    return element;
};
xq.removeClassName = function(element, className) {
	if (xq.hasClassName(element, className)) {
		element.className = element.className.replace(new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
	}
    return element;
};
xq.hasClassName = function(element, className) {
	var classNames = element.className;
	return (classNames.length > 0 && (classNames === className || new RegExp("(^|\\s)" + className + "(\\s|$)").test(classNames)));
};

xq.serializeForm = function(f) {
	var options = {hash: true};
	var data = {};
	var elements = f.getElementsByTagName("*");
	for(var i = 0; i < elements.length; i++) {
		var element = elements[i];
		var tagName = element.tagName.toLowerCase();
		if(element.disabled || !element.name || ['input', 'textarea', 'option', 'select'].indexOf(tagName) === -1) {
			continue;
		}
		
		var key = element.name;
		var value = xq.getValueOfElement(element);
		
		if(value === undefined) {
			continue;
		}
		
		if(key in data) {
			if(data[key].constructor === Array) {
				data[key] = [data[key]];
			}
			data[key].push(value);
		} else {
			data[key] = value;
		}
	}
	return data;
};

xq.getValueOfElement = function(e) {
	var type = e.type.toLowerCase();
	if(type === 'checkbox' || type === 'radio') {
		return e.checked ? e.value : undefined;
	} else {
		return e.value;
	}
};

/**
 * Find elements by class name (and tag name)
 * 
 * @param {Element} element Root element
 * @param {String} className Target class name
 * @param {String} tagName Optional tag name
 */
xq.getElementsByClassName = function(element, className, tagName) {
	if(!tagName && element.getElementsByClassName) {
		return element.getElementsByClassName(className);
	}
	
	var elements = element.getElementsByTagName(tagName || "*");
	var len = elements.length;
	var result = [];
	var p = xq.compilePattern("(^|\\s)" + className + "($|\\s)", "i");
	for(var i = 0; i < len; i++) {
		var cur = elements[i];
		if(p.test(cur.className)) {
			result.push(cur);
		}
	}
	return result;
};

if(!window.Prototype) {
	if(!Function.prototype.bind) {
		Function.prototype.bind = function() {
			var m = this, arg = xq.$A(arguments), o = arg.shift();
			return function() {
				return m.apply(o, arg.concat(xq.$A(arguments)));
			};
		};
	}
	
	if(!Function.prototype.bindAsEventListener) {
		Function.prototype.bindAsEventListener = function() {
			var m = this, arg = xq.$A(arguments), o = arg.shift();
			return function(event) {
				return m.apply(o, [event || window.event].concat(arg));
			};
		};
	}
	
	Array.prototype.find = function(f) {
		for(var i = 0; i < this.length; i++) {
			if(f(this[i])) {
				return this[i];
			}
		}
	};
	
	Array.prototype.findAll = function(f) {
		var result = [];
		for(var i = 0; i < this.length; i++) {
			if(f(this[i])) {
				result.push(this[i]);
			}
		}
		return result;
	};
	
	Array.prototype.first = function() {return this[0];};
	
	Array.prototype.last = function() {return this[this.length - 1];};
	
	Array.prototype.flatten = function() {
		var result = [];
		var recursive = function(array) {
			for(var i = 0; i < array.length; i++) {
				if(array[i].constructor === Array) {
					recursive(array[i]);
				} else {
					result.push(array[i]);
				}
			}
		};
		recursive(this);
		
		return result;
	};
	
	xq.pStripTags = xq.compilePattern("</?[^>]+>", "gi");
	String.prototype.stripTags = function() {
	    return this.replace(xq.pStripTags, '');
	};
	String.prototype.escapeHTML = function() {
		xq.textNode.data = this;
		return xq.divNode.innerHTML;
	};
	xq.textNode = document.createTextNode('');
	xq.divNode = document.createElement('div');
	xq.divNode.appendChild(xq.textNode);
	
	xq.pStrip1 = xq.compilePattern("^\\s+");
	xq.pStrip2 = xq.compilePattern("\\s+$");
	String.prototype.strip = function() {
		return this.replace(xq.pStrip1, '').replace(xq.pStrip2, '');
	};
	
	Array.prototype.indexOf = function(n) {
		for(var i = 0; i < this.length; i++) {
			if(this[i] === n) {
				return i;
			}
		}
		
		return -1;
	};
}

Array.prototype.includeElement = function(o) {
	if (this.indexOf(o) !== -1) {
		return true;
	}

    var found = false;
    for(var i = 0; i < this.length; i++) {
    	if(this[i] === o) {
    		return true;
    	}
    }
    
    return false;
};


/**
 * Make given object as event source
 *
 * @param {Object} object target object
 * @param {String} prefix prefix for generated functions
 * @param {Array} events array of string which contains name of events
 */
xq.asEventSource = function(object, prefix, events) {
	object.autoRegisteredEventListeners = [];
	object.registerEventFirer = function(prefix, name) {
		this["_fireOn" + name] = function() {
			for(var i = 0; i < this.autoRegisteredEventListeners.length; i++) {
				var listener = this.autoRegisteredEventListeners[i];
				var func = listener["on" + prefix + name];
				if(func) {
					func.apply(listener, xq.$A(arguments));
				}
			}
		};
	};
	object.addListener = function(l) {
		this.autoRegisteredEventListeners.push(l);
	};
	
	for(var i = 0; i < events.length; i++) {
		object.registerEventFirer(prefix, events[i]);
	}
};



/**
 * JSON to Element mapper
 */
xq.json2element = function(json, doc) {
	var div = doc.createElement("DIV");
	div.innerHTML = xq.json2html(json);
	return div.firstChild || {};
};

/**
 * Element to JSON mapper
 */
xq.element2json = function(element) {
	var o, i, childElements;
	
	if(element.nodeName === 'DL') {
		o = {};
		childElements = xq.findChildElements(element);
		for(i = 0; i < childElements.length; i++) {
			var dt = childElements[i];
			var dd = childElements[++i];
			o[dt.innerHTML] = xq.element2json(xq.findChildElements(dd)[0]);
		}
		return o;
	} else if (element.nodeName === 'OL') {
		o = [];
		childElements = xq.findChildElements(element);
		for(i = 0; i < childElements.length; i++) {
			var li = childElements[i];
			o[i] = xq.element2json(xq.findChildElements(li)[0]);
		}
	} else if(element.nodeName === 'SPAN' && element.className === 'number') {
		return parseFloat(element.innerHTML);
	} else if(element.nodeName === 'SPAN' && element.className === 'string') {
		return element.innerHTML;
	} else { // ignore textnode or unknown tag
		return null;
	}
};

/**
 * JSON to HTML string mapper
 */
xq.json2html = function(json) {
	var sb = [];
	xq._json2html(json, sb);
	return sb.join('');
};

xq._json2html = function(o, sb) {
	if(typeof o === 'number') {
		sb.push('<span class="number">' + o + '</span>');
	} else if(typeof o === 'string') {
		sb.push('<span class="string">' + o.escapeHTML() + '</span>');
	} else if(o.constructor === Array) {
		sb.push('<ol>');
		for(var i = 0; i < o.length; i++) {
			sb.push('<li>');
			xq._json2html(o[i], sb);
			sb.push('</li>');
		}
		sb.push('</ol>');
	} else { // Object
		sb.push('<dl>');
		for (var key in o) if (o.hasOwnProperty(key)) {
			sb.push('<dt>' + key + '</dt>');
			sb.push('<dd>');
			xq._json2html(o[key], sb);
			sb.push('</dd>');
		}
		sb.push('</dl>');
	}
};

xq.findChildElements = function(parent) {
	var childNodes = parent.childNodes;
	var elements = [];
	for(var i = 0; i < childNodes.length; i++) {
		if(childNodes[i].nodeType === 1) {
			elements.push(childNodes[i]);
		}
	}
	return elements;
};



Date.preset = null;
Date.pass = function(msec) {
	if(Date.preset !== null) {
		Date.preset = new Date(Date.preset.getTime() + msec);
	}
};
Date.get = function() {
	return Date.preset === null ? new Date() : Date.preset;
};
Date.prototype.elapsed = function(msec, curDate) {
	return (curDate || Date.get()).getTime() - this.getTime() >= msec;
};

String.prototype.merge = function(data) {
	var newString = this;
	for(var k in data) if(data.hasOwnProperty(k)) {
		newString = newString.replace("{" + k + "}", data[k]);
	}
	return newString;
};
xq.pBlank = xq.compilePattern("^\\s*$");
String.prototype.isBlank = function() {
	return xq.pBlank.test(this);
};
xq.pURL = xq.compilePattern("((((\\w+)://(((([^@:]+)(:([^@]+))?)@)?([^:/\\?#]+)?(:(\\d+))?))?([^\\?#]+)?)(\\?([^#]+))?)(#(.+))?");
String.prototype.parseURL = function() {
	var m = this.match(xq.pURL);
	
	var includeAnchor = m[0];
	var includeQuery = m[1] || undefined;
	var includePath = m[2] || undefined;
	var includeHost = m[3] || undefined;
	var includeBase = null;
	var protocol = m[4] || undefined;
	var user = m[8] || undefined;
	var password = m[10] || undefined;
	var domain = m[11] || undefined;
	var port = m[13] || undefined;
	var path = m[14] || undefined;
	var query = m[16] || undefined;
	var anchor = m[18] || undefined;
	
	if(!path || path === '/') {
		includeBase = includeHost + '/';
	} else {
		var index = path.lastIndexOf('/');
		includeBase = includeHost + path.substring(0, index + 1);
	}
	
	return {
		includeAnchor: includeAnchor,
		includeQuery: includeQuery,
		includePath: includePath,
		includeBase: includeBase,
		includeHost: includeHost,
		protocol: protocol,
		user: user,
		password: password,
		domain: domain,
		port: port,
		path: path,
		query: query,
		anchor: anchor
	};
};



xq.commonAttrs = ['title', 'class', 'id', 'style'];;

/**
 * Pre-defined whitelist
 */
xq.predefinedWhitelist = { 
   'a':				xq.commonAttrs.concat('href', 'charset', 'rev', 'rel', 'type', 'hreflang', 'tabindex'),
   'abbr':			xq.commonAttrs.concat(),
   'acronym':		xq.commonAttrs.concat(),
   'address':		xq.commonAttrs.concat(),
   'blockquote':	xq.commonAttrs.concat('cite'),
   'br':			xq.commonAttrs.concat(),
   'button':		xq.commonAttrs.concat('disabled', 'type', 'name', 'value'),
   'caption':		xq.commonAttrs.concat(),
   'cite':			xq.commonAttrs.concat(),
   'code':			xq.commonAttrs.concat(),
   'dd':			xq.commonAttrs.concat(),
   'dfn':			xq.commonAttrs.concat(),
   'div':			xq.commonAttrs.concat(),
   'dl':			xq.commonAttrs.concat(),
   'dt':			xq.commonAttrs.concat(),
   'em':			xq.commonAttrs.concat(),
   'embed':			xq.commonAttrs.concat('src', 'width', 'height', 'allowscriptaccess', 'type', 'allowfullscreen', 'bgcolor'),
   'h1':			xq.commonAttrs.concat(),
   'h2':			xq.commonAttrs.concat(),
   'h3':			xq.commonAttrs.concat(),
   'h4':			xq.commonAttrs.concat(),
   'h5':			xq.commonAttrs.concat(),
   'h6':			xq.commonAttrs.concat(),
   'hr':			xq.commonAttrs.concat(),
   'iframe':		xq.commonAttrs.concat('name', 'src', 'frameborder', 'scrolling', 'width', 'height', 'longdesc'),
   'input':			xq.commonAttrs.concat('type', 'name', 'value', 'size', 'checked', 'readonly', 'src', 'maxlength'),
   'img':			xq.commonAttrs.concat('alt', 'width', 'height', 'src', 'longdesc'),
   'label':			xq.commonAttrs.concat('for'),
   'kbd':			xq.commonAttrs.concat(),
   'li':			xq.commonAttrs.concat(),
   'object':		xq.commonAttrs.concat('align', 'classid', 'codetype', 'archive', 'width', 'type', 'codebase', 'height', 'data', 'name', 'standby', 'declare'),
   'ol':			xq.commonAttrs.concat(),
   'option':		xq.commonAttrs.concat('disabled', 'selected', 'laabel', 'value'),
   'p':				xq.commonAttrs.concat(),
   'param':			xq.commonAttrs.concat('name', 'value', 'valuetype', 'type'),
   'pre':			xq.commonAttrs.concat(),
   'q':				xq.commonAttrs.concat('cite'),
   'samp':			xq.commonAttrs.concat(),
   'script':		xq.commonAttrs.concat('src', 'type'),
   'select':		xq.commonAttrs.concat('disabled', 'size', 'multiple', 'name'),
   'span':			xq.commonAttrs.concat(),
   'sup':			xq.commonAttrs.concat(),
   'sub':			xq.commonAttrs.concat(),
   'strong':		xq.commonAttrs.concat(),
   'table':			xq.commonAttrs.concat('summary', 'width'),
   'thead':			xq.commonAttrs.concat(),
   'textarea':		xq.commonAttrs.concat('cols', 'disabled', 'rows', 'readonly', 'name'),
   'tbody':			xq.commonAttrs.concat(),
   'th':			xq.commonAttrs.concat('colspan', 'rowspan'),
   'td':			xq.commonAttrs.concat('colspan', 'rowspan'),
   'tr':			xq.commonAttrs.concat(),
   'tt':			xq.commonAttrs.concat(),
   'ul':			xq.commonAttrs.concat(),
   'var':			xq.commonAttrs.concat()
};



/**
 * Automatic finalization queue
 */
xq.autoFinalizeQueue = [];

/**
 * Automatic finalizer
 */
xq.addToFinalizeQueue = function(obj) {
	xq.autoFinalizeQueue.push(obj);
};

/**
 * Finalizes given object
 */
xq.finalize = function(obj) {
	if(typeof obj.finalize === "function") {
		try {obj.finalize();} catch(ignored) {}
	}
	
	for(var key in obj) if(obj.hasOwnProperty(key)) {
		obj[key] = null;
	}
};

xq.observe(window, "unload", function() {
	// "xq" and "xq.autoFinalizeQueue" could be removed by another libraries' clean-up mechanism.
	if(xq && xq.autoFinalizeQueue) {
		for(var i = 0; i < xq.autoFinalizeQueue.length; i++) {
			xq.finalize(xq.autoFinalizeQueue[i]);
		}
		xq = null;
	}
});



/**
 * Finds Xquared's <script> element
 */
xq.findXquaredScript = function() {
    return xq.$A(document.getElementsByTagName("script")).find(function(script) {
    	return script.src && script.src.match(/xquared\.js/i);
    });
};
xq.shouldLoadOthers = function() {
	var script = xq.findXquaredScript();
    return script && !!script.src.match(/xquared\.js\?load_others=1/i);
};
/**
 * Loads javascript from given URL
 */
xq.loadScript = function(url) {
    document.write('<script type="text/javascript" src="' + url + '"></script>');
};

/**
 * Returns all Xquared script file names
 */
xq.getXquaredScriptFileNames = function() {
	return [
		'Xquared.js',
		'Browser.js',
		'DomTree.js',
		'rdom/Base.js',
		'rdom/W3.js',
		'rdom/Gecko.js',
		'rdom/Webkit.js',
		'rdom/Trident.js',
		'rdom/Factory.js',
		'validator/Base.js',
		'validator/W3.js',
		'validator/Gecko.js',
		'validator/Webkit.js',
		'validator/Trident.js',
		'validator/Factory.js',
		'macro/Base.js',
		'macro/Factory.js',
		'macro/FlashMovieMacro.js',
		'macro/IFrameMacro.js',
		'macro/JavascriptMacro.js',
		'EditHistory.js',
		'plugin/Base.js',
		'RichTable.js',
		'Timer.js',
		'Layer.js',
		'ui/Base.js',
		'ui/Control.js',
		'ui/Toolbar.js',
		'ui/_templates.js',
		'Json2.js',
		'Shortcut.js',
		'Editor.js'
	];
};
xq.getXquaredScriptBasePath = function() {
	var script = xq.findXquaredScript();
	return script.src.match(/(.*\/)xquared\.js.*/i)[1];
};

xq.loadOthers = function() {
	var basePath = xq.getXquaredScriptBasePath();
	var others = xq.getXquaredScriptFileNames();
	
	// Xquared.js(this file) should not be loaded again. So the value of "i" starts with 1 instead of 0
	for(var i = 1; i < others.length; i++) {
		xq.loadScript(basePath + others[i]);
	}
};

if(xq.shouldLoadOthers()) {
	xq.loadOthers();
}
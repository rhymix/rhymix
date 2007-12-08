/**
 * Namespace for entire Xquared classes
 */
var xq = {
	majorVersion: '0.2',
	minorVersion: '20071205'
};



/**
 * Add prototype.js like functions
 */
xq.Class = function() { // TODO
	var parent = null, properties = xq.$A(arguments);
	if (typeof properties[0] == "function")
		parent = properties.shift();
	
	function klass() {
		this.initialize.apply(this, arguments);
	}
	
	if(parent) {
		for (var key in parent.prototype)
			klass.prototype[key] = parent.prototype[key];
	}
		
	for (var key in properties[0])
		klass.prototype[key] = properties[0][key];
	
	if (!klass.prototype.initialize)
		klass.prototype.initialize = function() {};
	
	klass.prototype.constructor = klass;
	
	return klass;
}

xq.observe = function(element, eventName, handler) {
	if (element.addEventListener) {
		element.addEventListener(eventName, handler, false);
	} else {
		element.attachEvent('on' + eventName, handler);
	}
	element = null;
}

xq.stopObserving = function(element, eventName, handler) {
	if (element.removeEventListener) {
		element.removeEventListener(eventName, handler, false);
	} else {
		element.detachEvent("on" + eventName, handler);
	}
	element = null;
}

xq.cancelHandler = function(e) {xq.stopEvent(e); return false};

xq.stopEvent = function(event) {
      if(event.preventDefault) event.preventDefault();
      if(event.stopPropagation) event.stopPropagation();
      event.returnValue = false;
      event.cancelBubble = true;
      event.stopped = true;
}

xq.isButton = function(event, code) {
     return event.which ? (event.which === code + 1) : (event.button === code);
}
xq.isLeftClick = function(event) {return isButton(event, 0);}
xq.isMiddleClick = function(event) {return isButton(event, 1);}
xq.isRightClick = function(event) {return isButton(event, 2);}

xq.getEventPoint = function(event) {
	return {
		x: event.pageX || (event.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft)),
		y: event.pageY || (event.clientY + (document.documentElement.scrollTop || document.body.scrollTop))
	};
}

xq.getCumulativeOffset = function(element) {
	var top = 0, left = 0;
	
	do {
		top += element.offsetTop  || 0;
		left += element.offsetLeft || 0;
		element = element.offsetParent;
	} while (element);
	
	return {top:top, left:left};
}

xq.$ = function(id) {
	return document.getElementById(id);
}

xq.isEmptyHash = function(h) {
	for(var key in h) {
		return false;
	}
	return true;
}

xq.$A = function(arraylike) {
	var len = arraylike.length, a = new Array(len);
	while (len--) a[len] = arraylike[len];
	return a;
}

xq.hasClassName = function(element, className) {
	var classNames = element.className;
	return (classNames.length > 0 && (classNames == className || new RegExp("(^|\\s)" + className + "(\\s|$)").test(classNames)));
}

xq.serializeForm = function(f) {

try{
	var options = {hash: true};
	var data = {};
	var elements = f.getElementsByTagName("*");
	for(var i = 0; i < elements.length; i++) {
		var element = elements[i];
		var tagName = element.tagName.toLowerCase();
		if(element.disabled || !element.name || ['input', 'textarea', 'option', 'select'].indexOf(tagName) == -1) continue;
		
		var key = element.name;
		var value = xq.getValueOfElement(element);
		
		if(value === undefined) continue;
		
		if(key in data) {
			if(data[key].constructor == Array) data[key] = [data[key]];
			data[key].push(value);
		} else {
			data[key] = value;
		}
	}
	return data;
} catch(e) {alert(e)}
}

xq.getValueOfElement = function(e) {
	var type = e.type.toLowerCase();
	if(type == 'checkbox' || type == 'radio') return e.checked ? e.value : undefined;
	return e.value;
}

xq.getElementsByClassName = function(element, className) {
	if(element.getElementsByClassName) return element.getElementsByClassName(className);
	
	var elements = element.getElementsByTagName("*");
	var len = elements.length;
	var result = [];
	var p = new RegExp("(^|\\s)" + className + "($|\\s)");
	for(var i = 0; i < len; i++) {
		var cur = elements[i];
		if(p.test(cur.className)) result.push(cur);
	}
	return result;
}

try {Prototype.version;	__prototype = true;} catch(ignored) {__prototype = false;}

if(!__prototype) {
	if(!Function.prototype.bind) {
		Function.prototype.bind = function() {
			var __m = this, arg = xq.$A(arguments), o = arg.shift();
			return function() {
				return __m.apply(o, arg.concat(xq.$A(arguments)));
			}
		}
	}
	
	if(!Function.prototype.bindAsEventListener) {
		Function.prototype.bindAsEventListener = function() {
			var __m = this, arg = xq.$A(arguments), o = arg.shift();
			return function(event) {
				return __m.apply(o, [event || window.event].concat(arg));
			}
		}
	}
	
	Array.prototype.find = function(f) {
		for(var i = 0; i < this.length; i++) {
			if(f(this[i])) return this[i];
		}
	}
	
	Array.prototype.findAll = function(f) {
		var result = [];
		for(var i = 0; i < this.length; i++) {
			if(f(this[i])) result.push(this[i]);
		}
		return result;
	}
	
	Array.prototype.first = function() {return this[0]}
	
	Array.prototype.last = function() {return this[this.length - 1]}
	
	Array.prototype.include = function(o) {
		if (this.indexOf(o) != -1) return true;
	
	    var found = false;
	    for(var i = 0; i < this.length; i++) {
	    	if(this[i] == o) return true;
	    }
	    
	    return false;
	}
	
	Array.prototype.flatten = function() {
		var result = [];
		var _flatten = function(array) {
			for(var i = 0; i < array.length; i++) {
				if(array[i].constructor === Array) {
					_flatten(array[i]);
				} else {
					result.push(array[i]);
				}
			}
		}
		_flatten(this);
		
		return result;
	}
	
	String.prototype.blank = function() {
		return /^\s*$/.test(this);
	}
	String.prototype.stripTags = function() {
	    return this.replace(/<\/?[^>]+>/gi, '');
	}
	String.prototype.escapeHTML = function() {
		xq._text.data = this;
		return xq._div.innerHTML;
	}
	xq._text = document.createTextNode('');
	xq._div = document.createElement('div');
	xq._div.appendChild(xq._text);
	
	String.prototype.strip = function() {
		return this.replace(/^\s+/, '').replace(/\s+$/, '');
	}
	
	Array.prototype.indexOf = function(n) {
		for(var i = 0; i < this.length; i++) {
			if(this[i] == n) return i;
		}
		
		return -1;
	}
}



/**
 * Make given object as event source
 *
 * @param {Object} object target object
 * @param {String} prefix prefix for generated functions
 * @param {Array} events array of string which contains name of events
 */
xq.asEventSource = function(object, prefix, events) {
	object._listeners = []
	object._registerEventFirer = function(prefix, name) {
		this["_fireOn" + name] = function() {
			for(var i = 0; i < this._listeners.length; i++) {
				var listener = this._listeners[i];
				var func = listener["on" + prefix + name];
				if(func) func.apply(listener, xq.$A(arguments));
			}
		}
	}
	object.addListener = function(l) {
		this._listeners.push(l);
	}
	
	for(var i = 0; i < events.length; i++) {
		object._registerEventFirer(prefix, events[i]);
	}
}



Date.preset = null;
Date.pass = function(msec) {
	if(Date.preset == null) return;
	Date.preset = new Date(Date.preset.getTime() + msec);
}
Date.get = function() {
	return Date.preset == null ? new Date() : Date.preset;
}
Date.prototype.elapsed = function(msec) {
	return Date.get().getTime() - this.getTime() >= msec;
}

String.prototype.merge = function(data) {
	var newString = this;
	for(k in data) {
		newString = newString.replace("{" + k + "}", data[k]);
	}
	return newString;
}

String.prototype.parseURL = function() {
	var m = this.match(/((((\w+):\/\/(((([^@:]+)(:([^@]+))?)@)?([^:\/\?#]+)?(:(\d+))?))?([^\?#]+)?)(\?([^#]+))?)(#(.+))?/);
	
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
	
	if(!path || path == '/') {
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
}



/**
 * Automatic finalizer
 */
xq.autoFinalizeQueue = [];

xq.addToFinalizeQueue = function(obj) {
	xq.autoFinalizeQueue.push(obj);
}

xq.finalize = function(obj) {
	if(typeof obj.finalize == "function") {
		try {obj.finalize();} catch(ignored) {}
	}
	
	for(key in obj) obj[key] = null;
}

xq.observe(window, "unload", function() {
	for(var i = 0; i < xq.autoFinalizeQueue.length; i++) xq.finalize(xq.autoFinalizeQueue[i]);
	xq = null;
});



/**
 * Script loader
 */
xq.findXquaredScript = function() {
    return xq.$A(document.getElementsByTagName("script")).find(function(script) {
    	return script.src && script.src.match(/xquared\.js/i);
    });
}
xq.shouldLoadOthers = function() {
	var script = xq.findXquaredScript();
    return script && !!script.src.match(/xquared\.js\?load_others=1/i);
}
xq.loadScript = function(url) {
    document.write('<script type="text/javascript" src="' + url + '"></script>');
}
xq.loadOthers = function() {
	var script = xq.findXquaredScript();
	var basePath = script.src.match(/(.*\/)xquared\.js.*/i)[1];
	var others = [
		'Editor.js',
		'Browser.js',
		'Shortcut.js',
		'DomTree.js',
		'RichDom.js',
		'RichDomW3.js',
		'RichDomGecko.js',
		'RichDomWebkit.js',
		'RichDomTrident.js',
		'RichTable.js',
		'Validator.js',
		'ValidatorW3.js',
		'ValidatorGecko.js',
		'ValidatorWebkit.js',
		'ValidatorTrident.js',
		'EditHistory.js',
		'Controls.js',
		'_ui_templates.js'
	];
	for(var i = 0; i < others.length; i++) {
		xq.loadScript(basePath + others[i]);
	};
}

if(xq.shouldLoadOthers()) xq.loadOthers();
/**
 * @fileOverview xq.Editor manages configurations such as autocompletion and autocorrection, edit mode/normal mode switching, handles editing commands, keyboard shortcuts and other events.
 */
xq.Editor = xq.Class({
	/**
	 * Initialize editor but it doesn't automatically start designMode. setEditMode should be called after initialization.
	 *
     * @constructor
	 * @param {Object} contentElement HTML element(TEXTAREA or normal block element such as DIV) to be replaced with editable area, or DOM ID string.
	 * @param {Object} toolbarContainer HTML element which contains toolbar icons, or DOM ID string.
	 */
	initialize: function(contentElement, toolbarContainer) {
		xq.addToFinalizeQueue(this);
		
		if(typeof contentElement == 'string') contentElement = xq.$(contentElement);
		if(!contentElement) throw "[contentElement] is null";
		if(contentElement.nodeType != 1) throw "[contentElement] is not an element";
		
		if(typeof toolbarContainer == 'string') toolbarContainer = xq.$(toolbarContainer);
		
		xq.asEventSource(this, "Editor", ["ElementChanged", "BeforeEvent", "AfterEvent", "CurrentContentChanged", "StaticContentChanged", "CurrentEditModeChanged"]);
		
		/**
		 * Editor's configuration
		 * @type object
		 */
		this.config = {};
		this.config.enableLinkClick = false;
		this.config.changeCursorOnLink = false;
		this.config.generateDefaultToolbar = true;
		this.config.defaultToolbarButtonMap = [
			[
				{className:"foregroundColor", title:"Foreground color", handler:"xed.handleForegroundColor()"},
				{className:"backgroundColor", title:"Background color", handler:"xed.handleBackgroundColor()"}
			],
			[
				{className:"link", title:"Link", handler:"xed.handleLink()"},
				{className:"strongEmphasis", title:"Strong emphasis", handler:"xed.handleStrongEmphasis()"},
				{className:"emphasis", title:"Emphasis", handler:"xed.handleEmphasis()"},
				{className:"underline", title:"Underline", handler:"xed.handleUnderline()"},
				{className:"strike", title:"Strike", handler:"xed.handleStrike()"},
				{className:"superscription", title:"Superscription", handler:"xed.handleSuperscription()"},
				{className:"subscription", title:"Subscription", handler:"xed.handleSubscription()"}
			],
			[
				{className:"removeFormat", title:"Remove format", handler:"xed.handleRemoveFormat()"}
			],
			[
				{className:"justifyLeft", title:"Justify left", handler:"xed.handleJustify('left')"},
				{className:"justifyCenter", title:"Justify center", handler:"xed.handleJustify('center')"},
				{className:"justifyRight", title:"Justify right", handler:"xed.handleJustify('right')"},
				{className:"justifyBoth", title:"Justify both", handler:"xed.handleJustify('both')"}
			],
			[
				{className:"indent", title:"Indent", handler:"xed.handleIndent()"},
				{className:"outdent", title:"Outdent", handler:"xed.handleOutdent()"}
			],
			[
				{className:"unorderedList", title:"Unordered list", handler:"xed.handleList('UL')"},
				{className:"orderedList", title:"Ordered list", handler:"xed.handleList('OL')"}
			],
			[
				{className:"paragraph", title:"Paragraph", handler:"xed.handleApplyBlock('P')"},
				{className:"heading1", title:"Heading 1", handler:"xed.handleApplyBlock('H1')"},
				{className:"blockquote", title:"Blockquote", handler:"xed.handleApplyBlock('BLOCKQUOTE')"},
				{className:"code", title:"Code", handler:"xed.handleList('CODE')"},
				{className:"division", title:"Division", handler:"xed.handleApplyBlock('DIV')"}
			],
			[
				{className:"table", title:"Table", handler:"xed.handleTable(3,3,'tl')"},
				{className:"separator", title:"Separator", handler:"xed.handleSeparator()"}
			],
			[
				{className:"html", title:"Edit source", handler:"xed.toggleSourceAndWysiwygMode()"}
			],
			[
				{className:"undo", title:"Undo", handler:"xed.handleUndo()"},
				{className:"redo", title:"Redo", handler:"xed.handleRedo()"}
			]
		];
		
		this.config.imagePathForDefaultToobar = 'images/toolbar/';
		this.config.imagePathForContent = 'images/content/';
		
		// relative | host_relative | absolute | browser_default
		this.config.urlValidationMode = 'absolute';
		
		this.config.automaticallyHookSubmitEvent = true;
		
		this.config.allowedTags = ['a', 'abbr', 'acronym', 'address', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'dfn', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'img', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'span', 'sup', 'sub', 'strong', 'table', 'thead', 'tbody', 'td', 'th', 'tr', 'ul', 'var'];
		this.config.allowedAttributes = ['alt', 'cite', 'class', 'datetime', 'height', 'href', 'id', 'rel', 'rev', 'src', 'style', 'title', 'width'];
		
		this.config.shortcuts = {};
		this.config.autocorrections = {};
		this.config.autocompletions = {};
		this.config.templateProcessors = {};
		this.config.contextMenuHandlers = {};
		
		/**
		 * Original content element
		 * @type Element
		 */
		this.contentElement = contentElement;
		
		/**
		 * Owner document of content element
		 * @type Document
		 */
		this.doc = this.contentElement.ownerDocument;
		
		/**
		 * Body of content element
		 * @type Element
		 */
		this.body = this.doc.body;
		
		/**
		 * False or 'readonly' means read-only mode, true or 'wysiwyg' means WYSIWYG editing mode, and 'source' means source editing mode.
		 * @type Object
		 */
		this.currentEditMode = 'readonly';
		
		/**
		 * RichDom instance
		 * @type xq.RichDom
		 */
		this.rdom = xq.RichDom.createInstance();
		
		/**
		 * Validator instance
		 * @type xq.Validator
		 */
		this.validator = null;
		
		/**
		 * Outmost wrapper div
		 * @type Element
		 */
		this.outmostWrapper = null;
		
		/**
		 * Source editor container
		 * @type Element
		 */
		this.sourceEditorDiv = null;
		
		/**
		 * Source editor textarea
		 * @type Element
		 */
		this.sourceEditorTextarea = null;
		
		/**
		 * WYSIWYG editor container
		 * @type Element
		 */
		this.wysiwygEditorDiv = null;
		
		/**
		 * Design mode iframe
		 * @type IFrame
		 */
		this.editorFrame = null;
		
		/**
		 * Window that contains design mode iframe
		 * @type Window
		 */
		this.editorWin = null;
		
		/**
		 * Document that contained by design mode iframe
		 * @type Document
		 */
		this.editorDoc = null;
		
		/**
		 * Body that contained by design mode iframe
		 * @type Element
		 */
		this.editorBody = null;
		
		/**
		 * Toolbar container
		 * @type Element
		 */
		this.toolbarContainer = toolbarContainer;
		
		/**
		 * Toolbar buttons
		 * @type Array
		 */
		this.toolbarButtons = null;
		this._toolbarAnchorsCache = [];
		
		/**
		 * Undo/redo manager
		 * @type xq.EditHistory
		 */
		this.editHistory = null;
		
		this._contextMenuContainer = null;
		this._contextMenuItems = null;
		
		this._validContentCache = null;
		this._lastModified = null;
		
		this.addShortcuts(this._getDefaultShortcuts());
		this.addTemplateProcessors(this._getDefaultTemplateProcessors());
		
		this.addListener({
			onEditorCurrentContentChanged: function(xed) {
				var curFocusElement = xed.rdom.getCurrentElement();
				if(!curFocusElement) return;
				
				if(xed._lastFocusElement != curFocusElement) {
					if(!xed.rdom.tree.isBlockOnlyContainer(xed._lastFocusElement) && xed.rdom.tree.isBlock(xed._lastFocusElement)) {
						xed.rdom.removeTrailingWhitespace(xed._lastFocusElement);
					}
					xed._fireOnElementChanged(xed._lastFocusElement, curFocusElement);
					xed._lastFocusElement = curFocusElement;
				}

				xed.updateAllToolbarButtonsStatus(curFocusElement);
			}
		});
	},
	
	finalize: function() {
		for(var i = 0; i < this._toolbarAnchorsCache.length; i++) {
			this._toolbarAnchorsCache[i].xed = null;
			this._toolbarAnchorsCache[i].handler = null;
			this._toolbarAnchorsCache[i] = null;
		}
		this._toolbarAnchorsCache = null;
	},
	
	
	
	/////////////////////////////////////////////
	// Configuration Management
	
	_getDefaultShortcuts: function() {
		if(xq.Browser.isMac) {
			// Mac FF & Safari
			return [
				{event:"Ctrl+Shift+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
				
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Meta+Z", handler:"this.handleUndo()"},
				{event:"Meta+Shift+Z", handler:"this.handleRedo()"},
				{event:"Meta+Y", handler:"this.handleRedo()"}
			];
		} else if(xq.Browser.isUbuntu) {
			//  Ubunto FF
			return [
				{event:"Ctrl+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
			
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Ctrl+Z", handler:"this.handleUndo()"},
				{event:"Ctrl+Y", handler:"this.handleRedo()"}
			];
		} else {
			// Win IE & FF
			return [
				{event:"Ctrl+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
			
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Ctrl+Z", handler:"this.handleUndo()"},
				{event:"Ctrl+Y", handler:"this.handleRedo()"}
			];
		}
	},
	
	_getDefaultTemplateProcessors: function() {
		return [
			{
				id:"predefinedKeywordProcessor",
				handler:function(html) {
					var today = Date.get();
					var keywords = {
						year: today.getFullYear(),
						month: today.getMonth() + 1,
						date: today.getDate(),
						hour: today.getHours(),
						min: today.getMinutes(),
						sec: today.getSeconds()
					};
					
					return html.replace(/\{xq:(year|month|date|hour|min|sec)\}/img, function(text, keyword) {
						return keywords[keyword] || keyword;
					});
				}
			}
		];
	},
	
	/**
	 * Adds or replaces keyboard shortcut.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 * @param {Object} handler string or function to be evaluated or called
	 */
	addShortcut: function(shortcut, handler) {
		this.config.shortcuts[shortcut] = {"event":new xq.Shortcut(shortcut), "handler":handler};
	},
	
	/**
	 * Adds several keyboard shortcuts at once.
	 *
	 * @param {Array} list of shortcuts. each element should have following structure: {event:"keymap expression", handler:handler}
	 */
	addShortcuts: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addShortcut(list[i].event, list[i].handler);
		}
	},

	/**
	 * Returns keyboard shortcut matches with given keymap expression.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 */
	getShortcut: function(shortcut) {return this.config.shortcuts[shortcut];},

	/**
	 * Returns entire keyboard shortcuts' map
	 */
	getShortcuts: function() {return this.config.shortcuts;},
	
	/**
	 * Remove keyboard shortcut matches with given keymap expression.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 */
	removeShortcut: function(shortcut) {delete this.config.shortcuts[shortcut];},
	
	/**
	 * Adds or replaces autocorrection handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} criteria regex pattern or function to be used as a criterion for match
	 * @param {Object} handler string or function to be evaluated or called when criteria met
	 */
	addAutocorrection: function(id, criteria, handler) {
		if(criteria.exec) {
			var pattern = criteria;
			criteria = function(text) {return text.match(pattern)};
		}
		this.config.autocorrections[id] = {"criteria":criteria, "handler":handler};
	},
	
	/**
	 * Adds several autocorrection handlers at once.
	 *
	 * @param {Array} list of autocorrection. each element should have following structure: {id:"identifier", criteria:criteria, handler:handler}
	 */
	addAutocorrections: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addAutocorrection(list[i].id, list[i].criteria, list[i].handler);
		}
	},
	
	/**
	 * Returns autocorrection handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getAutocorrection: function(id) {return this.config.autocorrection[id];},
	
	/**
	 * Returns entire autocorrections' map
	 */
	getAutocorrections: function() {return this.config.autocorrections;},
	
	/**
	 * Removes autocorrection handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeAutocorrection: function(id) {delete this.config.autocorrections[id];},
	
	/**
	 * Adds or replaces autocompletion handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} criteria regex pattern or function to be used as a criterion for match
	 * @param {Object} handler string or function to be evaluated or called when criteria met
	 */
	addAutocompletion: function(id, criteria, handler) {
		if(criteria.exec) {
			var pattern = criteria;
			criteria = function(text) {
				var m = pattern.exec(text);
				return m ? m.index : -1;
			};
		}
		this.config.autocompletions[id] = {"criteria":criteria, "handler":handler};
	},
	
	/**
	 * Adds several autocompletion handlers at once.
	 *
	 * @param {Array} list of autocompletion. each element should have following structure: {id:"identifier", criteria:criteria, handler:handler}
	 */
	addAutocompletions: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addAutocompletion(list[i].id, list[i].criteria, list[i].handler);
		}
	},
	
	/**
	 * Returns autocompletion handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getAutocompletion: function(id) {return this.config.autocompletions[id];},
	
	/**
	 * Returns entire autocompletions' map
	 */
	getAutocompletions: function() {return this.config.autocompletions;},
	
	/**
	 * Removes autocompletion handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeAutocompletion: function(id) {delete this.config.autocompletions[id];},
	
	/**
	 * Adds or replaces template processor.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} handler string or function to be evaluated or called when template inserted
	 */
	addTemplateProcessor: function(id, handler) {
		this.config.templateProcessors[id] = {"handler":handler};
	},
	
	/**
	 * Adds several template processors at once.
	 *
	 * @param {Array} list of template processors. Each element should have following structure: {id:"identifier", handler:handler}
	 */
	addTemplateProcessors: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addTemplateProcessor(list[i].id, list[i].handler);
		}
	},
	
	/**
	 * Returns template processor matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getTemplateProcessor: function(id) {return this.config.templateProcessors[id];},

	/**
	 * Returns entire template processors' map
	 */
	getTemplateProcessors: function() {return this.config.templateProcessors;},

	/**
	 * Removes template processor matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeTemplateProcessor: function(id) {delete this.config.templateProcessors[id];},



	/**
	 * Adds or replaces context menu handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} handler string or function to be evaluated or called when onContextMenu occured
	 */
	addContextMenuHandler: function(id, handler) {
		this.config.contextMenuHandlers[id] = {"handler":handler};
	},
	
	/**
	 * Adds several context menu handlers at once.
	 *
	 * @param {Array} list of handlers. Each element should have following structure: {id:"identifier", handler:handler}
	 */
	addContextMenuHandlers: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addContextMenuHandler(list[i].id, list[i].handler);
		}
	},
	
	/**
	 * Returns context menu handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getContextMenuHandler: function(id) {return this.config.contextMenuHandlers[id];},

	/**
	 * Returns entire context menu handlers' map
	 */
	getContextMenuHandlers: function() {return this.config.contextMenuHandlers;},

	/**
	 * Removes context menu handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeContextMenuHandler: function(id) {delete this.config.contextMenuHandlers[id];},
	
	
	
	/////////////////////////////////////////////
	// Edit mode management
	
	/**
	 * Returns current edit mode - readonly, wysiwyg, source
	 */
	getCurrentEditMode: function() {
		return this.currentEditMode;
	},
	
	toggleSourceAndWysiwygMode: function() {
		var mode = this.getCurrentEditMode();
		if(mode == 'readonly') return;
		this.setEditMode(mode == 'wysiwyg' ? 'source' : 'wysiwyg');
		
		return true;
	},
	
	/**
	 * Switches between edit-mode/normal mode.
	 *
	 * @param {Object} mode false or 'readonly' means read-only mode, true or 'wysiwyg' means WYSIWYG editing mode, and 'source' means source editing mode.
	 */
	setEditMode: function(mode) {
		if(this.currentEditMode == mode) return;
		
		var firstCall = mode != false && mode != 'readonly' && !this.outmostWrapper;
		if(firstCall) {
			// Create editor element if needed
			this._createEditorFrame();
			this._registerEventHandlers();
			
			this.loadCurrentContentFromStaticContent();
			this.editHistory = new xq.EditHistory(this.rdom);
		}
		
		if(mode == 'wysiwyg') {
			// Update contents
			if(this.currentEditMode == 'source') this.setStaticContent(this.getSourceContent());
			this.loadCurrentContentFromStaticContent();
			
			// Make static content invisible
			this.contentElement.style.display = "none";
			
			// Make WYSIWYG editor visible
			this.sourceEditorDiv.style.display = "none";
			this.wysiwygEditorDiv.style.display = "block";
			this.outmostWrapper.style.display = "block";
			
			this.currentEditMode = mode;
			
			if(!xq.Browser.isTrident) {
				window.setTimeout(function() {
					if(this.getDoc().designMode == 'On') return;
					
					// Without it, Firefox doesn't display embedded SWF
					this.getDoc().designMode = 'On';
					
					// turn off Firefox's table editing feature
					try {this.getDoc().execCommand("enableInlineTableEditing", false, "false")} catch(ignored) {}
				}.bind(this), 0);
			}
			
			this.enableToolbarButtons();
			if(!firstCall) this.focus();
		} else if(mode == 'source') {
			// Update contents
			if(this.currentEditMode == 'wysiwyg') this.setStaticContent(this.getWysiwygContent());
			this.loadCurrentContentFromStaticContent();
			
			// Make static content invisible
			this.contentElement.style.display = "none";
			
			// Make source editor visible
			this.sourceEditorDiv.style.display = "block";
			this.wysiwygEditorDiv.style.display = "none";
			this.outmostWrapper.style.display = "block";
			
			this.currentEditMode = mode;

			this.disableToolbarButtons(['html']);
			if(!firstCall) this.focus();
		} else {
			// Update contents
			this.setStaticContent(this.getCurrentContent());
			this.loadCurrentContentFromStaticContent();

			// Make editor and toolbar invisible
			this.outmostWrapper.style.display = "none";
			
			// Make static content visible
			this.contentElement.style.display = "block";
			
			this.currentEditMode = mode;
		}
		
		this._fireOnCurrentEditModeChanged(this, mode);
	},
	
	/**
	 * Load CSS into editing-mode document
	 *
	 * @param {string} path URL
	 */
	loadStylesheet: function(path) {
		var head = this.editorDoc.getElementsByTagName("HEAD")[0];
		var link = this.editorDoc.createElement("LINK");
		link.rel = "Stylesheet";
		link.type = "text/css";
		link.href = path;
		head.appendChild(link);
	},
	
	/**
	 * Sets editor's dynamic content from static content
	 */
	loadCurrentContentFromStaticContent: function() {
		// update WYSIWYG editor
		var html = this.validator.invalidate(this.getStaticContentAsDOM());
		html = this.removeUnnecessarySpaces(html);
		
		if(html.blank()) {
			this.rdom.clearRoot();
		} else {
			this.rdom.getRoot().innerHTML = html;
		}
		this.rdom.wrapAllInlineOrTextNodesAs("P", this.rdom.getRoot(), true);
		
		// update source editor
		var source = this.getWysiwygContent(true, true);
		
		this.sourceEditorTextarea.value = source;
		if(xq.Browser.isWebkit) {
			this.sourceEditorTextarea.innerHTML = source;
		}
		
		this._fireOnCurrentContentChanged(this);
	},
	
	/**
	 * Enables all toolbar buttons
	 *
	 * @param {Array} [exceptions] array of string containing classnames to exclude
	 */
	enableToolbarButtons: function(exceptions) {
		if(!this.toolbarContainer) return;
		
		this._execForAllToolbarButtons(exceptions, function(li, exception) {
			li.firstChild.className = !exception ? '' : 'disabled';
		});
		
		// Toolbar image icon disappears without following code:
		if(xq.Browser.isIE6) {
			this.toolbarContainer.style.display = 'none';
			setTimeout(function() {this.toolbarContainer.style.display = 'block';}.bind(this), 0);
		}
	},
	
	/**
	 * Disables all toolbar buttons
	 *
	 * @param {Array} [exceptions] array of string containing classnames to exclude
	 */
	disableToolbarButtons: function(exceptions) {
		this._execForAllToolbarButtons(exceptions, function(li, exception) {
			li.firstChild.className = exception ? '' : 'disabled';
		});
	},
	
	_execForAllToolbarButtons: function(exceptions, exec) {
		if(!this.toolbarContainer) return;
		exceptions = exceptions || [];
		
		var lis = this.toolbarContainer.getElementsByTagName('LI');
		for(var i = 0; i < lis.length; i++) {
			var buttonsClassName = lis[i].className.split(" ").find(function(name) {return name != 'xq_separator'});
			var exception = exceptions.indexOf(buttonsClassName) != -1;
			exec(lis[i], exception);
		}
	},

	_updateToolbarButtonStatus: function(buttonClassName, selected) {
		var button = this.toolbarButtons[buttonClassName];
		if(button) button.firstChild.firstChild.className = selected ? 'selected' : '';
	},
	
	updateAllToolbarButtonsStatus: function(element) {
		if(!this.toolbarContainer) return;
		if(!this.toolbarButtons) {
			var classNames = [
				"emphasis", "strongEmphasis", "underline", "strike", "superscription", "subscription",
				"justifyLeft", "justifyCenter", "justifyRight", "justifyBoth",
				"unorderedList", "orderedList", "code",
				"paragraph", "heading1", "heading2", "heading3", "heading4", "heading5", "heading6"
			];
			
			this.toolbarButtons = {};
			
			for(var i = 0; i < classNames.length; i++) {
				var found = xq.getElementsByClassName(this.toolbarContainer, classNames[i]);
				var button = found && found.length > 0 ? found[0] : null;
				if(button) this.toolbarButtons[classNames[i]] = button;
			}
		}
		
		var buttons = this.toolbarButtons;
		
		var info = this.rdom.collectStructureAndStyle(element);
		
		this._updateToolbarButtonStatus('emphasis', info.em);
		this._updateToolbarButtonStatus('strongEmphasis', info.strong);
		this._updateToolbarButtonStatus('underline', info.underline);
		this._updateToolbarButtonStatus('strike', info.strike);
		this._updateToolbarButtonStatus('superscription', info.superscription);
		this._updateToolbarButtonStatus('subscription', info.subscription);
		
		this._updateToolbarButtonStatus('justifyLeft', info.justification == 'left');
		this._updateToolbarButtonStatus('justifyCenter', info.justification == 'center');
		this._updateToolbarButtonStatus('justifyRight', info.justification == 'right');
		this._updateToolbarButtonStatus('justifyBoth', info.justification == 'justify');
		
		this._updateToolbarButtonStatus('orderedList', info.list == 'OL');
		this._updateToolbarButtonStatus('unorderedList', info.list == 'UL');
		this._updateToolbarButtonStatus('code', info.list == 'CODE');
		
		this._updateToolbarButtonStatus('paragraph', info.block == 'P');
		this._updateToolbarButtonStatus('heading1', info.block == 'H1');
		this._updateToolbarButtonStatus('heading2', info.block == 'H2');
		this._updateToolbarButtonStatus('heading3', info.block == 'H3');
		this._updateToolbarButtonStatus('heading4', info.block == 'H4');
		this._updateToolbarButtonStatus('heading5', info.block == 'H5');
		this._updateToolbarButtonStatus('heading6', info.block == 'H6');
	},
	
	removeUnnecessarySpaces: function(html) {
		var blocks = this.rdom.tree.getBlockTags().join("|");
		var regex = new RegExp("\\s*<(/?)(" + blocks + ")>\\s*", "img");
		return html.replace(regex, '<$1$2>');
	},
	
	/**
	 * Gets editor's dynamic content from current editor(source or WYSIWYG)
	 * 
	 * @return {Object} HTML String
	 */
	getCurrentContent: function(performFullValidation) {
		if(this.getCurrentEditMode() == 'source') {
			return this.getSourceContent(performFullValidation);
		} else {
			return this.getWysiwygContent(performFullValidation);
		}
	},
	
	/**
	 * Gets editor's dynamic content from WYSIWYG editor
	 * 
	 * @return {Object} HTML String
	 */
	getWysiwygContent: function(performFullValidation, dontUseCache) {
		if(dontUseCache || !performFullValidation) return this.validator.validate(this.rdom.getRoot(), performFullValidation);
		
		var lastModified = this.editHistory.getLastModifiedDate();
		if(this._lastModified != lastModified) {
			this._validContentCache = this.validator.validate(this.rdom.getRoot(), performFullValidation);
			this._lastModified = lastModified;
		}
		return this._validContentCache;
	},
	
	/**
	 * Gets editor's dynamic content from source editor
	 * 
	 * @return {Object} HTML String
	 */
	getSourceContent: function(performFullValidation) {
		var raw = this.sourceEditorTextarea[xq.Browser.isWebkit ? 'innerHTML' : 'value'];
		var tempDiv = document.createElement('div');
		tempDiv.innerHTML = this.removeUnnecessarySpaces(raw);

		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(document.body);
		rdom.wrapAllInlineOrTextNodesAs("P", tempDiv, true);
		
		return this.validator.validate(tempDiv, performFullValidation);
	},
	
	/**
	 * Sets editor's original content
	 *
	 * @param {Object} content HTML String
	 */
	setStaticContent: function(content) {
		if(this.contentElement.nodeName == 'TEXTAREA') {
			this.contentElement.value = content;
			if(xq.Browser.isWebkit) {
				this.contentElement.innerHTML = content;
			}
		} else {
			this.contentElement.innerHTML = content;
		}
		this._fireOnStaticContentChanged(this, content);
	},
	
	/**
	 * Gets editor's original content
	 *
	 * @return {Object} HTML String
	 */
	getStaticContent: function() {
		var content;
		if(this.contentElement.nodeName == 'TEXTAREA') {
			content = this.contentElement[xq.Browser.isWebkit ? 'innerHTML' : 'value'];
		} else {
			content = this.contentElement.innerHTML;
		}
		return content;
	},
	
	/**
	 * Gets editor's original content as DOM node
	 *
	 * @return {Object} HTML String
	 */
	getStaticContentAsDOM: function() {
		if(this.contentElement.nodeName == 'TEXTAREA') {
			var div = this.doc.createElement('DIV');
			div.innerHTML = this.contentElement[xq.Browser.isWebkit ? 'innerHTML' : 'value'];
			return div;
		} else {
			return this.contentElement;
		}
	},
	
	/**
	 * Gives focus to editor
	 */
	focus: function() {
		if(this.getCurrentEditMode() == 'wysiwyg') {
			this.rdom.focus();
			window.setTimeout(function() {
				this.updateAllToolbarButtonsStatus(this.rdom.getCurrentElement());
			}.bind(this), 0);
		} else if(this.getCurrentEditMode() == 'source') {
			this.sourceEditorTextarea.focus();
		}
	},
	
	/**
	 * Returns designmode iframe object
	 */
	getFrame: function() {
		return this.editorFrame;
	},
	
	/**
	 * Returns designmode window object
	 */
	getWin: function() {
		return this.editorWin;
	},
	
	/**
	 * Returns designmode document object
	 */
	getDoc: function() {
		return this.editorDoc;
	},
	
	/**
	 * Returns outmost wrapper element
	 */
	getOutmostWrapper: function() {
		return this.outmostWrapper;
	},
	
	/**
	 * Returns designmode body object
	 */
	getBody: function() {
		return this.editorBody;
	},
	
	_createEditorFrame: function() {
		// create outer DIV
		this.outmostWrapper = this.doc.createElement('div');
		this.outmostWrapper.className = "xquared";
		
		this.contentElement.parentNode.insertBefore(this.outmostWrapper, this.contentElement);
		
		// create toolbar is needed
		if(!this.toolbarContainer && this.config.generateDefaultToolbar) {
			this.toolbarContainer = this._generateDefaultToolbar();
			this.outmostWrapper.appendChild(this.toolbarContainer);
		}
		
		// create source editor div
		this.sourceEditorDiv = this.doc.createElement('div');
		this.sourceEditorDiv.className = "editor source_editor"; //TODO: remove editor
		this.sourceEditorDiv.style.display = "none";
		this.outmostWrapper.appendChild(this.sourceEditorDiv);
		
		// create TEXTAREA for source editor
		this.sourceEditorTextarea = this.doc.createElement('textarea');
		this.sourceEditorDiv.appendChild(this.sourceEditorTextarea);
		
		// create WYSIWYG editor div
		this.wysiwygEditorDiv = this.doc.createElement('div');
		this.wysiwygEditorDiv.className = "editor wysiwyg_editor"; //TODO: remove editor
		this.wysiwygEditorDiv.style.display = "none";
		this.outmostWrapper.appendChild(this.wysiwygEditorDiv);
		
		// create designmode iframe for WYSIWYG editor
		this.editorFrame = this.doc.createElement('iframe');
		this.rdom.setAttributes(this.editorFrame, {
			"frameBorder": "0",
			"marginWidth": "0",
			"marginHeight": "0",
			"leftMargin": "0",
			"topMargin": "0",
			"allowTransparency": "true"
		});
		this.wysiwygEditorDiv.appendChild(this.editorFrame);
		
		var doc = this.editorFrame.contentWindow.document;
		if(xq.Browser.isTrident) doc.designMode = 'On';
		
		doc.open();
		doc.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">');
		doc.write('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">');
		doc.write('<head>');
		
		// it is needed to force href of pasted content to be an absolute url
		if(!xq.Browser.isTrident) doc.write('<base href="./" />');
		
		doc.write('<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />');
		doc.write('<title>XQuared</title>');
		if(this.config.changeCursorOnLink) doc.write('<style>.xed a {cursor: pointer !important;}</style>');
		doc.write('</head>');
		doc.write('<body><p>' + this.rdom.makePlaceHolderString() + '</p></body>');
		doc.write('</html>');
		doc.close();
		
		this.editorWin = this.editorFrame.contentWindow;
		this.editorDoc = this.editorWin.document;
		this.editorBody = this.editorDoc.body;
		this.editorBody.className = "xed";
		
		// it is needed to fix IE6 horizontal scrollbar problem
		if(xq.Browser.isIE6) {
			this.editorDoc.documentElement.style.overflowY='auto';
			this.editorDoc.documentElement.style.overflowX='hidden';
		}
		
		// override image path
		if(this.config.generateDefaultToolbar) {
			this._addStyleRules([
				{selector:".xquared div.toolbar", rule:"background-image: url(" + this.config.imagePathForDefaultToobar + "toolbarBg.gif)"},
				{selector:".xquared ul.buttons li", rule:"background-image: url(" + this.config.imagePathForDefaultToobar + "toolbarButtonBg.gif)"},
				{selector:".xquared ul.buttons li.xq_separator", rule:"background-image: url(" + this.config.imagePathForDefaultToobar + "toolbarSeparator.gif)"}
			]);
		}
		
		this.rdom.setWin(this.editorWin);
		this.rdom.setRoot(this.editorBody);
		this.validator = xq.Validator.createInstance(this.doc.location.href, this.config.urlValidationMode, this.config.allowedTags, this.config.allowedAttributes);
		
		// hook onsubmit of form
		if(this.config.automaticallyHookSubmitEvent && this.contentElement.nodeName == 'TEXTAREA' && this.contentElement.form) {
			var original = this.contentElement.form.onsubmit;
			
			this.contentElement.form.onsubmit = function() {
				this.contentElement.value = this.getCurrentContent(true);
				if(original) {
					return original();
				} else {
					return true;
				}
			}.bind(this);
		}
	},
	
	_addStyleRules: function(rules) {
		if(!this.dynamicStyle) {
			if(xq.Browser.isTrident) {
			    this.dynamicStyle = this.doc.createStyleSheet();
			} else {
	    		var style = this.doc.createElement('style');
	    		this.doc.body.appendChild(style);
		    	this.dynamicStyle = xq.$A(this.doc.styleSheets).last();
			}
		}
		
		for(var i = 0; i < rules.length; i++) {
			var rule = rules[i];
			if(xq.Browser.isTrident) {
				this.dynamicStyle.addRule(rules[i].selector, rules[i].rule);
			} else {
		    	this.dynamicStyle.insertRule(rules[i].selector + " {" + rules[i].rule + "}", this.dynamicStyle.cssRules.length);
	    	}
		}
	},
	
	_defaultToolbarClickHandler: function(e) {
		var src = e.target || e.srcElement;
		while(src.nodeName != "A") src = src.parentNode;
		
		if(xq.hasClassName(src.parentNode, 'disabled') || xq.hasClassName(this.toolbarContainer, 'disabled')) {
			xq.stopEvent(e);
			return false;
		}
		
		if(xq.Browser.isTrident) this.focus();
		
		var handler = src.handler;
		var xed = this;
		var stop = (typeof handler == "function") ? handler(this) : eval(handler);
		
		if(stop) {
			xq.stopEvent(e);
			return false;
		} else {
			return true;
		}
	},
	
	_generateDefaultToolbar: function() {
		// outmost container
		var container = this.doc.createElement('div');
		container.className = 'toolbar';
		
		// button container
		var buttons = this.doc.createElement('ul');
		buttons.className = 'buttons';
		container.appendChild(buttons);
		
		// Generate buttons from map and append it to button container
		var map = this.config.defaultToolbarButtonMap;
		for(var i = 0; i < map.length; i++) {
			for(var j = 0; j < map[i].length; j++) {
				var buttonConfig = map[i][j];

				var li = this.doc.createElement('li');
				buttons.appendChild(li);
				li.className = buttonConfig.className;
				
				var span = this.doc.createElement('span');
				li.appendChild(span);
				
				var a = this.doc.createElement('a');
				span.appendChild(a);
				a.href = '#';
				a.title = buttonConfig.title;
				a.handler = buttonConfig.handler;
				
				this._toolbarAnchorsCache.push(a);
				
				xq.observe(a, 'mousedown', xq.cancelHandler);
				xq.observe(a, 'click', this._defaultToolbarClickHandler.bindAsEventListener(this));

				var img = this.doc.createElement('img');
				a.appendChild(img);
				img.src = this.config.imagePathForDefaultToobar + buttonConfig.className + '.gif';

				if(j == 0 && i != 0) li.className += ' xq_separator';
			}
		}
		
		return container;
	},
	
	
	
	/////////////////////////////////////////////
	// Event Management
	
	_registerEventHandlers: function() {
		var events = ['keydown', 'click', 'keyup', 'mouseup', 'contextmenu'];
		
		if(xq.Browser.isTrident && this.config.changeCursorOnLink) events.push('mousemove');
		if(xq.Browser.isMac && xq.Browser.isGecko) events.push('keypress');
		
		for(var i = 0; i < events.length; i++) {
			xq.observe(this.getDoc(), events[i], this._handleEvent.bindAsEventListener(this));
		}
	},
	
	_handleEvent: function(e) {
		this._fireOnBeforeEvent(this, e);
		
		var stop = false;
		
		var modifiedByCorrection = false;
		
		if(e.type == 'mousemove' && this.config.changeCursorOnLink) {
			// Trident only
			var link = !!this.rdom.getParentElementOf(e.srcElement, ["A"]);
			
			var editable = this.editorBody.contentEditable;
			editable = editable == 'inherit' ? false : editable;
			
			if(editable != link && !this.rdom.hasSelection()) this.editorBody.contentEditable = !link;
		} else if(e.type == 'click' && e.button == 0 && this.config.enableLinkClick) {
			var a = this.rdom.getParentElementOf(e.target || e.srcElement, ["A"]);
			if(a) stop = this.handleClick(e, a);
		} else if(e.type == (xq.Browser.isMac && xq.Browser.isGecko ? "keypress" : "keydown")) {
			var undoPerformed = false;
			
			modifiedByCorrection = this.rdom.correctParagraph();
			for(var key in this.config.shortcuts) {
				if(!this.config.shortcuts[key].event.matches(e)) continue;
				
				var handler = this.config.shortcuts[key].handler;
				var xed = this;
				stop = (typeof handler == "function") ? handler(this) : eval(handler);
				
				if(key == "undo") undoPerformed = true;
			}
		} else if(["mouseup", "keyup"].indexOf(e.type) != -1) {
			modifiedByCorrection = this.rdom.correctParagraph();
		} else if(["contextmenu"].indexOf(e.type) != -1) {
			this._handleContextMenu(e);
		}
		
		if(stop) xq.stopEvent(e);
		
		this._fireOnCurrentContentChanged(this);
		this._fireOnAfterEvent(this, e);
		
		if(!undoPerformed && !modifiedByCorrection) this.editHistory.onEvent(e);
		
		return !stop;
	},

	/**
	 * TODO: remove dup with handleAutocompletion
	 */
	handleAutocorrection: function() {
		var block = this.rdom.getCurrentBlockElement();
		
		// TODO: use complete unescape algorithm
		var text = this.rdom.getInnerText(block).replace(/&nbsp;/gi, " ");
		
		var acs = this.config.autocorrections;
		var performed = false;
		
		var stop = false;
		for(var key in acs) {
			var ac = acs[key];
			if(ac.criteria(text)) {
				try {
					this.editHistory.onCommand();
					this.editHistory.disable();
					if(typeof ac.handler == "String") {
						var xed = this;
						var rdom = this.rdom;
						eval(ac.handler);
					} else {
						stop = ac.handler(this, this.rdom, block, text);
					}
					this.editHistory.enable();
				} catch(ignored) {}
				
				block = this.rdom.getCurrentBlockElement();
				text = this.rdom.getInnerText(block);
				
				performed = true;
				if(stop) break;
			}
		}
		
		return stop;
	},
	
	/**
	 * TODO: remove dup with handleAutocorrection
	 */
	handleAutocompletion: function() {
		var acs = this.config.autocompletions;
		if(xq.isEmptyHash(acs)) return;

		if(this.rdom.hasSelection()) {
			var text = this.rdom.getSelectionAsText();
			this.rdom.deleteSelection();
			var wrapper = this.rdom.insertNode(this.rdom.createElement("SPAN"));
			wrapper.innerHTML = text;
			
			var marker = this.rdom.pushMarker();
			
			var filtered = [];
			for(var key in acs) {
				filtered.push([key, acs[key].criteria(text)]);
			}
			filtered = filtered.findAll(function(elem) {
				return elem[1] != -1;
			});

			if(filtered.length == 0) {
				this.rdom.popMarker(true);
				return;
			}

			var minIndex = 0;
			var min = filtered[0][1];
			for(var i = 0; i < filtered.length; i++) {
				if(filtered[i][1] < min) {
					minIndex = i;
					min = filtered[i][1];
				}
			}
			
			var ac = acs[filtered[minIndex][0]];
			
			this.editHistory.disable();
		} else {
			var marker = this.rdom.pushMarker();

			var filtered = [];
			for(var key in acs) {
				filtered.push([key, this.rdom.testSmartWrap(marker, acs[key].criteria).textIndex]);
			}
			filtered = filtered.findAll(function(elem) {
				return elem[1] != -1;
			});
			
			if(filtered.length == 0) {
				this.rdom.popMarker(true);
				return;
			}
			
			var minIndex = 0;
			var min = filtered[0][1];
			for(var i = 0; i < filtered.length; i++) {
				if(filtered[i][1] < min) {
					minIndex = i;
					min = filtered[i][1];
				}
			}
			
			var ac = acs[filtered[minIndex][0]];
			
			this.editHistory.disable();
			
			var wrapper = this.rdom.smartWrap(marker, "SPAN", ac.criteria);
		}
		
		var block = this.rdom.getCurrentBlockElement();
		
		// TODO: use complete unescape algorithm
		var text = this.rdom.getInnerText(wrapper).replace(/&nbsp;/gi, " ");
		
		try {
			// call handler
			if(typeof ac.handler == "String") {
				var xed = this;
				var rdom = this.rdom;
				eval(ac.handler);
			} else {
				ac.handler(this, this.rdom, block, wrapper, text);
			}
		} catch(ignored) {}
		
		try {
			this.rdom.unwrapElement(wrapper);
		} catch(ignored) {}
		
		if(this.rdom.isEmptyBlock(block)) this.rdom.correctEmptyElement(block);
		
		this.editHistory.enable();
		this.editHistory.onCommand();
		
		this.rdom.popMarker(true);
	},

	/**
	 * Handles click event
	 *
	 * @param {Event} e click event
	 * @param {Element} target target element(usually has A tag)
	 */
	handleClick: function(e, target) {
		var href = decodeURI(target.href);
		if(!xq.Browser.isTrident) {
			if(!e.ctrlKey && !e.shiftKey && e.button != 1) {
				window.location.href = href;
				return true;
			}
		} else {
			if(e.shiftKey) {
				window.open(href, "_blank");
			} else {
				window.location.href = href;
			}
			return true;
		}
		
		return false;
	},

	/**
	 * Show link dialog
	 *
	 * TODO: should support modify/unlink
	 */
	handleLink: function() {
		var text = this.rdom.getSelectionAsText() || '';
		var dialog = new xq.controls.FormDialog(
			this,
			xq.ui_templates.basicLinkDialog,
			function(dialog) {
				if(text) {
					dialog.form.text.value = text;
					dialog.form.url.focus();
					dialog.form.url.select();
				}
			},
			function(data) {
				this.focus();
				
				if(xq.Browser.isTrident) {
					var rng = this.rdom.rng();
					rng.moveToBookmark(bm);
					rng.select();
				}
				
				if(!data) return;
				this.handleInsertLink(false, data.url, data.text, data.text);
			}.bind(this)
		);
		
		if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
		
		dialog.show({position: 'centerOfEditor'});
		
		return true;
	},
	
	/**
	 * Inserts link or apply link into selected area
	 * 
	 * @param {boolean} autoSelection if set true and there's no selection, automatically select word to link(if possible)
	 * @param {String} url url
	 * @param {String} title title of link
	 * @param {String} text text of link. If there's a selection(manually or automatically), it will be replaced with this text
	 *
	 * @returns {Element} created element
	 */
	handleInsertLink: function(autoSelection, url, title, text) {
		if(autoSelection && !this.rdom.hasSelection()) {
			var marker = this.rdom.pushMarker();
			var a = this.rdom.smartWrap(marker, "A", function(text) {
				var index = text.lastIndexOf(" ");
				return index == -1 ? index : index + 1;
			});
			a.href = url;
			a.title = title;
			if(text) {
				a.innerHTML = ""
				a.appendChild(this.rdom.createTextNode(text));
			} else if(!a.hasChildNodes()) {
				this.rdom.deleteNode(a);
			}
			this.rdom.popMarker(true);
		} else {
			text = text || (this.rdom.hasSelection() ? this.rdom.getSelectionAsText() : null);
			if(!text) return;
			
			this.rdom.deleteSelection();
			
			var a = this.rdom.createElement('A');
			a.href = url;
			a.title = title;
			a.appendChild(this.rdom.createTextNode(text));
			this.rdom.insertNode(a);
		}
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Called when enter key pressed.
	 *
	 * @param {boolean} skipAutocorrection if set true, skips autocorrection
	 * @param {boolean} forceInsertParagraph if set true, inserts paragraph
	 */
	handleEnter: function(skipAutocorrection, forceInsertParagraph) {
		// If it has selection, perform default action.
		if(this.rdom.hasSelection()) return false;
		
		// Perform autocorrection
		if(!skipAutocorrection && this.handleAutocorrection()) return true;
		
		var atEmptyBlock = this.rdom.isCaretAtEmptyBlock();
		var atStart = atEmptyBlock || this.rdom.isCaretAtBlockStart();
		var atEnd = atEmptyBlock || (!atStart && this.rdom.isCaretAtBlockEnd());
		var atEdge = atEmptyBlock || atStart || atEnd;
		
		if(!atEdge) {
			var block = this.rdom.getCurrentBlockElement();
			var marker = this.rdom.pushMarker();
			
			if(this.rdom.isFirstLiWithNestedList(block) && !forceInsertParagraph) {
				var parent = block.parentNode;
				this.rdom.unwrapElement(block);
				block = parent;
			} else if(block.nodeName != "LI" && this.rdom.tree.isBlockContainer(block)) {
				block = this.rdom.wrapAllInlineOrTextNodesAs("P", block, true).first();
			}
			this.rdom.splitElementUpto(marker, block);
			
			this.rdom.popMarker(true);
		} else if(atEmptyBlock) {
			this._handleEnterAtEmptyBlock();
		} else {
			this._handleEnterAtEdge(atStart, forceInsertParagraph);
		}
		
		return true;
	},
	
	/**
	 * Moves current block upward or downward
	 *
	 * @param {boolean} up moves current block upward
	 */
	handleMoveBlock: function(up) {
		var block = this.rdom.moveBlock(this.rdom.getCurrentBlockElement(), up);
		if(block) {
			this.rdom.selectElement(block, false);
			block.scrollIntoView(false);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		return true;
	},
	
	/**
	 * Called when tab key pressed
	 */
	handleTab: function() {
		var hasSelection = this.rdom.hasSelection();
		var table = this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(), ["TABLE"]);
		
		if(hasSelection) {
			this.handleIndent();
		} else if (table && table.className == "datatable") {
			this.handleMoveToNextCell();
		} else if (this.rdom.isCaretAtBlockStart()) {
			this.handleIndent();
		} else {
			this.handleInsertTab();
		}

		return true;
	},
	
	/**
	 * Called when shift+tab key pressed
	 */
	handleShiftTab: function() {
		var hasSelection = this.rdom.hasSelection();
		var table = this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(), ["TABLE"]);
		
		if(hasSelection) {
			this.handleOutdent();
		} else if (table && table.className == "datatable") {
			this.handleMoveToPreviousCell();
		} else {
			this.handleOutdent();
		}
		
		return true;
	},
	
	/**
	 * Inserts three non-breaking spaces
	 */
	handleInsertTab: function() {
		this.rdom.insertHtml('&nbsp;');
		this.rdom.insertHtml('&nbsp;');
		this.rdom.insertHtml('&nbsp;');
		
		return true;
	},
	
	/**
	 * Called when delete key pressed
	 */
	handleDelete: function() {
		if(this.rdom.hasSelection() || !this.rdom.isCaretAtBlockEnd()) return false;
		return this._handleMerge(true);
	},
	
	/**
	 * Called when backspace key pressed
	 */
	handleBackspace: function() {
		if(this.rdom.hasSelection() || !this.rdom.isCaretAtBlockStart()) return false;
		return this._handleMerge(false);
	},
	
	_handleMerge: function(withNext) {
		var block = this.rdom.getCurrentBlockElement();
		
		// save caret position;
		var marker = this.rdom.pushMarker();
		
		// perform merge
		var merged = this.rdom.mergeElement(block, withNext, withNext);
		if(!merged && !withNext) this.rdom.extractOutElementFromParent(block);
		
		// restore caret position
		this.rdom.popMarker(true);
		if(merged) this.rdom.correctEmptyElement(merged);
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return !!merged;
	},
	
	/**
	 * (in table) Moves caret to the next cell
	 */
	handleMoveToNextCell: function() {
		this._handleMoveToCell("next");
	},

	/**
	 * (in table) Moves caret to the previous cell
	 */
	handleMoveToPreviousCell: function() {
		this._handleMoveToCell("prev");
	},

	/**
	 * (in table) Moves caret to the above cell
	 */
	handleMoveToAboveCell: function() {
		this._handleMoveToCell("above");
	},

	/**
	 * (in table) Moves caret to the below cell
	 */
	handleMoveToBelowCell: function() {
		this._handleMoveToCell("below");
	},

	_handleMoveToCell: function(dir) {
		var block = this.rdom.getCurrentBlockElement();
		var cell = this.rdom.getParentElementOf(block, ["TD", "TH"]);
		var table = this.rdom.getParentElementOf(cell, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var target = null;
		
		if(["next", "prev"].indexOf(dir) != -1) {
			var toNext = dir == "next";
			target = toNext ? rtable.getNextCellOf(cell) : rtable.getPreviousCellOf(cell);
		} else {
			var toBelow = dir == "below";
			target = toBelow ? rtable.getBelowCellOf(cell) : rtable.getAboveCellOf(cell);
		}

		if(!target) {
			var finder = function(node) {return ['TD', 'TH'].indexOf(node.nodeName) == -1 && this.tree.isBlock(node) && !this.tree.hasBlocks(node);}.bind(this.rdom);
			var exitCondition = function(node) {return this.tree.isBlock(node) && !this.tree.isDescendantOf(this.getRoot(), node)}.bind(this.rdom);
			
			target = (toNext || toBelow) ? 
				this.rdom.tree.findForward(cell, finder, exitCondition) :
				this.rdom.tree.findBackward(table, finder, exitCondition);
		}
		
		if(target) this.rdom.placeCaretAtStartOf(target);
	},
	
	/**
	 * Applies STRONG tag
	 */
	handleStrongEmphasis: function() {
		this.rdom.applyStrongEmphasis();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies EM tag
	 */
	handleEmphasis: function() {
		this.rdom.applyEmphasis();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies EM.underline tag
	 */
	handleUnderline: function() {
		this.rdom.applyUnderline();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies SPAN.strike tag
	 */
	handleStrike: function() {
		this.rdom.applyStrike();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Removes all style
	 */
	handleRemoveFormat: function() {
		this.rdom.applyRemoveFormat();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Inserts table
	 *
	 * @param {Number} cols number of columns
	 * @param {Number} rows number of rows
	 * @param {String} headerPosition position of THs. "T" or "L" or "TL". "T" means top, "L" means left.
	 */
	handleTable: function(cols, rows, headerPositions) {
		var cur = this.rdom.getCurrentBlockElement();
		if(this.rdom.getParentElementOf(cur, ["TABLE"])) return true;
		
		var rtable = xq.RichTable.create(this.rdom, cols, rows, headerPositions);
		if(this.rdom.tree.isBlockContainer(cur)) {
			var wrappers = this.rdom.wrapAllInlineOrTextNodesAs("P", cur, true);
			cur = wrappers.last();
		}
		var tableDom = this.rdom.insertNodeAt(rtable.getDom(), cur, "after");
		this.rdom.placeCaretAtStartOf(rtable.getCellAt(0, 0));
		
		if(this.rdom.isEmptyBlock(cur)) this.rdom.deleteNode(cur, true);
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	handleInsertNewRowAt: function(where) {
		var cur = this.rdom.getCurrentBlockElement();
		var tr = this.rdom.getParentElementOf(cur, ["TR"]);
		if(!tr) return true;
		
		var table = this.rdom.getParentElementOf(tr, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var row = rtable.insertNewRowAt(tr, where);
		
		this.rdom.placeCaretAtStartOf(row.cells[0]);
		return true;
	},
	handleInsertNewColumnAt: function(where) {
		var cur = this.rdom.getCurrentBlockElement();
		var td = this.rdom.getParentElementOf(cur, ["TD"], true);
		if(!td) return true;
		
		var table = this.rdom.getParentElementOf(td, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		rtable.insertNewCellAt(td, where);
		
		this.rdom.placeCaretAtStartOf(cur);
		return true;
	},
	
	handleDeleteRow: function() {
		var cur = this.rdom.getCurrentBlockElement();
		var tr = this.rdom.getParentElementOf(cur, ["TR"]);
		if(!tr) return true;

		var table = this.rdom.getParentElementOf(tr, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var blockToMove = rtable.deleteRow(tr);
		
		this.rdom.placeCaretAtStartOf(blockToMove);
		return true;
	},
	
	handleDeleteColumn: function() {
		var cur = this.rdom.getCurrentBlockElement();
		var td = this.rdom.getParentElementOf(cur, ["TD"], true);
		if(!td) return true;

		var table = this.rdom.getParentElementOf(td, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		rtable.deleteCell(td);

		return true;
	},
	
	/**
	 * Performs block indentation
	 */
	handleIndent: function() {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() != blocks.last()) {
				var affected = this.rdom.indentElements(blocks.first(), blocks.last());
				this.rdom.selectBlocksBetween(affected.first(), affected.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		var block = this.rdom.getCurrentBlockElement();
		var affected = this.rdom.indentElement(block);
		
		if(affected) {
			this.rdom.placeCaretAtStartOf(affected);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		
		return true;
	},

	/**
	 * Performs block outdentation
	 */
	handleOutdent: function() {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() != blocks.last()) {
				var affected = this.rdom.outdentElements(blocks.first(), blocks.last());
				this.rdom.selectBlocksBetween(affected.first(), affected.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		var block = this.rdom.getCurrentBlockElement();
		var affected = this.rdom.outdentElement(block);
		
		if(affected) {
			this.rdom.placeCaretAtStartOf(affected);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		
		return true;
	},
	
	/**
	 * Applies list.
	 *
	 * @param {String} type "UL" or "OL" or "CODE". CODE generates OL.code
	 */
	handleList: function(type) {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() != blocks.last()) {
				blocks = this.rdom.applyLists(blocks.first(), blocks.last(), type);
			} else {
				blocks[0] = blocks[1] = this.rdom.applyList(blocks.first(), type);
			}
			this.rdom.selectBlocksBetween(blocks.first(), blocks.last());
		} else {
			var block = this.rdom.applyList(this.rdom.getCurrentBlockElement(), type);
			this.rdom.placeCaretAtStartOf(block);
		}
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies justification
	 *
	 * @param {String} dir "left", "center", "right" or "both"
	 */
	handleJustify: function(dir) {
		var block = this.rdom.getCurrentBlockElement();
		var dir = (dir == "left" || dir == "both") && (block.style.textAlign == "left" || block.style.textAlign == "") ? "both" : dir;
		
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getSelectedBlockElements();
			this.rdom.justifyBlocks(blocks, dir);
			this.rdom.selectBlocksBetween(blocks.first(), blocks.last());
		} else {
			this.rdom.justifyBlock(block, dir);
		}
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Removes current block element
	 */
	handleRemoveBlock: function() {
		var block = this.rdom.getCurrentBlockElement();
		var blockToMove = this.rdom.removeBlock(block);
		this.rdom.placeCaretAtStartOf(blockToMove);
		blockToMove.scrollIntoView(false);
	},
	
	/**
	 * Applies background color
	 *
	 * @param {String} color CSS color string
	 */
	handleBackgroundColor: function(color) {
		if(color) {
			this.rdom.applyBackgroundColor(color);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			var dialog = new xq.controls.FormDialog(
				this,
				xq.ui_templates.basicColorPickerDialog,
				function(dialog) {},
				function(data) {
					this.focus();
					
					if(xq.Browser.isTrident) {
						var rng = this.rdom.rng();
						rng.moveToBookmark(bm);
						rng.select();
					}
					
					if(!data) return;
					
					this.handleBackgroundColor(data.color);
				}.bind(this)
			);
			
			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			
			dialog.show({position: 'centerOfEditor'});
		}
		return true;
	},
	
	/**
	 * Applies foreground color
	 *
	 * @param {String} color CSS color string
	 */
	handleForegroundColor: function(color) {
		if(color) {
			this.rdom.applyForegroundColor(color);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			var dialog = new xq.controls.FormDialog(
				this,
				xq.ui_templates.basicColorPickerDialog,
				function(dialog) {},
				function(data) {
					this.focus();
					
					if(xq.Browser.isTrident) {
						var rng = this.rdom.rng();
						rng.moveToBookmark(bm);
						rng.select();
					}
					
					if(!data) return;
					
					this.handleForegroundColor(data.color);
				}.bind(this)
			);
			
			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			
			dialog.show({position: 'centerOfEditor'});
		}
		return true;
	},

	/**
	 * Applies superscription
	 */	
	handleSuperscription: function() {
		this.rdom.applySuperscription();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Applies subscription
	 */	
	handleSubscription: function() {
		this.rdom.applySubscription();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Change of wrap current block's tag
	 */	
	handleApplyBlock: function(tagName) {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() != blocks.last()) {
				var applied = this.rdom.applyTagIntoElements(tagName, blocks.first(), blocks.last());
				this.rdom.selectBlocksBetween(applied.first(), applied.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		var block = this.rdom.getCurrentBlockElement();
		this.rdom.pushMarker();
		var applied =
			this.rdom.applyTagIntoElement(tagName, block) ||
			block;
		this.rdom.popMarker(true);

		if(this.rdom.isEmptyBlock(applied)) {
			this.rdom.correctEmptyElement(applied);
			this.rdom.placeCaretAtStartOf(applied);
		}
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},

	/**
	 * Inserts seperator (HR)
	 */
	handleSeparator: function() {
		this.rdom.collapseSelection();
		
		var curBlock = this.rdom.getCurrentBlockElement();
		var atStart = this.rdom.isCaretAtBlockStart();
		if(this.rdom.tree.isBlockContainer(curBlock)) curBlock = this.rdom.wrapAllInlineOrTextNodesAs("P", curBlock, true)[0];
		
		this.rdom.insertNodeAt(this.rdom.createElement("HR"), curBlock, atStart ? "before" : "after");
		this.rdom.placeCaretAtStartOf(curBlock);

		// add undo history
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Performs UNDO
	 */
	handleUndo: function() {
		var performed = this.editHistory.undo();
		this._fireOnCurrentContentChanged(this);
		
		var curBlock = this.rdom.getCurrentBlockElement();
		if(!xq.Browser.isTrident && curBlock) {
			curBlock.scrollIntoView(false);
		}
		return true;
	},
	
	/**
	 * Performs REDO
	 */
	handleRedo: function() {
		var performed = this.editHistory.redo();
		this._fireOnCurrentContentChanged(this);
		
		var curBlock = this.rdom.getCurrentBlockElement();
		if(!xq.Browser.isTrident && curBlock) {
			curBlock.scrollIntoView(false);
		}
		return true;
	},
	
	
	
	_handleContextMenu: function(e) {
		if (xq.Browser.isWebkit) {
			if (e.metaKey || xq.isLeftClick(e)) return false;
		} else if (e.shiftKey || e.ctrlKey || e.altKey) {
			return false;
		}
		
		var point = xq.getEventPoint(e);
		var x = point.x;
		var y = point.y;
		
		var pos = xq.getCumulativeOffset(this.getFrame());
		x += pos.left;
		y += pos.top;
		this._contextMenuTargetElement = e.target || e.srcElement;
		
		//TODO: Safari on Windows doesn't work with context key(app key)
		if (!x || !y || xq.Browser.isTrident) {
			var pos = xq.getCumulativeOffset(this._contextMenuTargetElement);
			var posFrame = xq.getCumulativeOffset(this.getFrame());
			x = pos.left + posFrame.left - this.getDoc().documentElement.scrollLeft;
			y = pos.top + posFrame.top - this.getDoc().documentElement.scrollTop;
		}
		
		if (!xq.Browser.isTrident) {
			var doc = this.getDoc();
			var body = this.getBody();
			
			x -= doc.documentElement.scrollLeft;
			y -= doc.documentElement.scrollTop;
			
			if (doc != body) {
				x -= body.scrollLeft;
				y -= body.scrollTop;
			}
		}
		
		for(var cmh in this.config.contextMenuHandlers) {
			var stop = this.config.contextMenuHandlers[cmh].handler(this, this._contextMenuTargetElement, x, y);
			if(stop) {
				xq.stopEvent(e);
				return true;
			}
		}
		
		return false;
	},
	
	showContextMenu: function(menuItems, x, y) {
		if (!menuItems || menuItems.length <= 0) return;
		
		if (!this._contextMenuContainer) {
			this._contextMenuContainer = this.doc.createElement('UL');
			this._contextMenuContainer.className = 'xqContextMenu';
			this._contextMenuContainer.style.display='none';
			
			xq.observe(this.doc, 'click', this._contextMenuClicked.bindAsEventListener(this));
			xq.observe(this.rdom.getDoc(), 'click', this.hideContextMenu.bindAsEventListener(this));
			
			this.body.appendChild(this._contextMenuContainer);
		} else {
			while (this._contextMenuContainer.childNodes.length > 0)
				this._contextMenuContainer.removeChild(this._contextMenuContainer.childNodes[0]);
		}
		
		for (var i=0; i < menuItems.length; i++) {
			menuItems[i]._node = this._addContextMenuItem(menuItems[i]);
		}

		this._contextMenuContainer.style.display='block';
		this._contextMenuContainer.style.left=Math.min(Math.max(this.doc.body.scrollWidth, this.doc.documentElement.clientWidth)-this._contextMenuContainer.offsetWidth, x)+'px';
		this._contextMenuContainer.style.top=Math.min(Math.max(this.doc.body.scrollHeight, this.doc.documentElement.clientHeight)-this._contextMenuContainer.offsetHeight, y)+'px';

		this._contextMenuItems = menuItems;
	},
	
	hideContextMenu: function() {
		if (this._contextMenuContainer)
			this._contextMenuContainer.style.display='none';
	},
	
	_addContextMenuItem: function(item) {
		if (!this._contextMenuContainer) throw "No conext menu container exists";
		
		var node = this.doc.createElement('LI');
		if (item.disabled) node.className += ' disabled'; 
		
		if (item.title == '----') {
			node.innerHTML = '&nbsp;';
			node.className = 'separator';
		} else {
			if(item.handler) {
				node.innerHTML = '<a href="javascript:;" onclick="return false;">'+(item.title.toString().escapeHTML())+'</a>';
			} else {
				node.innerHTML = (item.title.toString().escapeHTML());
			}
		}
		
		if(item.className) node.className = item.className;
		
		this._contextMenuContainer.appendChild(node);
		
		return node;
	},
	
	_contextMenuClicked: function(e) {
		this.hideContextMenu();
		
		if (!this._contextMenuContainer) return;
		
		var node = e.srcElement || e.target;
		while(node && node.nodeName != "LI") {
			node = node.parentNode;
		}
		if (!node || !this.rdom.tree.isDescendantOf(this._contextMenuContainer, node)) return;

		for (var i=0; i < this._contextMenuItems.length; i++) {
			if (this._contextMenuItems[i]._node == node) {
				var handler = this._contextMenuItems[i].handler;
				if (!this._contextMenuItems[i].disabled && handler) {
					var xed = this;
					var element = this._contextMenuTargetElement;
					if(typeof handler == "function") {
						handler(xed, element);
					} else {
						eval(handler);
					}
				}
				break;
			}
		}
	},
	
	/**
	 * Inserts HTML template
	 *
	 * @param {String} html Template string. It should have single root element
	 * @returns {Element} inserted element
	 */
	insertTemplate: function(html) {
		return this.rdom.insertHtml(this._processTemplate(html));
	},
	
	/**
	 * Places given HTML template nearby target.
	 *
	 * @param {String} html Template string. It should have single root element
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Element} Inserted element.
	 */
	insertTemplateAt: function(html, target, where) {
		return this.rdom.insertHtmlAt(this._processTemplate(html), target, where);
	},
	
	_processTemplate: function(html) {
		// apply template processors
		var tps = this.getTemplateProcessors();
		for(var key in tps) {
			var value = tps[key];
			html = value.handler(html);
		}
		
		// remove all whitespace characters between block tags
		return html = this.removeUnnecessarySpaces(html);
	},
	
	
	
	/** @private */
	_handleEnterAtEmptyBlock: function() {
		var block = this.rdom.getCurrentBlockElement();
		if(this.rdom.tree.isTableCell(block) && this.rdom.isFirstBlockOfBody(block)) {
			block = this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(), this.rdom.getRoot(), "start");
		} else {
			block = 
				this.rdom.outdentElement(block) ||
				this.rdom.extractOutElementFromParent(block) ||
				this.rdom.replaceTag("P", block) ||
				this.rdom.insertNewBlockAround(block);
		}
		
		this.rdom.placeCaretAtStartOf(block);
		if(!xq.Browser.isTrident) block.scrollIntoView(false);
	},
	
	/** @private */
	_handleEnterAtEdge: function(atStart, forceInsertParagraph) {
		var block = this.rdom.getCurrentBlockElement();
		var blockToPlaceCaret;
		
		if(atStart && this.rdom.isFirstBlockOfBody(block)) {
			blockToPlaceCaret = this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(), this.rdom.getRoot(), "start");
		} else {
			if(this.rdom.tree.isTableCell(block)) forceInsertParagraph = true;
			var newBlock = this.rdom.insertNewBlockAround(block, atStart, forceInsertParagraph ? "P" : null);
			blockToPlaceCaret = !atStart ? newBlock : newBlock.nextSibling;
		}
		
		this.rdom.placeCaretAtStartOf(blockToPlaceCaret);
		if(!xq.Browser.isTrident) blockToPlaceCaret.scrollIntoView(false);
	}
});
xq.Browser = {
	// By Layout Engines
	isTrident: navigator.appName == "Microsoft Internet Explorer",
	isWebkit: navigator.userAgent.indexOf('AppleWebKit/') > -1,
	isGecko: navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1,
	isKHTML: navigator.userAgent.indexOf('KHTML') != -1,
	isPresto: navigator.appName == "Opera",
	
	// By Platforms
	isMac: navigator.userAgent.indexOf("Macintosh") != -1,
	isUbuntu: navigator.userAgent.indexOf('Ubuntu') != -1,

	// By Browsers
	isIE: navigator.appName == "Microsoft Internet Explorer",
	isIE6: navigator.userAgent.indexOf('MSIE 6') != -1,
	isIE7: navigator.userAgent.indexOf('MSIE 7') != -1
};
xq.Shortcut = xq.Class({
	initialize: function(keymapOrExpression) {
		xq.addToFinalizeQueue(this);

		this.keymap = (typeof keymapOrExpression == "string") ?
			xq.Shortcut.interprete(keymapOrExpression).keymap :
			keymapOrExpression;
	},
	matches: function(e) {
		var which = xq.Browser.isGecko && xq.Browser.isMac ? (e.keyCode + "_" + e.charCode) : e.keyCode;
		
		var keyMatches =
			(this.keymap.which == which) ||
			(this.keymap.which == 32 && which == 25); // 25 is SPACE in Type-3 keyboard.
		
		if(typeof e.metaKey == "undefined") e.metaKey = false;
		
		var modifierMatches = 
			(typeof this.keymap.shiftKey == "undefined" || this.keymap.shiftKey == e.shiftKey) &&
			(typeof this.keymap.altKey == "undefined" || this.keymap.altKey == e.altKey) &&
			(typeof this.keymap.ctrlKey == "undefined" || this.keymap.ctrlKey == e.ctrlKey) &&
			(typeof this.keymap.metaKey == "undefined" || this.keymap.metaKey == e.metaKey)
		
		return modifierMatches && keyMatches;
	}
});

xq.Shortcut.interprete = function(expression) {
	expression = expression.toUpperCase();
	
	var which = xq.Shortcut._interpreteWhich(expression.split("+").pop());
	var ctrlKey = xq.Shortcut._interpreteModifier(expression, "CTRL");
	var altKey = xq.Shortcut._interpreteModifier(expression, "ALT");
	var shiftKey = xq.Shortcut._interpreteModifier(expression, "SHIFT");
	var metaKey = xq.Shortcut._interpreteModifier(expression, "META");
	
	var keymap = {};
	
	keymap.which = which;
	if(typeof ctrlKey != "undefined") keymap.ctrlKey = ctrlKey;
	if(typeof altKey != "undefined") keymap.altKey = altKey;
	if(typeof shiftKey != "undefined") keymap.shiftKey = shiftKey;
	if(typeof metaKey != "undefined") keymap.metaKey = metaKey;
	
	return new xq.Shortcut(keymap);
}

xq.Shortcut._interpreteModifier = function(expression, modifierName) {
	return expression.match("\\(" + modifierName + "\\)") ?
		undefined :
			expression.match(modifierName) ?
			true : false;
}
xq.Shortcut._interpreteWhich = function(keyName) {
	var which = keyName.length == 1 ?
		((xq.Browser.isMac && xq.Browser.isGecko) ? "0_" + keyName.toLowerCase().charCodeAt(0) : keyName.charCodeAt(0)) :
		xq.Shortcut._keyNames[keyName];
	
	if(typeof which == "undefined") throw "Unknown special key name: [" + keyName + "]"
	
	return which;
}
xq.Shortcut._keyNames =
	xq.Browser.isMac && xq.Browser.isGecko ?
	{
		BACKSPACE: "8_0",
		TAB: "9_0",
		RETURN: "13_0",
		ENTER: "13_0",
		ESC: "27_0",
		SPACE: "0_32",
		LEFT: "37_0",
		UP: "38_0",
		RIGHT: "39_0",
		DOWN: "40_0",
		DELETE: "46_0",
		HOME: "36_0",
		END: "35_0",
		PAGEUP: "33_0",
		PAGEDOWN: "34_0",
		COMMA: "0_44",
		HYPHEN: "0_45",
		EQUAL: "0_61",
		PERIOD: "0_46",
		SLASH: "0_47",
		F1: "112_0",
		F2: "113_0",
		F3: "114_0",
		F4: "115_0",
		F5: "116_0",
		F6: "117_0",
		F7: "118_0",
		F8: "119_0"
	}
	:
	{
		BACKSPACE: 8,
		TAB: 9,
		RETURN: 13,
		ENTER: 13,
		ESC: 27,
		SPACE: 32,
		LEFT: 37,
		UP: 38,
		RIGHT: 39,
		DOWN: 40,
		DELETE: 46,
		HOME: 36,
		END: 35,
		PAGEUP: 33,
		PAGEDOWN: 34,
		COMMA: 188,
		HYPHEN: xq.Browser.isTrident ? 189 : 109,
		EQUAL: xq.Browser.isTrident ? 187 : 61,
		PERIOD: 190,
		SLASH: 191,
		F1:112,
		F2:113,
		F3:114,
		F4:115,
		F5:116,
		F6:117,
		F7:118,
		F8:119,
		F9:120,
		F10:121,
		F11:122,
		F12:123
	}
/**
 * Provide various tree operations.
 *
 * TODO: Add specs
 */
xq.DomTree = xq.Class({
	initialize: function() {
		xq.addToFinalizeQueue(this);
		this._blockTags = ["DIV", "DD", "LI", "ADDRESS", "CAPTION", "DT", "H1", "H2", "H3", "H4", "H5", "H6", "HR", "P", "BODY", "BLOCKQUOTE", "PRE", "PARAM", "DL", "OL", "UL", "TABLE", "THEAD", "TBODY", "TR", "TH", "TD"];
		this._blockContainerTags = ["DIV", "DD", "LI", "BODY", "BLOCKQUOTE", "UL", "OL", "DL", "TABLE", "THEAD", "TBODY", "TR", "TH", "TD"];
		this._listContainerTags = ["OL", "UL", "DL"];
		this._tableCellTags = ["TH", "TD"];
		this._blockOnlyContainerTags = ["BODY", "BLOCKQUOTE", "UL", "OL", "DL", "TABLE", "THEAD", "TBODY", "TR"];
		this._atomicTags = ["IMG", "OBJECT", "BR", "HR"];
	},
	
	getBlockTags: function() {
		return this._blockTags;
	},
	
	/**
	 * Find common ancestor(parent) and his immediate children(left and right).
	 *
	 * A --- B -+- C -+- D -+- E
	 *          |
	 *          +- F -+- G
	 *
	 * For example:
	 * > findCommonAncestorAndImmediateChildrenOf("E", "G")
	 *
	 * will return
	 *
	 * > {parent:"B", left:"C", right:"F"}
	 */
	findCommonAncestorAndImmediateChildrenOf: function(left, right) {
		if(left.parentNode == right.parentNode) {
			return {
				left:left,
				right:right,
				parent:left.parentNode
			};
		} else {
			var parentsOfLeft = this.collectParentsOf(left, true);
			var parentsOfRight = this.collectParentsOf(right, true);
			var ca = this.getCommonAncestor(parentsOfLeft, parentsOfRight);
	
			var leftAncestor = parentsOfLeft.find(function(node) {return node.parentNode == ca});
			var rightAncestor = parentsOfRight.find(function(node) {return node.parentNode == ca});
			
			return {
				left:leftAncestor,
				right:rightAncestor,
				parent:ca
			};
		}
	},
	
	/**
	 * Find leaves at edge.
	 *
	 * A --- B -+- C -+- D -+- E
	 *          |
	 *          +- F -+- G
	 *
	 * For example:
	 * > getLeavesAtEdge("A")
	 *
	 * will return
	 *
	 * > ["E", "G"]
	 */
	getLeavesAtEdge: function(element) {
		if(!element.hasChildNodes()) return [null, null];
		
		var findLeft = function(el) {
			for (var i = 0; i < el.childNodes.length; i++) {
				if (el.childNodes[i].nodeType == 1 && this.isBlock(el.childNodes[i])) return findLeft(el.childNodes[i]);
			}
			return el;
		}.bind(this);
		
		var findRight=function(el) {
			for (var i = el.childNodes.length; i--;) {
				if (el.childNodes[i].nodeType == 1 && this.isBlock(el.childNodes[i])) return findRight(el.childNodes[i]);
			}
			return el;
		}.bind(this);
		
		var left = findLeft(element);
		var right = findRight(element);
		
		return [left == element ? null : left, right == element ? null : right];
	},
	
	getCommonAncestor: function(parents1, parents2) {
		for(var i = 0; i < parents1.length; i++) {
			for(var j = 0; j < parents2.length; j++) {
				if(parents1[i] == parents2[j]) return parents1[i];
			}
		}
	},
	
	collectParentsOf: function(node, includeSelf, exitCondition) {
		var parents = [];
		if(includeSelf) parents.push(node);
		
		while((node = node.parentNode) && (node.nodeName != "HTML") && !(typeof exitCondition == "function" && exitCondition(node))) parents.push(node);
		return parents;
	},
	
	isDescendantOf: function(parent, child) {
		if(parent.length > 0) {
			for(var i = 0; i < parent.length; i++) {
				if(this.isDescendantOf(parent[i], child)) return true;
			}
			return false;
		}
		
		if(parent == child) return false;
		
	    while (child = child.parentNode)
	      if (child == parent) return true;
	    return false;
	},
	
	/**
	 * Perform tree walking (foreward)
	 */
	walkForward: function(node) {
		if(node.hasChildNodes()) return node.firstChild;
		if(node.nextSibling) return node.nextSibling;
		
		while(node = node.parentNode) {
			if(node.nextSibling) return node.nextSibling;
		}
		
		return null;
	},
	
	/**
	 * Perform tree walking (backward)
	 */
	walkBackward: function(node) {
		if(node.previousSibling) {
			node = node.previousSibling;
			while(node.hasChildNodes()) {node = node.lastChild;}
			return node;
		}
		
		return node.parentNode;
	},
	
	/**
	 * Perform tree walking (to next siblings)
	 */
	walkNext: function(node) {return node.nextSibling},
	
	/**
	 * Perform tree walking (to next siblings)
	 */
	walkPrev: function(node) {return node.previousSibling},
	
	/**
	 * Returns true if target is followed by start
	 */
	checkTargetForward: function(start, target) {
		return this._check(start, this.walkForward, target);
	},

	/**
	 * Returns true if start is followed by target
	 */
	checkTargetBackward: function(start, target) {
		return this._check(start, this.walkBackward, target);
	},
	
	findForward: function(start, condition, exitCondition) {
		return this._find(start, this.walkForward, condition, exitCondition);
	},
	
	findBackward: function(start, condition, exitCondition) {
		return this._find(start, this.walkBackward, condition, exitCondition);
	},
	
	/** @private */
	_check: function(start, direction, target) {
		if(start == target) return false;
		
		while(start = direction(start)) {
			if(start == target) return true;
		}
		return false;
	},
	
	/** @private */
	_find: function(start, direction, condition, exitCondition) {
		while(start = direction(start)) {
			if(exitCondition && exitCondition(start)) return null;
			if(condition(start)) return start;
		}
		return null;
	},

	/**
	 * Walks Forward through DOM tree from start to end, and collects all nodes that matches with a filter.
	 * If no filter provided, it just collects all nodes.
	 *
	 * @param function filter a filter function
	 */
	collectNodesBetween: function(start, end, filter) {
		if(start == end) return [start, end].findAll(filter || function() {return true});
		
		var nodes = this.collectForward(start, function(node) {return node == end}, filter);
		if(
			start != end &&
			typeof filter == "function" &&
			filter(end)
		) nodes.push(end);
		
		return nodes;
	},

	collectForward: function(start, exitCondition, filter) {
		return this.collect(start, this.walkForward, exitCondition, filter);
	},
	
	collectBackward: function(start, exitCondition, filter) {
		return this.collect(start, this.walkBackward, exitCondition, filter);
	},
	
	collectNext: function(start, exitCondition, filter) {
		return this.collect(start, this.walkNext, exitCondition, filter);
	},
	
	collectPrev: function(start, exitCondition, filter) {
		return this.collect(start, this.walkPrev, exitCondition, filter);
	},
	
	collect: function(start, next, exitCondition, filter) {
		var nodes = [start];

		while(true) {
			start = next(start);
			if(
				(start == null) ||
				(typeof exitCondition == "function" && exitCondition(start))
			) break;
			
			nodes.push(start);
		}

		return (typeof filter == "function") ? nodes.findAll(filter) : nodes;
	},


	hasBlocks: function(element) {
		var nodes = element.childNodes;
		for(var i = 0; i < nodes.length; i++) {
			if(this.isBlock(nodes[i])) return true;
		}
		return false;
	},
	
	hasMixedContents: function(element) {
		if(!this.isBlock(element)) return false;
		if(!this.isBlockContainer(element)) return false;
		
		var hasTextOrInline = false;
		var hasBlock = false;
		for(var i = 0; i < element.childNodes.length; i++) {
			var node = element.childNodes[i];
			if(!hasTextOrInline && this.isTextOrInlineNode(node)) hasTextOrInline = true;
			if(!hasBlock && this.isBlock(node)) hasBlock = true;
			
			if(hasTextOrInline && hasBlock) break;
		}
		if(!hasTextOrInline || !hasBlock) return false;
		
		return true;
	},
	
	isBlockOnlyContainer: function(element) {
		if(!element) return false;
		return this._blockOnlyContainerTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isTableCell: function(element) {
		if(!element) return false;
		return this._tableCellTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isBlockContainer: function(element) {
		if(!element) return false;
		return this._blockContainerTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isHeading: function(element) {
		if(!element) return false;
		return (typeof element == 'string' ? element : element.nodeName).match(/H\d/);
	},
	
	isBlock: function(element) {
		if(!element) return false;
		return this._blockTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isAtomic: function(element) {
		if(!element) return false;
		return this._atomicTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isListContainer: function(element) {
		if(!element) return false;
		return this._listContainerTags.indexOf(typeof element == 'string' ? element : element.nodeName) != -1;
	},
	
	isTextOrInlineNode: function(node) {
		return node && (node.nodeType == 3 || !this.isBlock(node));
	}
});
/**
 * Encapsulates browser incompatibility problem and provides rich set of DOM manipulation API.
 *
 * RichDom provides basic CRUD + Advanced DOM manipulation API, various query methods and caret/selection management API
 */
xq.RichDom = xq.Class({
	/**
	 * Initialize RichDom. Target window and root element should be set after initialization. See setWin and setRoot.
	 *
     * @constructor
	 */
	initialize: function() {
		xq.addToFinalizeQueue(this);

		/**
		 * {xq.DomTree} instance of DomTree
		 */
		this.tree = new xq.DomTree();
		
		this._lastMarkerId = 0;
	},
	
	
	
	/**
	 * @param {Window} win Browser's window object
	 */
	setWin: function(win) {
		if(!win) throw "[win] is null";
		this.win = win;
	},
	
	/**
	 * @param {Element} root Root element
	 */
	setRoot: function(root) {
		if(!root) throw "[root] is null";
		if(this.win && (root.ownerDocument != this.win.document)) throw "root.ownerDocument != this.win.document";
		this.root = root;
		this.doc = this.root.ownerDocument;
	},
	
	/**
	 * @returns Browser's window object.
	 */
	getWin: function() {return this.win},
	
	/**
	 * @returns Document object of root element.
	 */
	getDoc: function() {return this.doc},
	
	/**
	 * @returns Root element.
	 */
	getRoot: function() {return this.root},
	
	
	
	/////////////////////////////////////////////
	// CRUDs
	
	clearRoot: function() {
		this.root.innerHTML = "";
		this.root.appendChild(this.makeEmptyParagraph());
	},
	
	/**
	 * Removes place holders and empty text nodes of given element.
	 *
	 * @param {Element} element target element
	 */
	removePlaceHoldersAndEmptyNodes: function(element) {
		var children = element.childNodes;
		if(!children) return;
		var stopAt = this.getBottommostLastChild(element);
		if(!stopAt) return;
		stopAt = this.tree.walkForward(stopAt);
		
		while(true) {
			if(!element || element == stopAt) break;
			
			if(
				this.isPlaceHolder(element) ||
				(element.nodeType == 3 && element.nodeValue == "") ||
				(!this.getNextSibling(element) && element.nodeType == 3 && element.nodeValue.strip() == "")
			) {
				var deleteTarget = element;
				element = this.tree.walkForward(element);
				
				this.deleteNode(deleteTarget);
			} else {
				element = this.tree.walkForward(element);
			}
		}
	},
	
	/**
	 * Sets multiple attributes into element at once
	 *
	 * @param {Element} element target element
	 * @param {Object} map key-value pairs
	 */
	setAttributes: function(element, map) {
		for(var key in map) element.setAttribute(key, map[key]);
	},

	/**
	 * Creates textnode by given node value.
	 *
	 * @param {String} value value of textnode
	 * @returns {Node} Created text node
	 */	
	createTextNode: function(value) {return this.doc.createTextNode(value);},

	/**
	 * Creates empty element by given tag name.
	 *
	 * @param {String} tagName name of tag
	 * @returns {Element} Created element
	 */	
	createElement: function(tagName) {return this.doc.createElement(tagName);},

	/**
	 * Creates element from HTML string
	 * 
	 * @param {String} html HTML string
	 * @returns {Element} Created element
	 */
	createElementFromHtml: function(html) {
		var node = this.createElement("div");
		node.innerHTML = html;
		if(node.childNodes.length != 1) {
			throw "Illegal HTML fragment";
		}
		return this.getFirstChild(node);
	},
	
	/**
	 * Deletes node from DOM tree.
	 *
	 * @param {Node} node Target node which should be deleted
	 * @param {boolean} deleteEmptyParentsRecursively Recursively delete empty parent elements
	 * @param {boolean} correctEmptyParent Call #correctEmptyElement on empty parent element after deletion
	 */	
	deleteNode: function(node, deleteEmptyParentsRecursively, correctEmptyParent) {
		if(!node || !node.parentNode) return;
		
		var parent = node.parentNode;
		parent.removeChild(node);
		
		if(deleteEmptyParentsRecursively) {
			while(!parent.hasChildNodes()) {
				node = parent;
				parent = node.parentNode;
				if(!parent || this.getRoot() == node) break;
				parent.removeChild(node);
			}
		}
		
		if(correctEmptyParent && this.isEmptyBlock(parent)) {
			parent.innerHTML = "";
			this.correctEmptyElement(parent);
		}
	},

	/**
	 * Inserts given node into current caret position
	 *
	 * @param {Node} node Target node
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertNode: function(node) {throw "Not implemented"},

	/**
	 * Inserts given html into current caret position
	 *
	 * @param {String} html HTML string
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertHtml: function(html) {
		return this.insertNode(this.createElementFromHtml(html));
	},
	
	/**
	 * Creates textnode from given text and inserts it into current caret position
	 *
	 * @param {String} text Value of textnode
	 * @returns {Node} Inserted node
	 */
	insertText: function(text) {
		this.insertNode(this.createTextNode(text));
	},
	
	/**
	 * Places given node nearby target.
	 *
	 * @param {Node} node Node to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 * @param {boolean} performValidation Validate node if needed. For example when P placed into UL, its tag name automatically replaced with LI
	 *
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertNodeAt: function(node, target, where, performValidation) {
		if(
			["HTML", "HEAD"].indexOf(target.nodeName) != -1 ||
			"BODY" == target.nodeName && ["before", "after"].indexOf(where) != -1
		) throw "Illegal argument. Cannot move node[" + node.nodeName + "] to '" + where + "' of target[" + target.nodeName + "]"
		
		var object;
		var message;
		var secondParam;
		
		switch(where.toLowerCase()) {
			case "before":
				object = target.parentNode;
				message = 'insertBefore';
				secondParam = target;
				break
			case "start":
				if(target.firstChild) {
					object = target;
					message = 'insertBefore';
					secondParam = target.firstChild;
				} else {
					object = target;
					message = 'appendChild';
				}
				break
			case "end":
				object = target;
				message = 'appendChild';
				break
			case "after":
				if(target.nextSibling) {
					object = target.parentNode;
					message = 'insertBefore';
					secondParam = target.nextSibling;
				} else {
					object = target.parentNode;
					message = 'appendChild';
				}
				break
		}

		if(performValidation && this.tree.isListContainer(object) && node.nodeName != "LI") {
			var li = this.createElement("LI");
			li.appendChild(node);
			node = li;
			object[message](node, secondParam);		
		} else if(performValidation && !this.tree.isListContainer(object) && node.nodeName == "LI") {
			this.wrapAllInlineOrTextNodesAs("P", node, true);
			var div = this.createElement("DIV");
			this.moveChildNodes(node, div);
			this.deleteNode(node);
			object[message](div, secondParam);
			node = this.unwrapElement(div, true);
		} else {
			object[message](node, secondParam);
		}
		
		return node;
	},

	/**
	 * Creates textnode from given text and places given node nearby target.
	 *
	 * @param {String} text Text to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Node} Inserted node.
	 */
	insertTextAt: function(text, target, where) {
		return this.insertNodeAt(this.createTextNode(text), target, where);
	},

	/**
	 * Creates element from given HTML string and places given it nearby target.
	 *
	 * @param {String} html HTML to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Node} Inserted node.
	 */
	insertHtmlAt: function(html, target, where) {
		return this.insertNodeAt(this.createElementFromHtml(html), target, where);
	},

	/**
	 * Replaces element's tag by removing current element and creating new element by given tag name.
	 *
	 * @param {String} tag New tag name
	 * @param {Element} element Target element
	 *
	 * @returns {Element} Replaced element
	 */	
	replaceTag: function(tag, element) {
		if(element.nodeName == tag) return null;
		if(this.tree.isTableCell(element)) return null;
		
		var newElement = this.createElement(tag);
		this.moveChildNodes(element, newElement);
		this.copyAttributes(element, newElement, true);
		element.parentNode.replaceChild(newElement, element);
		
		if(!newElement.hasChildNodes()) this.correctEmptyElement(newElement);
		
		return newElement;
	},

	/**
	 * Unwraps unnecessary paragraph.
	 *
	 * Unnecessary paragraph is P which is the only child of given container element.
	 * For example, P which is contained by LI and is the only child is the unnecessary paragraph.
	 * But if given container element is a block-only-container(BLOCKQUOTE, BODY), this method does nothing.
	 *
	 * @param {Element} element Container element
	 * @returns {boolean} True if unwrap performed.
	 */
	unwrapUnnecessaryParagraph: function(element) {
		if(!element) return false;
		
		if(!this.tree.isBlockOnlyContainer(element) && element.childNodes.length == 1 && element.firstChild.nodeName == "P" && !this.hasImportantAttributes(element.firstChild)) {
			var p = element.firstChild;
			this.moveChildNodes(p, element);
			this.deleteNode(p);
			return true;
		}
		return false;
	},
	
	/**
	 * Unwraps element by extracting all children out and removing the element.
	 *
	 * @param {Element} element Target element
	 * @param {boolean} wrapInlineAndTextNodes Wrap all inline and text nodes with P before unwrap
	 * @returns {Node} First child of unwrapped element
	 */
	unwrapElement: function(element, wrapInlineAndTextNodes) {
		if(wrapInlineAndTextNodes) this.wrapAllInlineOrTextNodesAs("P", element);
		
		var nodeToReturn = element.firstChild;
		
		while(element.firstChild) this.insertNodeAt(element.firstChild, element, "before");
		this.deleteNode(element);
		
		return nodeToReturn;
	},
	
	/**
	 * Wraps element by given tag
	 *
	 * @param {String} tag tag name
	 * @param {Element} element target element to wrap
	 * @returns {Element} wrapper
	 */
	wrapElement: function(tag, element) {
		var wrapper = this.insertNodeAt(this.createElement(tag), element, "before");
		wrapper.appendChild(element);
		return wrapper;
	},
	
	/**
	 * Tests #smartWrap with given criteria but doesn't change anything
	 */
	testSmartWrap: function(endElement, criteria) {
		return this.smartWrap(endElement, null, criteria, true);
	},
	
	/**
	 * Create inline element with given tag name and wraps nodes nearby endElement by given criteria
	 *
	 * @param {Element} endElement Boundary(end point, exclusive) of wrapper.
	 * @param {String} tag Tag name of wrapper.
	 * @param {Object} function which returns text index of start boundary.
	 * @param {boolean} testOnly just test boundary and do not perform actual wrapping.
	 *
	 * @returns {Element} wrapper
	 */
	smartWrap: function(endElement, tag, criteria, testOnly) {
		var block = this.getParentBlockElementOf(endElement);

		tag = tag || "SPAN";
		criteria = criteria || function(text) {return -1};
		
		// check for empty wrapper
		if(!testOnly && (!endElement.previousSibling || this.isEmptyBlock(block))) {
			var wrapper = this.insertNodeAt(this.createElement(tag), endElement, "before");
			return wrapper;
		}
		
		// collect all textnodes
		var textNodes = this.tree.collectForward(block, function(node) {return node == endElement}, function(node) {return node.nodeType == 3});
		
		// find textnode and break-point
		var nodeIndex = 0;
		var nodeValues = [];
		for(var i = 0; i < textNodes.length; i++) {
			nodeValues.push(textNodes[i].nodeValue);
		}
		var textToWrap = nodeValues.join("");
		var textIndex = criteria(textToWrap)
		var breakPoint = textIndex;
		
		if(breakPoint == -1) {
			breakPoint = 0;
		} else {
			textToWrap = textToWrap.substring(breakPoint);
		}
		
		for(var i = 0; i < textNodes.length; i++) {
			if(breakPoint > nodeValues[i].length) {
				breakPoint -= nodeValues[i].length;
			} else {
				nodeIndex = i;
				break;
			}
		}
		
		if(testOnly) return {text:textToWrap, textIndex:textIndex, nodeIndex:nodeIndex, breakPoint:breakPoint};
		
		// break textnode if necessary 
		if(breakPoint != 0) {
			var splitted = textNodes[nodeIndex].splitText(breakPoint);
			nodeIndex++;
			textNodes.splice(nodeIndex, 0, splitted);
		}
		var startElement = textNodes[nodeIndex] || block.firstChild;
		
		// split inline elements up to parent block if necessary
		var family = this.tree.findCommonAncestorAndImmediateChildrenOf(startElement, endElement);
		var ca = family.parent;
		if(ca) {
			if(startElement.parentNode != ca) startElement = this.splitElementUpto(startElement, ca, true);
			if(endElement.parentNode != ca) endElement = this.splitElementUpto(endElement, ca, true);
			
			var prevStart = startElement.previousSibling;
			var nextEnd = endElement.nextSibling;
			
			// remove empty inline elements
			if(prevStart && prevStart.nodeType == 1 && this.isEmptyBlock(prevStart)) this.deleteNode(prevStart);
			if(nextEnd && nextEnd.nodeType == 1 && this.isEmptyBlock(nextEnd)) this.deleteNode(nextEnd);
			
			// wrap
			var wrapper = this.insertNodeAt(this.createElement(tag), startElement, "before");
			while(wrapper.nextSibling != endElement) wrapper.appendChild(wrapper.nextSibling);
			return wrapper;
		} else {
			// wrap
			var wrapper = this.insertNodeAt(this.createElement(tag), endElement, "before");
			return wrapper;
		}
	},
	
	/**
	 * Wraps all adjust inline elements and text nodes into block element.
	 *
	 * TODO: empty element should return empty array when it is not forced and (at least) single item array when forced
	 *
	 * @param {String} tag Tag name of wrapper
	 * @param {Element} element Target element
	 * @param {boolean} force Force wrapping. If it is set to false, this method do not makes unnecessary wrapper.
	 *
	 * @returns {Array} Array of wrappers. If nothing performed it returns empty array
	 */
	wrapAllInlineOrTextNodesAs: function(tag, element, force) {
		var wrappers = [];
		
		if(!force && !this.tree.hasMixedContents(element)) return wrappers;
		
		var node = element.firstChild;
		while(node) {
			if(this.tree.isTextOrInlineNode(node)) {
				var wrapper = this.wrapInlineOrTextNodesAs(tag, node);
				wrappers.push(wrapper);
				node = wrapper.nextSibling;
			} else {
				node = node.nextSibling;
			}
		}

		return wrappers;
	},

	/**
	 * Wraps node and its adjust next siblings into an element
	 */
	wrapInlineOrTextNodesAs: function(tag, node) {
		var wrapper = this.createElement(tag);
		var from = node;

		from.parentNode.replaceChild(wrapper, from);
		wrapper.appendChild(from);

		// move nodes into wrapper
		while(wrapper.nextSibling && this.tree.isTextOrInlineNode(wrapper.nextSibling)) wrapper.appendChild(wrapper.nextSibling);

		return wrapper;
	},
	
	/**
	 * Turns block element into list item
	 *
	 * @param {Element} element Target element
	 * @param {String} type One of "UL", "OL", "CODE". "CODE" is same with "OL" but it gives "OL" a class name "code"
	 *
	 * @return {Element} LI element
	 */
	turnElementIntoListItem: function(element, type) {
		type = type.toUpperCase();
		
		var container = this.createElement(type == "UL" ? "UL" : "OL");
		if(type == "CODE") container.className = "code";
		
		if(this.tree.isTableCell(element)) {
			var p = this.wrapAllInlineOrTextNodesAs("P", element, true)[0];
			container = this.insertNodeAt(container, element, "start");
			var li = this.insertNodeAt(this.createElement("LI"), container, "start");
			li.appendChild(p);
		} else {
			container = this.insertNodeAt(container, element, "after");
			var li = this.insertNodeAt(this.createElement("LI"), container, "start");
			li.appendChild(element);
		}
		
		this.unwrapUnnecessaryParagraph(li);
		this.mergeAdjustLists(container);
		
		return li;
	},
	
	/**
	 * Extracts given element out from its parent element.
	 * 
	 * @param {Element} element Target element
	 */
	extractOutElementFromParent: function(element) {
		if(element == this.root || this.root == element.parentNode || !element.offsetParent) return null;
		
		if(element.nodeName == "LI") {
			this.wrapAllInlineOrTextNodesAs("P", element, true);
			element = element.firstChild;
		}

		var container = element.parentNode;
		var nodeToReturn = null;
		
		if(container.nodeName == "LI" && container.parentNode.parentNode.nodeName == "LI") {
			// nested list item
			if(element.previousSibling) {
				this.splitContainerOf(element, true);
				this.correctEmptyElement(element);
			}
			
			this.outdentListItem(element);
			nodeToReturn = element;
		} else if(container.nodeName == "LI") {
			// not-nested list item
			
			if(this.tree.isListContainer(element.nextSibling)) {
				// 1. split listContainer
				var listContainer = container.parentNode;
				this.splitContainerOf(container, true);
				this.correctEmptyElement(element);
				
				// 2. extract out LI's children
				nodeToReturn = container.firstChild;
				while(container.firstChild) {
					this.insertNodeAt(container.firstChild, listContainer, "before");
				}
				
				// 3. remove listContainer and merge adjust lists
				var prevContainer = listContainer.previousSibling;
				this.deleteNode(listContainer);
				if(prevContainer && this.tree.isListContainer(prevContainer)) this.mergeAdjustLists(prevContainer);
			} else {
				// 1. split LI
				this.splitContainerOf(element, true);
				this.correctEmptyElement(element);
				
				// 2. split list container
				var listContainer = this.splitContainerOf(container);
				
				// 3. extract out
				this.insertNodeAt(element, listContainer.parentNode, "before");
				this.deleteNode(listContainer.parentNode);
				
				nodeToReturn = element;
			}
		} else if(this.tree.isTableCell(container) || this.tree.isTableCell(element)) {
			// do nothing
		} else {
			// normal block
			this.splitContainerOf(element, true);
			this.correctEmptyElement(element);
			nodeToReturn = this.insertNodeAt(element, container, "before");
			
			this.deleteNode(container);
		}
		
		return nodeToReturn;
	},
	
	/**
	 * Insert new block above or below given element.
	 *
	 * @param {Element} block Target block
	 * @param {boolean} before Insert new block above(before) target block
	 * @param {String} forceTag New block's tag name. If omitted, target block's tag name will be used.
	 *
	 * @returns {Element} Inserted block
	 */
	insertNewBlockAround: function(block, before, forceTag) {
		var isListItem = block.nodeName == "LI" || block.parentNode.nodeName == "LI";
		
		this.removeTrailingWhitespace(block);
		if(this.isFirstLiWithNestedList(block) && !forceTag && before) {
			var li = this.getParentElementOf(block, ["LI"]);
			var newBlock = this._insertNewBlockAround(li, before);
			return newBlock;
		} else if(isListItem && !forceTag) {
			var li = this.getParentElementOf(block, ["LI"]);
			var newBlock = this._insertNewBlockAround(block, before);
			if(li != block) newBlock = this.splitContainerOf(newBlock, false, "prev");
			return newBlock;
		} else if(this.tree.isBlockContainer(block)) {
			this.wrapAllInlineOrTextNodesAs("P", block, true);
			return this._insertNewBlockAround(block.firstChild, before, forceTag);
		} else {
			return this._insertNewBlockAround(block, before, this.tree.isHeading(block) ? "P" : forceTag);
		}
	},
	
	/**
	 * @private
	 *
	 * TODO: Rename
	 */
	_insertNewBlockAround: function(element, before, tagName) {
		var newElement = this.createElement(tagName || element.nodeName);
		this.copyAttributes(element, newElement, false);
		this.correctEmptyElement(newElement);
		newElement = this.insertNodeAt(newElement, element, before ? "before" : "after");
		return newElement;
	},
	
	/**
	 * Wrap or replace element with given tag name.
	 *
	 * @param {String} tag Tag name
	 * @param {Element} element Target element
	 *
	 * @return {Element} wrapper element or replaced element.
	 */
	applyTagIntoElement: function(tag, element) {
		if(this.tree.isBlockOnlyContainer(tag)) {
			return this.wrapBlock(tag, element);
		} else if(this.tree.isBlockContainer(element)) {
			var wrapper = this.createElement(tag);
			this.moveChildNodes(element, wrapper);
			return this.insertNodeAt(wrapper, element, "start");
		} else {
			if(this.tree.isBlockContainer(tag) && this.hasImportantAttributes(element)) {
				return this.wrapBlock(tag, element);
			} else {
				return this.replaceTag(tag, element);
			}
		}
		
		throw "IllegalArgumentException - [" + tag + ", " + element + "]";
	},
	
	/**
	 * Wrap or replace elements with given tag name.
	 *
	 * @param {String} tag Tag name
	 * @param {Element} from Start boundary (inclusive)
	 * @param {Element} to End boundary (inclusive)
	 *
	 * @returns {Array} Array of wrappers or replaced elements
	 */
	applyTagIntoElements: function(tagName, from, to) {
		var applied = [];
		
		if(this.tree.isBlockContainer(tagName)) {
			var family = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
			var node = family.left;
			var wrapper = this.insertNodeAt(this.createElement(tagName), node, "before");
			
			var coveringWholeList =
				family.parent.nodeName == "LI" &&
				family.parent.parentNode.childNodes.length == 1 &&
				!family.left.previousSilbing &&
				!family.right.nextSibling;
				
			if(coveringWholeList) {
				var ul = node.parentNode.parentNode;
				this.insertNodeAt(wrapper, ul, "before");
				wrapper.appendChild(ul);
			} else {
				while(node != family.right) {
					next = node.nextSibling;
					wrapper.appendChild(node);
					node = next;
				}
				wrapper.appendChild(family.right);
			}
			applied.push(wrapper);
		} else {
			// is normal tagName
			var elements = this.getBlockElementsBetween(from, to);
			for(var i = 0; i < elements.length; i++) {
				if(this.tree.isBlockContainer(elements[i])) {
					var wrappers = this.wrapAllInlineOrTextNodesAs(tagName, elements[i], true);
					for(var j = 0; j < wrappers.length; j++) {
						applied.push(wrappers[j]);
					}
				} else {
					applied.push(this.replaceTag(tagName, elements[i]));
				}
			}
		}
		return applied;
	},
	
	/**
	 * Moves block up or down
	 *
	 * @param {Element} block Target block
	 * @param {boolean} up Move up if true
	 * 
	 * @returns {Element} Moved block. It could be different with given block.
	 */
	moveBlock: function(block, up) {
		// if block is table cell or contained by table cell, select its row as mover
		block = this.getParentElementOf(block, ["TR"]) || block;
		
		// if block is only child, select its parent as mover
		while(block.nodeName != "TR" && block.parentNode != this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		// find target and where
		var target, where;
		if (up) {
			target = block.previousSibling;
			
			if(target) {
				var singleNodeLi = target.nodeName == 'LI' && ((target.childNodes.length == 1 && this.tree.isBlock(target.firstChild)) || !this.tree.hasBlocks(target));
				var table = ['TABLE', 'TR'].indexOf(target.nodeName) != -1;

				where = this.tree.isBlockContainer(target) && !singleNodeLi && !table ? "end" : "before";
			} else if(block.parentNode != this.getRoot()) {
				target = block.parentNode;
				where = "before";
			}
		} else {
			target = block.nextSibling;
			
			if(target) {
				var singleNodeLi = target.nodeName == 'LI' && ((target.childNodes.length == 1 && this.tree.isBlock(target.firstChild)) || !this.tree.hasBlocks(target));
				var table = ['TABLE', 'TR'].indexOf(target.nodeName) != -1;
				
				where = this.tree.isBlockContainer(target) && !singleNodeLi && !table ? "start" : "after";
			} else if(block.parentNode != this.getRoot()) {
				target = block.parentNode;
				where = "after";
			}
		}
		
		
		// no way to go?
		if(!target) return null;
		if(["TBODY", "THEAD"].indexOf(target.nodeName) != -1) return null;
		
		// normalize
		this.wrapAllInlineOrTextNodesAs("P", target, true);
		
		// make placeholder if needed
		if(this.isFirstLiWithNestedList(block)) {
			this.insertNewBlockAround(block, false, "P");
		}
		
		// perform move
		var parent = block.parentNode;
		var moved = this.insertNodeAt(block, target, where, true);
		
		// cleanup
		if(!parent.hasChildNodes()) this.deleteNode(parent, true);
		this.unwrapUnnecessaryParagraph(moved);
		this.unwrapUnnecessaryParagraph(target);

		// remove placeholder
		if(up) {
			if(moved.previousSibling && this.isEmptyBlock(moved.previousSibling) && !moved.previousSibling.previousSibling && moved.parentNode.nodeName == "LI" && this.tree.isListContainer(moved.nextSibling)) {
				this.deleteNode(moved.previousSibling);
			}
		} else {
			if(moved.nextSibling && this.isEmptyBlock(moved.nextSibling) && !moved.previousSibling && moved.parentNode.nodeName == "LI" && this.tree.isListContainer(moved.nextSibling.nextSibling)) {
				this.deleteNode(moved.nextSibling);
			}
		}
		
		this.correctEmptyElement(moved);
		
		return moved;
	},
	
	/**
	 * Remove given block
	 *
	 * @param {Element} block Target block
	 * @returns {Element} Nearest block of remove element
	 */
	removeBlock: function(block) {
		var blockToMove;

		// if block is only child, select its parent as mover
		while(block.parentNode != this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		var finder = function(node) {return this.tree.isBlock(node) && !this.tree.isAtomic(node) && !this.tree.isDescendantOf(block, node) && !this.tree.hasBlocks(node);}.bind(this);
		var exitCondition = function(node) {return this.tree.isBlock(node) && !this.tree.isDescendantOf(this.getRoot(), node)}.bind(this);
		
		if(this.isFirstLiWithNestedList(block)) {
			blockToMove = this.outdentListItem(block.nextSibling.firstChild);
			this.deleteNode(blockToMove.previousSibling, true);
		} else if(this.tree.isTableCell(block)) {
			var rtable = new xq.RichTable(this, this.getParentElementOf(block, ["TABLE"]));
			blockToMove = rtable.getBelowCellOf(block);
			
			// should not delete row when there's thead and the row is the only child of tbody
			if(
				block.parentNode.parentNode.nodeName == "TBODY" &&
				rtable.hasHeadingAtTop() &&
				rtable.getDom().tBodies[0].rows.length == 1) return blockToMove;
			
			blockToMove = blockToMove ||
				this.tree.findForward(block, finder, exitCondition) ||
				this.tree.findBackward(block, finder, exitCondition);
			
			this.deleteNode(block.parentNode, true);
		} else {
			blockToMove = blockToMove ||
				this.tree.findForward(block, finder, exitCondition) ||
				this.tree.findBackward(block, finder, exitCondition);
			
			if(!blockToMove) blockToMove = this.insertNodeAt(this.makeEmptyParagraph(), block, "after");
			
			this.deleteNode(block, true);
		}
		if(!this.getRoot().hasChildNodes()) {
			blockToMove = this.createElement("P");
			this.getRoot().appendChild(blockToMove);
			this.correctEmptyElement(blockToMove);
		}
		
		return blockToMove;
	},
	
	/**
	 * Removes trailing whitespaces of given block
	 *
	 * @param {Element} block Target block
	 */
	removeTrailingWhitespace: function(block) {throw "Not implemented"},
	
	/**
	 * Extract given list item out and change its container's tag
	 *
	 * @param {Element} element LI or P which is a child of LI
	 * @param {String} type "OL", "UL", or "CODE"
	 *
	 * @returns {Element} changed element
	 */
	changeListTypeTo: function(element, type) {
		type = type.toUpperCase();
		
		var li = this.getParentElementOf(element, ["LI"]);
		if(!li) throw "IllegalArgumentException";
		
		var container = li.parentNode;

		this.splitContainerOf(li);
		
		var newContainer = this.insertNodeAt(this.createElement(type == "UL" ? "UL" : "OL"), container, "before");
		if(type == "CODE") newContainer.className = "code";
		
		this.insertNodeAt(li, newContainer, "start");
		this.deleteNode(container);
		
		this.mergeAdjustLists(newContainer);
		
		return element;
	},
	
	/**
	 * Split container of element into (maxium) three pieces.
	 */
	splitContainerOf: function(element, preserveElementItself, dir) {
		if([element, element.parentNode].indexOf(this.getRoot()) != -1) return element;

		var container = element.parentNode;
		if(element.previousSibling && (!dir || dir.toLowerCase() == "prev")) {
			var prev = this.createElement(container.nodeName);
			this.copyAttributes(container, prev);
			while(container.firstChild != element) {
				prev.appendChild(container.firstChild);
			}
			this.insertNodeAt(prev, container, "before");
			this.unwrapUnnecessaryParagraph(prev);
		}
		
		if(element.nextSibling && (!dir || dir.toLowerCase() == "next")) {
			var next = this.createElement(container.nodeName);
			this.copyAttributes(container, next);
			while(container.lastChild != element) {
				this.insertNodeAt(container.lastChild, next, "start");
			}
			this.insertNodeAt(next, container, "after");
			this.unwrapUnnecessaryParagraph(next);
		}
		
		if(!preserveElementItself) element = this.unwrapUnnecessaryParagraph(container) ? container : element;
		return element;
	},

	/**
	 * TODO: Add specs
	 */
	splitParentElement: function(seperator) {
		var parent = seperator.parentNode;
		if(["HTML", "HEAD", "BODY"].indexOf(parent.nodeName) != -1) throw "Illegal argument. Cannot seperate element[" + parent.nodeName + "]";

		var previousSibling = seperator.previousSibling;
		var nextSibling = seperator.nextSibling;
		
		var newElement = this.insertNodeAt(this.createElement(parent.nodeName), parent, "after");
		
		var next;
		while(next = seperator.nextSibling) newElement.appendChild(next);
		
		this.insertNodeAt(seperator, newElement, "start");
		this.copyAttributes(parent, newElement);
		
		return newElement;
	},
	
	/**
	 * TODO: Add specs
	 */
	splitElementUpto: function(seperator, element, excludeElement) {
		while(seperator.previousSibling != element) {
			if(excludeElement && seperator.parentNode == element) break;
			seperator = this.splitParentElement(seperator);
		}
		return seperator;
	},
	
	/**
	 * Merges two adjust elements
	 *
	 * @param {Element} element base element
	 * @param {boolean} withNext merge base element with next sibling
	 * @param {boolean} skip skip merge steps
	 */
	mergeElement: function(element, withNext, skip) {
		this.wrapAllInlineOrTextNodesAs("P", element.parentNode, true);
		
		// find two block
		if(withNext) {
			var prev = element;
			var next = this.tree.findForward(
				element,
				function(node) {return this.tree.isBlock(node) && !this.tree.isListContainer(node) && node != element.parentNode}.bind(this)
			);
		} else {
			var next = element;
			var prev = this.tree.findBackward(
				element,
				function(node) {return this.tree.isBlock(node) && !this.tree.isListContainer(node) && node != element.parentNode}.bind(this)
			);
		}
		
		// normalize next block
		if(next && this.tree.isDescendantOf(this.getRoot(), next)) {
			var nextContainer = next.parentNode;
			if(this.tree.isBlockContainer(next)) {
				nextContainer = next;
				this.wrapAllInlineOrTextNodesAs("P", nextContainer, true);
				next = nextContainer.firstChild;
			}
		} else {
			next = null;
		}
		
		// normalize prev block
		if(prev && this.tree.isDescendantOf(this.getRoot(), prev)) {
			var prevContainer = prev.parentNode;
			if(this.tree.isBlockContainer(prev)) {
				prevContainer = prev;
				this.wrapAllInlineOrTextNodesAs("P", prevContainer, true);
				prev = prevContainer.lastChild;
			}
		} else {
			prev = null;
		}
		
		try {
			var containersAreTableCell =
				prevContainer && (this.tree.isTableCell(prevContainer) || ['TR', 'THEAD', 'TBODY'].indexOf(prevContainer.nodeName) != -1) &&
				nextContainer && (this.tree.isTableCell(nextContainer) || ['TR', 'THEAD', 'TBODY'].indexOf(nextContainer.nodeName) != -1);
			
			if(containersAreTableCell && prevContainer != nextContainer) return null;
			
			// if next has margin, perform outdent
			if((!skip || !prev) && next && this.outdentElement(next)) return element;

			// nextContainer is first li and next of it is list container
			if(nextContainer && nextContainer.nodeName == 'LI' && this.tree.isListContainer(next.nextSibling)) {
				this.extractOutElementFromParent(nextContainer);
				return prev;
			}
			
			// merge two list containers
			if(nextContainer && nextContainer.nodeName == 'LI' && this.tree.isListContainer(nextContainer.parentNode.previousSibling)) {
				this.mergeAdjustLists(nextContainer.parentNode.previousSibling, true, "next");
				return prev;
			}

			if(next && !containersAreTableCell && prevContainer && prevContainer.nodeName == 'LI' && nextContainer && nextContainer.nodeName == 'LI' && prevContainer.parentNode.nextSibling == nextContainer.parentNode) {
				var nextContainerContainer = nextContainer.parentNode;
				this.moveChildNodes(nextContainer.parentNode, prevContainer.parentNode);
				this.deleteNode(nextContainerContainer);
				return prev;
			}
			
			// merge two containers
			if(next && !containersAreTableCell && prevContainer && prevContainer.nextSibling == nextContainer && ((skip && prevContainer.nodeName != "LI") || (!skip && prevContainer.nodeName == "LI"))) {
				this.moveChildNodes(nextContainer, prevContainer);
				return prev;
			}

			// unwrap container
			if(nextContainer && nextContainer.nodeName != "LI" && !this.getParentElementOf(nextContainer, ["TABLE"]) && !this.tree.isListContainer(nextContainer) && nextContainer != this.getRoot() && !next.previousSibling) {
				return this.unwrapElement(nextContainer, true);
			}
			
			// delete table
			if(withNext && nextContainer && nextContainer.nodeName == "TABLE") {
				this.deleteNode(nextContainer, true);
				return prev;
			} else if(!withNext && prevContainer && this.tree.isTableCell(prevContainer) && !this.tree.isTableCell(nextContainer)) {
				this.deleteNode(this.getParentElementOf(prevContainer, ["TABLE"]), true);
				return next;
			}
			
			// if prev is same with next, do nothing
			if(prev == next) return null;

			// if there is a null block, do nothing
			if(!prev || !next || !prevContainer || !nextContainer) return null;
			
			// if two blocks are not in the same table cell, do nothing
			if(this.getParentElementOf(prev, ["TD", "TH"]) != this.getParentElementOf(next, ["TD", "TH"])) return null;
			
			var prevIsEmpty = false;
			
			// cleanup empty block before merge

			// 1. cleanup prev node which ends with marker + &nbsp;
			if(
				xq.Browser.isTrident &&
				prev.childNodes.length >= 2 &&
				this.isMarker(prev.lastChild.previousSibling) &&
				prev.lastChild.nodeType == 3 &&
				prev.lastChild.nodeValue.length == 1 &&
				prev.lastChild.nodeValue.charCodeAt(0) == 160
			) {
				this.deleteNode(prev.lastChild);
			}

			// 2. cleanup prev node (if prev is empty, then replace prev's tag with next's)
			this.removePlaceHoldersAndEmptyNodes(prev);
			if(this.isEmptyBlock(prev)) {
				// replace atomic block with normal block so that following code don't need to care about atomic block
				if(this.tree.isAtomic(prev)) prev = this.replaceTag("P", prev);
				
				prev = this.replaceTag(next.nodeName, prev) || prev;
				prev.innerHTML = "";
			} else if(prev.firstChild == prev.lastChild && this.isMarker(prev.firstChild)) {
				prev = this.replaceTag(next.nodeName, prev) || prev;
			}
			
			// 3. cleanup next node
			if(this.isEmptyBlock(next)) {
				// replace atomic block with normal block so that following code don't need to care about atomic block
				if(this.tree.isAtomic(next)) next = this.replaceTag("P", next);
				
				next.innerHTML = "";
			}
			
			// perform merge
			this.moveChildNodes(next, prev);
			this.deleteNode(next);
			return prev;
		} finally {
			// cleanup
			if(prevContainer && this.isEmptyBlock(prevContainer)) this.deleteNode(prevContainer, true);
			if(nextContainer && this.isEmptyBlock(nextContainer)) this.deleteNode(nextContainer, true);
			
			if(prevContainer) this.unwrapUnnecessaryParagraph(prevContainer);
			if(nextContainer) this.unwrapUnnecessaryParagraph(nextContainer);
		}
	},
	
	/**
	 * Merges adjust list containers which has same tag name
	 *
	 * @param {Element} container target list container
	 * @param {boolean} force force adjust list container even if they have different list type
	 * @param {String} dir Specify merge direction: PREV or NEXT. If not supplied it will be merged with both direction.
	 */
	mergeAdjustLists: function(container, force, dir) {
		var prev = container.previousSibling;
		var isPrevSame = prev && (prev.nodeName == container.nodeName && prev.className == container.className);
		if((!dir || dir.toLowerCase() == 'prev') && (isPrevSame || (force && this.tree.isListContainer(prev)))) {
			while(prev.lastChild) {
				this.insertNodeAt(prev.lastChild, container, "start");
			}
			this.deleteNode(prev);
		}
		
		var next = container.nextSibling;
		var isNextSame = next && (next.nodeName == container.nodeName && next.className == container.className);
		if((!dir || dir.toLowerCase() == 'next') && (isNextSame || (force && this.tree.isListContainer(next)))) {
			while(next.firstChild) {
				this.insertNodeAt(next.firstChild, container, "end");
			}
			this.deleteNode(next);
		}
	},
	
	/**
	 * Moves child nodes from one element into another.
	 *
	 * @param {Elemet} from source element
	 * @param {Elemet} to target element
	 */
	moveChildNodes: function(from, to) {
		if(this.tree.isDescendantOf(from, to) || ["HTML", "HEAD"].indexOf(to.nodeName) != -1)
			throw "Illegal argument. Cannot move children of element[" + from.nodeName + "] to element[" + to.nodeName + "]";
		
		if(from == to) return;
		
		while(from.firstChild) to.appendChild(from.firstChild);
	},
	
	/**
	 * Copies attributes from one element into another.
	 *
	 * @param {Element} from source element
	 * @param {Element} to target element
	 * @param {boolean} copyId copy ID attribute of source element
	 */
	copyAttributes: function(from, to, copyId) {
		// IE overrides this
		
		var attrs = from.attributes;
		if(!attrs) return;
		
		for(var i = 0; i < attrs.length; i++) {
			if(attrs[i].nodeName == "class" && attrs[i].nodeValue) {
				to.className = attrs[i].nodeValue;
			} else if((copyId || "id" != attrs[i].nodeName) && attrs[i].nodeValue) {
				to.setAttribute(attrs[i].nodeName, attrs[i].nodeValue);
			}
		}
	},

	_indentElements: function(node, blocks, affect) {
		for (var i=0; i < affect.length; i++) {
			if (affect[i] == node || this.tree.isDescendantOf(affect[i], node))
				return;
		}
		leaves = this.tree.getLeavesAtEdge(node);
		
		if (blocks.include(leaves[0])) {
			var affected = this.indentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}
		
		if (blocks.include(node)) {
			var affected = this.indentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}

		var children=xq.$A(node.childNodes);
		for (var i=0; i < children.length; i++)
			this._indentElements(children[i], blocks, affect);
		return;
	},

	indentElements: function(from, to) {
		var blocks = this.getBlockElementsBetween(from, to);
		var top = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
		
		var affect = [];
		
		leaves = this.tree.getLeavesAtEdge(top.parent);
		if (blocks.include(leaves[0])) {
			var affected = this.indentElement(top.parent);
			if (affected)
				return [affected];
		}
		
		var children = xq.$A(top.parent.childNodes);
		for (var i=0; i < children.length; i++) {
			this._indentElements(children[i], blocks, affect);
		}
		
		affect = affect.flatten()
		return affect.length > 0 ? affect : blocks;
	},
	
	outdentElementsCode: function(node) {
		if (node.tagName == 'LI')
			node = node.parentNode;
		if (node.tagName == 'OL' && node.className == 'code')
			return true;
		return false;
	},
	
	_outdentElements: function(node, blocks, affect) {
		for (var i=0; i < affect.length; i++) {
			if (affect[i] == node || this.tree.isDescendantOf(affect[i], node))
				return;
		}
		leaves = this.tree.getLeavesAtEdge(node);
		
		if (blocks.include(leaves[0]) && !this.outdentElementsCode(leaves[0])) {
			var affected = this.outdentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}
		
		if (blocks.include(node)) {
			var children = xq.$A(node.parentNode.childNodes);
			var isCode = this.outdentElementsCode(node);
			var affected = this.outdentElement(node, true, isCode);
			if (affected) {
				if (children.include(affected) && this.tree.isListContainer(node.parentNode) && !isCode) {
					for (var i=0; i < children.length; i++) {
						if (blocks.include(children[i]) && !affect.include(children[i]))
							affect.push(children[i]);
					}
				}else
					affect.push(affected);
				return;
			}
		}

		var children=xq.$A(node.childNodes);
		for (var i=0; i < children.length; i++)
			this._outdentElements(children[i], blocks, affect);
		return;
	},

	outdentElements: function(from, to) {
		var start, end;
		
		if (from.parentNode.tagName == 'LI') start=from.parentNode;
		if (to.parentNode.tagName == 'LI') end=to.parentNode;
		
		var blocks = this.getBlockElementsBetween(from, to);
		var top = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
		
		var affect = [];
		
		leaves = this.tree.getLeavesAtEdge(top.parent);
		if (blocks.include(leaves[0]) && !this.outdentElementsCode(top.parent)) {
			var affected = this.outdentElement(top.parent);
			if (affected)
				return [affected];
		}
		
		var children = xq.$A(top.parent.childNodes);
		for (var i=0; i < children.length; i++) {
			this._outdentElements(children[i], blocks, affect);
		}

		if (from.offsetParent && to.offsetParent) {
			start = from;
			end = to;
		}else if (blocks.first().offsetParent && blocks.last().offsetParent) {
			start = blocks.first();
			end = blocks.last();
		}
		
		affect = affect.flatten()
		if (!start || !start.offsetParent)
			start = affect.first();
		if (!end || !end.offsetParent)
			end = affect.last();
		
		return this.getBlockElementsBetween(start, end);
	},
	
	/**
	 * Performs indent by increasing element's margin-left
	 */	
	indentElement: function(element, noParent, forceMargin) {
		if(
			!forceMargin &&
			(element.nodeName == "LI" || (!this.tree.isListContainer(element) && !element.previousSibling && element.parentNode.nodeName == "LI"))
		) return this.indentListItem(element, noParent);
		
		var root = this.getRoot();
		if(!element || element == root) return null;
		
		if (element.parentNode != root && !element.previousSibling && !noParent) element=element.parentNode;
		
		var margin = element.style.marginLeft;
		var cssValue = margin ? this._getCssValue(margin, "px") : {value:0, unit:"em"};
		
		cssValue.value += 2;
		element.style.marginLeft = cssValue.value + cssValue.unit;
		
		return element;
	},
	
	/**
	 * Performs outdent by decreasing element's margin-left
	 */	
	outdentElement: function(element, noParent, forceMargin) {
		if(!forceMargin && element.nodeName == "LI") return this.outdentListItem(element, noParent);
		
		var root = this.getRoot();
		if(!element || element == root) return null;
		
		var margin = element.style.marginLeft;
		
		var cssValue = margin ? this._getCssValue(margin, "px") : {value:0, unit:"em"};
		if(cssValue.value == 0) {
			return element.previousSibling || forceMargin ?
				null :
				this.outdentElement(element.parentNode, noParent);
		}
		
		cssValue.value -= 2;
		element.style.marginLeft = cssValue.value <= 0 ? "" : cssValue.value + cssValue.unit;
		if(element.style.cssText == "") element.removeAttribute("style");
		
		return element;
	},
	
	/**
	 * Performs indent for list item
	 */
	indentListItem: function(element, treatListAsNormalBlock) {
		var li = this.getParentElementOf(element, ["LI"]);
		var container = li.parentNode;
		var prev = li.previousSibling;
		if(!li.previousSibling) return this.indentElement(container);
		
		if(li.parentNode.nodeName == "OL" && li.parentNode.className == "code") return this.indentElement(li, treatListAsNormalBlock, true);
		
		if(!prev.lastChild) prev.appendChild(this.makePlaceHolder());
		
		var targetContainer = 
			this.tree.isListContainer(prev.lastChild) ?
			// if there's existing list container, select it as target container
			prev.lastChild :
			// if there's nothing, create new one
			this.insertNodeAt(this.createElement(container.nodeName), prev, "end");
		
		this.wrapAllInlineOrTextNodesAs("P", prev, true);
		
		// perform move
		targetContainer.appendChild(li);
		
		// flatten nested list
		if(!treatListAsNormalBlock && li.lastChild && this.tree.isListContainer(li.lastChild)) {
			var childrenContainer = li.lastChild;
			var child;
			while(child = childrenContainer.lastChild) {
				this.insertNodeAt(child, li, "after");
			}
			this.deleteNode(childrenContainer);
		}
		
		this.unwrapUnnecessaryParagraph(li);
		
		return li;
	},
	
	/**
	 * Performs outdent for list item
	 *
	 * @return {Element} outdented list item or null if no outdent performed
	 */
	outdentListItem: function(element, treatListAsNormalBlock) {
		var li = this.getParentElementOf(element, ["LI"]);
		var container = li.parentNode;

		if(!li.previousSibling) {
			var performed = this.outdentElement(container);
			if(performed) return performed;
		}

		if(li.parentNode.nodeName == "OL" && li.parentNode.className == "code") return this.outdentElement(li, treatListAsNormalBlock, true);
		
		var parentLi = container.parentNode;
		if(parentLi.nodeName != "LI") return null;
		
		if(treatListAsNormalBlock) {
			while(container.lastChild != li) {
				this.insertNodeAt(container.lastChild, parentLi, "after");
			}
		} else {
			// make next siblings as children
			if(li.nextSibling) {
				var targetContainer =
					li.lastChild && this.tree.isListContainer(li.lastChild) ?
						// if there's existing list container, select it as target container
						li.lastChild :
						// if there's nothing, create new one
						this.insertNodeAt(this.createElement(container.nodeName), li, "end");
				
				this.copyAttributes(container, targetContainer);
				
				var sibling;
				while(sibling = li.nextSibling) {
					targetContainer.appendChild(sibling);
				}
			}
		}
		
		// move current LI into parent LI's next sibling
		li = this.insertNodeAt(li, parentLi, "after");
		
		// remove empty container
		if(container.childNodes.length == 0) this.deleteNode(container);
		
		if(li.firstChild && this.tree.isListContainer(li.firstChild)) {
			this.insertNodeAt(this.makePlaceHolder(), li, "start");
		}
		
		this.wrapAllInlineOrTextNodesAs("P", li);
		this.unwrapUnnecessaryParagraph(parentLi);
		
		return li;
	},
	
	/**
	 * Performs justification
	 *
	 * @param {Element} block target element
	 * @param {String} dir one of "LEFT", "CENTER", "RIGHT", "BOTH"
	 */
	justifyBlock: function(block, dir) {
		// if block is only child, select its parent as mover
		while(block.parentNode != this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		var styleValue = dir.toLowerCase() == "both" ? "justify" : dir;
		if(styleValue == "left") {
			block.style.textAlign = "";
			if(block.style.cssText == "") block.removeAttribute("style");
		} else {
			block.style.textAlign = styleValue;
		}
		return block;
	},
	
	justifyBlocks: function(blocks, dir) {
		for(var i = 0; i < blocks.length; i++) {
			this.justifyBlock(blocks[i], dir);
		}
		return blocks;
	},
	
	/**
     * Turn given element into list. If the element is a list already, it will be reversed into normal element.
	 *
	 * @param {Element} element target element
	 * @param {String} type one of "UL", "OL"
	 * @returns {Element} affected element
	 */
	applyList: function(element, type) {
		type = type.toUpperCase();
		var containerTag = type == "UL" ? "UL" : "OL";
		
		if(element.nodeName == "LI" || (element.parentNode.nodeName == "LI" && !element.previousSibling)) {
			var element = this.getParentElementOf(element, ["LI"]);
			var container = element.parentNode;
			if(container.nodeName == containerTag) {
				return this.extractOutElementFromParent(element);
			} else {
				return this.changeListTypeTo(element, type);
			}
		} else {
			return this.turnElementIntoListItem(element, type);
		}
	},
	
	applyLists: function(from, to, type) {
		type = type.toUpperCase();
		var containerTag = type == "UL" ? "UL" : "OL";
		var blocks = this.getBlockElementsBetween(from, to);
		
		// LIs or Non-containing blocks
		var whole = blocks.findAll(function(e) {
			return e.nodeName == "LI" || !this.tree.isBlockContainer(e);
		}.bind(this));
		
		// LIs
		var listItems = whole.findAll(function(e) {return e.nodeName == "LI"}.bind(this));
		
		// Non-containing blocks which is not a descendant of any LIs selected above(listItems).
		var normalBlocks = whole.findAll(function(e) {
			return e.nodeName != "LI" &&
				!(e.parentNode.nodeName == "LI" && !e.previousSibling && !e.nextSibling) &&
				!this.tree.isDescendantOf(listItems, e)
		}.bind(this));
		
		var diffListItems = listItems.findAll(function(e) {
			return e.parentNode.nodeName != containerTag;
		}.bind(this));
		
		// Conditions needed to determine mode
		var hasNormalBlocks = normalBlocks.length > 0;
		var hasDifferentListStyle = diffListItems.length > 0;
		
		var blockToHandle = null;
		
		if(hasNormalBlocks) {
			blockToHandle = normalBlocks;
		} else if(hasDifferentListStyle) {
			blockToHandle = diffListItems;
		} else {
			blockToHandle = listItems;
		}
		
		// perform operation
		for(var i = 0; i < blockToHandle.length; i++) {
			var block = blockToHandle[i];
			
			// preserve original index to restore selection
			var originalIndex = blocks.indexOf(block);
			blocks[originalIndex] = this.applyList(block, type);
		}
		
		return blocks;
	},

	/**
	 * Insert place-holder for given empty element. Empty element does not displayed and causes many editing problems.
	 *
	 * @param {Element} element empty element
	 */
	correctEmptyElement: function(element) {throw "Not implemented"},

	/**
	 * Corrects current block-only-container to do not take any non-block element or node.
	 */
	correctParagraph: function() {throw "Not implemented"},
	
	/**
	 * Makes place-holder for empty element.
	 *
	 * @returns {Node} Platform specific place holder
	 */
	makePlaceHolder: function() {throw "Not implemented"},
	
	/**
	 * Makes place-holder string.
	 *
	 * @returns {String} Platform specific place holder string
	 */
	makePlaceHolderString: function() {throw "Not implemented"},
	
	/**
	 * Makes empty paragraph which contains only one place-holder
	 */
	makeEmptyParagraph: function() {throw "Not implemented"},

	/**
	 * Applies background color to selected area
	 *
	 * @param {Object} color valid CSS color value
	 */
	applyBackgroundColor: function(color) {throw "Not implemented";},

	/**
	 * Applies foreground color to selected area
	 *
	 * @param {Object} color valid CSS color value
	 */
	applyForegroundColor: function(color) {
		this.execCommand("forecolor", color);
	},
	
	execCommand: function(commandId, param) {throw "Not implemented";},
	
	applyRemoveFormat: function() {throw "Not implemented";},
	applyEmphasis: function() {throw "Not implemented";},
	applyStrongEmphasis: function() {throw "Not implemented";},
	applyStrike: function() {throw "Not implemented";},
	applyUnderline: function() {throw "Not implemented";},
	applySuperscription: function() {
		this.execCommand("superscript");
	},
	applySubscription: function() {
		this.execCommand("subscript");
	},
	indentBlock: function(element, treatListAsNormalBlock) {
		return (!element.previousSibling && element.parentNode.nodeName == "LI") ?
			this.indentListItem(element, treatListAsNormalBlock) :
			this.indentElement(element);
	},
	outdentBlock: function(element, treatListAsNormalBlock) {
		while(true) {
			if(!element.previousSibling && element.parentNode.nodeName == "LI") {
				element = this.outdentListItem(element, treatListAsNormalBlock);
				return element;
			} else {
				var performed = this.outdentElement(element);
				if(performed) return performed;
				
				// first-child can outdent container
				if(!element.previousSibling) {
					element = element.parentNode;
				} else {
					break;
				}
			}
		}
		
		return null;
	},
	wrapBlock: function(tag, start, end) {
		if(this.tree._blockTags.indexOf(tag) == -1) throw "Unsuppored block container: [" + tag + "]";
		if(!start) start = this.getCurrentBlockElement();
		if(!end) end = start;
		
		// Check if the selection captures valid fragement
		var validFragment = false;
		
		if(start == end) {
			// are they same block?
			validFragment = true;
		} else if(start.parentNode == end.parentNode && !start.previousSibling && !end.nextSibling) {
			// are they covering whole parent?
			validFragment = true;
			start = end = start.parentNode;
		} else {
			// are they siblings of non-LI blocks?
			validFragment =
				(start.parentNode == end.parentNode) &&
				(start.nodeName != "LI");
		}
		
		if(!validFragment) return null;
		
		var wrapper = this.createElement(tag);
		
		if(start == end) {
			// They are same.
			if(this.tree.isBlockContainer(start) && !this.tree.isListContainer(start)) {
				// It's a block container. Wrap its contents.
				if(this.tree.isBlockOnlyContainer(wrapper)) {
					this.correctEmptyElement(start);
					this.wrapAllInlineOrTextNodesAs("P", start, true);
				}
				this.moveChildNodes(start, wrapper);
				start.appendChild(wrapper);
			} else {
				// It's not a block container. Wrap itself.
				wrapper = this.insertNodeAt(wrapper, start, "after");
				wrapper.appendChild(start);
			}
			
			this.correctEmptyElement(wrapper);
		} else {
			// They are siblings. Wrap'em all.
			wrapper = this.insertNodeAt(wrapper, start, "before");
			var node = start;
			
			while(node != end) {
				next = node.nextSibling;
				wrapper.appendChild(node);
				node = next;
			}
			wrapper.appendChild(node);
		}
		
		return wrapper;
	},


	
	/////////////////////////////////////////////
	// Focus/Caret/Selection
	
	/**
	 * Gives focus to root element's window
	 */
	focus: function() {throw "Not implemented";},

	/**
	 * Returns selection object
	 */
	sel: function() {throw "Not implemented";},
	
	/**
	 * Returns range object
	 */
	rng: function() {throw "Not implemented";},
	
	/**
	 * Returns true if DOM has selection
	 */
	hasSelection: function() {throw "Not implemented";},

	/**
	 * Returns true if root element's window has selection
	 */
	hasFocus: function() {
		var cur = this.getCurrentElement();
		return (cur && cur.ownerDocument == this.getDoc());
	},
	
	/**
	 * Adjust scrollbar to make the element visible in current viewport.
	 *
	 * @param {Element} element Target element
	 * @param {boolean} toTop Align element to top of the viewport
	 * @param {boolean} moveCaret Move caret to the element
	 */
	scrollIntoView: function(element, toTop, moveCaret) {
		element.scrollIntoView(toTop);
		if(moveCaret) this.placeCaretAtStartOf(element);
	},
	
	/**
	 * Select all document
	 */
	selectAll: function() {
		return this.execCommand('selectall');
	},
	
	/**
	 * Select specified element.
	 *
	 * @param {Element} element element to select
	 * @param {boolean} entireElement true to select entire element, false to select inner content of element 
	 */
	selectElement: function(node, entireElement) {throw "Not implemented"},
	
	/**
	 * Select all elements between two blocks(inclusive).
	 *
	 * @param {Element} start start of selection
	 * @param {Element} end end of selection
	 */
	selectBlocksBetween: function(start, end) {throw "Not implemented"},
	
	/**
	 * Delete selected area
	 */
	deleteSelection: function() {throw "Not implemented"},
	
	/**
	 * Collapses current selection.
	 *
	 * @param {boolean} toStart true to move caret to start of selected area.
	 */
	collapseSelection: function(toStart) {throw "Not implemented"},
	
	/**
	 * Returns selected area as HTML string
	 */
	getSelectionAsHtml: function() {throw "Not implemented"},
	
	/**
	 * Returns selected area as text string
	 */
	getSelectionAsText: function() {throw "Not implemented"},
	
	/**
	 * Places caret at start of the element
	 *
	 * @param {Element} element Target element
	 */
	placeCaretAtStartOf: function(element) {throw "Not implemented"},
	
	/**
	 * Checks if the node is empty-text-node or not
	 */
	isEmptyTextNode: function(node) {
		return node.nodeType == 3 && node.nodeValue.length == 0;
	},
	
	/**
	 * Checks if the caret is place in empty block element
	 */
	isCaretAtEmptyBlock: function() {
		return this.isEmptyBlock(this.getCurrentBlockElement());
	},
	
	/**
	 * Checks if the caret is place at start of the block
	 */
	isCaretAtBlockStart: function() {throw "Not implemented"},

	/**
	 * Checks if the caret is place at end of the block
	 */
	isCaretAtBlockEnd: function() {throw "Not implemented"},
	
	/**
	 * Saves current selection info
	 *
	 * @returns {Object} Bookmark for selection
	 */
	saveSelection: function() {throw "Not implemented"},
	
	/**
	 * Restores current selection info
	 *
	 * @param {Object} bookmark Bookmark
	 */
	restoreSelection: function(bookmark) {throw "Not implemented"},
	
	/**
	 * Create marker
	 */
	createMarker: function() {
		var marker = this.createElement("SPAN");
		marker.id = "xquared_marker_" + (this._lastMarkerId++);
		marker.className = "xquared_marker";
		return marker;
	},

	/**
	 * Create and insert marker into current caret position.
	 * Marker is an inline element which has no child nodes. It can be used with many purposes.
	 * For example, You can push marker to mark current caret position.
	 *
	 * @returns {Element} marker
	 */
	pushMarker: function() {
		var marker = this.createMarker();
		return this.insertNode(marker);
	},
	
	/**
	 * Removes last marker
	 *
	 * @params {boolean} moveCaret move caret into marker before delete.
	 */
	popMarker: function(moveCaret) {
		var id = "xquared_marker_" + (--this._lastMarkerId);
		var marker = this.$(id);
		if(!marker) return;
		
		if(moveCaret) {
			this.selectElement(marker, true);
			this.collapseSelection(false);
		}
		
		this.deleteNode(marker);
	},
	
	
	
	/////////////////////////////////////////////
	// Query methods
	
	isMarker: function(node) {
		return (node.nodeType == 1 && node.nodeName == "SPAN" && node.className == "xquared_marker");
	},
	
	isFirstBlockOfBody: function(block) {
		var root = this.getRoot();
		var found = this.tree.findBackward(
			block,
			function(node) {return (node == root) || node.previousSibling;}.bind(this)
		);
		
		return found == root;
	},
	
	/**
	 * Returns outer HTML of given element
	 */
	getOuterHTML: function(element) {throw "Not implemented"},
	
	/**
	 * Returns inner text of given element
	 * 
	 * @param {Element} element Target element
	 * @returns {String} Text string
	 */
	getInnerText: function(element) {
		return element.innerHTML.stripTags();
	},

	/**
	 * Checks if given node is place holder or not.
	 * 
	 * @param {Node} node DOM node
	 */
	isPlaceHolder: function(node) {throw "Not implemented"},
	
	/**
	 * Checks if given block is the first LI whose next sibling is a nested list.
	 *
	 * @param {Element} block Target block
	 */
	isFirstLiWithNestedList: function(block) {
		return !block.previousSibling &&
			block.parentNode.nodeName == "LI" &&
			this.tree.isListContainer(block.nextSibling);
	},
	
	/**
	 * Search all links within given element
	 *
	 * @param {Element} [element] Container element. If not given, the root element will be used.
	 * @param {Array} [found] if passed, links will be appended into this array.
	 * @returns {Array} Array of anchors. It returns empty array if there's no links.
	 */
	searchAnchors: function(element, found) {
		if(!element) element = this.getRoot();
		if(!found) found = [];

		var anchors = element.getElementsByTagName("A");
		for(var i = 0; i < anchors.length; i++) {
			found.push(anchors[i]);
		}

		return found;
	},
	
	/**
	 * Search all headings within given element
	 *
	 * @param {Element} [element] Container element. If not given, the root element will be used.
	 * @param {Array} [found] if passed, headings will be appended into this array.
	 * @returns {Array} Array of headings. It returns empty array if there's no headings.
	 */
	searchHeadings: function(element, found) {
		if(!element) element = this.getRoot();
		if(!found) found = [];

		var regexp = /^h[1-6]/ig;
		var nodes = element.childNodes;
		if (!nodes) return [];
		
		for(var i = 0; i < nodes.length; i++) {
			var isContainer = nodes[i] && this.tree._blockContainerTags.indexOf(nodes[i].nodeName) != -1;
			var isHeading = nodes[i] && nodes[i].nodeName.match(regexp);

			if (isContainer) {
				this.searchHeadings(nodes[i], found);
			} else if (isHeading) {
				found.push(nodes[i]);
			}
		}

		return found;
	},
	
	/**
	 * Collect structure and style informations of given element.
	 *
	 * @param {Element} element target element
	 * @returns {Object} object that contains information: {em: true, strong: false, block: "p", list: "ol", ...}
	 */
	collectStructureAndStyle: function(element) {
		if(!element || element.nodeName == "#document") return {};

		var block = this.getParentBlockElementOf(element);
		
		// IEÔøΩÔøΩÔøΩÔøΩ ÔøΩÔøΩ»§ DOMÔøΩÔøΩ ÔøΩÔøΩ ÔøΩÔøΩ: elementÔøΩÔøΩ ÔøΩÔøΩÔøΩ⁄∑ÔøΩ ÔøΩ—æÔøΩ4Ôø?ÔøΩÔøΩÏ∞°ÔøΩÔø?
		if(block == null) return {};
		
		var parents = this.tree.collectParentsOf(element, true, function(node) {return block.parentNode == node});
		var blockName = block.nodeName;

		var info = {};
		
		var doc = this.getDoc();
		var em = doc.queryCommandState("Italic");
		var strong = doc.queryCommandState("Bold");
		var strike = doc.queryCommandState("Strikethrough");
		var underline = doc.queryCommandState("Underline") && !this.getParentElementOf(element, ["A"]);
		var superscription = doc.queryCommandState("superscript");
		var subscription = doc.queryCommandState("subscript");
		
		// if block is only child, select its parent
		while(block.parentNode && block.parentNode != this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}

		var list = false;
		if(block.nodeName == "LI") {
			var parent = block.parentNode;
			var isCode = parent.nodeName == "OL" && parent.className == "code";
			list = isCode ? "CODE" : parent.nodeName;
		}
		
		var justification = block.style.textAlign || "left";
		
		return {
			block:blockName,
			em: em,
			strong: strong,
			strike: strike,
			underline: underline,
			superscription: superscription,
			subscription: subscription,
			list: list,
			justification: justification
		};
	},
	
	/**
	 * Checks if the element has one or more important attributes: id, class, style
	 *
	 * @param {Element} element Target element
	 */
	hasImportantAttributes: function(element) {throw "Not implemented"},
	
	/**
	 * Checks if the element is empty or not. Place-holder is not counted as a child.
	 *
	 * @param {Element} element Target element
	 */
	isEmptyBlock: function(element) {throw "Not implemented"},
	
	/**
	 * Returns element that contains caret.
	 */
	getCurrentElement: function() {throw "Not implemented"},
	
	/**
	 * Returns block element that contains caret.
	 */
	getCurrentBlockElement: function() {
		var cur = this.getCurrentElement();
		if(!cur) return null;
		
		var block = this.getParentBlockElementOf(cur);
		if(!block) return null;
		
		return (block.nodeName == "BODY") ? null : block;
	},
	
	/**
	 * Returns parent block element of parameter.
	 * If the parameter itself is a block, it will be returned.
	 *
	 * @param {Element} element Target element
	 *
	 * @returns {Element} Element or null
	 */
	getParentBlockElementOf: function(element) {
		while(element) {
			if(this.tree._blockTags.indexOf(element.nodeName) != -1) return element;
			element = element.parentNode;
		}
		return null;
	},
	
	/**
	 * Returns parent element of parameter which has one of given tag name.
	 * If the parameter itself has the same tag name, it will be returned.
	 *
	 * @param {Element} element Target element
	 * @param {Array} tagNames Array of string which contains tag names
	 *
	 * @returns {Element} Element or null
	 */
	getParentElementOf: function(element, tagNames) {
		while(element) {
			if(tagNames.indexOf(element.nodeName) != -1) return element;
			element = element.parentNode;
		}
		return null;
	},
	
	/**
	 * Collects all block elements between two elements
	 *
	 * @param {Element} from Start element(inclusive)
	 * @param {Element} to End element(inclusive)
	 */
	getBlockElementsBetween: function(from, to) {
		return this.tree.collectNodesBetween(from, to, function(node) {
			return node.nodeType == 1 && this.tree.isBlock(node);
		}.bind(this));
	},
	
	/**
	 * Returns block element that contains selection start.
	 *
	 * This method will return exactly same result with getCurrentBlockElement method
	 * when there's no selection.
	 */
	getBlockElementAtSelectionStart: function() {throw "Not implemented"},
	
	/**
	 * Returns block element that contains selection end.
	 *
	 * This method will return exactly same result with getCurrentBlockElement method
	 * when there's no selection.
	 */
	getBlockElementAtSelectionEnd: function() {throw "Not implemented"},
	
	/**
	 * Returns blocks at each edge of selection(start and end).
	 *
	 * TODO: implement ignoreEmptyEdges for FF
	 *
	 * @param {boolean} naturalOrder Mak the start element always comes before the end element
	 * @param {boolean} ignoreEmptyEdges Prevent some browser(Gecko) from selecting one more block than expected
	 */
	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {throw "Not implemented"},
	
	/**
	 * Returns array of selected block elements
	 */
	getSelectedBlockElements: function() {
		var selectionEdges = this.getBlockElementsAtSelectionEdge(true, true);
		var start = selectionEdges[0];
		var end = selectionEdges[1];
		
		return this.tree.collectNodesBetween(start, end, function(node) {
			return node.nodeType == 1 && this.tree.isBlock(node);
		}.bind(this));
	},
	
	/**
	 * Get element by ID
	 *
	 * @param {String} id Element's ID
	 * @returns {Element} element or null
	 */
	getElementById: function(id) {return this.doc.getElementById(id)},
	
	/**
	 * Shortcut for #getElementById
	 */
	$: function(id) {return this.getElementById(id)},
	
	/**
	  * Returns first "valid" child of given element. It ignores empty textnodes.
	  *
	  * @param {Element} element Target element
	  * @returns {Node} first child node or null
	  */
	getFirstChild: function(element) {
		if(!element) return null;
		
		var nodes = xq.$A(element.childNodes);
		return nodes.find(function(node) {return !this.isEmptyTextNode(node)}.bind(this));
	},
	
	/**
	  * Returns last "valid" child of given element. It ignores empty textnodes and place-holders.
	  *
	  * @param {Element} element Target element
	  * @returns {Node} last child node or null
	  */
	getLastChild: function(element) {throw "Not implemented"},

	getNextSibling: function(node) {
		while(node = node.nextSibling) {
			if(node.nodeType != 3 || node.nodeValue.strip() != "") break;
		}
		return node;
	},

	getBottommostFirstChild: function(node) {
		while(node.firstChild && node.nodeType == 1) node = node.firstChild;
		return node;
	},
	
	getBottommostLastChild: function(node) {
		while(node.lastChild && node.nodeType == 1) node = node.lastChild;
		return node;
	},

	/** @private */
	_getCssValue: function(str, defaultUnit) {
		if(!str || str.length == 0) return {value:0, unit:defaultUnit};
		
		var tokens = str.match(/(\d+)(.*)/);
		return {
			value:parseInt(tokens[1]),
			unit:tokens[2] || defaultUnit
		};
	}
});

/**
 * Creates and returns instance of browser specific implementation.
 */
xq.RichDom.createInstance = function() {
	if(xq.Browser.isTrident) {
		return new xq.RichDomTrident();
	} else if(xq.Browser.isWebkit) {
		return new xq.RichDomWebkit();
	} else {
		return new xq.RichDomGecko();
	}
}
/**
 * RichDom for W3C Standard Engine
 */
xq.RichDomW3 = xq.Class(xq.RichDom, {
	insertNode: function(node) {
		var rng = this.rng();
		rng.insertNode(node);
		rng.selectNode(node);
		rng.collapse(false);
		return node;
	},

	removeTrailingWhitespace: function(block) {
		// TODO: do nothing
	},

	getOuterHTML: function(element) {
		var div = element.ownerDocument.createElement("div");
		div.appendChild(element.cloneNode(true));
		return div.innerHTML;
	},
	
	correctEmptyElement: function(element) {
		if(!element || element.nodeType != 1 || this.tree.isAtomic(element)) return;
		
		if(element.firstChild)
			this.correctEmptyElement(element.firstChild);
		else
			element.appendChild(this.makePlaceHolder());
	},
	
	correctParagraph: function() {
		if(this.hasSelection()) return false;
		
		var block = this.getCurrentElement();
		var modified = false;
		
		if(this.tree.isBlockOnlyContainer(block)) {
			this.execCommand("InsertParagraph");
			
			// check for atomic block element such as HR
			var newBlock = this.getCurrentElement();
			if(this.tree.isAtomic(newBlock.previousSibling)) {
				var nextBlock = this.tree.findForward(
					newBlock,
					function(node) {return this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node)}.bind(this)
				);
				if(nextBlock) {
					this.deleteNode(newBlock);
					this.placeCaretAtStartOf(nextBlock);
				}
			}
			modified = true;
		} else if(this.tree.hasMixedContents(block)) {
			this.wrapAllInlineOrTextNodesAs("P", block, true);
			modified = true;
		}
		
		block = this.getCurrentElement();
		if(this.tree.isBlock(block) && !this._hasPlaceHolderAtEnd(block)) {
			block.appendChild(this.makePlaceHolder());
			modified = true;
		}
		
		if(this.tree.isBlock(block)) {
			var parentsLastChild = block.parentNode.lastChild;
			if(this.isPlaceHolder(parentsLastChild)) {
				this.deleteNode(parentsLastChild);
				modified = true;
			}
		}
		
		return modified;
	},
	
	_hasPlaceHolderAtEnd: function(block) {
		if(!block.hasChildNodes()) return false;
		return this.isPlaceHolder(block.lastChild) || this._hasPlaceHolderAtEnd(block.lastChild);
	},
	
	applyBackgroundColor: function(color) {
		this.execCommand("styleWithCSS", "true");
		this.execCommand("hilitecolor", color);
		this.execCommand("styleWithCSS", "false");
		
		// 0. Save current selection
		var bookmark = this.saveSelection();
		
		// 1. Get selected blocks
		var blocks = this.getSelectedBlockElements();
		if(blocks.length == 0) return;
		
		// 2. Apply background-color to all adjust inline elements
		// 3. Remove background-color from blocks
		for(var i = 0; i < blocks.length; i++) {
			if((i == 0 || i == blocks.length-1) && !blocks[i].style.backgroundColor) continue;
			
			var spans = this.wrapAllInlineOrTextNodesAs("SPAN", blocks[i], true);
			for(var j = 0; j < spans.length; j++) {
				spans[j].style.backgroundColor = color;
			}
			blocks[i].style.backgroundColor = "";
		}
		
		// 4. Restore selection
		this.restoreSelection(bookmark);
	},
	
	
	
	
	//////
	// Commands
	execCommand: function(commandId, param) {
		return this.doc.execCommand(commandId, false, param || null);
	},
	
	saveSelection: function() {
		var rng = this.rng();
		return [rng.startContainer, rng.startOffset, rng.endContainer, rng.endOffset];
	},
	
	restoreSelection: function(bookmark) {
		var rng = this.rng();
		rng.setStart(bookmark[0], bookmark[1]);
		rng.setEnd(bookmark[2], bookmark[3]);
	},
	
	applyRemoveFormat: function() {
		this.execCommand("RemoveFormat");
		this.execCommand("Unlink");
	},
	applyEmphasis: function() {
		// Generate <i> tag. It will be replaced with <emphasis> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("italic");
	},
	applyStrongEmphasis: function() {
		// Generate <b> tag. It will be replaced with <strong> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("bold");
	},
	applyStrike: function() {
		// Generate <strike> tag. It will be replaced with <style class="strike"> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("strikethrough");
	},
	applyUnderline: function() {
		// Generate <u> tag. It will be replaced with <em class="underline"> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("underline");
	},
	execHeading: function(level) {
		this.execCommand("Heading", "H" + level);
	},



	//////
	// Focus/Caret/Selection
	
	focus: function() {
		setTimeout(this._focus.bind(this), 0);
	},
	
	/** @private */
	_focus: function() {
		this.win.focus();
		if(!this.hasSelection() && this.getCurrentElement().nodeName == "HTML") {
			this.selectElement(this.doc.body.firstChild);
			this.collapseSelection(true);
		}
	},

	sel: function() {
		return this.win.getSelection();
	},
	
	rng: function() {
		var sel = this.sel();
		return (sel == null || sel.rangeCount == 0) ? null : sel.getRangeAt(0);
	},

	hasSelection: function() {
		var sel = this.sel();
		return sel && !sel.isCollapsed;
	},
	
	deleteSelection: function() {
		this.rng().deleteContents();
		this.sel().collapseToStart();
	},
	
	selectElement: function(element, entireElement) {throw "Not implemented yet"},

	selectBlocksBetween: function(start, end) {
		// required to avoid FF selection bug.
		try {
			if(!xq.Browser.isMac) this.doc.execCommand("SelectAll", false, null);
		} catch(ignored) {}
		
		var rng = this.rng();
		rng.setStart(start.firstChild, 0);
		rng.setEnd(end, end.childNodes.length);
	},

	collapseSelection: function(toStart) {
		this.rng().collapse(toStart);
	},
	
	placeCaretAtStartOf: function(element) {
		while(this.tree.isBlock(element.firstChild)) {
			element = element.firstChild;
		}
		this.selectElement(element, false);
		this.collapseSelection(true);
	},
	
	getSelectionAsHtml: function() {
		var container = document.createElement("div");
		container.appendChild(this.rng().cloneContents());
		return container.innerHTML;
	},
	
	getSelectionAsText: function() {
		return this.rng().toString()
	},
	
	hasImportantAttributes: function(element) {
		return !!(element.id || element.className || element.style.cssText);
	},
	
	isEmptyBlock: function(element) {
		if(!element.hasChildNodes()) return true;
		var children = element.childNodes;
		for(var i = 0; i < children.length; i++) {
			if(!this.isPlaceHolder(children[i]) && !this.isEmptyTextNode(children[i])) return false;
		}
		return true;
	},
	
	getLastChild: function(element) {
		if(!element || !element.hasChildNodes()) return null;
		
		var nodes = xq.$A(element.childNodes).reverse();
		
		for(var i = 0; i < nodes.length; i++) {
			if(!this.isPlaceHolder(nodes[i]) && !this.isEmptyTextNode(nodes[i])) return nodes[i];
		}
		return null;
	},
	
	getCurrentElement: function() {
		var rng = this.rng();
		if(!rng) return null;
		
		var container = rng.startContainer;
		return container.nodeType == 3 ? container.parentNode : container;
	},

	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {
		var start = this.getBlockElementAtSelectionStart();
		var end = this.getBlockElementAtSelectionEnd();
		
		var reversed = false;
		
		if(naturalOrder && start != end && this.tree.checkTargetBackward(start, end)) {
			var temp = start;
			start = end;
			end = temp;
			
			reversed = true;
		}
		
		if(ignoreEmptyEdges && start != end) {
			// TODO - Firefox sometimes selects one more block.
/*
			
			var sel = this.sel();
			if(reversed) {
				if(sel.focusNode.nodeType == 1) start = start.nextSibling;
				if(sel.anchorNode.nodeType == 3 && sel.focusOffset == 0) end = end.previousSibling;
			} else {
				if(sel.anchorNode.nodeType == 1) start = start.nextSibling;
				if(sel.focusNode.nodeType == 3 && sel.focusOffset == 0) end = end.previousSibling;
			}
*/
		}
		
		return [start, end];
	},
	
	getBlockElementAtSelectionStart: function() {
		var block = this.getParentBlockElementOf(this.sel().anchorNode);
		
		// find bottom-most first block child
		while(this.tree.isBlockContainer(block) && block.firstChild && this.tree.isBlock(block.firstChild)) {
			block = block.firstChild;
		}
		
		return block;
	},
	
	getBlockElementAtSelectionEnd: function() {
		var block = this.getParentBlockElementOf(this.sel().focusNode);
		
		// find bottom-most last block child
		while(this.tree.isBlockContainer(block) && block.lastChild && this.tree.isBlock(block.lastChild)) {
			block = block.lastChild;
		}
		
		return block;
	},

	isCaretAtBlockStart: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		var rng = this.rng();
		var node = this.getCurrentBlockElement();
		var isTrue = false;
		
		if(node == rng.startContainer) {
			var marker = this.pushMarker();
			while (node = this.getFirstChild(node)) {
				if (node == marker) {
					isTrue = true;
					break;
				}
			}
			this.popMarker();
		} else {
			while (node = node.firstChild) {
				if (node == rng.startContainer && rng.startOffset == 0) {
					isTrue = true;
					break;
				}
			}
		}
		
		return isTrue;
	},
	
	isCaretAtBlockEnd: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		
		var rng = this.rng();
		var node = this.getCurrentBlockElement();
		var isTrue = false;
		
		if(node == rng.startContainer) {
			var marker = this.pushMarker();
			while (node = this.getLastChild(node)) {
				if ((node == marker) || (this.isPlaceHolder(node) && node.previousSibling == marker)) {
					isTrue = true;
					break;
				}
			}
			this.popMarker();
		} else {
			while (node = this.getLastChild(node)) {
				if (node == rng.endContainer && rng.endContainer.nodeType == 1) {
					isTrue = true;
					break;
				} else if (node == rng.endContainer && rng.endOffset == node.nodeValue.length) {
					isTrue = true;
					break;
				}
			}
		}
		
		return isTrue;
	}
});
/**
 * RichDom for Gecko
 */
xq.RichDomGecko = xq.Class(xq.RichDomW3, {
	makePlaceHolder: function() {
		var holder = this.createElement("BR");
		holder.setAttribute("type", "_moz");
		return holder;
	},
	
	makePlaceHolderString: function() {
		return '<br type="_moz" />';
	},
	
	makeEmptyParagraph: function() {
		return this.createElementFromHtml('<p><br type="_moz" /></p>');
	},

	isPlaceHolder: function(node) {
		if(node.nodeType != 1) return false;
		
		var typeMatches = node.nodeName == "BR" && node.getAttribute("type") == "_moz";
		if(typeMatches) return true;
		
		var positionMatches = node.nodeName == "BR" && !this.getNextSibling(node);
		if(positionMatches) return true;
		
		return false;
	},

	selectElement: function(element, entireElement) {
		if(!element) throw "[element] is null";
		if(element.nodeType != 1) throw "[element] is not an element";

		// required to avoid Windows FF selection bug.
		try {
			if(!xq.Browser.isMac) this.doc.execCommand("SelectAll", false, null);
		} catch(ignored) {}
		
		if(entireElement) {
			this.rng().selectNode(element);
		} else {
			this.rng().selectNodeContents(element);
		}
	}
});
/**
 * RichDom for Webkit
 */
xq.RichDomWebkit = xq.Class(xq.RichDomW3, {
	makePlaceHolder: function() {
		var holder = this.createElement("BR");
		holder.className = "webkit-block-placeholder";
		return holder;
	},
	
	makePlaceHolderString: function() {
		return '<br class="webkit-block-placeholder" />';
	},
	
	makeEmptyParagraph: function() {
		return this.createElementFromHtml('<p><br class="webkit-block-placeholder" /></p>');
	},
	
	isPlaceHolder: function(node) {
		return node.nodeName == "BR" && node.className == "webkit-block-placeholder";
	},

	rng: function() {
		var sel = this.sel();
		var rng = this.doc.createRange();
		if (!this._rng ||
			this._anchorNode != sel.anchorNode ||
			this._anchorOffset != sel.anchorOffset ||
			this._focusNode != sel.focusNode ||
			this._focusOffset != sel.focusOffset ) {

			if (sel.type != 'None') {
				rng.setStart(sel.anchorNode, sel.anchorOffset);
				rng.setEnd(sel.focusNode, sel.focusOffset);
			}
			this._anchorNode = sel.anchorNode;
			this._anchorOffset = sel.anchorOffset;
			this._focusNode = sel.focusNode;
			this._focusOffset = sel.focusOffset;
			this._rng = rng;
		}
		return this._rng;
	},

	selectElement: function(element, entireElement) {
		if(!element) throw "[element] is null";
		if(element.nodeType != 1) throw "[element] is not an element";
		
		var rng = this.rng();
		if(entireElement) {
			rng.selectNode(element);
		} else {
			rng.selectNodeContents(element);
		}
		this._setSelectionByRange(rng);
	},

	deleteSelection: function() {
		this.rng().deleteContents();
	},

	collapseSelection: function(toStart) {
		var rng = this.rng();
		rng.collapse(toStart);
		this._setSelectionByRange(rng);
	},

	getSelectionAsHtml: function() {
		var container = this.createElement("div");
		var rng = this.rng();
		var contents = this.rng().cloneContents();
		if(contents) container.appendChild(contents);
		return container.innerHTML;
	},
	
	_setSelectionByRange: function(rng) {
		var sel = this.sel();
		sel.setBaseAndExtent(rng.startContainer, rng.startOffset, rng.endContainer, rng.endOffset);
		this._anchorNode = sel.anchorNode;
		this._anchorOffset = sel.anchorOffset;
		this._focusNode = sel.focusNode;
		this._focusOffset = sel.focusOffset;
	}
});
/**
 * RichDom for Internet Explorer 6 and 7
 */
xq.RichDomTrident = xq.Class(xq.RichDom, {
	makePlaceHolder: function() {
		return this.createTextNode(" ");
	},
	
	makePlaceHolderString: function() {
		return '&nbsp;';
	},
	
	makeEmptyParagraph: function() {
		return this.createElementFromHtml("<p>&nbsp;</p>");
	},

	isPlaceHolder: function(node) {
		return false;
	},

	getOuterHTML: function(element) {
		return element.outerHTML;
	},
	
	insertNode: function(node) {
		if(this.hasSelection()) this.collapseSelection(true);
		
		this.rng().pasteHTML('<span id="xquared_temp"></span>');
		var marker = this.$('xquared_temp');
		if(node.id == 'xquared_temp') return marker;
		
		marker.replaceNode(node);
		return node;
	},
	
	removeTrailingWhitespace: function(block) {
		if(!block) return;
		
		// TODO: reimplement to handle atomic tags and so on. (use DomTree)
		if(this.tree.isBlockContainer(block)) return;
		if(this.isEmptyBlock(block)) return;
		
		var text = block.innerText;
		var lastCharCode = text.charCodeAt(text.length - 1);
		if(text.length <= 1 || [32,160].indexOf(lastCharCode) == -1) return;
		
		var node = block;
		
		while(node && node.nodeType != 3) node = node.lastChild;
		
		if(!node) return;
		
		// DO NOT REMOVE OR MODIFY FOLLOWING CODE:
		//
		// Modifying following code crash IE7
		var nodeValue = node.nodeValue;
		if(nodeValue.length <= 1) {
			this.deleteNode(node, true);
		} else {
			node.nodeValue = nodeValue.substring(0, nodeValue.length - 1);
		}
	},
	
	correctEmptyElement: function(element) {
		if(!element || element.nodeType != 1 || this.tree.isAtomic(element)) return;
		
		if(element.firstChild) {
			this.correctEmptyElement(element.firstChild);
		} else {
			element.innerHTML = "&nbsp;";
		}
	},

	copyAttributes: function(from, to, copyId) {
		to.mergeAttributes(from, !copyId);
	},

	correctParagraph: function() {
		if(!this.hasFocus()) return false;
		if(this.hasSelection()) return false;
		
		var block = this.getCurrentElement();
		
		if(block.nodeName == "BODY") {
			// check for atomic block element such as HR
			block = this.insertNode(this.makeEmptyParagraph());
			var next = block.nextSibling;
			if(this.tree.isAtomic(next)) {
				block = this.insertNodeAt(block, next, "after");
				this.placeCaretAtStartOf(block);
				
				var nextBlock = this.tree.findForward(
					block,
					function(node) {return this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node)}.bind(this)
				);
				if(nextBlock) {
					this.deleteNode(block);
					this.placeCaretAtStartOf(nextBlock);
				}
				return true;
			} else {
				var nextBlock = this.tree.findForward(
					block,
					function(node) {return this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node)}.bind(this)
				);
				
				if(nextBlock) {
					this.deleteNode(block);
					this.placeCaretAtStartOf(nextBlock);
				} else {
					this.placeCaretAtStartOf(block);
				}
				
				return true;
			}
		} else {
			block = this.getCurrentBlockElement();
			if(block.nodeType == 3) block = block.parentNode;
			
			if(this.tree.hasMixedContents(block)) {
				var marker = this.pushMarker();
				this.wrapAllInlineOrTextNodesAs("P", block, true);
				this.popMarker(true);
				return true;
			} else if((this.tree.isTextOrInlineNode(block.previousSibling) || this.tree.isTextOrInlineNode(block.nextSibling)) && this.tree.hasMixedContents(block.parentNode)) {
				// IE?êÏÑú??BlockÍ≥?Inline/TextÍ∞??∏Ï†ë??Í≤ΩÏö∞ getCurrentElement ?±Ïù¥ ?§Ïûë?ôÌïú??
				// ?∞Îùº???ÑÏû¨ Block Ï£ºÎ?ÍπåÏ? ?úÎ≤à???°ÏïÑÏ£ºÏñ¥???úÎã§.
				this.wrapAllInlineOrTextNodesAs("P", block.parentNode, true);
				return true;
			} else {
				return false;
			}
		}
	},
	
	
	
	//////
	// Commands
	execCommand: function(commandId, param) {
		return this.doc.execCommand(commandId, false, param);
	},
	
	applyBackgroundColor: function(color) {
		this.execCommand("BackColor", color);
	},
	
	applyEmphasis: function() {
		// Generate <i> tag. It will be replaced with <emphasis> tag during cleanup phase.
		this.execCommand("Italic");
	},
	applyStrongEmphasis: function() {
		// Generate <b> tag. It will be replaced with <strong> tag during cleanup phase.
		this.execCommand("Bold");
	},
	applyStrike: function() {
		// Generate <strike> tag. It will be replaced with <style class="strike"> tag during cleanup phase.
		this.execCommand("strikethrough");
	},
	applyUnderline: function() {
		// Generate <u> tag. It will be replaced with <em class="underline"> tag during cleanup phase.
		this.execCommand("underline");
	},
	applyRemoveFormat: function() {
		this.execCommand("RemoveFormat");
		this.execCommand("Unlink");
	},
	execHeading: function(level) {
		this.execCommand("FormatBlock", "<H" + level + ">");
	},



	//////
	// Focus/Caret/Selection
	
	focus: function() {
		this.win.focus();
		
		// ?¥Í≤å ?ÜÏúºÎ©?Ï¥àÍ∏∞??caret??P Î∞ñÏóê ?ÑÏπò?òÎ©¥??		// getCurrentElementÎ•??òÎ©¥ PÎ•?Î¶¨ÌÑ¥?òÎäî Í∏∞Ïù¥???ÑÏÉÅ??Î∞úÏÉù.
		if(!this._focusedBefore) {
			this.correctParagraph();
			this.placeCaretAtStartOf(this.getCurrentBlockElement());
			this._focusedBefore = true;
		}
	},

	sel: function() {
		return this.doc.selection;
	},
	
	rng: function() {
		try {
			var sel = this.sel();
			return (sel == null) ? null : sel.createRange();
		} catch(ignored) {
			// IE often fails
			return null;
		}
	},
	
	hasSelection: function() {
		var selectionType = this.sel().type.toLowerCase();
		if("none" == selectionType) return false;
		if("text" == selectionType && this.getSelectionAsHtml().length == 0) return false;
		return true;
	},
	deleteSelection: function() {
		if(this.getSelectionAsText() != "") this.sel().clear();
	},
	
	placeCaretAtStartOf: function(element) {
		// If there's no empty span, caret sometimes moves into a previous node.
		var ph = this.insertNodeAt(this.createElement("SPAN"), element, "start");
		this.selectElement(ph);
		this.collapseSelection(false);
		this.deleteNode(ph);
	},
	
	selectElement: function(element, entireElement) {
		if(!element) throw "[element] is null";
		if(element.nodeType != 1) throw "[element] is not an element";
		
		var rng = this.rng();
		rng.moveToElementText(element);
		rng.select();
	},

	selectBlocksBetween: function(start, end) {
		var rng = this.rng();
		var rngTemp = this.rng();

		rngTemp.moveToElementText(start);
		rng.setEndPoint("StartToStart", rngTemp);
		
		rngTemp.moveToElementText(end);
		rng.setEndPoint("EndToEnd", rngTemp);
		
		rng.select();
	},
	
	collapseSelection: function(toStart) {
		var rng = this.rng();
		rng.collapse(toStart);
		rng.select();
	},
	
	getSelectionAsHtml: function() {
		var rng = this.rng()
		return rng && rng.htmlText ? rng.htmlText : ""
	},
	
	getSelectionAsText: function() {
		var rng = this.rng();
		return rng && rng.text ? rng.text : "";
	},
	
	hasImportantAttributes: function(element) {
		return !!(element.id || element.className || element.style.cssText);
	},

	isEmptyBlock: function(element) {
		if(!element.hasChildNodes()) return true;
		if(element.nodeType == 3 && !element.nodeValue) return true;
		if(["&nbsp;", " ", ""].indexOf(element.innerHTML) != -1) return true;
		
		return false;
	},
	
	getLastChild: function(element) {
		if(!element || !element.hasChildNodes()) return null;
		
		var nodes = xq.$A(element.childNodes).reverse();
		
		for(var i = 0; i < nodes.length; i++) {
			if(nodes[i].nodeType != 3 || nodes[i].nodeValue.length != 0) return nodes[i];
		}
		
		return null;
	},
	
	getCurrentElement: function() {
		if(this.sel().type.toLowerCase() == "control") return this.rng().item(0);
		return this.rng().parentElement();
	},
	
	getBlockElementAtSelectionStart: function() {
		var rng = this.rng();
		var dup = rng.duplicate();
		dup.collapse(true);
		
		var result = this.getParentBlockElementOf(dup.parentElement());
		if(result.nodeName == "BODY") result = result.firstChild;
		
		return result;
	},
	
	getBlockElementAtSelectionEnd: function() {
		var rng = this.rng();
		var dup = rng.duplicate();
		dup.collapse(false);
		
		var result = this.getParentBlockElementOf(dup.parentElement());
		if(result.nodeName == "BODY") result = result.lastChild;

		return result;
	},
	
	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {
		return [
			this.getBlockElementAtSelectionStart(),
			this.getBlockElementAtSelectionEnd()
		];
	},
	
	isCaretAtBlockStart: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		var node = this.getCurrentBlockElement();
		var marker = this.pushMarker();
		
		var isTrue = false;
		while (node = this.getFirstChild(node)) {
			if (node == marker) {
				isTrue = true;
				break;
			}
		}
		
		this.popMarker();
		
		return isTrue;
	},
	isCaretAtBlockEnd: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		var node = this.getCurrentBlockElement();
		var marker = this.pushMarker();
		var isTrue = false;
		while (node = this.getLastChild(node)) {
			var nodeValue = node.nodeValue;
			
			if (node == marker) {
				isTrue = true;
				break;
			} else if(
				node.nodeType == 3 &&
				node.previousSibling == marker &&
				(nodeValue == " " || (nodeValue.length == 1 && nodeValue.charCodeAt(0) == 160))
			) {
				isTrue = true;
				break;
			}
		}
		
		this.popMarker();
		return isTrue;
	},
	saveSelection: function() {
		return this.rng();
	},
	restoreSelection: function(bookmark) {
		bookmark.select();
	}
});
xq.RichTable = xq.Class({
	initialize: function(rdom, table) {
		xq.addToFinalizeQueue(this);

		this.rdom = rdom;
		this.table = table;
	},
	insertNewRowAt: function(tr, where) {
		var row = this.rdom.createElement("TR");
		var cells = tr.cells;
		for(var i = 0; i < cells.length; i++) {
			var cell = this.rdom.createElement(cells[i].nodeName);
			this.rdom.correctEmptyElement(cell);
			row.appendChild(cell);
		}
		return this.rdom.insertNodeAt(row, tr, where);
	},
	insertNewCellAt: function(cell, where) {
		// collect cells;
		var cells = [];
		var x = this.getXIndexOf(cell);
		var y = 0;
		while(true) {
			var cur = this.getCellAt(x, y);
			if(!cur) break;
			cells.push(cur);
			y++;
		}
		
		// insert new cells
		for(var i = 0; i < cells.length; i++) {
			var cell = this.rdom.createElement(cells[i].nodeName);
			this.rdom.correctEmptyElement(cell);
			this.rdom.insertNodeAt(cell, cells[i], where);
		}
	},
	deleteRow: function(tr) {
		return this.rdom.removeBlock(tr);
	},
	deleteCell: function(cell) {
		if(!cell.previousSibling && !cell.nextSibling) {
			this.rdom.deleteNode(this.table);
			return;
		}
		
		// collect cells;
		var cells = [];
		var x = this.getXIndexOf(cell);
		var y = 0;
		while(true) {
			var cur = this.getCellAt(x, y);
			if(!cur) break;
			cells.push(cur);
			y++;
		}
		
		for(var i = 0; i < cells.length; i++) {
			this.rdom.deleteNode(cells[i]);
		}
	},
	getPreviousCellOf: function(cell) {
		if(cell.previousSibling) return cell.previousSibling;
		var adjRow = this.getPreviousRowOf(cell.parentNode);
		if(adjRow) return adjRow.lastChild;
		return null;
	},
	getNextCellOf: function(cell) {
		if(cell.nextSibling) return cell.nextSibling;
		var adjRow = this.getNextRowOf(cell.parentNode);
		if(adjRow) return adjRow.firstChild;
		return null;
	},
	getPreviousRowOf: function(row) {
		if(row.previousSibling) return row.previousSibling;
		var rowContainer = row.parentNode;
		if(rowContainer.previousSibling && rowContainer.previousSibling.lastChild) return rowContainer.previousSibling.lastChild;
		return null;
	},
	getNextRowOf: function(row) {
		if(row.nextSibling) return row.nextSibling;
		var rowContainer = row.parentNode;
		if(rowContainer.nextSibling && rowContainer.nextSibling.firstChild) return rowContainer.nextSibling.firstChild;
		return null;
	},
	getAboveCellOf: function(cell) {
		var row = this.getPreviousRowOf(cell.parentNode);
		if(!row) return null;
		
		var x = this.getXIndexOf(cell);
		return row.cells[x];
	},
	getBelowCellOf: function(cell) {
		var row = this.getNextRowOf(cell.parentNode);
		if(!row) return null;
		
		var x = this.getXIndexOf(cell);
		return row.cells[x];
	},
	getXIndexOf: function(cell) {
		var row = cell.parentNode;
		for(var i = 0; i < row.cells.length; i++) {
			if(row.cells[i] == cell) return i;
		}
		
		return -1;
	},
	getYIndexOf: function(cell) {
		var y = -1;
		
		// find y
		var group = row.parentNode;
		for(var i = 0; i <group.rows.length; i++) {
			if(group.rows[i] == row) {
				y = i;
				break;
			}
		}
		if(this.hasHeadingAtTop() && group.nodeName == "TBODY") y = y + 1;
		
		return y;
	},
	/**
	 * TODO: Not used. Delete or not?
	 */
	getLocationOf: function(cell) {
		var x = this.getXIndexOf(cell);
		var y = this.getYIndexOf(cell);
		return {x:x, y:y};
	},
	getCellAt: function(col, row) {
		var row = this.getRowAt(row);
		return (row && row.cells.length > col) ? row.cells[col] : null;
	},
	getRowAt: function(index) {
		if(this.hasHeadingAtTop()) {
			return index == 0 ? this.table.tHead.rows[0] : this.table.tBodies[0].rows[index - 1];
		} else {
			var rows = this.table.tBodies[0].rows;
			return (rows.length > index) ? rows[index] : null;
		}
	},
	getDom: function() {
		return this.table;
	},
	hasHeadingAtTop: function() {
		return !!(this.table.tHead && this.table.tHead.rows[0]);
	},
	hasHeadingAtLeft: function() {
		return this.table.tBodies[0].rows[0].cells[0].nodeName == "TH";
	},
	correctEmptyCells: function() {
		var cells = xq.$A(this.table.getElementsByTagName("TH"));
		var tds = xq.$A(this.table.getElementsByTagName("TD"));
		for(var i = 0; i < tds.length; i++) {
			cells.push(tds[i]);
		}
		
		for(var i = 0; i < cells.length; i++) {
			if(this.rdom.isEmptyBlock(cells[i])) this.rdom.correctEmptyElement(cells[i])
		}
	}
});

xq.RichTable.create = function(rdom, cols, rows, headerPositions) {
	if(["t", "tl", "lt"].indexOf(headerPositions) != -1) var headingAtTop = true
	if(["l", "tl", "lt"].indexOf(headerPositions) != -1) var headingAtLeft = true

	var sb = []
	sb.push('<table class="datatable">')
	
	// thead
	if(headingAtTop) {
		sb.push('<thead><tr>')
		for(var i = 0; i < cols; i++) sb.push('<th></th>')
		sb.push('</tr></thead>')
		rows -= 1
	}
		
	// tbody
	sb.push('<tbody>')
	for(var i = 0; i < rows; i++) {
		sb.push('<tr>')
		
		for(var j = 0; j < cols; j++) {
			if(headingAtLeft && j == 0) {
				sb.push('<th></th>')
			} else {
				sb.push('<td></td>')
			}
		}
		
		sb.push('</tr>')
	}
	sb.push('</tbody>')
	
	sb.push('</table>')
	
	// create DOM element
	var container = rdom.createElement("div");
	container.innerHTML = sb.join("");
	
	// correct empty cells and return
	var rtable = new xq.RichTable(rdom, container.firstChild);
	rtable.correctEmptyCells();
	return rtable;
}
/**
 * Validates and invalidates designmode contents
 */
xq.Validator = xq.Class({
	initialize: function(curUrl, urlValidationMode, allowedTags, allowedAttrs) {
		xq.addToFinalizeQueue(this);

		this.allowedTags = (allowedTags || ['a', 'abbr', 'acronym', 'address', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'dfn', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'img', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'span', 'sup', 'sub', 'strong', 'table', 'thead', 'tbody', 'td', 'th', 'tr', 'ul', 'var']).join(' ') + ' ';
		this.allowedAttrs = (allowedAttrs || ['alt', 'cite', 'class', 'datetime', 'height', 'href', 'id', 'rel', 'rev', 'src', 'style', 'title', 'width']).join(' ') + ' ';
		
		this.curUrl = curUrl;
		this.curUrlParts = curUrl ? curUrl.parseURL() : null;
		this.urlValidationMode = urlValidationMode;
	},
	
	/**
	 * Perform validation on given element
	 *
	 * @param {Element} element Target element. It is not affected by validation.
	 * @param {boolean} fullValidation Perform full validation. If you just want to use the result to assign innerHTML, set it false
	 *
	 * @returns {String} Validated HTML string
	 */
	validate: function(element, fullValidation) {throw "Not implemented"},
	
	/**
	 * Perform invalidation on given element to make the designmode works well.
	 *
	 * @param {Element} element Target element.
	 * @returns {String} Invalidated HTML string
	 */
	invalidate: function(element) {throw "Not implemented"},
	
	validateStrike: function(content) {
		content = content.replace(/<strike(>|\s+[^>]*>)/ig, "<span class=\"strike\"$1");
		content = content.replace(/<\/strike>/ig, "</span>");
		return content;
	},
	
	validateUnderline: function(content) {
		content = content.replace(/<u(>|\s+[^>]*>)/ig, "<em class=\"underline\"$1");
		content = content.replace(/<\/u>/ig, "</em>");
		return content;
	},
	
	replaceTag: function(content, from, to) {
		return content.replace(new RegExp("(</?)" + from + "(>|\\s+[^>]*>)", "ig"), "$1" + to + "$2");
	},
	
	validateSelfClosingTags: function(content) {
		return content.replace(/<(br|hr|img)([^>]*?)>/img, function(str, tag, attrs) {
			return "<" + tag + attrs + " />"
		});
	},
	
	removeComments: function(content) {
		return content.replace(/<!--.*?-->/img, '');
	},
	
	removeDangerousElements: function(element) {
		var scripts = xq.$A(element.getElementsByTagName('SCRIPT')).reverse();
		for(var i = 0; i < scripts.length; i++) {
			scripts[i].parentNode.removeChild(scripts[i]);
		}
	},

	// TODO: very slow
	applyWhitelist: function(content) {
		var allowedTags = this.allowedTags;
		var allowedAttrs = this.allowedAttrs;
		
		return content.replace(new RegExp("(</?)([^>]+?)(>|\\s+([^>]*?)(\\s?/?)>)", "g"), function(str, head, tag, tail, attrs, selfClosing) {
			if(allowedTags.indexOf(tag) == -1) return '';
			
			if(attrs) {
				attrs = attrs.replace(/(^|\s")([^"=]+)(\s|$)/g, '$1$2="$2"$3'); // for IE
				
				var sb = [];
				var m = attrs.match(/([^=]+)="[^"]*?"/g);
				for(var i = 0; i < m.length; i++) {
					m[i] = m[i].strip();
					var name = m[i].split('=')[0];
					if(allowedAttrs.indexOf(name) != -1) sb.push(m[i]);
				}
				attrs = sb.join(' ');
				if(attrs != '') attrs = ' ' + attrs;
				return head + tag + attrs + selfClosing + '>';
			} else {
				return str;
			}
		});
	},
	
	makeUrlsRelative: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(/(href|src)="([^"]+)"/g, function(str, name, url) {
					// 3. first, make it absolute
					var abs = null;
					if(url.charAt(0) == '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) == '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) == '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(/^\w+:\/\//)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}
					
					// 4. make it relative by removing same part
					var rel = abs;
					
					if(abs.indexOf(urlParts.includeQuery) == 0) {
						rel = abs.substring(urlParts.includeQuery.length);
					} else if(abs.indexOf(urlParts.includePath) == 0) {
						rel = abs.substring(urlParts.includePath.length);
					} else if(abs.indexOf(urlParts.includeBase) == 0) {
						rel = abs.substring(urlParts.includeBase.length);
					} else if(abs.indexOf(urlParts.includeHost) == 0) {
						rel = abs.substring(urlParts.includeHost.length);
					}
					if(rel == '') rel = '#';
					
					return name + '="' + rel + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
		
		return content;
	},
	
	makeUrlsHostRelative: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(/(href|src)="([^"]+)"/g, function(str, name, url) {
					// 3. first, make it absolute
					var abs = null;
					if(url.charAt(0) == '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) == '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) == '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(/^\w+:\/\//)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}
					
					// 4. make it relative by removing same part
					var rel = abs;
					if(abs.indexOf(urlParts.includeHost) == 0) {
						rel = abs.substring(urlParts.includeHost.length);
					}
					if(rel == '') rel = '#';
					
					return name + '="' + rel + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
		
		return content;
	},
	
	makeUrlsAbsolute: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(/(href|src)="([^"]+)"/g, function(str, name, url) {
					var abs = null;
					if(url.charAt(0) == '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) == '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) == '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(/^\w+:\/\//)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}

					return name + '="' + abs + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
	}
});

/**
 * Creates and returns instance of browser specific implementation.
 */
xq.Validator.createInstance = function(curUrl, urlValidationMode, allowedTags, allowedAttrs) {
	if(xq.Browser.isTrident) {
		return new xq.ValidatorTrident(curUrl, urlValidationMode, allowedTags, allowedAttrs);
	} else if(xq.Browser.isWebkit) {
		return new xq.ValidatorWebkit(curUrl, urlValidationMode, allowedTags, allowedAttrs);
	} else {
		return new xq.ValidatorGecko(curUrl, urlValidationMode, allowedTags, allowedAttrs);
	}
}
/**
 * Validator for W3C Standard Engine
 */
xq.ValidatorW3 = xq.Class(xq.Validator, {
	validate: function(element, fullValidation) {
		element = element.cloneNode(true);

		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);
		rdom.removePlaceHoldersAndEmptyNodes(element);
		this.removeDangerousElements(element);
		this.validateFontColor(element);

		var content = element.innerHTML;
		
		try {
			content = this.replaceTag(content, "b", "strong");
			content = this.replaceTag(content, "i", "em");
			
			content = this.validateStrike(content);
			content = this.validateUnderline(content);
			content = this.addNbspToEmptyBlocks(content);
			
			if(fullValidation) content = this.performFullValidation(content);
		} catch(ignored) {}

		// insert newline between block-tags
		var blocks = rdom.tree.getBlockTags().join("|");
		var regex = new RegExp("</(" + blocks + ")>([^\n])", "img");
		content = content.replace(regex, '</$1>\n$2');
		
		return content;
	},
	invalidate: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);
		
		// <span class="strike"> -> <strike>
		var strikes = xq.getElementsByClassName(rdom.getRoot(), "strike");
		for(var i = 0; i < strikes.length; i++) {
			if("SPAN" == strikes[i].nodeName) rdom.replaceTag("strike", strikes[i]).removeAttribute("class");
		}
		
		// <em|i class="underline"> -> <u>
		var underlines = xq.getElementsByClassName(rdom.getRoot(), "underline");
		for(var i = 0; i < underlines.length; i++) {
			if(["EM", "I"].indexOf(underlines[i].nodeName) != -1) rdom.replaceTag("u", underlines[i]).removeAttribute("class");
		}
		
		var content = rdom.getRoot().innerHTML;
		
		content = this.replaceTag(content, "strong", "b");
		content = this.replaceTag(content, "em", "i");
		content = this.removeComments(content);
		content = this.replaceNbspToBr(content);
		
		return content;
	},
	
	performFullValidation: function(content) {
		content = this.validateSelfClosingTags(content);
		content = this.applyWhitelist(content);
		
		if(this.urlValidationMode == 'relative') {
			content = this.makeUrlsRelative(content);
		} else if(this.urlValidationMode == 'host_relative') {
			content = this.makeUrlsHostRelative(content);
		} else if(this.urlValidationMode == 'absolute') {
			content = this.makeUrlsAbsolute(content);
		}

		return content;
	},
	
	validateFontColor: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);

		var fonts = xq.$A(element.getElementsByTagName('FONT')).reverse();
		for(var i = 0; i < fonts.length; i++) {
			var font = fonts[i];
			var color = font.getAttribute('color');
			
			if(color) {
				var span = rdom.replaceTag("span", font);
				span.removeAttribute('color');
				span.style.color = color;
			}
		}
	},
	
	addNbspToEmptyBlocks: function(content) {
		var blocks = new xq.DomTree().getBlockTags().join("|");
		var regex = new RegExp("<(" + blocks + ")>\\s*?</(" + blocks + ")>", "img");
		return content.replace(regex, '<$1>&nbsp;</$2>');
	},
	
	replaceNbspToBr: function(content) {
		var blocks = new xq.DomTree().getBlockTags().join("|");
		var regex = new RegExp("<(" + blocks + ")>(&nbsp;)?</(" + blocks + ")>", "img");
		var rdom = xq.RichDom.createInstance();
		return content.replace(regex, '<$1>' + rdom.makePlaceHolderString() + '</$3>');
	}
});
/**
 * Validator for Gecko Engine
 */
xq.ValidatorGecko = xq.Class(xq.ValidatorW3, {
});
/**
 * Validator for Webkit
 */
xq.ValidatorWebkit = xq.Class(xq.ValidatorW3, {
});

/*
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.fontStyle == "italic") em = true;
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.fontWeight == "bold") strong = true;
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.textDecoration == "line-through") strike = true;
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.textDecoration == "underline") underline = true;
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.cssText.indexOf("vertical-align: super;") != -1) superscription = true;
if(node.nodeName == "SPAN" && node.className == "Apple-style-span" && node.style.cssText.indexOf("vertical-align: sub;") != -1) subscription = true;
*/
/**
 * Validator for Internet Explorer 6 and 7
 */
xq.ValidatorTrident = xq.Class(xq.Validator, {
	validate: function(element, fullValidation) {
		element = element.cloneNode(true);
		
		this.removeDangerousElements(element);
		this.validateFontColor(element);
		this.validateBackgroundColor(element);
		
		var content = element.innerHTML;
		
		try {
			content = this.validateStrike(content);
			content = this.validateUnderline(content);
			
			if(fullValidation) content = this.performFullValidation(content);
		} catch(ignored) {}
		
		return content;
	},
	
	invalidate: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);
		
		this.invalidateFontColor(element);
		this.invalidateBackgroundColor(element);
		
		// <span class="strike"> -> <strike>
		var strikes = xq.getElementsByClassName(rdom.getRoot(), "strike");
		for(var i = 0; i < strikes.length; i++) {
			if("SPAN" == strikes[i].nodeName) rdom.replaceTag("strike", strikes[i]).removeAttribute("className");
		}
		
		// <em|i class="underline"> -> <u>
		var underlines = xq.getElementsByClassName(rdom.getRoot(), "underline");
		for(var i = 0; i < underlines.length; i++) {
			if(["EM", "I"].indexOf(underlines[i].nodeName) != -1) rdom.replaceTag("u", underlines[i]).removeAttribute("className");
		}

		var content = rdom.getRoot().innerHTML;

		content = this.removeComments(content);
		
		return content;
	},
	
	performFullValidation: function(content) {
		content = this.lowerTagNamesAndUniformizeQuotation(content);
		content = this.validateSelfClosingTags(content);
		content = this.applyWhitelist(content);
		
		if(this.urlValidationMode == 'relative') {
			content = this.makeUrlsRelative(content);
		} else if(this.urlValidationMode == 'host_relative') {
			content = this.makeUrlsHostRelative(content);
		} else if(this.urlValidationMode == 'absolute') {
			// Trident always use absolute URL so we don't need to do anything.
			//
			// content = this.makeUrlsAbsolute(content);
		}
		
		return content;
	},
	
	validateFontColor: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);
		
		// It should be reversed to deal with nested elements
		var fonts = xq.$A(element.getElementsByTagName('FONT')).reverse();
		for(var i = 0; i < fonts.length; i++) {
			var font = fonts[i];
			var color = font.getAttribute('color');
			
			if(color) {
				var span = rdom.replaceTag("span", font);
				span.removeAttribute('color');
				span.style.color = color;
			}
		}
	},

	invalidateFontColor: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);

		var spans = xq.$A(element.getElementsByTagName('SPAN')).reverse();
		for(var i = 0; i < spans.length; i++) {
			var span = spans[i];
			var color = span.style.color;
			
			if(color) {
				var font = rdom.replaceTag("font", span);
				font.style.color = "";
				font.setAttribute('color', color);
			}
		}
	},

	validateBackgroundColor: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);

		// It should be reversed to deal with nested elements
		var fonts = xq.$A(element.getElementsByTagName('FONT')).reverse();
		for(var i = 0; i < fonts.length; i++) {
			if(fonts[i].style.color || fonts[i].style.backgroundColor) rdom.replaceTag("span", fonts[i]);
		}
	},

	invalidateBackgroundColor: function(element) {
		var rdom = xq.RichDom.createInstance();
		rdom.setRoot(element);

		// It should be reversed to deal with nested elements
		var spans = xq.$A(element.getElementsByTagName('SPAN')).reverse();
		for(var i = 0; i < spans.length; i++) {
			if(spans[i].style.color || spans[i].style.backgroundColor) rdom.replaceTag("font", spans[i]);
		}
	},
	
	lowerTagNamesAndUniformizeQuotation: function(content) {
		// Uniformize quotation, turn tag names and attribute names into lower case
		content = content.replace(/<(\/?)(\w+)([^>]*?)>/img, function(str, closingMark, tagName, attrs) {
			return "<" + closingMark + tagName.toLowerCase() + this.correctHtmlAttrQuotation(attrs) + ">";
		}.bind(this));
		
		return content;
	},
	
	correctHtmlAttrQuotation: function(html) {
		html = html.replace(/\s(\w+?)=\s+"([^"]+)"/mg,function (str, name, value) {return " " + name.toLowerCase() + '=' + '"' + value + '"'});
		html = html.replace(/\s(\w+?)=([^ "]+)/mg,function (str, name, value) {return " " + name.toLowerCase() + '=' + '"' + value + '"'});
		return html;
	}
});
/**
 * @fileOverview xq.EditHistory manages editing history and performs UNDO/REDO.
 */
xq.EditHistory = xq.Class({
    /**
	 * Initializer
	 *
     * @constructor
	 * @param {xq.RichDom} rdom RichDom instance
	 * @param {Number} [max] maximum UNDO buffer size(default value is 100).
	 */
	initialize: function(rdom, max) {
		xq.addToFinalizeQueue(this);
		if (!rdom) throw "IllegalArgumentException";

		this.disabled = false;
		this.max = max || 100;
		this.rdom = rdom;
		this.root = rdom.getRoot();
		this.clear();
		
		this.lastModified = Date.get();
	},
	getLastModifiedDate: function() {
		return this.lastModified;
	},
	isUndoable: function() {
		return this.queue.length > 0 && this.index > 0;
	},
	isRedoable: function() {
		return this.queue.length > 0 && this.index < this.queue.length - 1;
	},
	disable: function() {
		this.disabled = true;
	},
	enable: function() {
		this.disabled = false;
	},
	undo: function() {
		this.pushContent();
		
		if (this.isUndoable()) {
			this.index--;
			this.popContent();
			return true;
		} else {
			return false;
		}
	},
	redo: function() {
		if (this.isRedoable()) {
			this.index++;
			this.popContent();
			return true;
		} else {
			return false;
		}
	},
	onCommand: function() {
		this.lastModified = Date.get();
		if(this.disabled) return false;

		return this.pushContent();
	},
	onEvent: function(event) {
		this.lastModified = Date.get();
		if(this.disabled) return false;

		// ignore normal keys
		if('keydown' == event.type && !(event.ctrlKey || event.metaKey)) return false;
		if(['keydown', 'keyup', 'keypress'].indexOf(event.type) != -1 && !event.ctrlKey && !event.altKey && !event.metaKey && [33,34,35,36,37,38,39,40].indexOf(event.keyCode) == -1) return false;
		if(['keydown', 'keyup', 'keypress'].indexOf(event.type) != -1 && (event.ctrlKey || event.metaKey) && [89,90].indexOf(event.keyCode) != -1) return false;
		
		// ignore ctrl/shift/alt/meta keys
		if([16,17,18,224].indexOf(event.keyCode) != -1) return false;
		
		return this.pushContent();
	},
	popContent: function() {
		this.lastModified = Date.get();
		var entry = this.queue[this.index];
		if (entry.caret > 0) {
			var html=entry.html.substring(0, entry.caret) + '<span id="caret_marker_00700"></span>' + entry.html.substring(entry.caret);
			this.root.innerHTML = html;
		} else {
			this.root.innerHTML = entry.html;
		}
		this.restoreCaret();
	},
	pushContent: function(ignoreCaret) {
		if(xq.Browser.isTrident && !ignoreCaret && !this.rdom.hasFocus()) return false;
		if(!this.rdom.getCurrentElement()) return false;
		
		var html = this.root.innerHTML;
		if(html == (this.queue[this.index] ? this.queue[this.index].html : null)) return false;
		var caret = ignoreCaret ? -1 : this.saveCaret();
		
		if(this.queue.length >= this.max) {
			this.queue.shift();
		} else {
			this.index++;
		}
		
		this.queue.splice(this.index, this.queue.length - this.index, {html:html, caret:caret});
		return true;
	},
	clear: function() {
		this.index = -1;
		this.queue = [];
		this.pushContent(true);
	},
	saveCaret: function() {
		if(this.rdom.hasSelection()) return null;

		// FF on Mac has a caret problem with these lines. --2007/11/19
		var marker = this.rdom.pushMarker();
		var str = xq.Browser.isTrident ? '<SPAN class='+marker.className : '<span class="'+marker.className+'"';
		var caret = this.rdom.getRoot().innerHTML.indexOf(str);
		this.rdom.popMarker(true);

		return caret;

/*
		// This is old code. It also has same problem.
		
		if(this.rdom.hasSelection()) return null;
		
		var bookmark = this.rdom.saveSelection();
		var marker = this.rdom.pushMarker();
		
		var str = xq.Browser.isTrident ? '<SPAN class='+marker.className : '<span class="'+marker.className+'"';
		var caret = this.rdom.getRoot().innerHTML.indexOf(str);
		
		this.rdom.popMarker();
		this.rdom.restoreSelection(bookmark);
		
		return caret;
*/
	},
	restoreCaret: function() {
		var marker = this.rdom.$('caret_marker_00700');
		
		if(marker) {
			this.rdom.selectElement(marker, true);
			this.rdom.collapseSelection(false);
			this.rdom.deleteNode(marker);
		} else {
			var node = this.rdom.tree.findForward(this.rdom.getRoot(), function(node) {
				return this.isBlock(node) && !this.hasBlocks(node);
			}.bind(this.rdom.tree));
			this.rdom.selectElement(node, false);
			this.rdom.collapseSelection(false);
			
		}
	}
});
/**
 * @fileOverview xq.controls provides common UI elements such as dialog.
 */
xq.controls = {};



xq.controls.FormDialog = xq.Class({
	/**
     * @constructor
     *
     * @param {String} html HTML string which contains FORM
     * @param {Function} [onLoadHandler] callback function to be called when the form is loaded
	 */
	initialize: function(xed, html, onLoadHandler, onCloseHandler) {
		xq.addToFinalizeQueue(this);

		this.xed = xed;
		this.html = html;
		this.onLoadHandler = onLoadHandler || function() {};
		this.onCloseHandler = onCloseHandler || function() {};
		this.form = null;
	},
	/**
	 * Show dialog
	 *
	 * @param {Object} [options] collection of options
	 */
	show: function(options) {
		options = options || {};
		options.position = options.position || 'centerOfWindow';
		options.mode = options.mode || 'modal';
		options.cancelOnEsc = options.cancelOnEsc || true;
		
		var self = this;
		
		// create and append container
		var container = document.createElement('DIV');
		container.style.display = 'none';
		document.body.appendChild(container);
		
		// initialize form
		container.innerHTML = this.html;
		this.form = container.getElementsByTagName('FORM')[0];
		
		this.form.onsubmit = function() {
			self.onCloseHandler(xq.serializeForm(this));
			self.close();
			return false;
		};
		
		var cancelButton = xq.getElementsByClassName(this.form, 'cancel')[0];
		cancelButton.onclick = function() {
			self.onCloseHandler();
			self.close();
		};
		
		// append dialog
		document.body.appendChild(this.form);
		container.parentNode.removeChild(container);
		
		// place dialog to center of window
		this.setPosition(options.position);
		
		// give focus
		var elementToFocus = xq.getElementsByClassName(this.form, 'initialFocus');
		if(elementToFocus.length > 0) elementToFocus[0].focus();
		
		// handle cancelOnEsc option
		if(options.cancelOnEsc) {
			xq.observe(this.form, 'keydown', function(e) {
				if(e.keyCode == 27) {
					this.onCloseHandler();
					this.close();
				}
				return false;
			}.bind(this));
		}
		
		this.onLoadHandler(this);
	},
	
	close: function() {
		this.form.parentNode.removeChild(this.form);
	},
	
	setPosition: function(target) {
		var targetElement = null;
		var left = 0;
		var top = 0;
		
		if(target == 'centerOfWindow') {
			targetElement = document.documentElement;
		} else if(target == 'centerOfEditor') {
			targetElement = this.xed.getFrame();
			var o = targetElement;
			do {
				left += o.offsetLeft;
				top += o.offsetTop;
			} while(o = o.offsetParent)
		} else if(target == 'nearbyCaret') {
			throw "Not implemented yet";
		} else {
			throw "Invalid argument: " + target;
		}
		
		var targetWidth = targetElement.clientWidth;
		var targetHeight = targetElement.clientHeight;
		var dialogWidth = this.form.clientWidth;
		var dialogHeight = this.form.clientHeight;
		
		left += parseInt((targetWidth - dialogWidth) / 2);
		top += parseInt((targetHeight - dialogHeight) / 2);
		
		this.form.style.left = left + "px";
		this.form.style.top = top + "px";
	}
})



xq.controls.QuickSearchDialog = xq.Class({
	/**
     * @constructor
	 */
	initialize: function(xed, param) {
		xq.addToFinalizeQueue(this);
		this.xed = xed;
		
		this.rdom = xq.RichDom.createInstance();
		this.rdom.setRoot(document.body);
		
		this.param = param;
		if(!this.param.renderItem) this.param.renderItem = function(item) {
			return this.rdom.getInnerText(item);
		}.bind(this);
		
		this.container = null;
	},
	
	getQuery: function() {
		if(!this.container) return "";
		return this._getInputField().value;
	},
	
	onSubmit: function(e) {
		if(this.matchCount() > 0) {
			this.param.onSelect(this.xed, this.list[this._getSelectedIndex()]);
		}
		
		this.close();
		xq.stopEvent(e);
		return false;
	},
	
	onCancel: function(e) {
		if(this.param.onCancel) this.param.onCancel(this.xed);
		this.close();
	},
	
	onBlur: function(e) {
		// TODO: Ugly
		setTimeout(function() {this.onCancel(e)}.bind(this), 400);
	},
	
	onKey: function(e) {
		var esc = new xq.Shortcut("ESC");
		var enter = new xq.Shortcut("ENTER");
		var up = new xq.Shortcut("UP");
		var down = new xq.Shortcut("DOWN");
		
		if(esc.matches(e)) {
			this.onCancel(e);
		} else if(enter.matches(e)) {
			this.onSubmit(e);
		} else if(up.matches(e)) {
			this._moveSelectionUp();
		} else if(down.matches(e)) {
			this._moveSelectionDown();
		} else {
			this.updateList();
		}
	},
	
	onClick: function(e) {
		var target = e.srcElement || e.target;
		if(target.nodeName == "LI") {
			
			var index = this._getIndexOfLI(target);
			this.param.onSelect(this.xed, this.list[index]);
		}
	},
	
	onList: function(list) {
		this.list = list;
		this.renderList(list);
	},
	
	updateList: function() {
		window.setTimeout(function() {
			this.param.listProvider(this.getQuery(), this.xed, this.onList.bind(this));
		}.bind(this), 0);
	},
	
	renderList: function(list) 
	{
		var ol = this._getListContainer();
		ol.innerHTML = "";
		
		for(var i = 0; i < list.length; i++) {
			var li = this.rdom.createElement('LI');
			li.innerHTML = this.param.renderItem(list[i]);
			ol.appendChild(li);
		}
		
		if(ol.hasChildNodes()) {
			ol.firstChild.className = "selected";
		}
	},
	
	show: function() {
		if(!this.container) this.container = this._create();
		
		var dialog = this.rdom.insertNodeAt(this.container, this.rdom.getRoot(), "end");
		this.setPosition('centerOfEditor');
		this.updateList();
		this.focus();
	},
	
	close: function() {
		this.rdom.deleteNode(this.container);
	},
	
	focus: function() {
		this._getInputField().focus();
	},
	
	setPosition: function(target) {
		var targetElement = null;
		var left = 0;
		var top = 0;
		
		if(target == 'centerOfWindow') {
			targetElement = document.documentElement;
		} else if(target == 'centerOfEditor') {
			targetElement = this.xed.getFrame();
			var o = targetElement;
			do {
				left += o.offsetLeft;
				top += o.offsetTop;
			} while(o = o.offsetParent)
		} else if(target == 'nearbyCaret') {
			throw "Not implemented yet";
		} else {
			throw "Invalid argument: " + target;
		}
		
		var targetWidth = targetElement.clientWidth;
		var targetHeight = targetElement.clientHeight;
		var dialogWidth = this.container.clientWidth;
		var dialogHeight = this.container.clientHeight;
		
		left += parseInt((targetWidth - dialogWidth) / 2);
		top += parseInt((targetHeight - dialogHeight) / 2);
		
		this.container.style.left = left + "px";
		this.container.style.top = top + "px";
	},
	
	matchCount: function() {
		return this.list ? this.list.length : 0;
	},
	
	_create: function() {
		// make container
		var container = this.rdom.createElement("DIV");
		container.className = "xqQuickSearch";
		
		// make title
		if(this.param.title) {
			var title = this.rdom.createElement("H1");
			title.innerHTML = this.param.title;
			container.appendChild(title);
		}
		
		// make input field
		var inputWrapper = this.rdom.createElement("DIV");
		inputWrapper.className = "input";
		var form = this.rdom.createElement("FORM");
		var input = this.rdom.createElement("INPUT");
		input.type = "text";
		input.value = "";
    	form.appendChild(input);
		inputWrapper.appendChild(form);
		container.appendChild(inputWrapper);
		
		// make list
		var list = this.rdom.createElement("OL");

	    xq.observe(input, 'blur', this.onBlur.bindAsEventListener(this));
    	xq.observe(input, 'keypress', this.onKey.bindAsEventListener(this));
    	xq.observe(list, 'click', this.onClick.bindAsEventListener(this), true);
    	xq.observe(form, 'submit', this.onSubmit.bindAsEventListener(this));
    	xq.observe(form, 'reset', this.onCancel.bindAsEventListener(this));

		container.appendChild(list);
		return container;
	},
	
	_getInputField: function() {
		return this.container.getElementsByTagName('INPUT')[0];
	},
	
	_getListContainer: function() {
		return this.container.getElementsByTagName('OL')[0];
	},
	
	_getSelectedIndex: function() {
		var ol = this._getListContainer();
		for(var i = 0; i < ol.childNodes.length; i++) {
			if(ol.childNodes[i].className == 'selected') return i;
		}
	},
	
	_getIndexOfLI: function(li) {
		var ol = this._getListContainer();
		for(var i = 0; i < ol.childNodes.length; i++) {
			if(ol.childNodes[i] == li) return i;
		}
	},
	
	_moveSelectionUp: function() {
		var count = this.matchCount();
		if(count == 0) return;
		var index = this._getSelectedIndex();
		var ol = this._getListContainer();
		ol.childNodes[index].className = "";
		
		index--;
		if(index < 0) index = count - 1;

		ol.childNodes[index].className = "selected";
	},
	
	_moveSelectionDown: function() {
		var count = this.matchCount();
		if(count == 0) return;
		var index = this._getSelectedIndex();
		var ol = this._getListContainer();
		ol.childNodes[index].className = "";
		
		index++;
		if(index >= count) index = 0;
		
		ol.childNodes[index].className = "selected";
	}
});
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicColorPickerDialog='<form action="#" class="xqFormDialog xqBasicColorPickerDialog">\n		<div>\n			<label>\n				<input type="radio" class="initialFocus" name="color" value="black" checked="checked" />\n				<span style="color: black;">Black</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="red" />\n				<span style="color: red;">Red</span>\n			</label>\n				<input type="radio" name="color" value="yellow" />\n				<span style="color: yellow;">Yellow</span>\n			</label>\n			</label>\n				<input type="radio" name="color" value="pink" />\n				<span style="color: pink;">Pink</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="blue" />\n				<span style="color: blue;">Blue</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="green" />\n				<span style="color: green;">Green</span>\n			</label>\n			\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</div>\n	</form>';
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicLinkDialog='<form action="#" class="xqFormDialog xqBasicLinkDialog">\n		<h3>Link</h3>\n		<div>\n			<input type="text" class="initialFocus" name="text" value="" />\n			<input type="text" name="url" value="http://" />\n			\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</div>\n	</form>';

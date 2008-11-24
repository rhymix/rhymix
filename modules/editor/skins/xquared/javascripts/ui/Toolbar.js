/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires ui/Base.js
 */
xq.ui.Toolbar = xq.Class(/** @lends xq.ui.Toolbar.prototype */{
	/**
	 * TODO: Add description
	 *
     * @constructs
	 */
	initialize: function(xed, container, wrapper, buttonMap, imagePath, structureAndStyleCollector) {
		xq.addToFinalizeQueue(this);
		
		this.xed = xed;
		
		if(typeof container === 'string') {
			container = xq.$(container);
		}
		if(container && container.nodeType !== 1) {
			throw "[container] is not an element";
		}
		
		this.wrapper = wrapper;
		this.doc = this.wrapper.ownerDocument;
		this.buttonMap = buttonMap;
		this.imagePath = imagePath;
		this.structureAndStyleCollector = structureAndStyleCollector;
		
		this.buttons = null;
		this.anchorsCache = [];
		this._scheduledUpdate = null;
		
		if(!container) {
			this.create();
			this._addStyleRules([
				{selector:".xquared div.toolbar", rule:"background-image: url(" + imagePath + "toolbarBg.gif)"},
				{selector:".xquared ul.buttons li", rule:"background-image: url(" + imagePath + "toolbarButtonBg.gif)"},
				{selector:".xquared ul.buttons li.xq_separator", rule:"background-image: url(" + imagePath + "toolbarSeparator.gif)"}
			]);
		} else {
			this.container = container;
		}
	},
	
	finalize: function() {
		for(var i = 0; i < this.anchorsCache.length; i++) {
			// TODO remove dependency to Editor
			this.anchorsCache[i].xed = null;
			this.anchorsCache[i].handler = null;
			this.anchorsCache[i] = null;
		}
	
		this.toolbarAnchorsCache = null;
	},
	
	triggerUpdate: function() {
		if(this._scheduledUpdate) return;
		
		this._scheduledUpdate = window.setTimeout(
			function() {
				this._scheduledUpdate = null;
				var ss = this.structureAndStyleCollector();
				if(ss) this.update(ss);
			}.bind(this), 200
		);
	},
	
	/**
	 * Updates all buttons' status. Override this to customize status L&F. Don't call this function directly. Use triggerUpdate() to call it indirectly.
	 * 
	 * @param {Object} structure and style information. see xq.rdom.Base.collectStructureAndStyle()
	 */
	update: function(info) {
		if(!this.container) return;
		if(!this.buttons) {
			var classNames = [
				"emphasis", "strongEmphasis", "underline", "strike", "superscription", "subscription",
				"justifyLeft", "justifyCenter", "justifyRight", "justifyBoth",
				"unorderedList", "orderedList", "code",
				"paragraph", "heading1", "heading2", "heading3", "heading4", "heading5", "heading6"
			];
			
			this.buttons = {};
			
			for(var i = 0; i < classNames.length; i++) {
				var found = xq.getElementsByClassName(this.container, classNames[i]);
				var button = found && found.length > 0 ? found[0] : null;
				if(button) this.buttons[classNames[i]] = button;
			}
		}
		
		var buttons = this.buttons;
		this._updateButtonStatus('emphasis', info.em);
		this._updateButtonStatus('strongEmphasis', info.strong);
		this._updateButtonStatus('underline', info.underline);
		this._updateButtonStatus('strike', info.strike);
		this._updateButtonStatus('superscription', info.superscription);
		this._updateButtonStatus('subscription', info.subscription);
		
		this._updateButtonStatus('justifyLeft', info.justification === 'left');
		this._updateButtonStatus('justifyCenter', info.justification === 'center');
		this._updateButtonStatus('justifyRight', info.justification === 'right');
		this._updateButtonStatus('justifyBoth', info.justification === 'justify');
		
		this._updateButtonStatus('orderedList', info.list === 'OL');
		this._updateButtonStatus('unorderedList', info.list === 'UL');
		this._updateButtonStatus('code', info.list === 'CODE');
		
		this._updateButtonStatus('paragraph', info.block === 'P');
		this._updateButtonStatus('heading1', info.block === 'H1');
		this._updateButtonStatus('heading2', info.block === 'H2');
		this._updateButtonStatus('heading3', info.block === 'H3');
		this._updateButtonStatus('heading4', info.block === 'H4');
		this._updateButtonStatus('heading5', info.block === 'H5');
		this._updateButtonStatus('heading6', info.block === 'H6');
	},

	/**
	 * Enables all buttons
	 *
	 * @param {Array} [exceptions] array of string containing classnames to exclude
	 */
	enableButtons: function(exceptions) {
		if(!this.container) return;
		
		this._execForAllButtons(exceptions, function(li, exception) {
			li.firstChild.className = !exception ? '' : 'disabled';
		});

		// @WORKAROUND: Image icon disappears without following code:
		if(xq.Browser.isIE6) {
			this.container.style.display = 'none';
			setTimeout(function() {this.container.style.display = 'block';}.bind(this), 0);
		}
	},
	
	/**
	 * Disables all buttons
	 *
	 * @param {Array} [exceptions] array of string containing classnames to exclude
	 */
	disableButtons: function(exceptions) {
		this._execForAllButtons(exceptions, function(li, exception) {
			li.firstChild.className = exception ? '' : 'disabled';
		});
	},
	
	/**
	 * Creates toolbar element
	 */
	create: function() {
		// outmost container
		this.container = this.doc.createElement('div');
		this.container.className = 'toolbar';
		
		// button container
		var buttons = this.doc.createElement('ul');
		buttons.className = 'buttons';
		this.container.appendChild(buttons);
		
		// Generate buttons from map and append it to button container
		for(var i = 0; i < this.buttonMap.length; i++) {
			for(var j = 0; j < this.buttonMap[i].length; j++) {
				var buttonConfig = this.buttonMap[i][j];
				
				var li = this.doc.createElement('li');
				buttons.appendChild(li);
				li.className = buttonConfig.className;
				
				var span = this.doc.createElement('span');
				li.appendChild(span);
				
				if(buttonConfig.handler) {
					this._createButton(buttonConfig, span);
				} else {
					this._createDropdown(buttonConfig, span);
				}

				if(j === 0 && i !== 0) li.className += ' xq_separator';
			}
		}
		
		this.wrapper.appendChild(this.container);
	},

	_createButton: function(buttonConfig, span) {
		var a = this.doc.createElement('a');
		span.appendChild(a);
		a.href = '#';
		a.title = buttonConfig.title;
		a.handler = buttonConfig.handler;
		
		this.anchorsCache.push(a);
		
		xq.observe(a, 'mousedown', xq.cancelHandler);
		xq.observe(a, 'click', this._clickHandler.bindAsEventListener(this));

		var img = this.doc.createElement('img');
		a.appendChild(img);
		img.src = this.imagePath + buttonConfig.className + '.gif';
	},
	
	_createDropdown: function(buttonConfig, span) {
		var select = this.doc.createElement('select');
		select.handlers = buttonConfig.list;
		var xed = this.xed;
		
		xq.observe(select, 'change', function(e) {
			var src = e.target || e.srcElement;
			if(src.value === "-1") {
				src.selectedIndex = 0;
				return true;
			}
			
			var handler = src.handlers[src.value].handler;
			xed.focus();
			var stop = (typeof handler === "function") ? handler(this) : eval(handler);
			src.selectedIndex = 0;
			
			if(stop) {
				xq.stopEvent(e);
				return false;
			} else {
				return true;
			}
		});
		
		var option = this.doc.createElement('option');
		option.innerHTML = buttonConfig.title;
		option.value = -1;
		select.appendChild(option);
		
		option = this.doc.createElement('option');
		option.innerHTML = '----';
		option.value = -1;
		select.appendChild(option);
		
		for(var i = 0; i < buttonConfig.list.length; i++) {
			option = this.doc.createElement('option');
			option.innerHTML = buttonConfig.list[i].title;
			option.value = i;
			
			select.appendChild(option);
		}
		span.appendChild(select);
	},
	
	_clickHandler: function(e) {
		var src = e.target || e.srcElement;
		while(src.nodeName !== "A") src = src.parentNode;
		
		if(xq.hasClassName(src.parentNode, 'disabled') || xq.hasClassName(this.container, 'disabled')) {
			xq.stopEvent(e);
			return false;
		}
		
		var handler = src.handler;
		var xed = this.xed;
		xed.focus();
		if(typeof handler === "function") {
			handler(this);
		} else {
			eval(handler);
		}
		
		xq.stopEvent(e);
		return false;
	},

	_updateButtonStatus: function(className, selected) {
		var button = this.buttons[className];
		if(button) {
			var newClassName = selected ? 'selected' : '';
			var target = button.firstChild.firstChild;
			if(target.className !== newClassName) target.className = newClassName;
		}
	},
	
	_execForAllButtons: function(exceptions, exec) {
		if(!this.container) return;
		exceptions = exceptions || [];
		
		var lis = this.container.getElementsByTagName('LI');
		for(var i = 0; i < lis.length; i++) {
			var className = lis[i].className.split(" ").find(function(name) {return name !== 'xq_separator'});
			var exception = exceptions.indexOf(className) !== -1;
			exec(lis[i], exception);
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
	}
});
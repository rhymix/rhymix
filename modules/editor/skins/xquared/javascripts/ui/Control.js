/**
 * @namespace UI Controls
 * 
 * @requires Xquared.js
 * @requires ui/Base.js
 */
xq.ui.FormDialog = xq.Class(/** @lends xq.ui.FormDialog.prototype */ {
	/**
     * Displays given HTML form as a dialog.
     * 
     * @constructs
     * @param {xq.Editor} xed Dialog owner.
     * @param {String} html HTML string which contains FORM.
     * @param {Function} [onLoadHandler] callback function to be called when the form is loaded.
     * @param {Function} [onCloseHandler] callback function to be called when the form is closed.
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
	 * Shows dialog
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
		
		if(options.mode === 'modal') {
			this.dimmed = document.createElement('DIV');
			this.dimmed.style.position = 'absolute';
			this.dimmed.style.backgroundColor = 'black';
			this.dimmed.style.opacity = 0.5;
			this.dimmed.style.filter = 'alpha(opacity=50)';
			this.dimmed.style.zIndex=902;
			this.dimmed.style.top='0px';
			this.dimmed.style.left='0px';
			document.body.appendChild(this.dimmed);
			
			this.resizeDimmedDiv = function(e) {
				this.dimmed.style.display='none';
				this.dimmed.style.width=document.documentElement.scrollWidth+'px';
				this.dimmed.style.height=document.documentElement.scrollHeight+'px';
				this.dimmed.style.display='block';
			}.bind(this);
			
			xq.observe(window, 'resize', this.resizeDimmedDiv);
			
			this.resizeDimmedDiv();
		}
		
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
				if(e.keyCode === 27) {
					this.onCloseHandler();
					this.close();
				}
			}.bind(this));
		}
		
		this.onLoadHandler(this);
	},
	
	/**
	 * Closes dialog
	 */
	close: function() {
		this.form.parentNode.removeChild(this.form);
		
		if(this.dimmed) {
			this.dimmed.parentNode.removeChild(this.dimmed);
			this.dimmed = null;
			xq.stopObserving(window, 'resize', this.resizeDimmedDiv);
			this.resizeDimmedDiv = null;
		}
	},
	
	/**
	 * Sets position of dialog
	 *
	 * @param {String} target "centerOfWindow" or "centerOfEditor"
	 */
	setPosition: function(target) {
		var targetElement = null;
		var left = 0;
		var top = 0;
		
		if(target === 'centerOfWindow') {
			targetElement = document.documentElement;
			left += targetElement.scrollLeft;
			top += targetElement.scrollTop;
		} else if(target === 'centerOfEditor') {
			targetElement = this.xed.getCurrentEditMode() == 'wysiwyg' ? this.xed.wysiwygEditorDiv : this.xed.sourceEditorDiv;
			var o = targetElement;
			do {
				left += o.offsetLeft;
				top += o.offsetTop;
			} while(o = o.offsetParent)
		} else if(target === 'nearbyCaret') {
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



xq.ui.QuickSearchDialog = xq.Class(/** @lends xq.ui.QuickSearchDialog.prototype */ {
	/**
     * Displays quick search dialog
     * 
     * @constructs
     * @param {xq.Editor} xed Dialog owner.
     * @param {Object} param Parameters.
	 */
	initialize: function(xed, param) {
		xq.addToFinalizeQueue(this);
		this.xed = xed;
		
		this.rdom = xq.rdom.Base.createInstance();
		
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
		// @WORKAROUND: Ugly
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
		if(target.nodeName === "LI") {
			
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
		
		if(target === 'centerOfWindow') {
			left += targetElement.scrollLeft;
			top += targetElement.scrollTop;
			targetElement = document.documentElement;
		} else if(target === 'centerOfEditor') {
			targetElement = this.xed.getCurrentEditMode() == 'wysiwyg' ? this.xed.wysiwygEditorDiv : this.xed.sourceEditorDiv;
			var o = targetElement;
			do {
				left += o.offsetLeft;
				top += o.offsetTop;
			} while(o = o.offsetParent)
		} else if(target === 'nearbyCaret') {
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
			if(ol.childNodes[i].className === 'selected') return i;
		}
	},
	
	_getIndexOfLI: function(li) {
		var ol = this._getListContainer();
		for(var i = 0; i < ol.childNodes.length; i++) {
			if(ol.childNodes[i] === li) return i;
		}
	},
	
	_moveSelectionUp: function() {
		var count = this.matchCount();
		if(count === 0) return;
		var index = this._getSelectedIndex();
		var ol = this._getListContainer();
		ol.childNodes[index].className = "";
		
		index--;
		if(index < 0) index = count - 1;
		
		ol.childNodes[index].className = "selected";
	},
	
	_moveSelectionDown: function() {
		var count = this.matchCount();
		if(count === 0) return;
		var index = this._getSelectedIndex();
		var ol = this._getListContainer();
		ol.childNodes[index].className = "";
		
		index++;
		if(index >= count) index = 0;
		
		ol.childNodes[index].className = "selected";
	}
});
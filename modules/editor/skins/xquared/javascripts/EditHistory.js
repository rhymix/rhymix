/**
 * @requires Xquared.js
 * @requires rdom/Factory.js
 */
xq.EditHistory = xq.Class(/** @lends xq.EditHistory.prototype */{
    /**
	 * Manages editing history and performs UNDO/REDO.
	 *
     * @constructs
	 * @param {xq.rdom.Base} rdom Base instance
	 * @param {Number} [max=100] maximum UNDO buffer size.
	 */
	initialize: function(rdom, max) {
		xq.addToFinalizeQueue(this);
		if (!rdom) throw "IllegalArgumentException";

		this.disabled = false;
		this.max = max || 100;
		this.rdom = rdom;
		this.index = -1;
		this.queue = [];
		
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
		
		var arrowKeys = [33,34,35,36,37,39];
		// @WORKAROUND: Mac에서 화살표 up/down 누를 때 pushContent 하면 캐럿이 튄다
		if(!xq.Browser.isMac) arrowKeys.push(38,40);
		
		// ignore some event types
		if(['blur', 'mouseup'].indexOf(event.type) !== -1) return false;
		
		// ignore normal keys
		if('keydown' === event.type && !(event.ctrlKey || event.metaKey)) return false;
		if(['keydown', 'keyup', 'keypress'].indexOf(event.type) !== -1 && !event.ctrlKey && !event.altKey && !event.metaKey && arrowKeys.indexOf(event.keyCode) === -1) return false;
		if(['keydown', 'keyup', 'keypress'].indexOf(event.type) !== -1 && (event.ctrlKey || event.metaKey) && [89,90].indexOf(event.keyCode) !== -1) return false;

		// ignore ctrl/shift/alt/meta keys
		if([16,17,18,224].indexOf(event.keyCode) !== -1) return false;
		
		return this.pushContent();
	},
	popContent: function() {
		this.lastModified = Date.get();
		var entry = this.queue[this.index];
		if (entry.caret > 0) {
			var html=entry.html.substring(0, entry.caret) + '<span id="caret_marker_eh"></span>' + entry.html.substring(entry.caret);
			this.rdom.getRoot().innerHTML = html;
		} else {
			this.rdom.getRoot().innerHTML = entry.html;
		}
		this.restoreCaret();
	},
	pushContent: function(ignoreCaret) {
		if(xq.Browser.isTrident && !ignoreCaret && !this.rdom.hasFocus()) return false;
		if(!this.rdom.getCurrentElement()) return false;

		var html = this.rdom.getRoot().innerHTML;
		if(html === (this.queue[this.index] ? this.queue[this.index].html : null)) return false;
		
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
		
		var bookmark = this.rdom.saveSelection();
		var marker = this.rdom.pushMarker();
		
		var str = xq.Browser.isTrident ? '<SPAN class='+marker.className : '<span class="'+marker.className+'"';
		var caret = this.rdom.getRoot().innerHTML.indexOf(str);
		
		this.rdom.popMarker();
		this.rdom.restoreSelection(bookmark);
		
		return caret;
	},
	restoreCaret: function() {
		var marker = this.rdom.$('caret_marker_eh');
		
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

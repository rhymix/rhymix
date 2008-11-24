/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 */
xq.plugin.EditorResizePlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.EditorResizePlugin
	 * @lends xq.plugin.EditorResizePlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	isEventListener: function() {return true;},
	
	onAfterLoad: function(xed) {
		this.xed = xed;
		this.bar = null;
		this.screen = null;
		this.active = false;
	},
	
	onEditorInitialized: function(xed) {
		xed.registerEventFirer("Editor", "Resized");
		
		var wrapper = this.xed.getOutmostWrapper();
		var doc = wrapper.ownerDocument;
		
		// create resize bar
		this.bar = doc.createElement("DIV");
		if(xq.Browser.isIE6) this.bar.innerHTML = "<span></span>";
		this.bar.style.height = "6px";
		this.bar.style.backgroundColor = "#ddd";
		this.bar.style.cursor = "n-resize";
		wrapper.appendChild(this.bar);
		
		// register event
		xq.observe(this.bar, 'mousedown', this.onMousedown.bindAsEventListener(this));
		xq.observe(this.bar, 'mouseup', this.onMouseup.bindAsEventListener(this));
		xq.observe(this.bar, 'click', this.onMouseup.bindAsEventListener(this));
		this.mousemoveHandler = this.onMousemove.bindAsEventListener(this);
	},
	
	onMousedown: function(e) {
		if(this.active) return;
		
		xq.observe(document, 'mousemove', this.mousemoveHandler);
		this.last = e.screenY;

		var wrapper = this.xed.getOutmostWrapper();
		var doc = wrapper.ownerDocument;
		var wysiwygDiv = this.xed.getWysiwygEditorDiv();
		var sourceDiv = this.xed.getSourceEditorDiv();
		var visibleDiv = this.xed.getCurrentEditMode() == "wysiwyg" ? wysiwygDiv : sourceDiv;
		var location = xq.getCumulativeOffset(visibleDiv);
		
		// create screen
		this.screen = doc.createElement("DIV");
		if(xq.Browser.isIE6) this.screen.innerHTML = "<span></span>";
		
		if(xq.Browser.isIE6) {
			this.screen.style.backgroundColor = "#EEE";
			wysiwygDiv.style.display = "none";
		} else {
			this.screen.style.position = "absolute";
			this.screen.style.left = location.left + "px";
			this.screen.style.top = location.top + "px";
		}
		
		this.screen.style.width = visibleDiv.clientWidth + "px";
		this.screen.style.height = visibleDiv.clientHeight + "px";
		wrapper.insertBefore(this.screen, visibleDiv);
		
		this.resize(e.screenY);
		this.active = true;
		
		xq.stopEvent(e);
		return true;
	},
	onMouseup: function(e) {
		if(!this.active) return;
		
		this.active = false;
		
		xq.stopObserving(document, 'mousemove', this.mousemoveHandler);
		this.resize(e.screenY);
		
		if(xq.Browser.isIE6) {
			var wysiwygDiv = this.xed.getWysiwygEditorDiv();
			var sourceDiv = this.xed.getSourceEditorDiv();
			var visibleDiv = this.xed.getCurrentEditMode() == "wysiwyg" ? wysiwygDiv : sourceDiv;
			visibleDiv.style.display = "block";
		}
		
		this.screen.parentNode.removeChild(this.screen);
		this.screen = null;

		this.xed._fireOnResized(this.xed);
		
		xq.stopEvent(e);
		return true;
	},
	onMousemove: function(e) {
		this.resize(e.screenY);

		xq.stopEvent(e);
		return true;
	},
	resize: function(y) {
		var delta = y - this.last;
		
		var wysiwygDiv = this.xed.getWysiwygEditorDiv();
		var sourceDiv = this.xed.getSourceEditorDiv();
		var newHeight = Math.max(0, this.screen.clientHeight + delta);
		
		sourceDiv.style.height = wysiwygDiv.style.height = this.screen.style.height = newHeight + "px";
		
		this.last = y;
	}
});
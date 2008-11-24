/**
 * @requires Xquared.js
 * @requires Editor.js
 */
xq.Layer = xq.Class(/** @lends xq.Layer.prototype */{
	/**
     * @constructs
     *
     * @param {xq.Editor} editor editor instance
     * @param {Element} element designMode document's element. Layer instance will be attached to this element
     * @param {String} html HTML for body.
	 */
	initialize: function(editor, element, html) {
		xq.addToFinalizeQueue(this);
		
		this.margin = 4;
		this.editor = editor;
		this.element = element;
		this.frame = this.editor._createIFrame(this.editor.getOuterDoc(), this.element.offsetWidth - (this.margin * 2) + "px", this.element.offsetHeight + (this.margin * 2) + "px");
		this.editor.getOuterDoc().body.appendChild(this.frame);
		this.doc = editor._createDoc(
			this.frame,
			'<style type="text/css">html, body {margin:0px; padding:0px; background-color: transparent; width: 100%; height: 100%; overflow: hidden;}</style>',
			[], null, null, html
		);
		this.frame.style.position = "absolute";
		this.updatePosition();
	},
	
	getFrame: function() {
		return this.frame;
	},
	
	getDoc: function() {
		return this.doc;
	},
	
	getBody: function() {
		return this.doc.body;
	},
	
	isValid: function() {
		return this.element && this.element.parentNode && this.element.offsetParent;
	},
	
	detach: function() {
		this.frame.parentNode.removeChild(this.frame);
		
		this.frame = null;
		this.element = null;
	},
	
	updatePosition: function() {
		// calculate element position
		var offset = xq.getCumulativeOffset(this.element, this.editor.rdom.getRoot());
		
		// and scroll position
		var doc = this.editor.getDoc();
		var body = this.editor.getBody();
		offset.left -= doc.documentElement.scrollLeft + body.scrollLeft - this.margin;
		offset.top -= doc.documentElement.scrollTop + body.scrollTop - this.margin;
		
		// apply new position
		this.frame.style.left = offset.left + "px";
		this.frame.style.top = offset.top + "px";
		
		// perform autofit
		var newWidth = this.doc.body.scrollWidth + (this.margin - 1) * 2;
		var newHeight = this.doc.body.scrollHeight + (this.margin - 1) * 2;
			
		// without -1, the element increasing slowly. 
		this.element.width = newWidth;
		this.element.height = newHeight;
		
		// resize frame
		this.frame.style.width = this.element.offsetWidth - (this.margin * 2) + "px";
		this.frame.style.height = this.element.offsetHeight - (this.margin * 2) + "px";
	}
});

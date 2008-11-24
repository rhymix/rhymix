/**
 * @requires Xquared.js
 * @requires rdom/W3.js
 */
xq.rdom.Webkit = xq.Class(xq.rdom.W3,
	/**
	 * @name xq.rdom.Webkit
	 * @lends xq.rdom.Webkit.prototype
	 * @extends xq.rdom.Base
	 * @constructor
	 */
	{
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
		return node.className === "webkit-block-placeholder";
	},

	selectElement: function(element, entireElement) {
		if(!element) throw "[element] is null";
		if(element.nodeType !== 1) throw "[element] is not an element";
		
		var rng = this.rng() || this.getDoc().createRange();
		if(entireElement) {
			rng.selectNode(element);
		} else {
			rng.selectNodeContents(element);
		}
		
		this._setSelectionByRange(rng);
	},

	getSelectionAsHtml: function() {
		var container = this.createElement("div");
		var rng = this.rng();
		var contents = this.rng().cloneContents();
		if(contents) container.appendChild(contents);
		return container.innerHTML;
	},

	collapseSelection: function(toStart) {
		var rng = this.rng();
		rng.collapse(toStart);
		this._setSelectionByRange(rng);
	},

	_setSelectionByRange: function(rng) {
		var sel = this.sel();
		sel.setBaseAndExtent(rng.startContainer, rng.startOffset, rng.endContainer, rng.endOffset);
	}
});
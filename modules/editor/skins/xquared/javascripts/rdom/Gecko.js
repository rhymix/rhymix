/**
 * @requires Xquared.js
 * @requires rdom/W3.js
 */
xq.rdom.Gecko = xq.Class(xq.rdom.W3,
	/**
	 * @name xq.rdom.Gecko
	 * @lends xq.rdom.Gecko.prototype
	 * @extends xq.rdom.W3
	 * @constructor
	 */
	{
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
		return node.nodeName === "BR" && (node.getAttribute("type") === "_moz" || !this.getNextSibling(node));
	},

	selectElement: function(element, entireElement) {
		if(!element) throw "[element] is null";
		if(element.nodeType !== 1) throw "[element] is not an element";

		// @WORKAROUND: required to avoid Windows FF selection bug.
		try {
			if(!xq.Browser.isMac) this.getDoc().execCommand("SelectAll", false, null);
		} catch(ignored) {}
		
		var rng = this.rng() || this.getDoc().createRange();
		
		if(entireElement) {
			rng.selectNode(element);
		} else {
			rng.selectNodeContents(element);
		}
	}
});

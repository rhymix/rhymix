/**
 * @requires Xquared.js
 * @requires validator/Base.js
 */
xq.validator.W3 = xq.Class(xq.validator.Base,
	/**
	 * @name xq.validator.W3
	 * @lends xq.validator.W3.prototype
	 * @extends xq.validator.Base
	 * @constructor
	 */
	{
	validateDom: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);
		this.removeDangerousElements(element);
		rdom.removePlaceHoldersAndEmptyNodes(element);
		this.validateFont(element);
	},
	
	validateString: function(html) {
		try {
			html = this.replaceTag(html, "b", "strong");
			html = this.replaceTag(html, "i", "em");
			
			html = this.validateStrike(html);
			html = this.validateUnderline(html);
			html = this.addNbspToEmptyBlocks(html);
			html = this.performFullValidation(html);
			html = this.insertNewlineBetweenBlockElements(html);
		} catch(ignored) {}
		
		return html;
	},
	
	invalidateDom: function(element) {
		this.invalidateFont(element);
		this.invalidateStrikesAndUnderlines(element);
	},
	
	invalidateString: function(html) {
		html = this.replaceTag(html, "strong", "b");
		html = this.replaceTag(html, "em", "i");
		html = this.removeComments(html);
		html = this.replaceNbspToBr(html);
		return html;
	},
	
	performFullValidation: function(html) {
		html = this.validateSelfClosingTags(html);
		html = this.applyWhitelist(html);
		
		if(this.urlValidationMode === 'relative') {
			html = this.makeUrlsRelative(html);
		} else if(this.urlValidationMode === 'host_relative') {
			html = this.makeUrlsHostRelative(html);
		} else if(this.urlValidationMode === 'absolute') {
			html = this.makeUrlsAbsolute(html);
		}

		return html;
	},
	
	insertNewlineBetweenBlockElements: function(html) {
		var blocks = new xq.DomTree().getBlockTags().join("|");
		var regex = new RegExp("</(" + blocks + ")>([^\n])", "img");
		return html.replace(regex, '</$1>\n$2');
	},
	
	addNbspToEmptyBlocks: function(content) {
		var blocks = new xq.DomTree().getBlockTags().join("|");
		var regex = new RegExp("<(" + blocks + ")>\\s*?</(" + blocks + ")>", "img");
		return content.replace(regex, '<$1>&nbsp;</$2>');
	},
	
	replaceNbspToBr: function(content) {
		var blocks = new xq.DomTree().getBlockTags().join("|");
		
		// Safari replaces &nbsp; into \xA0
		var regex = new RegExp("<(" + blocks + ")>(&nbsp;|\xA0)?</(" + blocks + ")>", "img");
		var rdom = xq.rdom.Base.createInstance();
		return content.replace(regex, '<$1>' + rdom.makePlaceHolderString() + '</$3>');
	}
});

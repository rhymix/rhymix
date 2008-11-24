/**
 * @requires Xquared.js
 * @requires validator/Base.js
 */
xq.validator.Trident = xq.Class(xq.validator.Base,
	/**
	 * @name xq.validator.Trident
	 * @lends xq.validator.Trident.prototype
	 * @extends xq.validator.Base
	 * @constructor
	 */
	{
	validateDom: function(element) {
		this.removeDangerousElements(element);
		this.validateFont(element);
	},
	
	validateString: function(html) {
		try {
			html = this.validateStrike(html);
			html = this.validateUnderline(html);
			html = this.performFullValidation(html);
		} catch(ignored) {}
		
		return html;
	},
	
	invalidateDom: function(element) {
		this.invalidateFont(element);
		this.invalidateStrikesAndUnderlines(element);
	},
	
	invalidateString: function(html) {
		html = this.removeComments(html);
		return html;
	},
	
	performFullValidation: function(html) {
		html = this.lowerTagNamesAndUniformizeQuotation(html);
		html = this.validateSelfClosingTags(html);
		html = this.applyWhitelist(html);
		
		if(this.urlValidationMode === 'relative') {
			html = this.makeUrlsRelative(html);
		} else if(this.urlValidationMode === 'host_relative') {
			html = this.makeUrlsHostRelative(html);
		} else if(this.urlValidationMode === 'absolute') {
			// Trident always use absolute URL so we don't need to do anything.
			//
			// html = this.makeUrlsAbsolute(html);
		}
		
		return html;
	},
	
	lowerTagNamesAndUniformizeQuotation: function(html) {
		this.pAttrQuotation1 = xq.compilePattern("\\s(\\w+?)=\\s+\"([^\"]+)\"", "mg");
		this.pAttrQuotation2 = xq.compilePattern("\\s(\\w+?)=([^ \"]+)", "mg");
		this.pAttrQuotation3 = xq.compilePattern("\\sNAME=\"(\\w+?)\" VALUE=\"(\\w+?)\"", "mg");

		// Uniformize quotation, turn tag names and attribute names into lower case
		html = html.replace(/<(\/?)(\w+)([^>]*?)>/img, function(str, closingMark, tagName, attrs) {
			return "<" + closingMark + tagName.toLowerCase() + this.correctHtmlAttrQuotation(attrs) + ">";
		}.bind(this));
		
		return html;
	},
	
	correctHtmlAttrQuotation: function(html) {
		html = html.replace(this.pAttrQuotation1, function (str, name, value) {return " " + name.toLowerCase() + '=' + '"' + value + '"'});
		html = html.replace(this.pAttrQuotation2, function (str, name, value) {return " " + name.toLowerCase() + '=' + '"' + value + '"'});
		html = html.replace(this.pAttrQuotation3, function (str, name, value) {return " name=\"" + name + "\" value=\"" + value + "\""});
		return html;
	}
});
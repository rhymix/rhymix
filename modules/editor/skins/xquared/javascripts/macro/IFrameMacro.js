/**
 * @requires macro/Base.js
 */
xq.macro.IFrameMacro = xq.Class(xq.macro.Base,
	/**
	 * IFrame macro
	 * 
	 * @name xq.macro.IFrameMacro
	 * @lends xq.macro.IFrameMacro.prototype
	 * @extends xq.macro.Base
	 * @constructor
	 */
	{
	initFromHtml: function() {
		this.params.html = this.html;
	},
	initFromParams: function() {
		if(this.params.html) return;
		
		var sb = [];
		sb.push('<iframe');
		for(var attrName in this.params) {
			var attrValue = this.params[attrName];
			if(attrValue) sb.push(' ' + attrName.substring("p_".length) + '="' + attrValue + '"');
		}
		sb.push('></iframe>');
		this.params = {html:sb.join("")};
	},
	createHtml: function() {
		return this.params.html;
	}
});
xq.macro.IFrameMacro.recognize = function(html) {
	var p = xq.compilePattern("<IFRAME\\s+[^>]+(?:/>|>.*?</(?:IFRAME)>)", "img");
	return !!html.match(p);
}
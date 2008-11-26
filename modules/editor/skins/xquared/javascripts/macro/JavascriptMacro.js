/**
 * @requires macro/Base.js
 */
xq.macro.JavascriptMacro = xq.Class(xq.macro.Base,
	/**
	 * Javascript macro
	 * 
	 * @name xq.macro.JavascriptMacro
	 * @lends xq.macro.JavascriptMacro.prototype
	 * @extends xq.macro.Base
	 * @constructor
	 */
	{
	initFromHtml: function() {
		var p = xq.compilePattern("src=[\"'](.+?)[\"']", "img");
		this.params.url = p.exec(this.html)[1];
	},
	initFromParams: function() {
		if(!xq.macro.JavascriptMacro.isSafeScript(this.params.url)) throw "Unknown src";
	},
	createHtml: function() {return '<script type="text/javascript" src="' + this.params.url + '"></script>'},
	
	onLayerInitialzied: function(layer) {
		layer.getDoc().write(this.createHtml());
	}
});

xq.macro.JavascriptMacro.recognize = function(html) {
	var p = xq.compilePattern("<SCRIPT\\s+[^>]*src=[\"']([^\"']+)[\"'][^>]*(?:/>|>.*?</(?:SCRIPT)>)", "img");
	var m = p.exec(html);
	if(!m || !m[1]) return false;
	return this.isSafeScript(m[1]);
}
xq.macro.JavascriptMacro.isSafeScript = function(url) {
	var safeSrcs = {
		googleGadget: /http:\/\/gmodules\.com\/ig\/ifr\?/img
	};
	for(var id in safeSrcs) {
		if(url.match(safeSrcs[id])) return true;
	}
	return false;
}

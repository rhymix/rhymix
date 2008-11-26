/**
 * @requires macro/Base.js
 */
xq.macro.FlashMovieMacro = xq.Class(xq.macro.Base,
	/**
	 * Flash movie macro
	 * 
	 * @name xq.macro.FlashMovieMacro
	 * @lends xq.macro.FlashMovieMacro.prototype
	 * @extends xq.macro.Base
	 * @constructor
	 */
	{
	initFromHtml: function() {
		this.params.html = this.html;
	},
	initFromParams: function() {
		if(!xq.macro.FlashMovieMacro.recognize(this.params.html)) throw "Unknown src";
	},
	createHtml: function() {
		return this.params.html;
	}
});
xq.macro.FlashMovieMacro.recognize = function(html) {
	var providers = {
		tvpot: /http:\/\/flvs\.daum\.net\/flvPlayer\.swf\?/,
		youtube: /http:\/\/(?:www\.)?youtube\.com\/v\//,
		pandoratv: /http:\/\/flvr\.pandora\.tv\/flv2pan\/flvmovie\.dll\?/,
		pandoratv2: /http:\/\/imgcdn\.pandora\.tv\/gplayer\/pandora\_EGplayer\.swf\?/,
		mncast: /http:\/\/dory\.mncast\.com\/mncHMovie\.swf\?/,
		yahoo: /http:\/\/d\.yimg\.com\//
	};
	
	for(var id in providers) {
		if(html.match(providers[id])) return true;
	}
	
	return false;
}
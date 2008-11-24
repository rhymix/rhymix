/**
 * @namespace
 */
xq.macro = {};

/**
 * @requires Xquared.js
 */
xq.macro.Base = xq.Class(/** @lends xq.macro.Base.prototype */{
	/**
     * @constructs
     *
     * @param {Object} Parameters or HTML fragment.
     * @param {String} URL to place holder image.
	 */
	initialize: function(id, paramsOrHtml, placeHolderImgSrc) {
		this.id = id;
		this.placeHolderImgSrc = placeHolderImgSrc;
		
		if(typeof paramsOrHtml === "string") {
			this.html = paramsOrHtml;
			this.params = {};
			
			this.initFromHtml();
		} else {
			this.html = null;
			this.params = paramsOrHtml;
			
			this.initFromParams();
		}
	},
	
	initFromHtml: function() {},
	initFromParams: function() {},
	createHtml: function() {throw "Not implemented";},
	onLayerInitialzied: function(layer) {},
	
	createPlaceHolderHtml: function() {
		var size = {width: 5, height: 5};
		var def = {};
		def.id = this.id;
		def.params = this.params;
		
		sb = [];
		sb.push('<img ');
		sb.push(	'class="xqlayer" ');
		sb.push(	'src="' + this.placeHolderImgSrc + '" ');
		sb.push(	'width="' + (size.width + 4) + '" height="' + (size.height + 4) + '" ');
		sb.push(	'longdesc="' + escape(JSON.stringify(def)) + '" ');
		sb.push(	'style="border: 1px solid #ccc" ');
		sb.push('/>');
		
		return sb.join('');
	}
})
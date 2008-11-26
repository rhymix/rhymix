/**
 * Creates and returns instance of browser specific implementation.
 * 
 * @requires Xquared.js
 * @requires rdom/Base.js
 * @requires rdom/Trident.js
 * @requires rdom/Gecko.js
 * @requires rdom/Webkit.js
 */
xq.rdom.Base.createInstance = function() {
	if(xq.Browser.isTrident) {
		return new xq.rdom.Trident();
	} else if(xq.Browser.isWebkit) {
		return new xq.rdom.Webkit();
	} else {
		return new xq.rdom.Gecko();
	}
}
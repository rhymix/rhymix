/**
 * Creates and returns instance of browser specific implementation.
 * 
 * @requires Xquared.js
 * @requires validator/Base.js
 * @requires validator/Trident.js
 * @requires validator/Gecko.js
 * @requires validator/Webkit.js
 */
xq.validator.Base.createInstance = function(curUrl, urlValidationMode, whitelist) {
	if(xq.Browser.isTrident) {
		return new xq.validator.Trident(curUrl, urlValidationMode, whitelist);
	} else if(xq.Browser.isWebkit) {
		return new xq.validator.Webkit(curUrl, urlValidationMode, whitelist);
	} else {
		return new xq.validator.Gecko(curUrl, urlValidationMode, whitelist);
	}
}
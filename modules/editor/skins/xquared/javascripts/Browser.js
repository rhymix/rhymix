/**
 * @namespace Contains browser detection codes
 * 
 * @requires Xquared.js
 */
xq.Browser = new function() {
	// By Rendering Engines
	
	/** 
	 * True if rendering engine is Trident
	 * @type boolean
	 */
	this.isTrident = navigator.appName === "Microsoft Internet Explorer",
	
	/**
	 * True if rendering engine is Webkit
	 * @type boolean
	 */
	this.isWebkit = navigator.userAgent.indexOf('AppleWebKit/') > -1,
	
	/**
	 * True if rendering engine is Gecko
	 * @type boolean
	 */
	this.isGecko = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') === -1,
	
	/**
	 * True if rendering engine is KHTML
	 * @type boolean
	 */
	this.isKHTML = navigator.userAgent.indexOf('KHTML') !== -1,
	
	/**
	 * True if rendering engine is Presto
	 * @type boolean
	 */
	this.isPresto = navigator.appName === "Opera",
	
	
	
	// By Platforms
	/**
	 * True if platform is Mac
	 * @type boolean
	 */
	this.isMac = navigator.userAgent.indexOf("Macintosh") !== -1,
	
	/**
	 * True if platform is Ubuntu Linux
	 * @type boolean
	 */
	this.isUbuntu = navigator.userAgent.indexOf('Ubuntu') !== -1,
	
	/**
	 * True if platform is Windows
	 * @type boolean
	 */
	this.isWin = navigator.userAgent.indexOf('Windows') !== -1,



	// By Browsers
	/**
	 * True if browser is Internet Explorer
	 * @type boolean
	 */
	this.isIE = navigator.appName === "Microsoft Internet Explorer",
	
	/**
	 * True if browser is Internet Explorer 6
	 * @type boolean
	 */
	this.isIE6 = navigator.userAgent.indexOf('MSIE 6') !== -1,
	
	/**
	 * True if browser is Internet Explorer 7
	 * @type boolean
	 */
	this.isIE7 = navigator.userAgent.indexOf('MSIE 7') !== -1,
	
	/**
	 * True if browser is Internet Explorer 8
	 * @type boolean
	 */
	this.isIE8 = navigator.userAgent.indexOf('MSIE 8') !== -1,
	
	/**
	 * True if browser is Firefox
	 * @type boolean
	 */
	this.isFF = navigator.userAgent.indexOf('Firefox') !== -1,
	
	/**
	 * True if browser is Firefox 2
	 * @type boolean
	 */
	this.isFF2 = navigator.userAgent.indexOf('Firefox/2') !== -1,
	
	/**
	 * True if browser is Firefox 3
	 * @type boolean
	 */
	this.isFF3 = navigator.userAgent.indexOf('Firefox/3') !== -1,
	
	/**
	 * True if browser is Safari
	 * @type boolean
	 */
	this.isSafari = navigator.userAgent.indexOf('Safari') !== -1
};
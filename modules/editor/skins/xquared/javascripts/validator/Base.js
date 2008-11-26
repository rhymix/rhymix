/**
 * @namespace
 */
xq.validator = {}

/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires rdom/Factory.js
 */
xq.validator.Base = xq.Class(/** @lends xq.validator.Base.prototype */{
	/**
     * @constructs
	 */
	initialize: function(curUrl, urlValidationMode, whitelist) {
		xq.addToFinalizeQueue(this);
		xq.asEventSource(this, "Validator", ["Preprocessing", "BeforeDomValidation", "AfterDomValidation", "BeforeStringValidation", "AfterStringValidation", "BeforeDomInvalidation", "AfterDomInvalidation", "BeforeStringInvalidation", "AfterStringInvalidation"]);
		
		this.whitelist = whitelist || xq.predefinedWhitelist;
		this.pRGB = xq.compilePattern("rgb\\((\\d+),\\s*(\\d+),\\s*(\\d+)\\)");
		
		this.curUrl = curUrl;
		this.curUrlParts = curUrl ? curUrl.parseURL() : null;
		this.urlValidationMode = urlValidationMode;
	},
	
	/**
	 * Perform validation on given element
	 *
	 * @param {Element} element Target element. It is not affected by validation.
	 *
	 * @returns {String} Validated HTML string
	 */
	validate: function(element, dontClone) {
		// DOM validation
		element = dontClone ? element : element.cloneNode(true);
		this._fireOnBeforeDomValidation(element);
		this.validateDom(element);
		this._fireOnAfterDomValidation(element);
		
		// String validation
		var html = {value: element.innerHTML};
		this._fireOnBeforeStringValidation(html);
		html.value = this.validateString(html.value);
		this._fireOnAfterStringValidation(html);
		
		return html.value;
	},
	
	validateDom: function(element) {throw "Not implemented";},
	validateString: function(html) {throw "Not implemented";},
	
	/**
	 * Perform invalidation on given element to make the designmode works well.
	 *
	 * @param {String} html HTML string.
	 * @returns {String} Invalidated HTML string
	 */
	invalidate: function(html) {
		// Preprocessing
		var html = {value: html};
		this._fireOnPreprocessing(html);
		
		// DOM invalidation
		var element = document.createElement("DIV");
		element.innerHTML = html.value;
		this._fireOnBeforeDomInvalidation(element);
		this.invalidateDom(element);
		this._fireOnAfterDomInvalidation(element);
		
		// String invalidation
		html.value = element.innerHTML;
		this._fireOnBeforeStringInvalidation(html);
		html.value = this.invalidateString(html.value);
		this._fireOnAfterStringInvalidation(html);
		
		return html.value;
	},
	
	invalidateDom: function(element) {throw "Not implemented"},
	invalidateString: function(html) {throw "Not implemented"},
	
	/**
	 * em.class="underline" -> u
	 * span.class="strike" -> strike
	 */ 
	invalidateStrikesAndUnderlines: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);

		var nameOfClassName = xq.Browser.isTrident ? "className" : "class";
		
		var underlines = xq.getElementsByClassName(rdom.getRoot(), "underline", "em");
		var pUnderline = xq.compilePattern("(^|\\s)underline($|\\s)");
		var lenOfUnderlines = underlines.length;
		for(var i = 0; i < lenOfUnderlines; i++) {
			rdom.replaceTag("u", underlines[i]).removeAttribute(nameOfClassName);
		}
		
		var strikes = xq.getElementsByClassName(rdom.getRoot(), "strike", "span")
		var pStrike = xq.compilePattern("(^|\\s)strike($|\\s)");
		var lenOfStrikes = strikes.length;
		for(var i = 0; i < lenOfStrikes; i++) {
			rdom.replaceTag("strike", strikes[i]).removeAttribute(nameOfClassName);
		}
	},
	
	validateStrike: function(content) {
		content = content.replace(/<strike(>|\s+[^>]*>)/ig, "<span class=\"strike\"$1");
		content = content.replace(/<\/strike>/ig, "</span>");
		return content;
	},
	
	validateUnderline: function(content) {
		content = content.replace(/<u(>|\s+[^>]*>)/ig, "<em class=\"underline\"$1");
		content = content.replace(/<\/u>/ig, "</em>");
		return content;
	},
	
	replaceTag: function(content, from, to) {
		return content.replace(new RegExp("(</?)" + from + "(>|\\s+[^>]*>)", "ig"), "$1" + to + "$2");
	},
	
	validateSelfClosingTags: function(content) {
		return content.replace(/<(br|hr|img|value)([^>]*?)>/img, function(str, tag, attrs) {
			return "<" + tag + attrs + " />"
		});
	},
	
	validateFont: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);
		
		// It should be reversed to deal with nested elements
		var fonts = element.getElementsByTagName('FONT');
		var fontSizes = ["xx-small", "x-small", "small", "medium", "large", "x-large", "xx-large"];
		var len = fonts.length - 1;
		for(var i = len; i >= 0; i--) {
			var font = fonts[i];
			var color = font.getAttribute('color');
			var backgroundColor = font.style.backgroundColor;
			var face = font.getAttribute('face');
			var size = fontSizes[parseInt(font.getAttribute('size')) % 8 - 1];
			
			if(color || backgroundColor || face || size) {
				var span = rdom.replaceTag("span", font);
				span.removeAttribute('color');
				span.removeAttribute('face');
				span.removeAttribute('size');
				
				if(color) span.style.color = color;
				if(backgroundColor) span.style.backgroundColor = backgroundColor;
				if(face) span.style.fontFamily = face;
				if(size) span.style.fontSize = size;
			}
		}
	},
	
	invalidateFont: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);

		// It should be reversed to deal with nested elements
		var spans = element.getElementsByTagName('SPAN');
		var fontSizes = {"xx-small":1, "x-small":2, "small":3, "medium":4, "large":5, "x-large":6, "xx-large":7};
		var len = spans.length - 1;
		for(var i = len; i >= 0; i--) {
			var span = spans[i];
			if(span.className === "strike") continue;
			
			var color = span.style.color;
			var backgroundColor = span.style.backgroundColor;
			var face = span.style.fontFamily;
			var size = fontSizes[span.style.fontSize];
			
			if(color || backgroundColor || face || size) {
				var font = rdom.replaceTag("font", span);
				font.style.cssText = "";
				
				if(color) font.setAttribute('color', this.asRGB(color));
				if(backgroundColor) font.style.backgroundColor = backgroundColor;
				if(face) font.setAttribute('face', face);
				if(size) font.setAttribute('size', size);
			}
		}
	},
	
	asRGB: function(color) {
		if(color.indexOf("#") === 0) return color;
		
		var m = this.pRGB.exec(color);
		if(!m) return color;
		
		var r = Number(m[1]).toString(16);
		var g = Number(m[2]).toString(16);
		var b = Number(m[3]).toString(16);
		
		if(r.length === 1) r = "0" + r;
		if(g.length === 1) g = "0" + g;
		if(b.length === 1) b = "0" + b;
		
		return "#" + r + g + b;
	},
	
	removeComments: function(content) {
		return content.replace(/<!--.*?-->/img, '');
	},
	
	removeDangerousElements: function(element) {
		var scripts = element.getElementsByTagName('SCRIPT');
		for(var i = scripts.length - 1; i >= 0; i--) {
			scripts[i].parentNode.removeChild(scripts[i]);
		}
	},
	
	applyWhitelist: function(content) {
		var whitelist = this.whitelist;
		var allowedAttrs = null;
		
		var p1 = xq.compilePattern("(^|\\s\")([^\"=]+)(\\s|$)", "g");
		var p2 = xq.compilePattern("(\\S+?)=\"[^\"]*\"", "g");
		return content.replace(new RegExp("(</?)([^>]+?)(>|\\s+([^>]*?)(\\s?/?)>)", "g"), function(str, head, tag, tail, attrs, selfClosing) {
			if(!(allowedAttrs = whitelist[tag])) return '';
			
			if(attrs) {
				if(xq.Browser.isTrident) attrs = attrs.replace(p1, '$1$2="$2"$3');
				
				var sb = [];
				var m = attrs.match(p2);
				for(var i = 0; i < m.length; i++) {
					var name = m[i].split('=')[0];
					if(allowedAttrs.indexOf(name) !== -1) sb.push(m[i]);
				}
				
				if(sb.length) {
					attrs = sb.join(' ');
					return head + tag + ' ' + attrs + selfClosing + '>';
				} else {
					return head + tag + selfClosing + '>';
				}
			} else {
				return str;
			}
		});
	},
	
	// TODO: very expansive
	makeUrlsRelative: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		var p1 = xq.compilePattern("(href|src)=\"([^\"]+)\"", "g");
		var p2 = xq.compilePattern("^\\w+://");
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(p1, function(str, name, url) {
					// 3. first, make it absolute
					var abs = null;
					if(url.charAt(0) === '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) === '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) === '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(p2)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}
					
					// 4. make it relative by removing same part
					var rel = abs;
					
					if(abs === urlParts.includeHost) {
						rel = "/";
					} else if(abs.indexOf(urlParts.includeQuery) === 0) {
						rel = abs.substring(urlParts.includeQuery.length);
					} else if(abs.indexOf(urlParts.includePath) === 0) {
						rel = abs.substring(urlParts.includePath.length);
					} else if(abs.indexOf(urlParts.includeBase) === 0) {
						rel = abs.substring(urlParts.includeBase.length);
					} else if(abs.indexOf(urlParts.includeHost) === 0) {
						rel = abs.substring(urlParts.includeHost.length);
					}
					
					if(rel === '') rel = '#';
					
					return name + '="' + rel + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
		
		return content;
	},
	
	// TODO: very expansive
	makeUrlsHostRelative: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		var p1 = xq.compilePattern("(href|src)=\"([^\"]+)\"", "g");
		var p2 = xq.compilePattern("^\\w+://");
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(p1, function(str, name, url) {
					// 3. first, make it absolute
					var abs = null;
					if(url.charAt(0) === '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) === '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) === '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(p2)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}
					
					// 4. make it relative by removing same part
					var rel = abs;
					if(abs === urlParts.includeHost) {
						rel = "/";
					} else if(abs.indexOf(urlParts.includeQuery) === 0 && abs.indexOf("#") !== -1) {
						// same except for fragment-part?
						rel = abs.substring(abs.indexOf("#"));
					} else if(abs.indexOf(urlParts.includeHost) === 0) {
						// same host?
						rel = abs.substring(urlParts.includeHost.length);
					}
					
					if(rel === '') rel = '#';
					
					return name + '="' + rel + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
		
		return content;
	},
	
	// TODO: very expansive
	makeUrlsAbsolute: function(content) {
		var curUrl = this.curUrl;
		var urlParts = this.curUrlParts;
		
		var p1 = xq.compilePattern("(href|src)=\"([^\"]+)\"", "g");
		var p2 = xq.compilePattern("^\\w+://");
		
		// 1. find attributes and...
		return content.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g, function(str, head, ignored, attrs, tail) {
			if(attrs) {
				// 2. validate URL part
				attrs = attrs.replace(p1, function(str, name, url) {
					var abs = null;
					if(url.charAt(0) === '#') {
						abs = urlParts.includeQuery + url;
					} else if(url.charAt(0) === '?') {
						abs = urlParts.includePath + url;
					} else if(url.charAt(0) === '/') {
						abs = urlParts.includeHost + url;
					} else if(url.match(p2)) {
						abs = url;
					} else {
						abs = urlParts.includeBase + url;
					}

					return name + '="' + abs + '"';
				});
				
				return head + attrs + tail + '>';
			} else {
				return str;
			}
		});
	}
});
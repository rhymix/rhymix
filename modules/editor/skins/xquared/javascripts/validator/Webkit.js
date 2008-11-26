/**
 * @requires Xquared.js
 * @requires validator/W3.js
 */
xq.validator.Webkit = xq.Class(xq.validator.W3,
	/**
	 * @name xq.validator.Webkit
	 * @lends xq.validator.Webkit.prototype
	 * @extends xq.validator.W3
	 * @constructor
	 */
	{
	validateDom: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);
		this.removeDangerousElements(element);
		rdom.removePlaceHoldersAndEmptyNodes(element);
		this.validateAppleStyleTags(element);
	},
	
	validateString: function(html) {
		try {
			html = this.addNbspToEmptyBlocks(html);
			html = this.performFullValidation(html);
			html = this.insertNewlineBetweenBlockElements(html);
		} catch(ignored) {}
		
		return html;
	},
	
	invalidateDom: function(element) {
		this.invalidateAppleStyleTags(element);
	},
	
	invalidateString: function(html) {
		html = this.replaceTag(html, "strong", "b");
		html = this.replaceTag(html, "em", "i");
		html = this.removeComments(html);
		html = this.replaceNbspToBr(html);
		return html;
	},
	
	validateAppleStyleTags: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);
		
		var nodes = xq.getElementsByClassName(rdom.getRoot(), "apple-style-span");
		for(var i = 0; i < nodes.length; i++) {
			var node = nodes[i];
			
			if(node.style.fontStyle === "italic") {
				// span -> em
				node = rdom.replaceTag("em", node);
				node.removeAttribute("class");
				node.style.fontStyle = "";
			} else if(node.style.fontWeight === "bold") {
				// span -> strong
				node = rdom.replaceTag("strong", node);
				node.removeAttribute("class");
				node.style.fontWeight = "";
			} else if(node.style.textDecoration === "underline") {
				// span -> em.underline
				node = rdom.replaceTag("em", node);
				node.className = "underline";
				node.style.textDecoration = "";
			} else if(node.style.textDecoration === "line-through") {
				// span -> span.strike
				node.className = "strike";
				node.style.textDecoration = "";
			} else if(node.style.verticalAlign === "super") {
				// span -> sup
				node = rdom.replaceTag("sup", node);
				node.removeAttribute("class");
				node.style.verticalAlign = "";
			} else if(node.style.verticalAlign === "sub") {
				// span -> sup
				node = rdom.replaceTag("sub", node);
				node.removeAttribute("class");
				node.style.verticalAlign = "";
			} else if(node.style.fontFamily) {
				// span -> span font-family
				node.removeAttribute("class");
			}
		}
	},
	
	invalidateAppleStyleTags: function(element) {
		var rdom = xq.rdom.Base.createInstance();
		rdom.setRoot(element);
		
		// span.strike -> span, span... -> span
		var spans = rdom.getRoot().getElementsByTagName("span");
		for(var i = 0; i < spans.length; i++) {
			var node = spans[i];
			if(node.className == "strike") {
				node.className = "Apple-style-span";
				node.style.textDecoration = "line-through";
			} else if(node.style.fontFamily) {
				node.className = "Apple-style-span";
			}
			// TODO: bg/fg/font-size
		}

		// em -> span, em.underline -> span
		var ems = rdom.getRoot().getElementsByTagName("em");
		for(var i = 0; i < ems.length; i++) {
			var node = ems[i];
			node = rdom.replaceTag("span", node);
			if(node.className === "underline") {
				node.className = "apple-style-span";
				node.style.textDecoration = "underline";
			} else {
				node.className = "apple-style-span";
				node.style.fontStyle = "italic";
			}
		}
		
		// strong -> span
		var strongs = rdom.getRoot().getElementsByTagName("strong");
		for(var i = 0; i < strongs.length; i++) {
			var node = strongs[i];
			node = rdom.replaceTag("span", node);
			node.className = "Apple-style-span";
			node.style.fontWeight = "bold";
		}
		
		// sup -> span
		var sups = rdom.getRoot().getElementsByTagName("sup");
		for(var i = 0; i < sups.length; i++) {
			var node = sups[i];
			node = rdom.replaceTag("span", node);
			node.className = "Apple-style-span";
			node.style.verticalAlign = "super";
		}
		
		// sub -> span
		var subs = rdom.getRoot().getElementsByTagName("sub");
		for(var i = 0; i < subs.length; i++) {
			var node = subs[i];
			node = rdom.replaceTag("span", node);
			node.className = "Apple-style-span";
			node.style.verticalAlign = "sub";
		}
	}
});
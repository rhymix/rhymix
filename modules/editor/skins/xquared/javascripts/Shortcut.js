/**
 * @requires Xquared.js
 */
xq.Shortcut = xq.Class(/** @lends xq.Shortcut.prototype */{
	/**
	 * Interpretes keyboard event.
	 *
     * @constructs
	 */
	initialize: function(keymapOrExpression) {
		xq.addToFinalizeQueue(this);
		this.keymap = keymapOrExpression;
	},
	matches: function(e) {
		if(typeof this.keymap === "string") this.keymap = xq.Shortcut.interprete(this.keymap).keymap;
		
		// check for key code
		var which = xq.Browser.isGecko && xq.Browser.isMac ? (e.keyCode + "_" + e.charCode) : e.keyCode;
		var keyMatches =
			(this.keymap.which === which) ||
			(this.keymap.which === 32 && which === 25); // 25 is SPACE in Type-3 keyboard.
		if(!keyMatches) return false;
		
		// check for modifier
		if(typeof e.metaKey === "undefined") e.metaKey = false;
		
		var modifierMatches = 
			(this.keymap.shiftKey === e.shiftKey || typeof this.keymap.shiftKey === "undefined") &&
			(this.keymap.altKey === e.altKey || typeof this.keymap.altKey === "undefined") &&
			(this.keymap.ctrlKey === e.ctrlKey || typeof this.keymap.ctrlKey === "undefined") &&
			// Webkit turns on meta key flag when alt key is pressed
			(xq.Browser.isWin && xq.Browser.isWebkit || this.keymap.metaKey === e.metaKey || typeof this.keymap.metaKey === "undefined")
		
		return modifierMatches;
	}
});

xq.Shortcut.interprete = function(expression) {
	expression = expression.toUpperCase();
	
	var which = xq.Shortcut._interpreteWhich(expression.split("+").pop());
	var ctrlKey = xq.Shortcut._interpreteModifier(expression, "CTRL");
	var altKey = xq.Shortcut._interpreteModifier(expression, "ALT");
	var shiftKey = xq.Shortcut._interpreteModifier(expression, "SHIFT");
	var metaKey = xq.Shortcut._interpreteModifier(expression, "META");
	
	var keymap = {};
	
	keymap.which = which;
	if(typeof ctrlKey !== "undefined") keymap.ctrlKey = ctrlKey;
	if(typeof altKey !== "undefined") keymap.altKey = altKey;
	if(typeof shiftKey !== "undefined") keymap.shiftKey = shiftKey;
	if(typeof metaKey !== "undefined") keymap.metaKey = metaKey;
	
	return new xq.Shortcut(keymap);
}

xq.Shortcut._interpreteModifier = function(expression, modifierName) {
	return expression.match("\\(" + modifierName + "\\)") ?
		undefined :
			expression.match(modifierName) ?
			true : false;
}
xq.Shortcut._interpreteWhich = function(keyName) {
	var which = keyName.length === 1 ?
		((xq.Browser.isMac && xq.Browser.isGecko) ? "0_" + keyName.toLowerCase().charCodeAt(0) : keyName.charCodeAt(0)) :
		xq.Shortcut._keyNames[keyName];
	
	if(typeof which === "undefined") throw "Unknown special key name: [" + keyName + "]"
	
	return which;
}
xq.Shortcut._keyNames =
	xq.Browser.isMac && xq.Browser.isGecko ?
	{
		BACKSPACE: "8_0",
		TAB: "9_0",
		RETURN: "13_0",
		ENTER: "13_0",
		ESC: "27_0",
		SPACE: "0_32",
		LEFT: "37_0",
		UP: "38_0",
		RIGHT: "39_0",
		DOWN: "40_0",
		DELETE: "46_0",
		HOME: "36_0",
		END: "35_0",
		PAGEUP: "33_0",
		PAGEDOWN: "34_0",
		COMMA: "0_44",
		HYPHEN: "0_45",
		EQUAL: "0_61",
		PERIOD: "0_46",
		SLASH: "0_47",
		F1: "112_0",
		F2: "113_0",
		F3: "114_0",
		F4: "115_0",
		F5: "116_0",
		F6: "117_0",
		F7: "118_0",
		F8: "119_0"
	}
	:
	{
		BACKSPACE: 8,
		TAB: 9,
		RETURN: 13,
		ENTER: 13,
		ESC: 27,
		SPACE: 32,
		LEFT: 37,
		UP: 38,
		RIGHT: 39,
		DOWN: 40,
		DELETE: 46,
		HOME: 36,
		END: 35,
		PAGEUP: 33,
		PAGEDOWN: 34,
		COMMA: 188,
		HYPHEN: xq.Browser.isTrident ? 189 : 109,
		EQUAL: xq.Browser.isTrident ? 187 : 61,
		PERIOD: 190,
		SLASH: 191,
		F1:112,
		F2:113,
		F3:114,
		F4:115,
		F5:116,
		F6:117,
		F7:118,
		F8:119,
		F9:120,
		F10:121,
		F11:122,
		F12:123
	}

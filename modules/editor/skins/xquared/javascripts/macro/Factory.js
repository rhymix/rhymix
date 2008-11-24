/**
 * @requires Xquared.js
 * @requires macro/Base.js
 */
xq.macro.Factory = xq.Class(/** @lends xq.macro.Factory.prototype */{
	/**
     * @constructs
     *
     * @param {String} URL to place holder image.
	 */
	initialize: function(placeHolderImgSrc) {
		this.placeHolderImgSrc = placeHolderImgSrc;
		this.macroClazzes = {};
	},
	/**
	 * Registers new macro by ID.
	 *
	 * @param {String} id Macro id.
	 */
	register: function(id) {
		var clazz = xq.macro[id + "Macro"];
		if(!clazz) throw "Unknown macro id: [" + id + "]";
		
		this.macroClazzes[id] = clazz;
	},
	/**
	 * Creates macro instance by given HTML fragment.
	 *
	 * @param {String} html HTML fragment.
	 * @returns {xq.macro.Base} Macro instance or null if recognization of the HTML fragment fails.
	 */
	createMacroFromHtml: function(html) {
		for(var id in this.macroClazzes) {
			var clazz = this.macroClazzes[id];
			if(clazz.recognize(html)) return new clazz(id, html, this.placeHolderImgSrc);
		}
		return null;
	},
	/**
	 * Creates macro instance by given macro definition.
	 *
	 * @param {Object} def Macro definition.
	 * @returns {xq.macro.Base} Macro instance
	 * @throws If macro not found by def[id].
	 */
	createMacroFromDefinition: function(def) {
		var clazz = this.macroClazzes[def.id];
		if(!clazz) return null;
		
		return new clazz(def.id, def.params, this.placeHolderImgSrc);
	}
})
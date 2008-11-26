/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * 
 * @requires macro/Factory.js
 * @requires Layer.js
 * @requires Json2.js
 * 
 * @requires plugin/Base.js
 */
xq.plugin.MacroPlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.MacroPlugin
	 * @lends xq.plugin.MacroPlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	isEventListener: function() {return true;},
	
	onAfterLoad: function(xed) {
		this.xed = xed;
		this.xed.config.macroIds = [];
		this.layers = [];
	},
	
	onEditorStartInitialization: function(xed) {
		this.xed.validator.addListener(this);
		
		this.xed.macroFactory = new xq.macro.Factory(this.xed.config.imagePathForContent + 'placeholder.gif');
		for(var i = 0; i < this.xed.config.macroIds.length; i++) {
			this.xed.macroFactory.register(this.xed.config.macroIds[i]);
		}

		xed.timer.register(this.updateLayers.bind(this), 100);
		xed.timer.register(this.updateLayerList.bind(this), 2000);
	},
	
	/**
	 * @param {Element} [placeHolder] place holder element
	 */
	attachMacro: function(element) {
		var longdesc = element.getAttribute("longdesc") || element.longdesc;
		var def = JSON.parse(unescape(longdesc));
		var macro = this.xed.macroFactory.createMacroFromDefinition(def);
		var layer = new xq.Layer(this.xed, element, macro.createHtml());
		macro.onLayerInitialzied(layer);
		this.layers.push(layer);
	},
	
	isAttachedPlaceHolder: function(element) {
		for(var i = 0; i < this.layers.length; i++) {
			if(this.layers[i].element === element) return true;
		}
		return false;
	},

	updateLayerList: function() {
		if(this.xed.getCurrentEditMode() !== 'wysiwyg') {
			for(var i = 0; i < this.layers.length; i++) {
				this.layers[i].detach();
			}
			this.layers = [];
		} else {
			var placeHolders = xq.getElementsByClassName(this.xed.rdom.getRoot(), "xqlayer", xq.Browser.isTrident ? "img" : null);
			for(var i = 0; i < placeHolders.length; i++) {
				if(!this.isAttachedPlaceHolder(placeHolders[i])) {
					this.attachMacro(placeHolders[i]);
				}
			}
		}
	},
		
	/**
	 * Updates all layers immediately. If there're invalid layers, detachs and removes them.
	 */
	updateLayers: function() {
		if(this.xed.getCurrentEditMode() !== 'wysiwyg') return;
		
		for(var i = 0; i < this.layers.length; i++) {
			var layer = this.layers[i];
			if(layer.isValid()) {
				layer.updatePosition();
			} else {
				layer.detach();
				this.layers.splice(i, 1);
			}
		}
	},

	onValidatorPreprocessing: function(html) {
		var p = xq.compilePattern("<(IFRAME|SCRIPT|OBJECT|EMBED)\\s+[^>]+(?:/>|>.*?</(?:IFRAME|SCRIPT|OBJECT|EMBED)>)", "img");
		html.value = html.value.replace(p, function(str, tag) {
			var macro = this.xed.macroFactory.createMacroFromHtml(str);
			return macro ? macro.createPlaceHolderHtml() : "";
		}.bind(this));
	},
	
	onValidatorAfterStringValidation: function(html) {
		var p1 = /<img\s+[^>]*class="xqlayer"\s+[^>]*\/>/mg;
		var p2 = /<img\s+[^>]*longdesc="(.+?)"\s+[^>]*\/>/m;
		
		html.value = html.value.replace(p1, function(img) {
			var def = JSON.parse(unescape(img.match(p2)[1]));
			var macro = this.xed.macroFactory.createMacroFromDefinition(def);
			return macro.createHtml();
		}.bind(this));
	}
});
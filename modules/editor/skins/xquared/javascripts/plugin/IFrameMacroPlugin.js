/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 * @requires ui/Control.js
 * @requires macro/Factory.js
 * @requires macro/IFrameMacro.js
 */
xq.plugin.IFrameMacroPlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.IFrameMacroPlugin
	 * @lends xq.plugin.IFrameMacroPlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	onAfterLoad: function(xed) {
		xed.config.macroIds.push("IFrame");
		xed.config.defaultToolbarButtonGroups.insert.push(
			{className:"iframe", title:"IFrame", handler:"xed.handleIFrame()"}
		)
		
		xed.handleIFrame = function() {
			var dialog = new xq.ui.FormDialog(
					this,
					xq.ui_templates.basicIFrameDialog,
					function(dialog) {},
					function(data) {
						this.focus();
						
						// cancel?
						if(!data) return;
						
						var macro = this.macroFactory.createMacroFromDefinition({id:"IFrame", params:data});
						if(macro) {
							var placeHolder = macro.createPlaceHolderHtml();
							this.rdom.insertHtml(placeHolder);
						} else {
							alert("Unknown error");
						}
					}.bind(this)
			);
			
			dialog.show({position: 'centerOfEditor'});
			
			return true;
		}
	}
});
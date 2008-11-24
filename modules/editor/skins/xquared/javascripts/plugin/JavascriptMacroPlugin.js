/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 * @requires ui/Control.js
 * @requires macro/Factory.js
 * @requires macro/JavascriptMacro.js
 */
xq.plugin.JavascriptMacroPlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.JavascriptMacroPlugin
	 * @lends xq.plugin.JavascriptMacroPlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	onAfterLoad: function(xed) {
		xed.config.macroIds.push("Javascript");
		xed.config.defaultToolbarButtonGroups.insert.push(
			{className:"script", title:"Script", handler:"xed.handleScript()"}
		)
		
		xed.handleInsertScript = function(url) {
			var params = {url: url};
			var macro = this.macroFactory.createMacroFromDefinition({id:"Javascript", params:params});
			if(macro) {
				var placeHolder = macro.createPlaceHolderHtml();
				this.rdom.insertHtml(placeHolder);

				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
			} else {
				alert("Unknown URL pattern");
			}
			return true;
		};
		
		xed.handleScript = function() {
			var dialog = new xq.ui.FormDialog(
				this,
				xq.ui_templates.basicScriptDialog,
				function(dialog) {},
				function(data) {
					this.focus();
					
					if(xq.Browser.isTrident) {
						var rng = this.rdom.rng();
						rng.moveToBookmark(bm);
						rng.select();
					}
					
					// cancel?
					if(!data) return;
					
					this.handleInsertScript(data.url);
				}.bind(this)
			);

			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			dialog.show({position: 'centerOfEditor'});
			
			return true;
		}
	}
});
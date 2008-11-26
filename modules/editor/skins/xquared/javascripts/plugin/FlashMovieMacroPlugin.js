/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 * @requires ui/Control.js
 * @requires macro/Factory.js
 * @requires macro/FlashMovieMacro.js
 */
xq.plugin.FlashMovieMacroPlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.FlashMovieMacroPlugin
	 * @lends xq.plugin.FlashMovieMacroPlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	onAfterLoad: function(xed) {
		xed.config.macroIds.push("FlashMovie");
		xed.config.defaultToolbarButtonGroups.insert.push(
			{className:"movie", title:"Movie", handler:"xed.handleMovie()"}
		)
		
		xed.handleInsertMovie = function(html) {
			var macro = this.macroFactory.createMacroFromDefinition({id:"FlashMovie", params:{html:html}});
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
		
		xed.handleMovie = function() {
			var dialog = new xq.ui.FormDialog(
				this,
				xq.ui_templates.basicMovieDialog,
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
					
					this.handleInsertMovie(data.html);
				}.bind(this)
			);

			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			dialog.show({position: 'centerOfEditor'});
			
			return true;
		}
	}
});
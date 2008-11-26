/**
 * @namespace
 */
xq.plugin = {};

/**
 * @requires Xquared.js
 */
xq.plugin.Base = xq.Class(/** @lends xq.plugin.Base.prototype */{
	/**
     * Abstract base class for Xquared plugins.
     * 
     * @constructs
     */
	initialize: function() {},
	
	/**
	 * Loads plugin. Automatically called by xq.Editor.
	 *
	 * @param {xq.Editor} editor Editor instance.
	 */
	load: function(editor) {
		this.editor = editor;
		if(this.isEventListener()) this.editor.addListener(this);
		
		this.onBeforeLoad(this.editor);
		this.editor.addShortcuts(this.getShortcuts() || []);
		this.editor.addAutocorrections(this.getAutocorrections() || []);
		this.editor.addAutocompletions(this.getAutocompletions() || []);
		this.editor.addTemplateProcessors(this.getTemplateProcessors() || []);
		this.editor.addContextMenuHandlers(this.getContextMenuHandlers() || []);
		this.onAfterLoad(this.editor);
	},
	
	/**
	 * Unloads plugin. Automatically called by xq.Editor
	 */
	unload: function() {
		this.onBeforeUnload(this.editor);
		for(var key in this.getShortcuts()) this.editor.removeShortcut(key);
		for(var key in this.getAutocorrections()) this.editor.removeAutocorrection(key);
		for(var key in this.getAutocompletions()) this.editor.removeAutocompletion(key);
		for(var key in this.getTemplateProcessors()) this.editor.removeTemplateProcessor(key);
		for(var key in this.getContextMenuHandlers()) this.editor.removeContextMenuHandler(key);
		this.onAfterUnload(this.editor);
	},
	
	
	
	/**
	 * Always returns false.<br />
	 * <br />
	 * Derived class may override this to make a plugin as a event listener.<br />
	 * Whenever you override this function, you should also implement at least one event handler for xq.Editor.
	 */
	isEventListener: function() {return false},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	onBeforeLoad: function(editor) {},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	onAfterLoad: function(editor) {},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	onBeforeUnload: function(editor) {},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	onAfterUnload: function(editor) {},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	getShortcuts: function() {return [];},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	getAutocorrections: function() {return [];},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	getAutocompletions: function() {return [];},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	getTemplateProcessors: function() {return [];},
	
	/**
	 * Callback function. Derived class may override this.
	 */
	getContextMenuHandlers: function() {return [];}
});
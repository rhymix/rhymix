/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Timer.js
 * @requires rdom/Factory.js
 * @requires validator/Factory.js
 * @requires EditHistory.js
 * @requires plugin/Base.js
 * @requires RichTable.js
 * @requires ui/Control.js
 * @requires ui/Toolbar.js
 * @requires ui/_templates.js
 * @requires Shortcut.js
 */
xq.Editor = xq.Class(/** @lends xq.Editor.prototype */{
	/**
	 * Initialize editor but it doesn't automatically start designMode. setEditMode should be called after initialization.
	 *
     * @constructs
	 * @param {Object} contentElement TEXTAREA to be replaced with editable area, or DOM ID string for TEXTAREA.
	 * @param {Object} toolbarContainer HTML element which contains toolbar icons, or DOM ID string.
	 */
	initialize: function(contentElement, toolbarContainer) {
		xq.addToFinalizeQueue(this);
		
		if(typeof contentElement === 'string') {
			contentElement = xq.$(contentElement);
		}
		if(!contentElement) {
			throw "[contentElement] is null";
		}
		if(contentElement.nodeName !== 'TEXTAREA') {
			throw "[contentElement] is not a TEXTAREA";
		}
		
		xq.asEventSource(this, "Editor", ["StartInitialization", "Initialized", "ElementChanged", "BeforeEvent", "AfterEvent", "CurrentContentChanged", "StaticContentChanged", "CurrentEditModeChanged"]);
		
		/**
		 * Editor's configuration.
		 * @type object
		 */
		this.config = {};
		
		/**
		 * Automatically gives initial focus.
		 * @type boolean
		 */
		this.config.autoFocusOnInit = false;
		
		/**
		 * Makes links clickable.
		 * @type boolean
		 */
		this.config.enableLinkClick = false;
		
		/**
		 * Changes mouse cursor to pointer when the cursor is on a link.
		 * @type boolean
		 */
		this.config.changeCursorOnLink = false;
		
		/**
		 * Generates default toolbar if there's no toolbar provided.
		 * @type boolean
		 */
		this.config.generateDefaultToolbar = true;
		
		
		this.config.defaultToolbarButtonGroups = {
			"color": [
 				{className:"foregroundColor", title:"Foreground color", handler:"xed.handleForegroundColor()"},
				{className:"backgroundColor", title:"Background color", handler:"xed.handleBackgroundColor()"}
 			],
 			"font": [
				{className:"fontFace", title:"Font face", list:[
                    {title:"Arial", handler:"xed.handleFontFace('Arial')"},
                    {title:"Helvetica", handler:"xed.handleFontFace('Helvetica')"},
                    {title:"Serif", handler:"xed.handleFontFace('Serif')"},
                    {title:"Tahoma", handler:"xed.handleFontFace('Tahoma')"},
                    {title:"Verdana", handler:"xed.handleFontFace('Verdana')"}
				]},
				{className:"fontSize", title:"Font size", list:[
                    {title:"1", handler:"xed.handleFontSize('1')"},
                    {title:"2", handler:"xed.handleFontSize('2')"},
                    {title:"3", handler:"xed.handleFontSize('3')"},
                    {title:"4", handler:"xed.handleFontSize('4')"},
                    {title:"5", handler:"xed.handleFontSize('5')"},
                    {title:"6", handler:"xed.handleFontSize('6')"}
				]}
			],
			"link": [
			    {className:"link", title:"Link", handler:"xed.handleLink()"},
			    {className:"removeLink", title:"Remove link", handler:"xed.handleRemoveLink()"}
			],
			"style": [
				{className:"strongEmphasis", title:"Strong emphasis", handler:"xed.handleStrongEmphasis()"},
				{className:"emphasis", title:"Emphasis", handler:"xed.handleEmphasis()"},
				{className:"underline", title:"Underline", handler:"xed.handleUnderline()"},
				{className:"strike", title:"Strike", handler:"xed.handleStrike()"},
				{className:"superscription", title:"Superscription", handler:"xed.handleSuperscription()"},
				{className:"subscription", title:"Subscription", handler:"xed.handleSubscription()"},
				{className:"removeFormat", title:"Remove format", handler:"xed.handleRemoveFormat()"}
			],
			"justification": [
  				{className:"justifyLeft", title:"Justify left", handler:"xed.handleJustify('left')"},
				{className:"justifyCenter", title:"Justify center", handler:"xed.handleJustify('center')"},
				{className:"justifyRight", title:"Justify right", handler:"xed.handleJustify('right')"},
				{className:"justifyBoth", title:"Justify both", handler:"xed.handleJustify('both')"}
			],
			"indentation": [
				{className:"indent", title:"Indent", handler:"xed.handleIndent()"},
				{className:"outdent", title:"Outdent", handler:"xed.handleOutdent()"}
  			],
  			"block": [
				{className:"paragraph", title:"Paragraph", handler:"xed.handleApplyBlock('P')"},
				{className:"heading1", title:"Heading 1", handler:"xed.handleApplyBlock('H1')"},
				{className:"blockquote", title:"Blockquote", handler:"xed.handleApplyBlock('BLOCKQUOTE')"},
				{className:"code", title:"Code", handler:"xed.handleList('OL', 'code')"},
				{className:"division", title:"Division", handler:"xed.handleApplyBlock('DIV')"},
				{className:"unorderedList", title:"Unordered list", handler:"xed.handleList('UL')"},
				{className:"orderedList", title:"Ordered list", handler:"xed.handleList('OL')"}
  			],
  			"insert": [
				{className:"table", title:"Table", handler:"xed.handleTable(4, 4,'tl')"},
				{className:"separator", title:"Separator", handler:"xed.handleSeparator()"}
  			]
		};
		
		/**
		 * Button map for default toolbar
		 * @type Object
		 */
		this.config.defaultToolbarButtonMap = [
		    this.config.defaultToolbarButtonGroups.color,
		    this.config.defaultToolbarButtonGroups.font,
		    this.config.defaultToolbarButtonGroups.link,
		    this.config.defaultToolbarButtonGroups.style,
		    this.config.defaultToolbarButtonGroups.justification,
		    this.config.defaultToolbarButtonGroups.indentation,
		    this.config.defaultToolbarButtonGroups.block,
		    this.config.defaultToolbarButtonGroups.insert,
			[
				{className:"html", title:"Edit source", handler:"xed.toggleSourceAndWysiwygMode()"}
			],
			[
				{className:"undo", title:"Undo", handler:"xed.handleUndo()"},
				{className:"redo", title:"Redo", handler:"xed.handleRedo()"}
			]
		];
		
		/**
		 * Image path for default toolbar.
		 * @type String
		 */
		this.config.imagePathForDefaultToolbar = '../images/toolbar/';
		
		/**
		 * Image path for content.
		 * @type String
		 */
		this.config.imagePathForContent = '../images/content/';
		
		/**
		 * Widget Container path.
		 * @type String
		 */
		this.config.widgetContainerPath = 'widget_container.html';
		
		/**
		 * Array of URL containig CSS for WYSIWYG area.
		 * @type Array
		 */
		this.config.contentCssList = ['../stylesheets/xq_contents.css'];
		
		/**
		 * URL Validation mode. One or "relative", "host_relative", "absolute", "browser_default"
		 * @type String
		 */
		this.config.urlValidationMode = 'absolute';
		
		/**
		 * Turns off validation in source editor.<br />
		 * Note that the validation will be performed regardless of this value when you switching edit mode.
		 * @type boolean
		 */
		this.config.noValidationInSourceEditMode = false;
		
		/**
		 * Automatically hooks onsubmit event.
		 * @type boolean
		 */
		this.config.automaticallyHookSubmitEvent = true;
		
		/**
		 * Set of whitelist(tag name and attributes) for use in validator
		 * @type Object
		 */
		this.config.whitelist = xq.predefinedWhitelist;
		
		/**
		 * Specifies a value of ID attribute for WYSIWYG document's body
		 * @type String
		 */
		this.config.bodyId = "";
		
		/**
		 * Specifies a value of CLASS attribute for WYSIWYG document's body
		 * @type String
		 */
		this.config.bodyClass = "xed";
		
		/**
		 * Plugins
		 * @type Object
		 */
		this.config.plugins = {};
		
		/**
		 * Shortcuts
		 * @type Object
		 */
		this.config.shortcuts = {};
		
		/**
		 * Autocorrections
		 * @type Object
		 */
		this.config.autocorrections = {};
		
		/**
		 * Autocompletions
		 * @type Object
		 */
		this.config.autocompletions = {};
		
		/**
		 * Template processors
		 * @type Object
		 */
		this.config.templateProcessors = {};
		
		/**
		 * Context menu handlers
		 * @type Object
		 */
		this.config.contextMenuHandlers = {};
		
		/**
		 * Original content element
		 * @type Element
		 */
		this.contentElement = contentElement;
		
		/**
		 * Owner document of content element
		 * @type Document
		 */
		this.doc = this.contentElement.ownerDocument;
		
		/**
		 * Body of content element
		 * @type Element
		 */
		this.body = this.doc.body;
		
		/**
		 * False or 'source' means source editing mode, true or 'wysiwyg' means WYSIWYG editing mode.
		 * @type Object
		 */
		this.currentEditMode = '';

		/**
		 * Timer
		 * @type xq.Timer
		 */
		this.timer = new xq.Timer(100);
		
		/**
		 * Base instance
		 * @type xq.rdom.Base
		 */
		this.rdom = xq.rdom.Base.createInstance();
		
		/**
		 * Base instance
		 * @type xq.validator.Base
		 */
		this.validator = null;
		
		/**
		 * Outmost wrapper div
		 * @type Element
		 */
		this.outmostWrapper = null;
		
		/**
		 * Source editor container
		 * @type Element
		 */
		this.sourceEditorDiv = null;
		
		/**
		 * Source editor textarea
		 * @type Element
		 */
		this.sourceEditorTextarea = null;
		
		/**
		 * WYSIWYG editor container
		 * @type Element
		 */
		this.wysiwygEditorDiv = null;
		
		/**
		 * Outer frame
		 * @type IFrame
		 */
		this.outerFrame = null;
		
		/**
		 * Design mode iframe
		 * @type IFrame
		 */
		this.editorFrame = null;
		
		this.toolbarContainer = toolbarContainer;
		
		/**
		 * Toolbar container
		 * @type Element
		 */
		this.toolbar = null;
		
		/**
		 * Undo/redo manager
		 * @type xq.EditHistory
		 */
		this.editHistory = null;
		
		/**
		 * Context menu container
		 * @type Element
		 */
		this.contextMenuContainer = null;
		
		/**
		 * Context menu items
		 * @type Array
		 */
		this.contextMenuItems = null;
		
		/**
		 * Platform dependent key event type
		 * @type String
		 */
		this.platformDepedentKeyEventType = (xq.Browser.isMac && xq.Browser.isGecko ? "keypress" : "keydown");
		
		this.addShortcuts(this.getDefaultShortcuts());
		
		this.addListener({
			onEditorCurrentContentChanged: function(xed) {
				var curFocusElement = xed.rdom.getCurrentElement();
				if(!curFocusElement || curFocusElement.ownerDocument !== xed.rdom.getDoc()) {
					return;
				}
				
				if(xed.lastFocusElement !== curFocusElement) {
					if(!xed.rdom.tree.isBlockOnlyContainer(xed.lastFocusElement) && xed.rdom.tree.isBlock(xed.lastFocusElement)) {
						xed.rdom.removeTrailingWhitespace(xed.lastFocusElement);
					}
					xed._fireOnElementChanged(xed, xed.lastFocusElement, curFocusElement);
					xed.lastFocusElement = curFocusElement;
				}
				
				xed.toolbar.triggerUpdate();
			}
		});
	},
	
	finalize: function() {
		for(var key in this.config.plugins) this.config.plugins[key].unload();
	},
	
	
	
	/////////////////////////////////////////////
	// Configuration Management
	
	getDefaultShortcuts: function() {
		if(xq.Browser.isMac) {
			// Mac FF & Safari
			return [
				{event:"Ctrl+Shift+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"SPACE", handler:"this.handleSpace()"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
				
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Meta+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Meta+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Meta+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Meta+K", handler:"this.handleStrike()"},
				{event:"Meta+Z", handler:"this.handleUndo()"},
				{event:"Meta+Shift+Z", handler:"this.handleRedo()"},
				{event:"Meta+Y", handler:"this.handleRedo()"}
			];
		} else if(xq.Browser.isUbuntu) {
			//  Ubunto FF
			return [
				{event:"Ctrl+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"SPACE", handler:"this.handleSpace()"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
			
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Ctrl+Z", handler:"this.handleUndo()"},
				{event:"Ctrl+Shift+Z", handler:"this.handleRedo()"},
				{event:"Ctrl+Y", handler:"this.handleRedo()"}
			];
		} else {
			// Win IE & FF
			return [
				{event:"Ctrl+SPACE", handler:"this.handleAutocompletion(); stop = true;"},
				{event:"SPACE", handler:"this.handleSpace()"},
				{event:"ENTER", handler:"this.handleEnter(false, false)"},
				{event:"Ctrl+ENTER", handler:"this.handleEnter(true, false)"},
				{event:"Ctrl+Shift+ENTER", handler:"this.handleEnter(true, true)"},
				{event:"TAB", handler:"this.handleTab()"},
				{event:"Shift+TAB", handler:"this.handleShiftTab()"},
				{event:"DELETE", handler:"this.handleDelete()"},
				{event:"BACKSPACE", handler:"this.handleBackspace()"},
			
				{event:"Ctrl+B", handler:"this.handleStrongEmphasis()"},
				{event:"Ctrl+I", handler:"this.handleEmphasis()"},
				{event:"Ctrl+U", handler:"this.handleUnderline()"},
				{event:"Ctrl+K", handler:"this.handleStrike()"},
				{event:"Ctrl+Z", handler:"this.handleUndo()"},
				{event:"Ctrl+Shift+Z", handler:"this.handleRedo()"},
				{event:"Ctrl+Y", handler:"this.handleRedo()"}
			];
		}
	},
	
	/**
	 * Adds or replaces plugin.
	 *
	 * @param {String} id unique identifier
	 */
	addPlugin: function(id) {
		// already added?
		if(this.config.plugins[id]) return;
		
		// else
		var clazz = xq.plugin[id + "Plugin"];
		if(!clazz) throw "Unknown plugin id: [" + id + "]";
		
		var plugin = new clazz();
		this.config.plugins[id] = plugin;
		plugin.load(this);
	},

	/**
	 * Adds several plugins at once.
	 *
	 * @param {Array} list of plugin ids.
	 */
	addPlugins: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addPlugin(list[i]);
		}
	},
	
	/**
	 * Returns plugin matches with given identifier.
	 *
	 * @param {String} id unique identifier
	 */
	getPlugin: function(id) {return this.config.plugins[id];},

	/**
	 * Returns entire plugins
	 */
	getPlugins: function() {return this.config.plugins;},
	
	/**
	 * Remove plugin matches with given identifier.
	 *
	 * @param {String} id unique identifier
	 */
	removePlugin: function(id) {
		var plugin = this.config.shortcuts[id];
		if(plugin) {
			plugin.unload();
		}
		
		delete this.config.shortcuts[id];
	},
	
	
	
	/**
	 * Adds or replaces keyboard shortcut.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 * @param {Object} handler string or function to be evaluated or called
	 */
	addShortcut: function(shortcut, handler) {
		this.config.shortcuts[shortcut] = {"event":new xq.Shortcut(shortcut), "handler":handler};
	},
	
	/**
	 * Adds several keyboard shortcuts at once.
	 *
	 * @param {Array} list of shortcuts. each element should have following structure: {event:"keymap expression", handler:handler}
	 */
	addShortcuts: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addShortcut(list[i].event, list[i].handler);
		}
	},

	/**
	 * Returns keyboard shortcut matches with given keymap expression.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 */
	getShortcut: function(shortcut) {return this.config.shortcuts[shortcut];},

	/**
	 * Returns entire keyboard shortcuts' map
	 */
	getShortcuts: function() {return this.config.shortcuts;},
	
	/**
	 * Remove keyboard shortcut matches with given keymap expression.
	 *
	 * @param {String} shortcut keymap expression like "CTRL+Space"
	 */
	removeShortcut: function(shortcut) {delete this.config.shortcuts[shortcut];},
	
	/**
	 * Adds or replaces autocorrection handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} criteria regex pattern or function to be used as a criterion for match
	 * @param {Object} handler string or function to be evaluated or called when criteria met
	 */
	addAutocorrection: function(id, criteria, handler) {
		if(criteria.exec) {
			var pattern = criteria;
			criteria = function(text) {return text.match(pattern)};
		}
		this.config.autocorrections[id] = {"criteria":criteria, "handler":handler};
	},
	
	/**
	 * Adds several autocorrection handlers at once.
	 *
	 * @param {Array} list of autocorrection. each element should have following structure: {id:"identifier", criteria:criteria, handler:handler}
	 */
	addAutocorrections: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addAutocorrection(list[i].id, list[i].criteria, list[i].handler);
		}
	},
	
	/**
	 * Returns autocorrection handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getAutocorrection: function(id) {return this.config.autocorrection[id];},
	
	/**
	 * Returns entire autocorrections' map
	 */
	getAutocorrections: function() {return this.config.autocorrections;},
	
	/**
	 * Removes autocorrection handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeAutocorrection: function(id) {delete this.config.autocorrections[id];},
	
	/**
	 * Adds or replaces autocompletion handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} criteria regex pattern or function to be used as a criterion for match
	 * @param {Object} handler string or function to be evaluated or called when criteria met
	 */
	addAutocompletion: function(id, criteria, handler) {
		if(criteria.exec) {
			var pattern = criteria;
			criteria = function(text) {
				var m = pattern.exec(text);
				return m ? m.index : -1;
			};
		}
		this.config.autocompletions[id] = {"criteria":criteria, "handler":handler};
	},
	
	/**
	 * Adds several autocompletion handlers at once.
	 *
	 * @param {Array} list of autocompletion. each element should have following structure: {id:"identifier", criteria:criteria, handler:handler}
	 */
	addAutocompletions: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addAutocompletion(list[i].id, list[i].criteria, list[i].handler);
		}
	},
	
	/**
	 * Returns autocompletion handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getAutocompletion: function(id) {return this.config.autocompletions[id];},
	
	/**
	 * Returns entire autocompletions' map
	 */
	getAutocompletions: function() {return this.config.autocompletions;},
	
	/**
	 * Removes autocompletion handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeAutocompletion: function(id) {delete this.config.autocompletions[id];},
	
	/**
	 * Adds or replaces template processor.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} handler string or function to be evaluated or called when template inserted
	 */
	addTemplateProcessor: function(id, handler) {
		this.config.templateProcessors[id] = {"handler":handler};
	},
	
	/**
	 * Adds several template processors at once.
	 *
	 * @param {Array} list of template processors. Each element should have following structure: {id:"identifier", handler:handler}
	 */
	addTemplateProcessors: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addTemplateProcessor(list[i].id, list[i].handler);
		}
	},
	
	/**
	 * Returns template processor matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getTemplateProcessor: function(id) {return this.config.templateProcessors[id];},

	/**
	 * Returns entire template processors' map
	 */
	getTemplateProcessors: function() {return this.config.templateProcessors;},

	/**
	 * Removes template processor matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeTemplateProcessor: function(id) {delete this.config.templateProcessors[id];},



	/**
	 * Adds or replaces context menu handler.
	 *
	 * @param {String} id unique identifier
	 * @param {Object} handler string or function to be evaluated or called when onContextMenu occured
	 */
	addContextMenuHandler: function(id, handler) {
		this.config.contextMenuHandlers[id] = {"handler":handler};
	},
	
	/**
	 * Adds several context menu handlers at once.
	 *
	 * @param {Array} list of handlers. Each element should have following structure: {id:"identifier", handler:handler}
	 */
	addContextMenuHandlers: function(list) {
		for(var i = 0; i < list.length; i++) {
			this.addContextMenuHandler(list[i].id, list[i].handler);
		}
	},
	
	/**
	 * Returns context menu handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	getContextMenuHandler: function(id) {return this.config.contextMenuHandlers[id];},

	/**
	 * Returns entire context menu handlers' map
	 */
	getContextMenuHandlers: function() {return this.config.contextMenuHandlers;},

	/**
	 * Removes context menu handler matches with given id
	 *
	 * @param {String} id unique identifier
	 */
	removeContextMenuHandler: function(id) {delete this.config.contextMenuHandlers[id];},
	
	
	
	/**
	 * Sets width of editor.
	 *
	 * @param {String} w Valid CSS value for style.width. For example, "100%", "200px".
	 */
	setWidth: function(w) {
		this.outmostWrapper.style.width = w;
	},
	
	
	
	/**
	 * Sets height of editor.
	 *
	 * @param {String} h Valid CSS value for style.height. For example, "100%", "200px".
	 */
	setHeight: function(h) {
		this.wysiwygEditorDiv.style.height = h;
		this.sourceEditorDiv.style.height = h;
	},
	
	
	
	/////////////////////////////////////////////
	// Edit mode management
	
	/**
	 * Returns current edit mode - wysiwyg, source
	 */
	getCurrentEditMode: function() {
		return this.currentEditMode;
	},
	
	/**
	 * Toggle edit mode between source and wysiwyg 
	 */
	toggleSourceAndWysiwygMode: function() {
		var mode = this.getCurrentEditMode();
		this.setEditMode(mode === 'wysiwyg' ? 'source' : 'wysiwyg');
	},
	
	/**
	 * Switches between WYSIWYG/Source mode.
	 *
	 * @param {String} mode 'wysiwyg' means WYSIWYG editing mode, and 'source' means source editing mode.
	 */
	setEditMode: function(mode) {
		if(typeof mode !== 'string') throw "[mode] is not a string."
		if(['wysiwyg', 'source'].indexOf(mode) === -1) throw "Illegal [mode] value: '" + mode + "'. Use 'wysiwyg' or 'source'";
		if(this.currentEditMode === mode) return;
		
		// create editor frame if there's no editor frame.
		var editorCreated = !!this.outmostWrapper;
		if(!editorCreated) {
			// create validator
			this.validator = xq.validator.Base.createInstance(
				this.doc.location.href,
				this.config.urlValidationMode,
				this.config.whitelist
			);

			this._fireOnStartInitialization(this);

			this._createEditorFrame(mode);
			var temp = window.setInterval(function() {
				// wait for loading
				if(this.getBody()) {
					window.clearInterval(temp);
	
					// @WORKAROUND: it is needed to fix IE6 horizontal scrollbar problem
					if(xq.Browser.isIE6) {
						this.rdom.getDoc().documentElement.style.overflowY='auto';
						this.rdom.getDoc().documentElement.style.overflowX='hidden';
					}
					
					this.setEditMode(mode);
					if(this.config.autoFocusOnInit) this.focus();
					
					this.timer.start();
					this._fireOnInitialized(this);
				}
			}.bind(this), 10);
			
			return;
		}
		
		// switch mode
		if(mode === 'wysiwyg') {
			this._setEditModeToWysiwyg();
		} else { // mode === 'source'
			this._setEditModeToSource();
		}
		
		// fire event
		var oldEditMode = this.currentEditMode;
		this.currentEditMode = mode;
		
		this._fireOnCurrentEditModeChanged(this, oldEditMode, this.currentEditMode);
	},
	
	_setEditModeToWysiwyg: function() {
		// Turn off static content and source editor
		this.contentElement.style.display = "none";
		this.sourceEditorDiv.style.display = "none";
		
		// Update contents
		if(this.currentEditMode === 'source') {
			// get html from source editor
			var html = this.getSourceContent(true);
			
			// invalidate it and load it into wysiwyg editor
			var invalidHtml = this.validator.invalidate(html);
			invalidHtml = this.removeUnnecessarySpaces(invalidHtml);
			if(invalidHtml.isBlank()) {
				this.rdom.clearRoot();
			} else {
				this.rdom.getRoot().innerHTML = invalidHtml;
				this.rdom.wrapAllInlineOrTextNodesAs("P", this.rdom.getRoot(), true);
			}
		} else {
			// invalidate static html and load it into wysiwyg editor
			var invalidHtml = this.validator.invalidate(this.getStaticContent());
			invalidHtml = this.removeUnnecessarySpaces(invalidHtml);
			if(invalidHtml.isBlank()) {
				this.rdom.clearRoot();
			} else {
				this.rdom.getRoot().innerHTML = invalidHtml;
				this.rdom.wrapAllInlineOrTextNodesAs("P", this.rdom.getRoot(), true);
			}
		}
		
		// Turn on wysiwyg editor
		this.wysiwygEditorDiv.style.display = "block";
		this.outmostWrapper.style.display = "block";
		
		// Without this, xq.rdom.Base.focus() doesn't work correctly.
		if(xq.Browser.isGecko) this.rdom.placeCaretAtStartOf(this.rdom.getRoot());
		
		if(this.toolbar) this.toolbar.enableButtons();
	},
	
	_setEditModeToSource: function() {
		// Update contents
		var validHtml = null;
		if(this.currentEditMode === 'wysiwyg') {
			validHtml = this.getWysiwygContent();
		} else {
			validHtml = this.getStaticContent();
		}
		this.sourceEditorTextarea.value = validHtml

		// Turn off static content and wysiwyg editor
		this.contentElement.style.display = "none";
		this.wysiwygEditorDiv.style.display = "none";

		// Turn on source editor
		this.sourceEditorDiv.style.display = "block";
		this.outmostWrapper.style.display = "block";
		if(this.toolbar) this.toolbar.disableButtons(['html']);
	},
	
	/**
	 * Load CSS into WYSIWYG mode document
	 *
	 * @param {string} path URL
	 */
	loadStylesheet: function(path) {
		var head = this.getDoc().getElementsByTagName("HEAD")[0];
		var link = this.getDoc().createElement("LINK");
		link.rel = "Stylesheet";
		link.type = "text/css";
		link.href = path;
		head.appendChild(link);
	},
	
	/**
	 * Sets editor's dynamic content from static content
	 */
	loadCurrentContentFromStaticContent: function() {
		if(this.getCurrentEditMode() == 'wysiwyg') {
			// update WYSIWYG editor
			var html = this.validator.invalidate(this.getStaticContent());
			html = this.removeUnnecessarySpaces(html);
			
			if(html.isBlank()) {
				this.rdom.clearRoot();
			} else {
				this.rdom.getRoot().innerHTML = html;
				this.rdom.wrapAllInlineOrTextNodesAs("P", this.rdom.getRoot(), true);
			}
		} else { // 'source'
			this.sourceEditorTextarea.value = this.getStaticContent();
		}
		
		this._fireOnCurrentContentChanged(this);
	},

	/**
	 * Removes unnecessary spaces, tabs and new lines.
	 * 
	 * @param {String} html HTML string.
	 * @returns {String} Modified HTML string.
	 */
	removeUnnecessarySpaces: function(html) {
		var blocks = this.rdom.tree.getBlockTags().join("|");
		var regex = new RegExp("\\s*<(/?)(" + blocks + ")>\\s*", "img");
		return html.replace(regex, '<$1$2>');
	},
	
	/**
	 * Gets editor's dynamic content from current editor(source or WYSIWYG)
	 * 
	 * @return {Object} HTML String
	 */
	getCurrentContent: function() {
		if(this.getCurrentEditMode() === 'source') {
			return this.getSourceContent(this.config.noValidationInSourceEditMode);
		} else {
			return this.getWysiwygContent();
		}
	},
	
	/**
	 * Gets editor's dynamic content from WYSIWYG editor
	 * 
	 * @return {Object} HTML String
	 */
	getWysiwygContent: function() {
		return this.validator.validate(this.rdom.getRoot());
	},
	
	/**
	 * Gets editor's dynamic content from source editor
	 * 
	 * @return {Object} HTML String
	 */
	getSourceContent: function(noValidation) {
		var raw = this.sourceEditorTextarea.value;
		if(noValidation) return raw;
		
		var tempDiv = document.createElement('div');
		tempDiv.innerHTML = this.removeUnnecessarySpaces(raw);
		
		var rdom = xq.rdom.Base.createInstance();
		rdom.wrapAllInlineOrTextNodesAs("P", tempDiv, true);
		
		return this.validator.validate(tempDiv, true);
	},
	
	/**
	 * Sets editor's original content
	 *
	 * @param {Object} content HTML String
	 */
	setStaticContent: function(content) {
		this.contentElement.value = content;
		this._fireOnStaticContentChanged(this, content);
	},
	
	/**
	 * Gets editor's original content
	 *
	 * @return {Object} HTML String
	 */
	getStaticContent: function() {
		return this.contentElement.value;
	},
	
	/**
	 * Gets editor's original content as (newely created) DOM node
	 *
	 * @return {Element} DIV element
	 */
	getStaticContentAsDOM: function() {
		var div = this.doc.createElement('DIV');
		div.innerHTML = this.contentElement.value;
		return div;
	},
	
	/**
	 * Gives focus to editor
	 */
	focus: function() {
		if(this.getCurrentEditMode() === 'wysiwyg') {
			this.rdom.focus();
			if(this.toolbar) this.toolbar.triggerUpdate();
		} else if(this.getCurrentEditMode() === 'source') {
			this.sourceEditorTextarea.focus();
		}
	},
	
	getWysiwygEditorDiv: function() {
		return this.wysiwygEditorDiv;
	},
	
	getSourceEditorDiv: function() {
		return this.sourceEditorDiv;
	},
	
	/**
	 * Returns outer iframe object
	 */
	getOuterFrame: function() {
		return this.outerFrame;
	},
	
	/**
	 * Returns outer iframe document
	 */
	getOuterDoc: function() {
		return this.outerFrame.contentWindow.document;
	},
	
	/**
	 * Returns designmode iframe object
	 */
	getFrame: function() {
		return this.editorFrame;
	},
	
	/**
	 * Returns designmode window object
	 */
	getWin: function() {
		return this.rdom.getWin();
	},
	
	/**
	 * Returns designmode document object
	 */
	getDoc: function() {
		return this.rdom.getDoc();
	},
	
	/**
	 * Returns designmode body object
	 */
	getBody: function() {
		return this.rdom.getRoot();
	},
	
	/**
	 * Returns outmost wrapper element
	 */
	getOutmostWrapper: function() {
		return this.outmostWrapper;
	},
	
	_createIFrame: function(doc, width, height) {
		var frame = doc.createElement("iframe");
		
		// IE displays warning when a protocol is HTTPS, because IE6 treats IFRAME
		// without SRC attribute as insecure.
		if(xq.Browser.isIE) frame.src = 'javascript:""';
		
		frame.style.width = width || "100%";
		frame.style.height = height || "100%";
		frame.setAttribute("frameBorder", "0");
		frame.setAttribute("marginWidth", "0");
		frame.setAttribute("marginHeight", "0");
		frame.setAttribute("allowTransparency", "auto");
		return frame;
	},

	_createDoc: function(frame, head, cssList, bodyId, bodyClass, body) {
		var sb = [];
		if(!xq.Browser.isTrident) {
			// @WORKAROUND: IE6/7 has caret movement and scrolling problem if I include following DTD.
			sb.push('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">');
		}
		sb.push('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">');
		sb.push('<head>');
		sb.push('<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />');
		if(head) sb.push(head);

		if(cssList) for(var i = 0; i < cssList.length; i++) {
			sb.push('<link rel="Stylesheet" type="text/css" href="' + cssList[i] + '" />');
		}
		sb.push('</head>');
		sb.push('<body ' + (bodyClass ? 'class="' + bodyClass + '"' : '') + ' ' + (bodyId ? 'id="' + bodyId + '"' : '') + '>');
		if(body) sb.push(body);
		sb.push('</body>');
		sb.push('</html>');
		
		var doc = frame.contentWindow.document;
		doc.open();
		doc.write(sb.join(""));
		doc.close();
		return doc;
	},

	_createEditorFrame: function(mode) {
		// turn off static content
		this.contentElement.style.display = "none";
		
		// create outer DIV
		this.outmostWrapper = this.doc.createElement('div');
		this.outmostWrapper.className = "xquared";
		this.contentElement.parentNode.insertBefore(this.outmostWrapper, this.contentElement);
		
		// create toolbar
		if(this.toolbarContainer || this.config.generateDefaultToolbar) {
			this.toolbar = new xq.ui.Toolbar(
				this,
				this.toolbarContainer,
				this.outmostWrapper,
				this.config.defaultToolbarButtonMap,
				this.config.imagePathForDefaultToolbar,
				function() {
					var element = this.getCurrentEditMode() === 'wysiwyg' ? this.lastFocusElement : null;
					return element && element.nodeName != "BODY" ? this.rdom.collectStructureAndStyle(element) : null;
				}.bind(this)
			);
		}
		
		// create source editor div
		this.sourceEditorDiv = this.doc.createElement('div');
		this.sourceEditorDiv.className = "editor source_editor"; //TODO: remove editor
		this.sourceEditorDiv.style.display = "none";
		this.outmostWrapper.appendChild(this.sourceEditorDiv);
		
		// create TEXTAREA for source editor
		this.sourceEditorTextarea = this.doc.createElement('textarea');
		this.sourceEditorDiv.appendChild(this.sourceEditorTextarea);
		
		// create WYSIWYG editor div
		this.wysiwygEditorDiv = this.doc.createElement('div');
		this.wysiwygEditorDiv.className = "editor wysiwyg_editor"; //TODO: remove editor
		this.outmostWrapper.appendChild(this.wysiwygEditorDiv);
		
		// create outer iframe for WYSIWYG editor
		this.outerFrame = this._createIFrame(document);
		this.wysiwygEditorDiv.appendChild(this.outerFrame);
		var outerDoc = this._createDoc(
			this.outerFrame,
			'<style type="text/css">html, body {margin:0px; padding:0px; background-color: transparent; width: 100%; height: 100%; overflow: hidden;}</style>'
		);

		// create designmode iframe for WYSIWYG editor
		this.editorFrame = this._createIFrame(outerDoc);
		
		outerDoc.body.appendChild(this.editorFrame);
		var editorDoc = this._createDoc(
			this.editorFrame,
			'<style type="text/css">html, body {margin:0px; padding:0px; background-color: transparent;}</style>' +
			(!xq.Browser.isTrident ? '<base href="./" />' : '') + // @WORKAROUND: it is needed to force href of pasted content to be an absolute url
			(this.config.changeCursorOnLink ? '<style>.xed a {cursor: pointer !important;}</style>' : ''),
			this.config.contentCssList,
			this.config.bodyId,
			this.config.bodyClass,
			''
		);
		this.rdom.setWin(this.editorFrame.contentWindow);
		this.editHistory = new xq.EditHistory(this.rdom);
		
		// turn on designmode
		this.rdom.getDoc().designMode = "On";
		
		// turn off Firefox's table editing feature
		if(xq.Browser.isGecko) {
			try {this.rdom.getDoc().execCommand("enableInlineTableEditing", false, "false")} catch(ignored) {}
		}
		
		// register event handlers
		this._registerEventHandlers();
		
		// hook onsubmit of form
		if(this.config.automaticallyHookSubmitEvent && this.contentElement.form) {
			var original = this.contentElement.form.onsubmit;
			this.contentElement.form.onsubmit = function() {
				this.contentElement.value = this.getCurrentContent();
				return original ? original.bind(this.contentElement.form)() : true;
			}.bind(this);
		}
	},
	
	
	
	/////////////////////////////////////////////
	// Event Management
	
	_registerEventHandlers: function() {
		var events = [this.platformDepedentKeyEventType, 'click', 'keyup', 'mouseup', 'contextmenu'];
		
		if(xq.Browser.isTrident && this.config.changeCursorOnLink) events.push('mousemove');
		
		var handler = this._handleEvent.bindAsEventListener(this);
		for(var i = 0; i < events.length; i++) {
			xq.observe(this.getDoc(), events[i], handler);
		}
		
		if(xq.Browser.isGecko) {
			xq.observe(this.getDoc(), "focus", handler);
			xq.observe(this.getDoc(), "blur", handler);
			xq.observe(this.getDoc(), "scroll", handler);
			xq.observe(this.getDoc(), "dragdrop", handler);
		} else {
			xq.observe(this.getWin(), "focus", handler);
			xq.observe(this.getWin(), "blur", handler);
			xq.observe(this.getWin(), "scroll", handler);
		}
	},
	
	_handleEvent: function(e) {
		this._fireOnBeforeEvent(this, e);
		if(e.stopProcess) {
			xq.stopEvent(e);
			return false;
		}
		
		// Trident only
		if(e.type === 'mousemove') {
			if(!this.config.changeCursorOnLink) return true;
			
			var link = !!this.rdom.getParentElementOf(e.srcElement, ["A"]);
			
			var editable = this.getBody().contentEditable;
			editable = editable === 'inherit' ? false : editable;
			
			if(editable !== link && !this.rdom.hasSelection()) this.getBody().contentEditable = !link;
			return true;
		}
		
		var stop = false;
		var modifiedByCorrection = false;
		if(e.type === this.platformDepedentKeyEventType) {
			var undoPerformed = false;
			modifiedByCorrection = this.rdom.correctParagraph();
			for(var key in this.config.shortcuts) {
				if(!this.config.shortcuts[key].event.matches(e)) continue;
				
				var handler = this.config.shortcuts[key].handler;
				var xed = this;
				stop = (typeof handler === "function") ? handler(this) : eval(handler);
				
				if(key === "undo") undoPerformed = true;
			}
		} else if(e.type === 'click' && e.button === 0 && this.config.enableLinkClick) {
			var a = this.rdom.getParentElementOf(e.target || e.srcElement, ["A"]);
			if(a) stop = this.handleClick(e, a);
		} else if(["keyup", "mouseup"].indexOf(e.type) !== -1) {
			modifiedByCorrection = this.rdom.correctParagraph();
		} else if(["contextmenu"].indexOf(e.type) !== -1) {
			this._handleContextMenu(e);
		} else if("focus" == e.type) {
			this.rdom.focused = true;
		} else if("blur" == e.type) {
			this.rdom.focused = false;
		}
		
		if(stop) xq.stopEvent(e);
		
		this._fireOnCurrentContentChanged(this);
		this._fireOnAfterEvent(this, e);
		
		if(!undoPerformed && !modifiedByCorrection) this.editHistory.onEvent(e);
		
		return !stop;
	},

	/**
	 * TODO: remove dup with handleAutocompletion
	 */
	handleAutocorrection: function() {
		var block = this.rdom.getCurrentBlockElement();
		// TODO: use complete unescape algorithm
		var text = this.rdom.getInnerText(block).replace(/&nbsp;/gi, " ");
		
		var acs = this.config.autocorrections;
		var performed = false;
		
		var stop = false;
		for(var key in acs) {
			var ac = acs[key];
			if(ac.criteria(text)) {
				try {
					this.editHistory.onCommand();
					this.editHistory.disable();
					if(typeof ac.handler === "String") {
						var xed = this;
						var rdom = this.rdom;
						eval(ac.handler);
					} else {
						stop = ac.handler(this, this.rdom, block, text);
					}
					this.editHistory.enable();
				} catch(ignored) {}
				
				block = this.rdom.getCurrentBlockElement();
				text = this.rdom.getInnerText(block);
				
				performed = true;
				if(stop) break;
			}
		}
		
		return stop;
	},
	
	/**
	 * TODO: remove dup with handleAutocorrection
	 */
	handleAutocompletion: function() {
		var acs = this.config.autocompletions;
		if(xq.isEmptyHash(acs)) return;
		
		if(this.rdom.hasSelection()) {
			var text = this.rdom.getSelectionAsText();
			this.rdom.deleteSelection();
			var wrapper = this.rdom.insertNode(this.rdom.createElement("SPAN"));
			wrapper.innerHTML = text;
			
			var marker = this.rdom.pushMarker();
			
			var filtered = [];
			for(var key in acs) {
				filtered.push([key, acs[key].criteria(text)]);
			}
			filtered = filtered.findAll(function(elem) {
				return elem[1] !== -1;
			});
			
			if(filtered.length === 0) {
				this.rdom.popMarker(true);
				return;
			}
			
			var minIndex = 0;
			var min = filtered[0][1];
			for(var i = 0; i < filtered.length; i++) {
				if(filtered[i][1] < min) {
					minIndex = i;
					min = filtered[i][1];
				}
			}
			
			var ac = acs[filtered[minIndex][0]];
			
			this.editHistory.disable();
			this.rdom.selectElement(wrapper);
		} else {
			var marker = this.rdom.pushMarker();

			var filtered = [];
			for(var key in acs) {
				filtered.push([key, this.rdom.testSmartWrap(marker, acs[key].criteria).textIndex]);
			}
			filtered = filtered.findAll(function(elem) {
				return elem[1] !== -1;
			});
			
			if(filtered.length === 0) {
				this.rdom.popMarker(true);
				return;
			}
			
			var minIndex = 0;
			var min = filtered[0][1];
			for(var i = 0; i < filtered.length; i++) {
				if(filtered[i][1] < min) {
					minIndex = i;
					min = filtered[i][1];
				}
			}
			
			var ac = acs[filtered[minIndex][0]];
			
			this.editHistory.disable();
			
			var wrapper = this.rdom.smartWrap(marker, "SPAN", ac.criteria);
		}
		
		var block = this.rdom.getCurrentBlockElement();
		
		// TODO: use complete unescape algorithm
		var text = this.rdom.getInnerText(wrapper).replace(/&nbsp;/gi, " ");
		
		try {
			// call handler
			if(typeof ac.handler === "String") {
				var xed = this;
				var rdom = this.rdom;
				eval(ac.handler);
			} else {
				ac.handler(this, this.rdom, block, wrapper, text);
			}
		} catch(ignored) {}
		
		try {
			this.rdom.unwrapElement(wrapper);
		} catch(ignored) {}
		
		if(this.rdom.isEmptyBlock(block)) this.rdom.correctEmptyElement(block);
		
		this.editHistory.enable();
		this.editHistory.onCommand();
		
		this.rdom.popMarker(true);
	},

	/**
	 * Handles click event
	 *
	 * @param {Event} e click event
	 * @param {Element} target target element(usually has A tag)
	 */
	handleClick: function(e, target) {
		var href = decodeURI(target.href);
		if(!xq.Browser.isTrident) {
			if(!e.ctrlKey && !e.shiftKey && e.button !== 1) {
				window.location.href = href;
				return true;
			}
		} else {
			if(e.shiftKey) {
				window.open(href, "_blank");
			} else {
				window.location.href = href;
			}
			return true;
		}
		
		return false;
	},

	/**
	 * Show link dialog
	 *
	 * TODO: should support modify/unlink
	 * TODO: Add selenium test
	 */
	handleLink: function() {
		var text = this.rdom.getSelectionAsText() || '';
		var dialog = new xq.ui.FormDialog(
			this,
			xq.ui_templates.basicLinkDialog,
			function(dialog) {
				if(text) {
					dialog.form.text.value = text;
					dialog.form.url.focus();
					dialog.form.url.select();
				}
			},
			function(data) {
				this.focus();
				
				if(xq.Browser.isTrident) {
					var rng = this.rdom.rng();
					rng.moveToBookmark(bm);
					rng.select();
				}
				
				if(!data) return;
				this.handleInsertLink(false, data.url, data.text, data.text);
			}.bind(this)
		);
		
		if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
		
		dialog.show({position: 'centerOfEditor'});
		
		return true;
	},
	
	/**
	 * Inserts link or apply link into selected area
	 * @TODO Add selenium test
	 * 
	 * @param {boolean} autoSelection if set true and there's no selection, automatically select word to link(if possible)
	 * @param {String} url url
	 * @param {String} title title of link
	 * @param {String} text text of link. If there's a selection(manually or automatically), it will be replaced with this text
	 *
	 * @returns {Element} created element
	 */
	handleInsertLink: function(autoSelection, url, title, text) {
		if(autoSelection && !this.rdom.hasSelection()) {
			var marker = this.rdom.pushMarker();
			var a = this.rdom.smartWrap(marker, "A", function(text) {
				var index = text.lastIndexOf(" ");
				return index === -1 ? index : index + 1;
			});
			a.href = url;
			a.title = title;
			if(text) {
				a.innerHTML = ""
				a.appendChild(this.rdom.createTextNode(text));
			} else if(!a.hasChildNodes()) {
				this.rdom.deleteNode(a);
			}
			this.rdom.popMarker(true);
		} else {
			text = text || (this.rdom.hasSelection() ? this.rdom.getSelectionAsText() : null);
			if(!text) return;
			
			this.rdom.deleteSelection();
			
			var a = this.rdom.createElement('A');
			a.href = url;
			a.title = title;
			a.appendChild(this.rdom.createTextNode(text));
			this.rdom.insertNode(a);
		}
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * @TODO Add selenium test
	 */
	handleSpace: function() {
		// If it has selection, perform default action.
		if(this.rdom.hasSelection()) return false;
		
		// Trident performs URL replacing automatically
		if(!xq.Browser.isTrident) {
			this.replaceUrlToLink();
		}
		
		return false;
	},
	
	/**
	 * Called when enter key pressed.
	 * @TODO Add selenium test
	 *
	 * @param {boolean} skipAutocorrection if set true, skips autocorrection
	 * @param {boolean} forceInsertParagraph if set true, inserts paragraph
	 */
	handleEnter: function(skipAutocorrection, forceInsertParagraph) {
		// If it has selection, perform default action.
		if(this.rdom.hasSelection()) return false;
		
		// @WORKAROUND:
		// If caret is in HR, default action should be performed and
		// this._handleEvent() will correct broken HTML
		if(xq.Browser.isTrident && this.rdom.tree.isBlockOnlyContainer(this.rdom.getCurrentElement()) && this.rdom.recentHR) {
			this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(), this.rdom.recentHR, "before");
			this.rdom.recentHR = null;
			return true;
		}
		
		// Perform autocorrection
		if(!skipAutocorrection && this.handleAutocorrection()) return true;
		
		var block = this.rdom.getCurrentBlockElement();
		var info = this.rdom.collectStructureAndStyle(block);
		
		// Perform URL replacing. Trident performs URL replacing automatically
		if(!xq.Browser.isTrident) {
			this.replaceUrlToLink();
		}
		
		var atEmptyBlock = this.rdom.isCaretAtEmptyBlock();
		var atStart = atEmptyBlock || this.rdom.isCaretAtBlockStart();
		var atEnd = atEmptyBlock || (!atStart && this.rdom.isCaretAtBlockEnd());
		var atEdge = atEmptyBlock || atStart || atEnd;
		
		if(!atEdge) {
			var marker = this.rdom.pushMarker();
			
			if(this.rdom.isFirstLiWithNestedList(block) && !forceInsertParagraph) {
				var parent = block.parentNode;
				this.rdom.unwrapElement(block);
				block = parent;
			} else if(block.nodeName !== "LI" && this.rdom.tree.isBlockContainer(block)) {
				block = this.rdom.wrapAllInlineOrTextNodesAs("P", block, true).first();
			}
			this.rdom.splitElementUpto(marker, block);
			
			this.rdom.popMarker(true);
		} else if(atEmptyBlock) {
			this._handleEnterAtEmptyBlock();

			if(!xq.Browser.isWebkit) {
				if(info.fontSize && info.fontSize !== "2") this.handleFontSize(info.fontSize);
				if(info.fontName) this.handleFontFace(info.fontName);
			}
		} else {
			this._handleEnterAtEdge(atStart, forceInsertParagraph);
			
			if(!xq.Browser.isWebkit) {
				if(info.fontSize && info.fontSize !== "2") this.handleFontSize(info.fontSize);
				if(info.fontName) this.handleFontFace(info.fontName);
			}
		}
		
		return true;
	},
	
	/**
	 * Moves current block upward or downward
	 *
	 * @param {boolean} up moves current block upward
	 */
	handleMoveBlock: function(up) {
		var block = this.rdom.moveBlock(this.rdom.getCurrentBlockElement(), up);
		if(block) {
			this.rdom.selectElement(block, false);
			if(this.rdom.isEmptyBlock(block)) this.rdom.collapseSelection(true);
			
			block.scrollIntoView(false);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		return true;
	},
	
	/**
	 * Called when tab key pressed
	 * @TODO: Add selenium test
	 */
	handleTab: function() {
		var hasSelection = this.rdom.hasSelection();
		var table = this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(), ["TABLE"]);
		
		if(hasSelection) {
			this.handleIndent();
		} else if (table && table.className === "datatable") {
			this.handleMoveToNextCell();
		} else if (this.rdom.isCaretAtBlockStart()) {
			this.handleIndent();
		} else {
			this.handleInsertTab();
		}

		return true;
	},
	
	/**
	 * Called when shift+tab key pressed
	 * @TODO: Add selenium test
	 */
	handleShiftTab: function() {
		var hasSelection = this.rdom.hasSelection();
		var table = this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(), ["TABLE"]);
		
		if(hasSelection) {
			this.handleOutdent();
		} else if (table && table.className === "datatable") {
			this.handleMoveToPreviousCell();
		} else {
			this.handleOutdent();
		}
		
		return true;
	},
	
	/**
	 * Inserts three non-breaking spaces
	 * @TODO: Add selenium test
	 */
	handleInsertTab: function() {
		this.rdom.insertHtml('&nbsp;');
		this.rdom.insertHtml('&nbsp;');
		this.rdom.insertHtml('&nbsp;');
		
		return true;
	},
	
	/**
	 * Called when delete key pressed
	 * @TODO: Add selenium test
	 */
	handleDelete: function() {
		if(this.rdom.hasSelection() || !this.rdom.isCaretAtBlockEnd()) return false;
		return this._handleMerge(true);
	},
	
	/**
	 * Called when backspace key pressed
	 * @TODO: Add selenium test
	 */
	handleBackspace: function() {
		if(this.rdom.hasSelection() || !this.rdom.isCaretAtBlockStart()) return false;
		return this._handleMerge(false);
	},
	
	_handleMerge: function(withNext) {
		var block = this.rdom.getCurrentBlockElement();
		
		if(this.rdom.isEmptyBlock(block) && !this.rdom.tree.isBlockContainer(block.nextSibling) && withNext) {
			var blockToMove = this.rdom.removeBlock(block);
			this.rdom.placeCaretAtStartOf(blockToMove);
			blockToMove.scrollIntoView(false);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
			
			return true;
		} else {
			// save caret position;
			var marker = this.rdom.pushMarker();

			// perform merge
			var merged = this.rdom.mergeElement(block, withNext, withNext);
			if(!merged && !withNext) this.rdom.extractOutElementFromParent(block);
			
			// restore caret position
			this.rdom.popMarker(true);
			if(merged) this.rdom.correctEmptyElement(merged);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
			return !!merged;
		}
	},
	
	/**
	 * (in table) Moves caret to the next cell
	 * @TODO: Add selenium test
	 */
	handleMoveToNextCell: function() {
		this._handleMoveToCell("next");
	},

	/**
	 * (in table) Moves caret to the previous cell
	 * @TODO: Add selenium test
	 */
	handleMoveToPreviousCell: function() {
		this._handleMoveToCell("prev");
	},

	/**
	 * (in table) Moves caret to the above cell
	 * @TODO: Add selenium test
	 */
	handleMoveToAboveCell: function() {
		this._handleMoveToCell("above");
	},

	/**
	 * (in table) Moves caret to the below cell
	 * @TODO: Add selenium test
	 */
	handleMoveToBelowCell: function() {
		this._handleMoveToCell("below");
	},

	_handleMoveToCell: function(dir) {
		var block = this.rdom.getCurrentBlockElement();
		var cell = this.rdom.getParentElementOf(block, ["TD", "TH"]);
		var table = this.rdom.getParentElementOf(cell, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var target = null;
		
		if(["next", "prev"].indexOf(dir) !== -1) {
			var toNext = dir === "next";
			target = toNext ? rtable.getNextCellOf(cell) : rtable.getPreviousCellOf(cell);
		} else {
			var toBelow = dir === "below";
			target = toBelow ? rtable.getBelowCellOf(cell) : rtable.getAboveCellOf(cell);
		}

		if(!target) {
			var finder = function(node) {return ['TD', 'TH'].indexOf(node.nodeName) === -1 && this.tree.isBlock(node) && !this.tree.hasBlocks(node);}.bind(this.rdom);
			var exitCondition = function(node) {return this.tree.isBlock(node) && !this.tree.isDescendantOf(this.getRoot(), node)}.bind(this.rdom);
			
			target = (toNext || toBelow) ? 
				this.rdom.tree.findForward(cell, finder, exitCondition) :
				this.rdom.tree.findBackward(table, finder, exitCondition);
		}
		
		if(target) this.rdom.placeCaretAtStartOf(target);
	},
	
	/**
	 * Applies STRONG tag
	 * @TODO: Add selenium test
	 */
	handleStrongEmphasis: function() {
		this.rdom.applyStrongEmphasis();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies EM tag
	 * @TODO: Add selenium test
	 */
	handleEmphasis: function() {
		this.rdom.applyEmphasis();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies EM.underline tag
	 * @TODO: Add selenium test
	 */
	handleUnderline: function() {
		this.rdom.applyUnderline();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies SPAN.strike tag
	 * @TODO: Add selenium test
	 */
	handleStrike: function() {
		this.rdom.applyStrike();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Removes all style
	 * @TODO: Add selenium test
	 */
	handleRemoveFormat: function() {
		this.rdom.applyRemoveFormat();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Remove link
	 * @TODO: Add selenium test
	 */
	handleRemoveLink: function() {
		this.rdom.applyRemoveLink();
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Inserts table
	 * @TODO: Add selenium test
	 *
	 * @param {Number} cols number of columns
	 * @param {Number} rows number of rows
	 * @param {String} headerPosition position of THs. "T" or "L" or "TL". "T" means top, "L" means left.
	 */
	handleTable: function(cols, rows, headerPositions) {
		var cur = this.rdom.getCurrentBlockElement();
		if(this.rdom.getParentElementOf(cur, ["TABLE"])) return true;
		
		var rtable = xq.RichTable.create(this.rdom, cols, rows, headerPositions);
		if(this.rdom.tree.isBlockContainer(cur)) {
			var wrappers = this.rdom.wrapAllInlineOrTextNodesAs("P", cur, true);
			cur = wrappers.last();
		}
		var tableDom = this.rdom.insertNodeAt(rtable.getDom(), cur, "after");
		this.rdom.placeCaretAtStartOf(rtable.getCellAt(0, 0));
		
		if(this.rdom.isEmptyBlock(cur)) this.rdom.deleteNode(cur, true);
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	handleInsertNewRowAt: function(where) {
		var cur = this.rdom.getCurrentBlockElement();
		var tr = this.rdom.getParentElementOf(cur, ["TR"]);
		if(!tr) return true;
		
		var table = this.rdom.getParentElementOf(tr, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var row = rtable.insertNewRowAt(tr, where);
		
		this.rdom.placeCaretAtStartOf(row.cells[0]);
		return true;
	},
	
	/**
	 * @TODO: Add selenium test
	 */
	handleInsertNewColumnAt: function(where) {
		var cur = this.rdom.getCurrentBlockElement();
		var td = this.rdom.getParentElementOf(cur, ["TD"], true);
		if(!td) return true;
		
		var table = this.rdom.getParentElementOf(td, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		rtable.insertNewCellAt(td, where);
		
		this.rdom.placeCaretAtStartOf(cur);
		return true;
	},
	
	/**
	 * @TODO: Add selenium test
	 */
	handleDeleteRow: function() {
		var cur = this.rdom.getCurrentBlockElement();
		var tr = this.rdom.getParentElementOf(cur, ["TR"]);
		if(!tr) return true;

		var table = this.rdom.getParentElementOf(tr, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		var blockToMove = rtable.deleteRow(tr);
		
		this.rdom.placeCaretAtStartOf(blockToMove);
		return true;
	},
	
	/**
	 * @TODO: Add selenium test
	 */
	handleDeleteColumn: function() {
		var cur = this.rdom.getCurrentBlockElement();
		var td = this.rdom.getParentElementOf(cur, ["TD"], true);
		if(!td) return true;

		var table = this.rdom.getParentElementOf(td, ["TABLE"]);
		var rtable = new xq.RichTable(this.rdom, table);
		rtable.deleteCell(td);

		//this.rdom.placeCaretAtStartOf(table);
		return true;
	},
	
	/**
	 * Performs block indentation
	 * @TODO: Add selenium test
	 */
	handleIndent: function() {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() !== blocks.last()) {
				var affected = this.rdom.indentElements(blocks.first(), blocks.last());
				this.rdom.selectBlocksBetween(affected.first(), affected.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		var block = this.rdom.getCurrentBlockElement();
		var affected = this.rdom.indentElement(block);
		
		if(affected) {
			this.rdom.placeCaretAtStartOf(affected);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		
		return true;
	},
	
	/**
	 * Performs block outdentation
	 * @TODO: Add selenium test
	 */
	handleOutdent: function() {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() !== blocks.last()) {
				var affected = this.rdom.outdentElements(blocks.first(), blocks.last());
				this.rdom.selectBlocksBetween(affected.first(), affected.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		var block = this.rdom.getCurrentBlockElement();
		var affected = this.rdom.outdentElement(block);
		
		if(affected) {
			this.rdom.placeCaretAtStartOf(affected);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		}
		
		return true;
	},
	
	/**
	 * Applies list.
	 * @TODO: Add selenium test
	 *
	 * @param {String} type "UL" or "OL"
	 * @param {String} CSS class name
	 */
	handleList: function(type, className) {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() !== blocks.last()) {
				blocks = this.rdom.applyLists(blocks.first(), blocks.last(), type, className);
			} else {
				blocks[0] = blocks[1] = this.rdom.applyList(blocks.first(), type, className);
			}
			this.rdom.selectBlocksBetween(blocks.first(), blocks.last());
		} else {
			var block = this.rdom.applyList(this.rdom.getCurrentBlockElement(), type, className);
			this.rdom.placeCaretAtStartOf(block);
		}
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies justification
	 * @TODO: Add selenium test
	 *
	 * @param {String} dir "left", "center", "right" or "both"
	 */
	handleJustify: function(dir) {
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getSelectedBlockElements();
    		var dir = (dir === "left" || dir === "both") && (blocks[0].style.textAlign === "left" || blocks[0].style.textAlign === "") ? "both" : dir;
			this.rdom.justifyBlocks(blocks, dir);
			this.rdom.selectBlocksBetween(blocks.first(), blocks.last());
		} else {
    		var block = this.rdom.getCurrentBlockElement();
    		var dir = (dir === "left" || dir === "both") && (block.style.textAlign === "left" || block.style.textAlign === "") ? "both" : dir;
			this.rdom.justifyBlock(block, dir);
		}
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Removes current block element
	 * @TODO: Add selenium test
	 */
	handleRemoveBlock: function() {
		var block = this.rdom.getCurrentBlockElement();
		var blockToMove = this.rdom.removeBlock(block);
		this.rdom.placeCaretAtStartOf(blockToMove);
		blockToMove.scrollIntoView(false);
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Applies background color
	 * @TODO: Add selenium test
	 *
	 * @param {String} color CSS color string
	 */
	handleBackgroundColor: function(color) {
		if(color) {
			this.rdom.applyBackgroundColor(color);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			var dialog = new xq.ui.FormDialog(
				this,
				xq.ui_templates.basicColorPickerDialog,
				function(dialog) {},
				function(data) {
					this.focus();
					
					if(xq.Browser.isTrident) {
						var rng = this.rdom.rng();
						rng.moveToBookmark(bm);
						rng.select();
					}
					
					if(!data) return;
					
					this.handleBackgroundColor(data.color);
				}.bind(this)
			);
			
			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			
			dialog.show({position: 'centerOfEditor'});
		}
		return true;
	},
	
	/**
	 * Applies foreground color
	 * @TODO: Add selenium test
	 *
	 * @param {String} color CSS color string
	 */
	handleForegroundColor: function(color) {
		if(color) {
			this.rdom.applyForegroundColor(color);
			
			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			var dialog = new xq.ui.FormDialog(
				this,
				xq.ui_templates.basicColorPickerDialog,
				function(dialog) {},
				function(data) {
					this.focus();
					
					if(xq.Browser.isTrident) {
						var rng = this.rdom.rng();
						rng.moveToBookmark(bm);
						rng.select();
					}
					
					if(!data) return;
					
					this.handleForegroundColor(data.color);
				}.bind(this)
			);
			
			if(xq.Browser.isTrident) var bm = this.rdom.rng().getBookmark();
			
			dialog.show({position: 'centerOfEditor'});
		}
		return true;
	},

	/**
	 * Applies font face
	 * @TODO: Add selenium test
	 *
	 * @param {String} face font face
	 */
	handleFontFace: function(face) {
		if(face) {
			this.rdom.applyFontFace(face);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			//TODO: popup font dialog
		}
		return true;
	},
	
	/**
	 * Applies font size
	 *
	 * @param {Number} font size (1 to 6)
	 */
	handleFontSize: function(size) {
		if(size) {
			this.rdom.applyFontSize(size);

			var historyAdded = this.editHistory.onCommand();
			this._fireOnCurrentContentChanged(this);
		} else {
			//TODO: popup font dialog
		}
		return true;
	},

	/**
	 * Applies superscription
	 * @TODO: Add selenium test
	 */	
	handleSuperscription: function() {
		this.rdom.applySuperscription();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},
	
	/**
	 * Applies subscription
	 * @TODO: Add selenium test
	 */	
	handleSubscription: function() {
		this.rdom.applySubscription();

		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);

		return true;
	},

	/**
	 * Change or wrap current block(or selected blocks)'s tag
	 * @TODO: Add selenium test
	 * 
	 * @param {String} [tagName] Name of tag. If not provided, it does not modify current tag name
	 * @param {String} [className] Class name of tag. If not provided, it does not modify current class name, and if empty string is provided, class attribute will be removed.  
	 */	
	handleApplyBlock: function(tagName, className) {
		if(!tagName && !className) return true;
		
		// if current selection contains multi-blocks
		if(this.rdom.hasSelection()) {
			var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
			if(blocks.first() !== blocks.last()) {
				var applied = this.rdom.applyTagIntoElements(tagName, blocks.first(), blocks.last(), className);
				this.rdom.selectBlocksBetween(applied.first(), applied.last());
				
				var historyAdded = this.editHistory.onCommand();
				this._fireOnCurrentContentChanged(this);
				
				return true;
			}
		}
		
		// else
		var block = this.rdom.getCurrentBlockElement();
		this.rdom.pushMarker();
		var applied =
			this.rdom.applyTagIntoElement(tagName, block, className) ||
			block;
		this.rdom.popMarker(true);
		
		if(this.rdom.isEmptyBlock(applied)) {
			this.rdom.correctEmptyElement(applied);
			this.rdom.placeCaretAtStartOf(applied);
		}
		
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Inserts seperator (HR)
	 * @TODO: Add selenium test
	 */
	handleSeparator: function() {
		this.rdom.collapseSelection();
		
		var curBlock = this.rdom.getCurrentBlockElement();
		var atStart = this.rdom.isCaretAtBlockStart();
		if(this.rdom.tree.isBlockContainer(curBlock)) curBlock = this.rdom.wrapAllInlineOrTextNodesAs("P", curBlock, true)[0];
		
		this.rdom.insertNodeAt(this.rdom.createElement("HR"), curBlock, atStart ? "before" : "after");
		this.rdom.placeCaretAtStartOf(curBlock);

		// add undo history
		var historyAdded = this.editHistory.onCommand();
		this._fireOnCurrentContentChanged(this);
		
		return true;
	},
	
	/**
	 * Performs UNDO
	 * @TODO: Add selenium test
	 */
	handleUndo: function() {
		var performed = this.editHistory.undo();
		this._fireOnCurrentContentChanged(this);
		
		var curBlock = this.rdom.getCurrentBlockElement();
		if(!xq.Browser.isTrident && curBlock) {
			curBlock.scrollIntoView(false);
		}
		return true;
	},
	
	/**
	 * Performs REDO
	 * @TODO: Add selenium test
	 */
	handleRedo: function() {
		var performed = this.editHistory.redo();
		this._fireOnCurrentContentChanged(this);
		
		var curBlock = this.rdom.getCurrentBlockElement();
		if(!xq.Browser.isTrident && curBlock) {
			curBlock.scrollIntoView(false);
		}
		return true;
	},
	
	
	
	_handleContextMenu: function(e) {
		if (xq.Browser.isWebkit) {
			if (e.metaKey || xq.isLeftClick(e)) return false;
		} else if (e.shiftKey || e.ctrlKey || e.altKey) {
			return false;
		}
		
		var point = xq.getEventPoint(e);
		var x = point.x;
		var y = point.y;

		var pos = xq.getCumulativeOffset(this.wysiwygEditorDiv);
		x += pos.left;
		y += pos.top;
		this._contextMenuTargetElement = e.target || e.srcElement;
		
		if (!xq.Browser.isTrident) {
			var doc = this.getDoc();
			var body = this.getBody();
			
			x -= doc.documentElement.scrollLeft;
			y -= doc.documentElement.scrollTop;
			
			x -= body.scrollLeft;
			y -= body.scrollTop;
		}
		
		for(var cmh in this.config.contextMenuHandlers) {
			var stop = this.config.contextMenuHandlers[cmh].handler(this, this._contextMenuTargetElement, x, y);
			if(stop) {
				xq.stopEvent(e);
				return true;
			}
		}
		
		return false;
	},
	
	showContextMenu: function(menuItems, x, y) {
		if (!menuItems || menuItems.length <= 0) return;
		
		if (!this.contextMenuContainer) {
			this.contextMenuContainer = this.doc.createElement('UL');
			this.contextMenuContainer.className = 'xqContextMenu';
			this.contextMenuContainer.style.display='none';
			
			xq.observe(this.doc, 'click', this._contextMenuClicked.bindAsEventListener(this));
			xq.observe(this.rdom.getDoc(), 'click', this.hideContextMenu.bindAsEventListener(this));
			
			this.body.appendChild(this.contextMenuContainer);
		} else {
			while (this.contextMenuContainer.childNodes.length > 0)
				this.contextMenuContainer.removeChild(this.contextMenuContainer.childNodes[0]);
		}
		
		for (var i=0; i < menuItems.length; i++) {
			menuItems[i]._node = this._addContextMenuItem(menuItems[i]);
		}

		this.contextMenuContainer.style.display='block';
		this.contextMenuContainer.style.left = Math.min(Math.max(this.doc.body.scrollWidth, this.doc.documentElement.clientWidth) - this.contextMenuContainer.offsetWidth, x) + 'px';
		this.contextMenuContainer.style.top = Math.min(Math.max(this.doc.body.scrollHeight, this.doc.documentElement.clientHeight) - this.contextMenuContainer.offsetHeight, y) + 'px';

		this.contextMenuItems = menuItems;
	},
	
	hideContextMenu: function() {
		if (this.contextMenuContainer)
			this.contextMenuContainer.style.display='none';
	},
	
	_addContextMenuItem: function(item) {
		if (!this.contextMenuContainer) throw "No conext menu container exists";
		
		var node = this.doc.createElement('LI');
		if (item.disabled) node.className += ' disabled'; 
		
		if (item.title === '----') {
			node.innerHTML = '&nbsp;';
			node.className = 'separator';
		} else {
			if(item.handler) {
				node.innerHTML = '<a href="#" onclick="return false;">'+(item.title.toString().escapeHTML())+'</a>';
			} else {
				node.innerHTML = (item.title.toString().escapeHTML());
			}
		}
		
		if(item.className) node.className = item.className;
		
		this.contextMenuContainer.appendChild(node);
		
		return node;
	},
	
	_contextMenuClicked: function(e) {
		this.hideContextMenu();
		
		if (!this.contextMenuContainer) return;
		
		var node = e.srcElement || e.target;
		while(node && node.nodeName !== "LI") {
			node = node.parentNode;
		}
		if (!node || !this.rdom.tree.isDescendantOf(this.contextMenuContainer, node)) return;

		for (var i=0; i < this.contextMenuItems.length; i++) {
			if (this.contextMenuItems[i]._node === node) {
				var handler = this.contextMenuItems[i].handler;
				if (!this.contextMenuItems[i].disabled && handler) {
					var xed = this;
					var element = this._contextMenuTargetElement;
					if(typeof handler === "function") {
						handler(xed, element);
					} else {
						eval(handler);
					}
				}
				break;
			}
		}
	},
	
	/**
	 * Inserts HTML template
	 * @TODO: Add selenium test
	 *
	 * @param {String} html Template string. It should have single root element
	 * @returns {Element} inserted element
	 */
	insertTemplate: function(html) {
		return this.rdom.insertHtml(this._processTemplate(html));
	},
	
	/**
	 * Places given HTML template nearby target.
	 * @TODO: Add selenium test
	 *
	 * @param {String} html Template string. It should have single root element
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Element} Inserted element.
	 */
	insertTemplateAt: function(html, target, where) {
		return this.rdom.insertHtmlAt(this._processTemplate(html), target, where);
	},
	
	_processTemplate: function(html) {
		// apply template processors
		var tps = this.getTemplateProcessors();
		for(var key in tps) {
			var value = tps[key];
			html = value.handler(html);
		}
		
		// remove all whitespace characters between block tags
		return this.removeUnnecessarySpaces(html);
	},
	
	
	
	/** @private */
	_handleEnterAtEmptyBlock: function() {
		var block = this.rdom.getCurrentBlockElement();
		if(this.rdom.tree.isTableCell(block) && this.rdom.isFirstBlockOfBody(block)) {
			block = this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(), this.rdom.getRoot(), "start");
		} else {
			block = 
				this.rdom.outdentElement(block) ||
				this.rdom.extractOutElementFromParent(block) ||
				this.rdom.replaceTag("P", block) ||
				this.rdom.insertNewBlockAround(block);
		}
		
		this.rdom.placeCaretAtStartOf(block);
		if(!xq.Browser.isTrident) block.scrollIntoView(false);
	},
	
	/** @private */
	_handleEnterAtEdge: function(atStart, forceInsertParagraph) {
		var block = this.rdom.getCurrentBlockElement();
		var blockToPlaceCaret;
		
		if(atStart && this.rdom.isFirstBlockOfBody(block)) {
			blockToPlaceCaret = this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(), this.rdom.getRoot(), "start");
		} else {
			if(this.rdom.tree.isTableCell(block)) forceInsertParagraph = true;
			var newBlock = this.rdom.insertNewBlockAround(block, atStart, forceInsertParagraph ? "P" : null);
			blockToPlaceCaret = !atStart ? newBlock : newBlock.nextSibling;
		}
		
		this.rdom.placeCaretAtStartOf(blockToPlaceCaret);
		if(!xq.Browser.isTrident) blockToPlaceCaret.scrollIntoView(false);
	},
	
	/**
	 * Replace URL text nearby caret into a link
	 * @TODO: Add selenium test
	 */
	replaceUrlToLink: function() {
		// If there's link nearby caret, nothing happens
		if(this.rdom.getParentElementOf(this.rdom.getCurrentElement(), ["A"])) return;
		
		var marker = this.rdom.pushMarker();
		var criteria = function(text) {
			var m = /(http|https|ftp|mailto)\:\/\/[^\s]+$/.exec(text);
			return m ? m.index : -1;
		};
		
		var test = this.rdom.testSmartWrap(marker, criteria);
		if(test.textIndex !== -1) {
			var a = this.rdom.smartWrap(marker, "A", criteria);
			a.href = encodeURI(test.text);
		}
		this.rdom.popMarker(true);
	}
});

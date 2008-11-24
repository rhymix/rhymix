/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 */
xq.plugin.EventLogPlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.EventLogPlugin
	 * @lends xq.plugin.EventLogPlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	isEventListener: function() {return true;},
	
	onAfterLoad: function(xed) {
		this.createLogWindow();
	},
	
	onEditorStartInitialization: function(xed) {
		this.log("Start initialization.");
	},
	onEditorInitialized: function(xed) {
		this.log("Initialized.");
	},
	onEditorElementChanged: function(xed, from, to) {
		this.log("Element changed from <" + (from ? from.nodeName : null) + "> to <" + (to ? to.nodeName : null) + ">.");
	},
	onEditorBeforeEvent: function(xed, e) {
		this.log("Before event [" + e.type + "]");
	},
	onEditorAfterEvent: function(xed, e) {
		this.log("After event [" + e.type + "]");
	},
	onEditorCurrentContentChanged: function(xed) {
		this.log("Current content changed.");
	},
	onEditorStaticContentChanged: function(xed, content) {
		this.log("Static content changed.");
	},
	onEditorCurrentEditModeChanged: function(xed, from, to) {
		this.log("Edit mode changed from <" + from + "> to <" + to + ">.");
	},
	
	
	
	createLogWindow: function() {
		var wrapper = document.createElement("DIV");
		wrapper.innerHTML = "<h2>Log</h2>";
		wrapper.style.width = "500px";
		document.body.appendChild(wrapper);
		
		this.logWindow = document.createElement("PRE");
		this.logWindow.style.fontSize = "0.75em";
		this.logWindow.style.height = "200px";
		this.logWindow.style.overflow = "scroll";
		this.logWindow.style.border = "1px solid black";
		this.logWindow.style.padding = "2px";
		wrapper.appendChild(this.logWindow);
	},
	
	log: function(message) {
		var line = document.createTextNode(this.getFormattedTime() + ": " + message);
		this.logWindow.insertBefore(document.createElement("BR"), this.logWindow.firstChild);
		this.logWindow.insertBefore(line, this.logWindow.firstChild);
	},
	
	getFormattedTime: function() {
		var date = new Date();
		var time = date.toTimeString().split(" ")[0];
		var msec = "000" + date.getMilliseconds();
		msec = msec.substring(msec.length - 4);
		
		return time + "." + msec;
	}
});
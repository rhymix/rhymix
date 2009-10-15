/**
 * @brief XE Application Framework
 * @namespace xe
 */
(function($){

var _xe_base, _app_base, _plugin_base;
var _apps = [];

_xe_base = {
	/**
	 * @brief return the name of Core module
	 */
	getName : function() {
		return 'Core';
	},

	/**
	 * @brief Create an application class
	 */
	createApp : function(sName, oDef) {
		var _base  = getTypeBase();
		var newApp = $.extend(_base.prototype, _app_base, oDef);

		newApp.prototype.getName = function() {
			return sName;
		};

		return newApp;
	},

	/**
	 * @brief Create a plugin class
	 */
	createPlugin : function(sName, oDef) {
		var _base     = getTypeBase();
		var newPlugin = $.extend(_base.prototype, _plugin_base, oDef);

		newPlugin.prototype.getName = function() {
			return sName;
		};

		return newPlugin;
	},

	/**
	 * @brief Get the array of applications
	 */
	getApps : function() {
		return $.makeArray(_apps);
	},

	/**
	 * @brief Get one application
	 */
	getApp : function(indexOrName) {
		if (typeof _apps[indexOrName] != 'undefined') {
			return _apps[indexOrName];
		} else {
			return null;
		}
	},

	/**
	 * @brief Register an application instance
	 */
	registerApp : function(oApp) {
		var sName = oPlugin.getName().toLowerCase();

		_apps.push(oApp);
		if (!$.isArray(_apps[sName])) {
			_apps[sName] = [];
		}
		_apps[sName].push(oApp);

		oApp.parent = this;
	},

	/**
	 * @brief Unregister an application instance
	 */
	unregisterApp : function(oApp) {
		var sName  = oPlugin.getName().toLowerCase();
		var nIndex = $.inArray(oApp, _apps);

		if (nIndex >= 0) _apps.splice(nIndex, 1);

		if ($.isArray(_apps[sName])) {
			nIndex = $.inArray(oApp, _apps[sName]);
			if (nIndex >= 0) _apps[sName].splice(nIndex, 1);
		}
	},

	/**
	 * @brief overrides broadcast method
	 */
	broadcast : function(oSender, msg, params) {
		for(var i=0; i < this._apps.length; i++) {
			this._apps[i].cast(oSender, msg, params);
		}

		// cast to child plugins
		this.cast(oSender, msg, params);
	}
}

_app_base = {
	_plugins  : [],
	_messages : [],

	_fn_level : -1,

	/**
	 * @brief register a plugin instance
	 */
	registerPlugin : function(oPlugin) {
		var sName = oPlugin.getName().toLowerCase();

		// check if the plugin is already registered
		if ($.inArray(oPlugin, this._plugins) >= 0) return false;

		// push the plugin into the _plugins array
		this._plugins.push(oPlugin);

		if (!$.isArray(this._plugins[sName])) {
			this._plugins[sName] = [];
		}
		this._plugins[sName].push(oPlugin);

		// register method pool
		var msgs = this._messages;
		$.each(oPlugin, function(key, val){
			if (!$.isFunction(val)) return true;
			if (!/^API_((BEFORE_|AFTER_)?[A-Z0-9_]+)$/.test(key)) return true;

			var fn = function(s,p){ return oPlugin[key](s,p) };
			fn._fn = val;

			if (RegExp.$2) { // is hooker?
				if ($.isArray(msgs[RegExp.$1])) msgs[RegExp.$1] = [];
				msgs[RegExp.$1].push(fn);
			} else { // register only one main function
				if ($.isFunction(msgs[RegExp.$1])) {
					msgs[RegExp.$1] = fn;
				}
			}
		});

		// set the application
		oPlugin.oApp = this;

		// binding
		oPlugin.cast = function(msg, params) {
			oPlugin._cast(msg, params);
		};

		oPlugin.broadcast = function(msg, params) {
			oPlugin._broadcast(msg, params);
		};

		return true;
	},

	/**
	 * @brief unregister a plugin  instance
	 */
	unregisterPlugin : function(oPlugin) {
		var sName = oPlugin.getName().toLowerCase();

		// remove from _plugins array
		var nIndex = $.inArray(oPlugin, this._plugins);
		if (nIndex >= 0) this._plugins.splice(nIndex, 1);

		if ($.isArray(this._plugins[sName])) {
			nIndex = $.inArray(oPlugin, this._plugins);
			if (nIndex >= 0) this._plugins[sName].splice(nIndex, 1);
		}

		// unregister method pool
		var msgs = this._messages;
		$.each(oPlugin, function(key, val){
			if (!$.isFunction(val)) return true;
			if (!/^API_([A-Z0-9_]+)$/.test(key)) return true;
			if (typeof msgs[RegExp.$1] == 'undefined') return true;

			if ($.isArray(msgs[RegExp.$1])) {
				msgs[RegExp.$1] = $.grep(msgs[RegExp.$1], function(fn,i){ return (fn._fn != val); });
				if (!msgs[RegExp.$1].length) {
					delete msgs[RegExp.$1];
				}
			} else {
				if (msgs[RegExp.$1]._fn == val) {
					delete msgs[RegExp.$1];
				}
				
			}
		});

		// unset the application
		oPlugin.oApp = null;
	},

	cast : function(sender, msg, params) {
		var i, len;
		var aMsg = this._messages;

		msg = msg.toUpperCase();

		// increase function level
		this._fn_level++;

		// BEFORE hooker
		var bContinue = this.cast(sender, 'BEFORE_'+msg, params);
		if (!bContinue) {
			this._fn_level--;
			return;
		}

		// main api function
		var vRet;
		if ($.isFunction(aMsg[msg])) {
			vRet = aMsg[msg](sender, params);
		} else if ($.isArray(aMsg[msg])) {
			vRet = [];
			for(i=0; i < aMsg[msg].length; i++) {
				vRet.push( aMsg[msg][i](sender, params) );
			}
		}

		// AFTER hooker
		this.cast(sender, 'AFTER_'+msg, params);

		// decrease function level
		this._fn_level--;

		if (_fn_level < 0) { // top level function
			return vRet;
		} else {
			if (typeof vRet == 'undefined') vRet = true;
			return $.isArray(vRet)?$.inArray(false, vRet):!!vRet;
		}
	},
	
	broadcast : function(sender, msg, params) {
		if (this.parent && this.parent.broadcast) {
			this.parent.broadcast(sender, msg, params);
		}
	}
};

_plugin_base = {
	oApp : null,
	_binded_fn : [],

	_cast : function(msg, params) {
		if (this.oApp && this.oApp.cast) {
			this.oApp.cast(this, msg, params || []);
		}
	},
	_broadcast : function(msg, params) {
		if (this.oApp && this.oApp.broadcast) {
			this.oApp.broadcast(this, mag, params || []);
		}
	}

	/**
	 * Event handler prototype
	 */
};

function getTypeBase() {
	var _base = function() {
		if ($.isArray(this._plugins))   this._plugins   = [];
		if ($.isArray(this._messages))  this._messages  = [];
		if ($.isArray(this._binded_fn)) this._binded_fn = [];

		if ($.isFunction(this.$init)) {
			this.$init.apply(this, arguments);
		}
	};

	return _base;
}

window.xe = $.extend(_app_base, _xe_base);

// domready event
$(function(){ xe.broadcast(xe, 'ONREADY'); });

// load event
$(window).load(function(){ xe.broadcast(xe, 'ONLOAD'); });

})(jQuery);
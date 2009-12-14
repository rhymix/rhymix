/**
 * @file js_app.js
 * @author zero (zero@nzeo.com)
 * @brief XE JavaScript Application Framework (JAF)
 * @namespace xe
 * @update 20091120
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
		var _base = getTypeBase();

		$.extend(_base.prototype, _app_base, oDef);

		_base.prototype.getName = function() {
			return sName;
		};

		return _base;
	},

	/**
	 * @brief Create a plugin class
	 */
	createPlugin : function(sName, oDef) {
		var _base = getTypeBase();

		$.extend(_base.prototype, _plugin_base, oDef);

		_base.prototype.getName = function() {
			return sName;
		};

		return _base;
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
		indexOrName = (indexOrName||'').toLowerCase();
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
		var sName = oApp.getName().toLowerCase();

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
	broadcast : function(sender, msg, params) {
		for(var i=0; i < _apps.length; i++) {
			_apps[i]._cast(sender, msg, params);
		}

		// cast to child plugins
		this._cast(sender, msg, params);
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

			if (!$.isArray(msgs[RegExp.$1])) msgs[RegExp.$1] = [];
			msgs[RegExp.$1].push(fn);
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

	cast : function(msg, params) {
		return this._cast(this, msg, params || []);
	},

	broadcast : function(sender, msg, params) {
		if (this.parent && this.parent.broadcast) {
			this.parent.broadcast(sender, msg, params);
		}
	},

	_cast : function(sender, msg, params) {
		var i, len;
		var aMsg = this._messages;

		msg = msg.toUpperCase();

		// increase function level
		this._fn_level++;

		// BEFORE hooker
		if (aMsg['BEFORE_'+msg] || this['BEFORE_'+msg]) {
			var bContinue = this._cast(sender, 'BEFORE_'+msg, params);
			if (!bContinue) {
				this._fn_level--;
				return;
			}
		}

		// main api function
		var vRet = [], sFn = 'API_'+msg;
		if ($.isFunction(this[sFn])) vRet.push( this[sFn](sender, params) );
		if ($.isFunction(aMsg[msg])) vRet.push( aMsg[msg](sender, params) );
		else if ($.isArray(aMsg[msg])) {
			for(i=0; i < aMsg[msg].length; i++) {
				vRet.push( aMsg[msg][i](sender, params) );
			}
		}
		if (vRet.length < 2) vRet = vRet[0];

		// AFTER hooker
		if (aMsg['AFTER_'+msg] || this['AFTER_'+msg]) {
			this._cast(sender, 'AFTER_'+msg, params);
		}

		// decrease function level
		this._fn_level--;

		if (this._fn_level < 0) { // top level function
			return vRet;
		} else {
			if (typeof vRet == 'undefined') vRet = true;
			return $.isArray(vRet)?$.inArray(false, vRet):!!vRet;
		}
	}
};

_plugin_base = {
	oApp : null,
	_binded_fn : [],

	_cast : function(msg, params) {
		if (this.oApp && this.oApp._cast) {
			this.oApp._cast(this, msg, params || []);
		}
	},
	_broadcast : function(msg, params) {
		if (this.oApp && this.oApp.broadcast) {
			this.oApp.broadcast(this, mag, params || []);
		}
	}

	/**
	 * Event handler prototype
	 *
	 * function (oSender, params)
	 */
};

function getTypeBase() {
	var _base = function() {
		if ($.isArray(this._plugins))   this._plugins   = [];
		if ($.isArray(this._messages))  this._messages  = [];
		if ($.isArray(this._binded_fn)) this._binded_fn = [];

		if ($.isFunction(this.init)) {
			this.init.apply(this, arguments);
		}
	};

	return _base;
}

window.xe = $.extend(_app_base, _xe_base);
window.xe.lang = {}; // language repository

// domready event
$(function(){ xe.broadcast(xe, 'ONREADY'); });

// load event
$(window).load(function(){ xe.broadcast(xe, 'ONLOAD'); });

})(jQuery);
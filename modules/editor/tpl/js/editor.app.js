(function($){
	"use strict";
	var XeEditorApp = xe.createApp('xeEditorApp', {
		init : function() {
		},
		API_ONREADY : function() {
		},
		getContent : function(seq) {
			this.cast('GET_CONTENT');
		},
		API_EDITOR_CREATED : function(){
		},
	});

	// Shortcut function in jQuery
	$.fn.xeEditorApp = function(opts) {
		var u = new XeEditorApp(this.eq(0), opts);
		if(u) xe.registerApp(u);

		return u;
	};

	// Shortcut function in XE
	window.xe.createXeEditor = function() {
		var u = new XeEditorApp();

		return u;
	};
	var u = new XeEditorApp();
	xe.registerApp(u);

})(jQuery);

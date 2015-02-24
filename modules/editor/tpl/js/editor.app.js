(function($){
	var xeEditorApp = xe.createApp('xeEditorApp', {
		init  : function() {
			console.log('INIT @ xeEditorApp')
		},
		API_ONREADY : function() {
			console.log('ONREADY @ xeEditorApp');
		},
		getContent : function(seq) {
			this.cast('GET_CONTENT');
		},
		API_EDITOR_CREATED : function(){
			console.log('APP @ API_EDITOR_CREATED');
		},
	});

	// Shortcut function in jQuery
	$.fn.xeEditorApp = function(opts) {
		var u = new xeEditorApp(this.eq(0), opts);
		if(u) xe.registerApp(u);

		return u;
	};

	// Shortcut function in XE
	window.xe.createXeEditor = function() {
		var u = new xeEditorApp();
		// if(u) xe.registerApp(u);

		return u;
	};
	var u = new xeEditorApp();
	xe.registerApp(u);

})(jQuery);

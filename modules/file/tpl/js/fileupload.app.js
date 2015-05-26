(function($){
	"use strict";
	var XeUploader = xe.createApp('XeUploader', {
		init : function() {
		}
	});

	// Shortcut function in jQuery
	$.fn.uploader = function(opts) {
		console.log();
		var u = new XeUploader(this.eq(0), opts);
		if(u) xe.registerApp(u);

		return u;
	};

	// Shortcut function in XE
	xe.createUploader = function(browseButton, opts) {
		var u = new XeUploader(browseButton, opts);
		if(u) xe.registerApp(u);

		return u;
	};
})(jQuery);

(function($){

var ver = '0.9.0';

$.fn.xeed = function(options) {
	var $this = this.eq(0), $head = $('head'), templates = {}, DEFAULT = 'default', isCSSLoaded, isScriptLoaded;
	var opts = $.extend({
		ui : DEFAULT
	}, options);

	// Load Editor UI
	function loadUI(ui, fnError){
		if (templates[ui]) {
			// load CSS
			if (!isCSSLoaded) {
				$head
					.append('<link rel="stylesheet" type="text/css" href="'+xeed_path+'tpl/xd.css" />')
					.append('<link rel="stylesheet" type="text/css" href="'+xeed_path+'tpl/xdcs.css" />');

				loadCSS = true;
			}

			// insert HTML
			$this.before(templates[ui]).hide();

			// load script
			setTimeout(loadScript, 0);

			return;	
		}

		$.ajax({
			url : xeed_path + 'template.php?ui=' + ui,
			dataType : 'text',
			cache    : false,
			success  : function(data){
				var html = processTemplate(data);
				templates[ui] = html;
				loadUI(ui);
			}
		});
	};

	// Load Xeed core script
	function loadScript(){
		function callback() {
			var xeed = new xe.Xeed($this, opts);

			$this.data('xeed', xeed);
			xe.registerApp(xeed);

			isScriptLoaded = true;
		}

		if (!xe.Xeed) xe.Xeed = {};
		if (!xe.Xeed.callbacks) xe.Xeed.callbacks = [];

		xe.Xeed.version = ver;

		if (isScriptLoaded) callback();
		else xe.Xeed.callbacks.push(callback);

		setTimeout(function(){
			var scr = document.createElement('script');
			scr.src  = xeed_path+'xeed.js?ver='+ver+'&_cache='+Math.round(Math.random()*1000); // XXX : cache_code
			scr.type = 'text/javascript';

			$head[0].appendChild(scr);
		}, 0);
	};

	// TODO : Process a template
	function processTemplate(data) {
		return data;
	};

	if (!this.data('xeed')) {
		this.data('xeed', 1);
		loadUI(opts.ui);
	}

	return this;
};

})(jQuery);

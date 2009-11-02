(function($){
	var protocol_re = '(https?|ftp|news|telnet|irc)://';
	var domain_re   = '(?:[\\w\\-]+\\.)+(?:[a-z]+)';
	var max_255_re  = '(?:1[0-9]{2}|2[0-4][0-9]|25[0-5])';
	var ip_re       = '(?:'+max_255_re+'\\.){3}'+max_255_re;
	var port_re     = '(?::([0-9]+))?';
	var path_re     = '((?:/[\\w!"$-/:-@]+)*)';
	var hash_re     = '(?:#([\\w!-@]+))?';

	var url_regex = new RegExp('('+protocol_re+'('+domain_re+'|'+ip_re+')'+port_re+path_re+hash_re+')', 'ig');

	var AutoLink = xe.createPlugin("autolink", {
		targets : [],
		init : function() {
			this.targets = [];
		},
		API_ONREADY : function() {
			var thisPlugin = this;

			// extract target text nodes
			this.extractTargets($('.xe_content'));

			$(this.targets).each(function(){
				thisPlugin.cast('AUTOLINK', [this]);
			});
		},
		API_AUTOLINK : function(oSender, params) {
			var textNode = params[0];
			var content  = textNode.nodeValue;
			var dummy    = $('<span>');

			content = content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
			content = content.replace(url_regex, '<a href="$1" target="_blank">$1</a>');

			$(textNode).before(dummy);
			$(textNode).replaceWith(content);
			params[0] = dummy.next('a');
			dummy.remove();
		},
		extractTargets : function(obj) {
			var thisPlugin = this;

			$(obj)
			.contents()
			.each(function(){
				// FIXED: When this meanless code wasn't executed, url_regex do not run correctly. why?
				url_regex.exec('');

				if (!$(this).is('a,pre,xml,code,script,style,:input')) {
					if (this.nodeType == 3 && url_regex.test(this.nodeValue)) { // text node
						thisPlugin.targets.push(this);
					} else {
						
						thisPlugin.extractTargets(this);
					}
				}
			});
		}
	});

	xe.registerPlugin(new AutoLink());
})(jQuery);
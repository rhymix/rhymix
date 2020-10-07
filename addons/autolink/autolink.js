/**
 * @file autolink.js
 * @brief javascript code for autolink addon
 * @author NAVER (developers@xpressengine.com)
 */
(function($){
	var protocol_re = '(?:(?:https?|ftp|news|telnet|irc|mms)://)';
	var domain_re   = '(?:[^\\s./)>]+\\.)+[^\\s./)>]+';
	var max_255_re  = '(?:1[0-9]{2}|2[0-4][0-9]|25[0-5]|[1-9]?[0-9])';
	var ip_re       = '(?:'+max_255_re+'\\.){3}'+max_255_re;
	var port_re     = '(?::([0-9]+))?';
	var user_re     = '(?:/~\\w+)?';
	var path_re     = '(?:/[^\\s]*)?';
	var hash_re     = '(?:#[^\\s]*)?';

	var url_regex = new RegExp('('+protocol_re+'('+domain_re+'|'+ip_re+'|localhost'+')'+port_re+user_re+path_re+hash_re+')', 'ig');

	var AutoLink = xe.createPlugin("autolink", {
		targets : [],
		init : function() {
			this.targets = [];
		},
		API_ONREADY : function() {
			var thisPlugin = this;

			// extract target text nodes
			this.extractTargets($('.rhymix_content, .xe_content'));

			$(this.targets).each(function(){
				thisPlugin.cast('AUTOLINK', [this]);
			});
		},
		API_AUTOLINK : function(oSender, params) {
			var textNode = params[0];
			if(!$(textNode).parent().length || $(textNode).parent().get(0).nodeName.toLowerCase() == 'a') return;
			var content  = textNode.nodeValue;
			var dummy    = $('<span>');

			content = content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
			content = content.replace(url_regex, function(match, p1, offset, string) {
				var match;
				var suffix = '';
				var attribute = '';
				if (p1.indexOf('(') < 0 && p1.match(/\)$/)) {
					p1 = p1.replace(/\)$/, '');
					suffix = ')';
				} else if (p1.indexOf('[') < 0 && p1.match(/\]$/)) {
					p1 = p1.replace(/\]$/, '');
					suffix = ']';
				} else if (p1.indexOf('&lt;') < 0 && p1.match(/&gt;$/)) {
					p1 = p1.replace(/&gt;$/, '');
					suffix = '&gt;';
				} else if (match = /^([\x21-\x7E]+\.[a-z]+)([가-힣]{1,3})$/.exec(p1)) {
					p1 = match[1];
					suffix = match[2];
				}
				if(!isSameOrigin(location.href, p1)) {
					attribute = ' target="_blank"';
				}
				return '<a href="' + p1 + '"' + attribute + '>' + p1 + '</a>' + suffix;
			});

			$(textNode).before(dummy);
			$(textNode).replaceWith(content);
			params[0] = dummy.next('a');
			dummy.remove();
		},
		extractTargets : function(obj) {
			var thisPlugin = this;
			var wrap = $('.rhymix_content, .xe_content', obj);
			if(wrap.length) {
				this.extractTargets(wrap);
				return;
			}

			$(obj).contents().each(function(){
				var node_name = this.nodeName.toLowerCase();
				if($.inArray(node_name, ['a', 'pre', 'xml', 'textarea', 'input', 'select', 'option', 'code', 'script', 'style', 'iframe', 'button', 'img', 'embed', 'object', 'ins']) != -1) return;

				// FIX ME : When this meanless code wasn't executed, url_regex do not run correctly. why?
				url_regex.exec('');

				if(this.nodeType == 3) { // text node
					var content = this.nodeValue;

					if(content.length < 5) return;

					if(!/(http|https|ftp|news|telnet|irc|mms):\/\//i.test(content)) return;

					thisPlugin.targets.push(this);
				} else {
					thisPlugin.extractTargets(this);
				}
			});
		}
	});

	xe.registerPlugin(new AutoLink());

	$(document).on('click', '.rhymix_content a, .xe_content a', function() {
		var $this = $(this);
		var href = $this.attr('href');
		if(!href || /^(?:javascript|mailto):|#/.test(href)) {
			return;
		}
		if (!$this.attr("target") && !isSameOrigin(location.href, href)) {
			$this.attr("target", "_blank");
		}
	});
	
})(jQuery);

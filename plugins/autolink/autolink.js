/**
 * Autolink script refactored as a Rhymix plugin.
 */
(function($) {

	const protocol_re = '(?:(?:https?|ftp|news|telnet|irc|mms)://)';
	const domain_re   = '(?:[^\\s./\'"&;)>]+\\.)+[^\\s./\'"&;)>]+';
	const max_255_re  = '(?:1[0-9]{2}|2[0-4][0-9]|25[0-5]|[1-9]?[0-9])';
	const ip_re       = '(?:'+max_255_re+'\\.){3}'+max_255_re;
	const port_re     = '(?::([0-9]+))?';
	const user_re     = '(?:/~\\w+)?';
	const path_re     = '(?:/[^\\s]*)?';
	const hash_re     = '(?:#[^\\s]*)?';
	const full_url_re = new RegExp('('+protocol_re+'('+domain_re+'|'+ip_re+'|localhost'+')'+port_re+user_re+path_re+hash_re+')', 'ig');

	/**
	 * Find text nodes that contain URLs.
	 */
	function findTargets(el) {
		let targets = [];
		$(el).contents().each(function(){
			let node_name = this.nodeName.toLowerCase();
			if (['a', 'pre', 'xml', 'textarea', 'input', 'select', 'option', 'code', 'script', 'style', 'iframe', 'button', 'img', 'embed', 'object', 'ins'].indexOf(node_name) != -1) {
				return;
			}
			if (this.nodeType == Node.TEXT_NODE) {
				let content = this.nodeValue;
				if (content.length >= 8 && content.match(/\b(http|https|ftp|news|telnet|irc|mms):\/\//i)) {
					targets.push(this);
				}
			} else {
				let subtargets = findTargets(this);
				for (let i = 0; i < subtargets.length; i++) {
					targets.push(subtargets[i]);
				}
			}
		});
		return targets;
	}

	/**
	 * Convert URLs in text nodes to clickable links.
	 */
	function addLinks(targets) {
		for (let i = 0; i < targets.length; i++) {
			let textNode = targets[i];
			let $textNode = $(textNode);
			let $parent = $textNode.parent();
			if (!$parent.length || $parent.get(0).nodeName.toLowerCase() == 'a') {
				return;
			}

			let content = textNode.nodeValue;
			content = content.escape().replace(full_url_re, function(match, url, offset, string) {
				let match2;
				let match3;
				let suffix = '';
				let attribute = '';
				if (url.indexOf('(') < 0 && url.match(/\)$/)) {
					url = url.replace(/\)$/, '');
					suffix = ')';
				} else if (url.indexOf('[') < 0 && url.match(/\]$/)) {
					url = url.replace(/\]$/, '');
					suffix = ']';
				} else if (url.indexOf('&lt;') < 0 && url.match(/&gt;$/)) {
					url = url.replace(/&gt;$/, '');
					suffix = '&gt;';
				}
				if (match2 = /^([\x21-\x7E]+\.[a-z]+)([가-힣]{1,3})$/.exec(url)) {
					url = match2[1];
					suffix = match2[2] + suffix;
				}
				if (match3 = /^(.*?)(&(lt|gt|quot);.*)$/i.exec(url)) {
					url = match3[1];
					suffix = match3[2] + suffix;
				}
				if (!Rhymix.isSameOrigin(location.href, url)) {
					attribute = ' target="_blank"';
				}
				return '<a href="' + url + '"' + attribute + '>' + url + '</a>' + suffix;
			});

			let dummy = $('<span></span>');
			$textNode.before(dummy);
			$textNode.replaceWith(content);
			dummy.remove();
		}
	}

	/**
	 * Define a jQuery plugin so that users can apply autolink to dynamically added DOM elements.
	 */
	$.fn.autolink = function() {
		return this.each(function() {
			addLinks(findTargets(this));
		});
	};

	/**
	 * On document ready, automatically convert URLs in existing content.
	 */
	$(document).on('ready', function() {
		$('.rhymix_content, .xe_content').autolink();
	});

	/**
	 * Open external links in a new tab/window.
	 */
	$(document).on('click', '.rhymix_content a, .xe_content a', function() {
		var $this = $(this);
		var href = $this.attr('href');
		if (!href || !href.match(/^https?:/i) || Rhymix.isSameOrigin(location.href, href)) {
			return;
		}
		if (!$this.attr('target')) {
			$this.attr('target', '_blank');
		}
	});

})(jQuery);

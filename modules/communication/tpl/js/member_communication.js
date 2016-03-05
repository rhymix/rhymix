(function($){
	window.xeNotifyMessage = function(text, count){
		var $bar;
		$bar = $('div.message.info');
		if(!$bar.length) {
			$bar = $('<div class="message info" />')
				.hide()
				.css({
					'position'   : 'absolute',
					'z-index' : '100',
				})
				.prependTo(document.body);
		}
		
		text = text.replace('%d', count);
		$bar.html('<p><a href="'+current_url.setQuery('module','').setQuery('act','dispCommunicationNewMessage')+'" onclick="popopen(this.href, \'popup\'); return false;">'+text+'</a></p>').height();
		$bar.show().animate({top:0});
	};
})(jQuery);

(function($){
	var $bar;
	window.xeNotifyMessage = function(text, count){
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
		$bar.html('<p><a href="'+current_url.setQuery('module','').setQuery('act','dispCommunicationNewMessage')+'" onclick="popopen(this.href, \'popup\');xeNotifyMessageClose(); return false;">'+text+'</a></p>').height();
		$bar.show().animate({top:0});
	};
	
	window.xeNotifyMessageClose = function(){
		setTimeout(function(){
			$bar.slideUp();
		}, 2000);
	};
})(jQuery);

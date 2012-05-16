(function($){
window.xeNotifyMessage = function(text, count){
	var $bar;
	$bar = $('div.message.info');
	if(!$bar.length) {
		$bar = $('<div class="message info" />')
			.hide()
			.prependTo(document.body);
			.css({
				'position'   : 'absolute',
				'z-index' : '100',
			})
			.appendTo(document.body);
	}
	text = text.replace('%d', count);
	h = $bar.html('<p><a href="'+current_url.setQuery('act','dispCommunicationMessages')+'">'+text+'</a></p>').height();
	$bar.css('top', -h-4).show().animate({top:0});

	// hide after 10 seconds
	setTimeout(function(){
		$bar.slideUp();
	}, 5000);
};
})(jQuery);

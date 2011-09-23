(function($){

window.xeNotifyMessage = function(text, count){
	var $bar;

	$bar = $('div.notifyMessage');
	if(!$bar.length) {
		$bar = $('<div class="notifyMessage" />')
			.hide()
			.css({
				position   : 'absolute',
				background : '#ff0',
				border     : '1px solid #990',
				textAlign  : 'center'
			})
			.appendTo(document.body);
	}

	h = $bar.html('<a href="'+current_url.setQuery('act','dispCommunicationMessages')+'">'+text+'</a>').height();
	$bar.css('top', -h-4).show().animate({top:0});

	// hide after 10 seconds
	setTimeout(function(){
		$bar.animate({top:-h-4}, function(){ $bar.hide() });
	}, 10000);
};

})(jQuery);

(function($){
	var $bar;
	window.xeNotifyMessage = function(text, count){
		$bar = $('div.message.info');
		if(!$bar.length) {
			$bar = jQuery('<div class="message info"></div>').hide().css({
				'position' : 'absolute',
				'opacity' : 0.7,
				'z-index' : 10000,
			}).appendTo(document.body);
		}
		
		text = text.replace('%d', count);
		var link = jQuery('<a></a>');
		link.attr("href", current_url.setQuery('module','').setQuery('act','dispCommunicationMessages'));
		//link.attr("onclick", "popopen(this.href, 'popup');xeNotifyMessageClose(); return false;");
		link.text(text);
		var para = jQuery('<p></p>');
		para.append(link).appendTo($bar);
		$bar.show().animate({top:0});
	};
	
	window.xeNotifyMessageClose = function(){
		setTimeout(function(){
			$bar.slideUp();
		}, 2000);
	};
})(jQuery);

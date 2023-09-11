'use strict';

$(function() {
	var items = $('.dashboard>div>section>ul>li');
	items.on('mouseenter focusin', function() {
		$(this).addClass('hover').find('>.action').show();
	});
	items.on('mouseleave focusout', function() {
		if(!$(this).find(':focus').length) {
			$(this).removeClass('hover').find('>.action').hide();
		}
	});
});

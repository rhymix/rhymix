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
	var forms = $('.dashboard>div>section form.action');
	forms.on('click', 'button', function(e) {
		var message;
		if ($(this).val() === 'trash' || $(this).val() === 'true') {
			message = xe.lang.confirm_trash;
		} else {
			message = xe.lang.confirm_delete;
		}
		if (!confirm(message)) {
			e.preventDefault();
		}
	});
});

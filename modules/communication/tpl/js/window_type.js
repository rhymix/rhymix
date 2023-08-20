'use strict';

(function($) {
	$(function() {

		// Find forms that submit to the Communication module.
		const regexp = /^procCommunication(SendMessage|AddFriend(Group?))$/;
		const forms = $('form').filter(function() {
			return String($(this).find('input[name=act]').val()).match(regexp);
		});

		// Add window_type=self to each form.
		forms.each(function() {
			if ($(this).find('input[name=window_type]').length) {
				return;
			}
			const hiddenInput = $('<input type="hidden" name="window_type" value="self" />')
			hiddenInput.insertAfter($(this).find('input[name=act]'));
		});

	});
})(jQuery);

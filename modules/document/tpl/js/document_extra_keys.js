(function($) {
	$(function() {
		$('select#type').on('change', function() {
			const selected_type = $(this).val();
			$(this).parents('form').find('.x_control-group').each(function() {
				const visible_types = $(this).data('visibleTypes');
				if (visible_types) {
					if (visible_types.split(',').indexOf(selected_type) >= 0) {
						$(this).show();
					} else {
						$(this).hide();
					}
				}
				const invisible_types = $(this).data('invisibleTypes');
				if (invisible_types) {
					if (invisible_types.split(',').indexOf(selected_type) >= 0) {
						$(this).hide();
					} else {
						$(this).show();
					}
				}
			});
		}).triggerHandler('change');
	});
})(jQuery);

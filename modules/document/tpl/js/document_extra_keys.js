(function($) {
	$(function() {

		// Reorder extra keys
		$('table.extra_keys.sortable').on('after-drag.st', function(e) {
			const $table = $(this);
			let order = [];
			let i = 1;
			$table.find('tbody > tr').each(function() {
				order.push({
					eid: $(this).data('eid'),
					old_idx: parseInt($(this).data('idx'), 10),
					new_idx: i++
				});
			});
			Rhymix.ajax('document.procDocumentAdminReorderExtraVars', {
				module_srl: $(this).data('moduleSrl'),
				order: order
			}, function() {
				let i = 1;
				$table.find('.var_idx').each(function() {
					$(this).text(i);
					i++;
				});
			});
		});

		// Show or hide fields depending on the type of variable
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

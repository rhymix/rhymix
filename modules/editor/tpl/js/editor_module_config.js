"use strict";

(function($) {
	$(function() {
		$('.editor_skin_selector').on('change', function() {
			var colorset_selector = $(this).siblings('.editor_colorset_selector').empty();
			var colorset_list = $(this).find('option:selected').data('colorsets');
			if (colorset_list && colorset_list.length) {
				$.each(colorset_list, function(i, colorset) {
					var option = $('<option></option>');
					option.attr('value', colorset.name);
					option.text(colorset.title);
					option.appendTo(colorset_selector);
					if (colorset.title.indexOf('L') > -1) {
						option.attr('selected', 'selected');
					}
				});
			}
		});
	});
})(jQuery);
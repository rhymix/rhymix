'use strict';

(function($) {
	$(function() {
		$('button.evFileRemover').on('click', function() {
			const container = $(this).parents('.ev_file_upload');
			container.find('span.filename').text('');
			container.find('span.filesize').text('');
			container.find('input[type=hidden][name^=_delete_]').val('Y');
			container.find('input[type=file]').val('');
		});
		$('input.rx_ev_file').on('change', function() {
			const container = $(this).parents('.ev_file_upload');
			container.find('input[type=hidden][name^=_delete_]').val('N');
		});
	});
})(jQuery);

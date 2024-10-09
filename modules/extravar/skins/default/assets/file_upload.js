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
			const max_size = parseInt($(this).data('allowedFilesize'), 10);
			const file_count = this.files.length;
			for (let i = 0; i < file_count; i++) {
				if (max_size && this.files[i].size > max_size) {
					alert($(this).data('msgFilesize'));
					$(this).val('');
					return;
				}
			}
			container.find('input[type=hidden][name^=_delete_]').val('N');
		});
	});
})(jQuery);

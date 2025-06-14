'use strict';

$(function() {
	$('.ev_file_upload').each(function() {
		const container = $(this);
		container.find('button.evFileRemover').on('click', function() {
			container.find('span.filename').text('');
			container.find('span.filesize').text('');
			container.find('input[type=hidden][name^=_delete_]').val('Y');
			container.find('input[type=file]').val('');
			$(this).remove();
		});
		container.find('input.rx_ev_file').on('change', function() {
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
});

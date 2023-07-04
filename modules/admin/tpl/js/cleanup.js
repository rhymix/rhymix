'use strict';

(function() {
	$(function() {
		$('.add_cleanup_exception').on('click', function(event) {
			event.preventDefault();
			const row = $(this).parents('tr');
			exec_json('admin.procAdminAddCleanupException', { path: $(this).data('path') }, function() {
				row.fadeOut();
			});
		});
		$('.reset_exception').on('click', function(event) {
			event.preventDefault();
			exec_json('admin.procAdminResetCleanupException', {}, function() {
				window.location.reload();
			});
		})
	});
})();

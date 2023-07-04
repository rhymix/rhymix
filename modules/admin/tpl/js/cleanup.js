'use strict';

(function() {
	$(function() {
		$('.add_cleanup_exception').on('click', function() {
			const row = $(this).parents('tr');
			exec_json('admin.procAdminAddCleanupException', { path: $(this).data('path') }, function() {
				row.fadeOut();
			});
		});
	});
})();

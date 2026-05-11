$(function() {
	$('.reset_layout').on('click', function(e) {
		var msg = $(this).data('confirmationMsg');
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});

(function($) {
	$(function() {
		$('.x_nav-tabs').on('click', '.theme-config-tab > a', function(e) {
			const target = $(this).data('target');
			$('.theme-config-tab').removeClass('x_active');
			$(this).parent().addClass('x_active');
			$('.theme-config-content').hide();
			$('.theme-config-content-' + target).show();
		});
	})
})(jQuery);

(function($) {
	$(function() {

		$("#queue_driver").on("change", function() {
			const selected_driver = $(this).val();
			$(this).parents("section").find("div.x_control-group.hidden-by-default, p.x_help-block.hidden-by-default").each(function() {
				if ($(this).hasClass("show-for-" + selected_driver)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}).triggerHandler("change");

		$("#queue_key").on('change keyup paste', function() {
			const key = encodeURIComponent(String($(this).val()));
			$('.webcron-url').text($('.webcron-url').text().replace(/\?key=[a-zA-Z0-9]+/g, '?key=' + key));
		});

		const qss = $('.queue-script-setup');
		const qss_tabs = qss.find('.qss-tabs');
		const qss_content = qss.find('.qss-content');
		qss_tabs.on('click', 'a', function(event) {
			const selected_tab = $(this).data('value');
			qss_tabs.find('li').removeClass('x_active');
			$(this).parent().addClass('x_active');
			qss_content.removeClass('active');
			qss_content.filter('.' + selected_tab).addClass('active');
			event.preventDefault();
		});

	});
})(jQuery);

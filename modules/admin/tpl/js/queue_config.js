(function($) {
	$(function() {

		$("#queue_driver").on("change", function() {
			var selected_driver = $(this).val();
			$(this).parents("section").find("div.x_control-group.hidden-by-default, p.x_help-block.hidden-by-default").each(function() {
				if ($(this).hasClass("show-for-" + selected_driver)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}).triggerHandler("change");

	});
})(jQuery);

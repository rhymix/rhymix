(function($) {
	$(".foo").click(function(event) {
		event.preventDefault();
		$(this).attr("bar", "baz");
	});
})(jQuery);

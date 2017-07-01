
(function($) {
	
	$(function() {
		
		$("a.show-errors").click(function(event) {
			event.preventDefault();
			var error_msg = $(this).siblings("div.mail-log-errors").html();
			alert(error_msg);
			$(".x_modal._common._small").removeClass("_small");
		});
		
	});
	
})(jQuery);

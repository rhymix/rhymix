
(function($) {
	
	// Editor replacement callback function
	var editor_replace = function(input) {
		var iframe = $('<iframe class="editor_iframe"></iframe>');
		iframe.attr("src", current_url.setQuery("module", "editor").setQuery("act", "dispEditorFrame").setQuery("parent_input_id", input.attr("id")));
		iframe.insertAfter(input);
		input.siblings(".editor_preview").hide();
		if (input.attr("type") !== "hidden") {
			input.hide();
		}
	};
	
	// Editor replacement
	$(function() {
		$(".editor_preview").on("click", function() {
			var input = $(this).siblings(".editor_content");
			if (input.size()) {
				$(this).off("click").off("focus");
				editor_replace(input.first());
			}
		});
		$(".editor_preview").on("focus", function() {
			$(this).triggerHandler("click");
		});
	});

})(jQuery);

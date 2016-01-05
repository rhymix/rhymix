jQuery(function($){
	$('.focus').focus();
	if($("#db_type").size()) {
		$("#db_type").click(function() {
			$("p.db_type").hide();
			$("p.db_type_" + $(this).val()).show();
		}).triggerHandler("click");
	}
});

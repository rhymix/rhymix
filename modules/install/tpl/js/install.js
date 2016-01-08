jQuery(function($){
	$('.focus').focus();
	if($("#db_type").size()) {
		$("#db_type").click(function() {
			$("p.db_type").hide();
			$("p.db_type_" + $(this).val()).show();
		}).triggerHandler("click");
	}
	if($("input[name='user_id']").size() && $("input[name='email_address']").size()) {
		var user_id_input = $("input[name='user_id']");
		var email_input = $("input[name='email_address']");
		email_input.on("blur", function() {
			if (user_id_input.val() == "") {
				user_id_input.val(email_input.val().replace(/@.+$/g, "").replace(/[^a-zA-Z0-9_]/g, ""));
			}
		});
	}
});

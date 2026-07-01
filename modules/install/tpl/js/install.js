jQuery(function($){
	$('.focus').focus();
	if($("#mod_rewrite_checking").size()) {
		var testval = 1000 + Math.floor(Math.random() * 9000);
		$.ajax({
			url: request_uri + 'common/rewrite/test/' + testval,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data.result == testval * 42) {
					$("#mod_write_status span.ok").show();
					$("#mod_write_status span.no").hide();
					$("#task-checklist-confirm").attr("href", $("#task-checklist-confirm").attr("href") + "&rewrite=Y");
					$("#mod_rewrite_checking").hide();
				} else {
					$("#mod_rewrite_no_support").show();
				}
			},
			error: function() {
				$("#mod_rewrite_no_support").show();
			}
		});
	}
	if($("input[name='user_id']").size() && $("input[name='email_address']").size()) {
		var user_id_input = $("input[name='user_id']");
		var email_input = $("input[name='email_address']");
		email_input.on("blur", function() {
			if (user_id_input.val() == "" && email_input.val() != "") {
				user_id_input.val(email_input.val().replace(/@.+$/g, "").replace(/[^a-zA-Z0-9_]/g, ""));
			}
		});
	}
	if($("#task-db-select").size()) {
		$("#task-db-select").parents("form").on("submit", function() {
			setTimeout(function() {
				$("#task-db-select").text($("#task-db-select").data("checking"));
				$("#task-db-select").prop("disabled", true);
			}, 100);
		});
	}
	if($("#task-complete-install").size()) {
		$("#task-complete-install").parents("form").on("submit", function() {
			setTimeout(function() {
				$("#task-complete-install").text($("#task-complete-install").data("installing"));
				$("#task-complete-install").prop("disabled", true);
			}, 100);
		});
	}
});

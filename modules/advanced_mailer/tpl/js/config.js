
(function($) {
	
	$(function() {
		
		$("#advanced_mailer_test_send_mail").click(function(event) {
			event.preventDefault();
			$("#advanced_mailer_test_result").text("");
			$(this).attr("disabled", "disabled");
			var ajax_data = {
				recipient_name: $("#advanced_mailer_recipient_name").val(),
				recipient_email: $("#advanced_mailer_recipient_email").val()
			};
			$.exec_json(
				"advanced_mailer.procAdvanced_mailerAdminTestSendMail", ajax_data,
				function(response) {
					$("#advanced_mailer_test_result").html(response.test_result);
					$("#advanced_mailer_test_send").removeAttr("disabled");
				},
				function(response) {
					$("#advanced_mailer_test_result").text("AJAX Error");
					$("#advanced_mailer_test_send").removeAttr("disabled");
				}
			);
		});
		
		$("#advanced_mailer_test_send_sms").click(function(event) {
			event.preventDefault();
			$("#advanced_mailer_test_result").text("");
			$(this).attr("disabled", "disabled");
			var ajax_data = {
				recipient_number: $("#advanced_mailer_recipient_number").val(),
				country_code: $("#advanced_mailer_country_code").val(),
				content: $("#advanced_mailer_content").val()
			};
			$.exec_json(
				"advanced_mailer.procAdvanced_mailerAdminTestSendSMS", ajax_data,
				function(response) {
					$("#advanced_mailer_test_result").html(response.test_result);
					$("#advanced_mailer_test_send").removeAttr("disabled");
				},
				function(response) {
					$("#advanced_mailer_test_result").text("AJAX Error");
					$("#advanced_mailer_test_send").removeAttr("disabled");
				}
			);
		});
		
		$("#advanced_mailer_test_send_push").click(function(event) {
			event.preventDefault();
			$("#advanced_mailer_test_result").text("");
			$(this).attr("disabled", "disabled");
			var ajax_data = {
				recipient_user_id: $("#advanced_mailer_recipient_user_id").val(),
				subject: $("#advanced_mailer_subject").val(),
				content: $("#advanced_mailer_content").val(),
				url: $("#advanced_mailer_url").val()
			};
			$.exec_json(
				"advanced_mailer.procAdvanced_mailerAdminTestSendPush", ajax_data,
				function(response) {
					$("#advanced_mailer_test_result").html(response.test_result);
					$("#advanced_mailer_test_send").removeAttr("disabled");
				},
				function(response) {
					$("#advanced_mailer_test_result").text("AJAX Error");
					$("#advanced_mailer_test_send").removeAttr("disabled");
				}
			);
		});
		
	});
	
})(jQuery);

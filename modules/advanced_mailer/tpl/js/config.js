
(function($) {
	
	$(function() {
		
		$("#advanced_mailer_sending_method").on("change", function() {
			var sending_method = $(this).val();
			$("div.x_control-group.hidden-by-default").not(".show-always").each(function() {
				if ($(this).hasClass("show-for-" + sending_method)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
			var reply_to = $("#advanced_mailer_reply_to").parents("div.x_control-group");
			if (sending_method === "woorimail") {
				reply_to.hide();
			} else {
				reply_to.show();
			}
		}).triggerHandler("change");
		
		$("#advanced_mailer_smtp_manual_entry").on("change", function() {
			var auto_fill = $(this).val();
			if (auto_fill === 'gmail') {
				$("#advanced_mailer_smtp_host").val('smtp.gmail.com');
				$("#advanced_mailer_smtp_port").val('465');
				$("#advanced_mailer_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_tls").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'hanmail') {
				$("#advanced_mailer_smtp_host").val('smtp.daum.net');
				$("#advanced_mailer_smtp_port").val('465');
				$("#advanced_mailer_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_tls").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'naver') {
				$("#advanced_mailer_smtp_host").val('smtp.naver.com');
				$("#advanced_mailer_smtp_port").val('587');
				$("#advanced_mailer_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_ssl").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'worksmobile') {
				$("#advanced_mailer_smtp_host").val('smtp.worksmobile.com');
				$("#advanced_mailer_smtp_port").val('587');
				$("#advanced_mailer_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_ssl").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'outlook') {
				$("#advanced_mailer_smtp_host").val('smtp-mail.outlook.com');
				$("#advanced_mailer_smtp_port").val('587');
				$("#advanced_mailer_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_ssl").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'yahoo') {
				$("#advanced_mailer_smtp_host").val('smtp.mail.yahoo.com');
				$("#advanced_mailer_smtp_port").val('465');
				$("#advanced_mailer_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#advanced_mailer_smtp_security_tls").parent().removeClass("checked");
				$("#advanced_mailer_smtp_security_none").parent().removeClass("checked");
				$("#advanced_mailer_force_sender").prop("checked", true).parent().addClass("checked");
			}
		});
		
		$("#advanced_mailer_woorimail_account_type_free,#advanced_mailer_woorimail_account_type_paid").on("change", function() {
			if ($("#advanced_mailer_woorimail_account_type_paid").is(":checked")) {
				$("#advanced_mailer_reply_to").attr("disabled", "disabled");
			} else {
				$("#advanced_mailer_reply_to").removeAttr("disabled");
			}
		}).triggerHandler("change");
		
		$("#advanced_mailer_test_send").click(function(event) {
			event.preventDefault();
			$("#advanced_mailer_test_result").text("");
			$(this).attr("disabled", "disabled");
			var ajax_data = {
				recipient_name: $("#advanced_mailer_recipient_name").val(),
				recipient_email: $("#advanced_mailer_recipient_email").val(),
			};
			$.exec_json(
				"advanced_mailer.procAdvanced_mailerAdminTestSend", ajax_data,
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
	
} (jQuery));

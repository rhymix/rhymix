
(function($) {
	
	$(function() {
		
		$("#mail_driver").on("change", function() {
			var selected_driver = $(this).val();
			$(this).parents("section").find("div.x_control-group.hidden-by-default, p.x_help-block.hidden-by-default").each(function() {
				if ($(this).hasClass("show-for-" + selected_driver)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}).triggerHandler("change");
		
		$("#sms_driver").on("change", function() {
			var selected_driver = $(this).val();
			$(this).parents("section").find("div.x_control-group.hidden-by-default, p.x_help-block.hidden-by-default").each(function() {
				if ($(this).hasClass("show-for-" + selected_driver)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}).triggerHandler("change");
		
		$("#mail_smtp_manual_entry").on("change", function() {
			var auto_fill = $(this).val();
			if (auto_fill === 'gmail') {
				$("#mail_smtp_smtp_host").val('smtp.gmail.com');
				$("#mail_smtp_smtp_port").val('465');
				$("#mail_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_tls").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'hanmail') {
				$("#mail_smtp_smtp_host").val('smtp.daum.net');
				$("#mail_smtp_smtp_port").val('465');
				$("#mail_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_tls").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'naver') {
				$("#mail_smtp_smtp_host").val('smtp.naver.com');
				$("#mail_smtp_smtp_port").val('587');
				$("#mail_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_ssl").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'worksmobile') {
				$("#mail_smtp_smtp_host").val('smtp.worksmobile.com');
				$("#mail_smtp_smtp_port").val('587');
				$("#mail_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_ssl").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'outlook') {
				$("#mail_smtp_smtp_host").val('smtp-mail.outlook.com');
				$("#mail_smtp_smtp_port").val('587');
				$("#mail_smtp_security_tls").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_ssl").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
			if (auto_fill === 'yahoo') {
				$("#mail_smtp_smtp_host").val('smtp.mail.yahoo.com');
				$("#mail_smtp_smtp_port").val('465');
				$("#mail_smtp_security_ssl").prop("checked", true).parent().addClass("checked");
				$("#mail_smtp_security_tls").parent().removeClass("checked");
				$("#mail_smtp_security_none").parent().removeClass("checked");
				$("#mail_force_default_sender").prop("checked", true).parent().addClass("checked");
			}
		});
		
	});
	
})(jQuery);

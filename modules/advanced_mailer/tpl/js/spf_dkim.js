
(function($) {
	
	$(function() {
		
		$("#advanced_mailer_check_spf,#advanced_mailer_check_dkim").click(function(event) {
			event.preventDefault();
			var check_type = $(this).attr("id").match(/_spf$/) ? "spf" : "dkim";
			var check_hostname = $(this).siblings("span.monospace").text();
			if (!check_hostname) {
				alert($("#spf_dkim_setting").data("nothing-to-check"));
			}
			$(this).attr("disabled", "disabled");
			$.exec_json(
				"advanced_mailer.procAdvanced_mailerAdminCheckDNSRecord",
				{ hostname: check_hostname, record_type: "TXT" },
				function(response) {
					if (response.record_content === false) {
						alert($("#spf_dkim_setting").data("check-failure"));
					}
					else if (response.record_content === "") {
						alert('<span class="monospace">' + check_hostname + "</span> " +
							$("#spf_dkim_setting").data("check-no-records"));
						$(".x_modal._common._small").removeClass("_small");
					}
					else {
						alert('<span class="monospace">' + check_hostname + "</span> " +
							$("#spf_dkim_setting").data("check-result") + "<br /><br />" +
							'<div class="monospace">' + response.record_content.replace("\n", "<br />") + "</div>");
						$(".x_modal._common._small").removeClass("_small");
					}
					$("#advanced_mailer_check_" + check_type).removeAttr("disabled");
				},
				function(response) {
					alert($("#spf_dkim_setting").data("check-failure"));
					$("#advanced_mailer_check_" + check_type).removeAttr("disabled");
				}
			);
		});
		
	});
	
})(jQuery);

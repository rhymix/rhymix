
function turnstileCallback() {
	var recaptcha_config = $("#turnstile-config");
	var recaptcha_instances = $(".turnstile-captcha");
	var recaptcha_instance_id = 1;
	var recaptcha_targets = String(recaptcha_config.data("targets")).split(",");

	if (recaptcha_instances.length === 0) {
		var autoinsert_candidates = $("form").filter(function() {
			var actinput = $("input[name='act']", this);
			if (actinput.length && actinput.val()) {
				var act = String(actinput.val());
				if (act.match(/^procMemberInsert$/i) && recaptcha_targets.indexOf("signup") > -1) {
					return true;
				}
				if (act.match(/^procMemberLogin$/i) && recaptcha_targets.indexOf("login") > -1) {
					return true;
				}
				if (act.match(/^procMember(FindAccount|ResendAuthMail)$/i) && recaptcha_targets.indexOf("recovery") > -1) {
					return true;
				}
				if (act.match(/^proc[A-Z][a-zA-Z0-9_]+InsertDocument$/i) && recaptcha_targets.indexOf("document") > -1) {
					return true;
				}
				if (act.match(/^proc[A-Z][a-zA-Z0-9_]+InsertComment$/i) && recaptcha_targets.indexOf("comment") > -1) {
					return true;
				}
			}
			var procfilter = $(this).attr("onsubmit");
			if (procfilter && procfilter.match(/procFilter\b.+\binsert/i) && (recaptcha_targets.indexOf("document") > -1 || recaptcha_targets.indexOf("comment") > -1)) {
				return true;
			}
			return false;
		});
		autoinsert_candidates.each(function() {
			var new_instance = $('<div class="turnstile-captcha"></div>');
			new_instance.attr("id", "turnstile-instance-" + recaptcha_instance_id++);
			var autoinsert_point = $(this).find("button[type='submit'],input[type='submit']").parent();
			if (autoinsert_point.size()) {
				new_instance.insertBefore(autoinsert_point);
			} else {
				new_instance.appendTo($(this));
			}
		});
		var recaptcha_instances = $(".turnstile-captcha");
	}

	recaptcha_instances.each(function() {
		var instance = $(this);
		var theme = recaptcha_config.data("theme");
		if (theme === 'auto') {
			theme = getColorScheme();
		}
		grecaptcha.render(`#${instance.attr("id")}`, {
			sitekey: recaptcha_config.data("sitekey"),
			size: recaptcha_config.data("size"),
			theme: theme
		});
	});
}

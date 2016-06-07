
function reCaptchaCallback() {
	var recaptcha_config = $("#recaptcha-config");
	var recaptcha_instances = $(".g-recaptcha");
	var recaptcha_instance_id = 1;
	
	if (recaptcha_instances.size() === 0) {
		var autoinsert_candidates = $("form").filter(function() {
			var actinput = $("input[name='act']", this);
			if (actinput.size() && actinput.val() && actinput.val().match(/^proc.+(Insert(Document|Comment|)|FindAccount|ResendAuthMail)/i)) {
				return true;
			}
			var procfilter = $(this).attr("onsubmit");
			if (procfilter && procfilter.match(/procFilter\b.+\binsert/i)) {
				return true;
			}
			return false;
		});
		autoinsert_candidates.each(function() {
			var new_instance = $('<div class="g-recaptcha"></div>');
			new_instance.attr("id", "recaptcha-instance-" + recaptcha_instance_id++);
			var autoinsert_point = $(this).find("button[type='submit'],input[type='submit']").parent();
			if (autoinsert_point.size()) {
				new_instance.insertBefore(autoinsert_point);
			} else {
				new_instance.appendTo($(this));
			}
		});
		var recaptcha_instances = $(".g-recaptcha");
	}
	
	recaptcha_instances.each(function() {
		var instance = $(this);
		grecaptcha.render(instance.attr("id"), {
			sitekey: recaptcha_config.data("sitekey"),
			size: recaptcha_config.data("size"),
			theme: recaptcha_config.data("theme")
		});
	});
}

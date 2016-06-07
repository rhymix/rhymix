
function reCaptchaCallback() {
	$(".g-recaptcha").each(function() {
		var instance = $(this);
		grecaptcha.render(instance.attr("id"), {
			sitekey: instance.data("sitekey"),
			size: instance.data("size"),
			theme: instance.data("theme")
		});
	});
}

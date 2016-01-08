/* Copyright (C) NAVER <http://www.navercorp.com> */

jQuery(function ($) {
	$("#api_handler").on("change", function (e) {
		var prop = $(this).val() != 1;
		$("#epostapi_regkey").prop("disabled", prop);
	});
});

/* End of file admin.js */
/* Location: ./modules/krzip/tpl/js/admin.js */

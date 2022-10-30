/**
 * @brief Load i18n file of datepicker
 * @author MinSoo Kim <misol.kr@gmail.com>
 **/
(function($) {
	var datepicker_lang = (typeof current_lang !== 'undefined') ? current_lang.replace('jp', 'ja') : 'en';
	var default_options = {
		dateFormat: 'yy-mm-dd'
	};
	$.extend(jQuery.datepicker.regional[datepicker_lang], default_options);
	$.datepicker.setDefaults(jQuery.datepicker.regional[datepicker_lang]);
})(jQuery);

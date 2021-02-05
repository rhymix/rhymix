/**
 * @brief Load i18n file of datepicker
 * @author MinSoo Kim <misol.kr@gmail.com>
 **/
if(typeof current_lang !== "undefined" && current_lang !== 'en') {
	jQuery.getScript(request_uri + "common/js/plugins/ui/i18n/datepicker-"+ current_lang.replace("jp", "ja") +".js", function() {
		var default_option = {
			dateFormat:'yy-mm-dd'
		};
		jQuery.extend(jQuery.datepicker.regional[current_lang.replace("jp", "ja")],default_option);
		jQuery.datepicker.setDefaults( jQuery.datepicker.regional[current_lang.replace("jp", "ja")] );
	});
}
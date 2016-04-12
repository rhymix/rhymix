/**
 * @brief Load i18n file of datepicker
 * @author MinSoo Kim <misol.kr@gmail.com>
 **/
if(typeof current_lang !== "undefined" && current_lang !== 'en') {
	jQuery.getScript(request_uri + "./common/js/plugins/ui/i18n/datepicker-"+ current_lang.replace("jp", "ja") +".js");
}
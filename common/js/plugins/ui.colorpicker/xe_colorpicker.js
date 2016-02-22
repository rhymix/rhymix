/**
 * @brief XE Colorpicker
 * @author NAVER (developers@xpressengine.com)
 **/
jQuery(function($){

	$.fn.xe_colorpicker = function(settings){
		return this.jPicker(settings);
	}

	// 컬러 피커가 내장된 브라우저에서는 내장된 컬러피커 이용 by misol 2016.02.05
	if ( $("input.color-indicator").prop('type') != 'color' ) {
		$('input.color-indicator').xe_colorpicker({
			window:
			{
				position:
				{
						x: 'screenCenter', // acceptable values "left", "center", "right", "screenCenter", or relative px value
						y: 'center', // acceptable values "top", "bottom", "center", or relative px value
				}
			}
		});
	}
});
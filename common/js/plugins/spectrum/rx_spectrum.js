/**
 * @brief Load colorpicker, spectrum
 * @author MinSoo Kim <misol.kr@gmail.com>
 **/
jQuery(function($){
	$.fn.rx_spectrum = function(settings){
		return this.spectrum(settings);
	}

	// 컬러 피커가 내장된 브라우저에서는 내장된 컬러피커 이용
	if ( $("input.rx-spectrum").prop('type') != 'color' ) {
		$.getScript(request_uri + "common/js/plugins/spectrum/i18n/jquery.spectrum-"+ xe.current_lang.replace("jp", "ja").toLowerCase() +".js", function() {
			var settings = {
				showInput: true,
				allowEmpty:true,
				showInitial: true,
				showPalette: true,
				showSelectionPalette: true,
				preferredFormat: "hex",
				move: function (color) {
					
				},
				show: function () {
				
				},
				beforeShow: function () {
				
				},
				hide: function () {
				
				},
				change: function() {
					
				},
				palette: [
					["#000000","#444444","#666666","#999999","#cccccc","#eeeeee","#f3f3f3","#ffffff"],
					["#ff0000","#ff9900","#ffff00","#00ff00","#00ffff","#0000ff","#9900ff","#ff00ff"],
					["#f4cccc","#fce5cd","#fff2cc","#d9ead3","#d0e0e3","#cfe2f3","#d9d2e9","#ead1dc"],
					["#ea9999","#f9cb9c","#ffe599","#b6d7a8","#a2c4c9","#9fc5e8","#b4a7d6","#d5a6bd"],
					["#e06666","#f6b26b","#ffd966","#93c47d","#76a5af","#6fa8dc","#8e7cc3","#c27ba0"],
					["#cc0000","#e69138","#f1c232","#6aa84f","#45818e","#3d85c6","#674ea7","#a64d79"],
					["#990000","#b45f06","#bf9000","#38761d","#134f5c","#0b5394","#351c75","#741b47"],
					["#660000","#783f04","#7f6000","#274e13","#0c343d","#073763","#20124d","#4c1130"]
				]
			}
			$('input.rx-spectrum').rx_spectrum(settings);
		});
	}
});
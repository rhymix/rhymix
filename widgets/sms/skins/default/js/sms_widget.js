
function completeSendSms(rec_obj) {
	console.log(rec_obj);
}

(function($) {

/* DOM READY */
jQuery(function($) {
	var $widgetContainer = $('div.smsxe');
	var $widgetForm = $('form#fo_sms_widget', $widgetContainer);
	var $widgetSerchForm = $('form#fo_sms_widget_search', $widgetContainer);
	var $searchArea = $('.search_area', $widgetContainer);
	var $sendPcsList = $('.send_pcs_list', $widgetContainer);
	var $searchInputbox = $('.input_box', $searchArea);
	var $statusArea = $('.status', $widgetContainer);

	$widgetForm.submit(function() {
		console.log('전송');
	});

	$widgetSerchForm.submit(function() {
		console.log('검색');
		return false;
	});

	$('textarea', $widgetForm).keyup(function() {
		var get_length = PerbizSMS.getByteLength(this);
		if(!get_length) return;

		$('.status_byte', $statusArea).text(get_length.length);
		$('.limit_byte', $statusArea).text(get_length.limit_byte);
		$('.status_count', $statusArea).text(get_length.sms_count);
	});

	$('textarea', $widgetForm).triggerHandler('keyup');

	$('input', $sendPcsList).keypress(function(e) {
		if(e.which == 13) {
			var $nextInput = $(this).parent().next().children('input');

			if($nextInput.length) {
				$nextInput.focus().select();
			} else {
				$('.return_pcs input', $widgetContainer).focus().select();
			}
			return false;
		}
	});

	$('.bth_addressbook a', $widgetContainer).click(function() {
		//PerbizSMS.showAddressbook('window');
		return false;
	});

	$searchInputbox.watermark($searchInputbox.attr('title'));

	/**
	 * @berif 목록에서 번호 삭제
	 **/
	$('.btn_delete', $sendPcsList).click(function() {
		var $inputAll = $('input', $sendPcsList);
		$(this).prev('input').val('');
		var $nextInput = $(this).parent().nextAll().children('input');
		var values = [];

		$inputAll.each(function(idx) {
			if(this.value && this.value != 'undefined') values.push(this.value);
		});

		$inputAll.val('');
		$inputAll.each(function(idx) {
			if(values[idx]) {
				this.value = values[idx];
			} else {
				this.value = '';
			}

		});
	});
});

}) (jQuery);

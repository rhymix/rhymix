'use strict';

$(function() {
	$('input.rx_ev_number').on('input', function() {
		this.value = this.value.replace(/[^0-9]/g, '');
	});
});

/**
 * @brief 금지 IP 삭제
 **/
function doDeleteDeniedIP(ipaddress) {
	var fo_obj = get_by_id('spamfilterDelete');
    fo_obj.ipaddress.value = ipaddress;
	fo_obj.act.value = "procSpamfilterAdminDeleteDeniedIP";
	fo_obj.submit();
}

/**
 * @brief 금지 단어 삭제
 **/
function doDeleteDeniedWord(word) {
	var fo_obj = get_by_id('spamfilterDelete');
	fo_obj.word.value = word;
	fo_obj.act.value = "procSpamfilterAdminDeleteDeniedWord";
	fo_obj.submit();
}

/**
 * Toggle attributes.
 */
$(function() {
	
	$('.denied_ip_toggle_except_member').on('click', function(e) {
		e.preventDefault();
		var that = $(this);
		var new_value = that.text() === 'Y' ? 'N' : 'Y';
		exec_json('spamfilter.procSpamfilterAdminUpdateDeniedIP', {
			ipaddress: that.data('ipaddress'),
			except_member: new_value
		}, function() {
			that.text(new_value);
		});
	});
	
	$('.denied_word_toggle_except_member').on('click', function(e) {
		e.preventDefault();
		var that = $(this);
		var new_value = that.text() === 'Y' ? 'N' : 'Y';
		exec_json('spamfilter.procSpamfilterAdminUpdateDeniedWord', {
			word: that.data('word'),
			except_member: new_value
		}, function() {
			that.text(new_value);
		});
	});
	
	$('.denied_word_toggle_filter_html').on('click', function(e) {
		e.preventDefault();
		var that = $(this);
		var new_value = that.text() === 'Y' ? 'N' : 'Y';
		exec_json('spamfilter.procSpamfilterAdminUpdateDeniedWord', {
			word: that.data('word'),
			filter_html: new_value
		}, function() {
			that.text(new_value);
		});
	});
});

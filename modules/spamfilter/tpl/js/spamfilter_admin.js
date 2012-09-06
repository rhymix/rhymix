/**
 * @brief 금지 IP 삭제
 **/
function doDeleteDeniedIP(ipaddress) {
	var fo_obj = get_by_id('spamfilterDelete');
    fo_obj.ipaddress.value = ipaddress;
	fo_obj.act.value = "procSpamfilterAdminDeleteDeniedIP";
	fo_obj.ruleset.value = 'deleteDeniedIp';
	fo_obj.submit();
}

/**
 * @brief 금지 단어 삭제
 **/
function doDeleteDeniedWord(word) {
	var fo_obj = get_by_id('spamfilterDelete');
	fo_obj.word.value = word;
	fo_obj.act.value = "procSpamfilterAdminDeleteDeniedWord";
	fo_obj.ruleset.value = 'deleteDeniedWord';
	fo_obj.submit();
}
function doInsertDeniedIP(msg_invalid_format){
	var fo_obj = get_by_id('spamfilterInsert');
	var reg_ipaddress = /^((\d{1,3}(?:.(\d{1,3}|\*)){3})\s*(\/\/(.*)\s*)?)*\s*$/;
	var matchStr_ipaddress = fo_obj.ipaddress_list.value;
	if(!matchStr_ipaddress.match(reg_ipaddress)) { 
		alert(msg_invalid_format); return false;
	}
	fo_obj.act.value = "procSpamfilterAdminInsertDeniedIP";
	fo_obj.ruleset.value = "insertDeniedIp";
	fo_obj.submit();
}
function doInsertDeniedWord(msg_invalid_format){
	var fo_obj = get_by_id('spamfilterInsert');
	var reg_word = /^(.{2,40}\s*)*$/;
	var matchStr_word = fo_obj.word_list.value;
	if(!matchStr_word.match(reg_word)) { 
		alert(msg_invalid_format); return false;
	}
	fo_obj.act.value = "procSpamfilterAdminInsertDeniedWord";
	fo_obj.ruleset.value = "insertDeniedWord";
	fo_obj.submit();
}


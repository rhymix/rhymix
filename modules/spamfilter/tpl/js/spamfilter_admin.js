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

function doInsertDeniedSome(some){
	var fo_obj = get_by_id('spamfilterInsert');
	fo_obj.flag.value = 'addsome';
	if(some == 'ipaddressList'){
		fo_obj.wordList.value = '';
	}
	else if(some == 'wordList'){
		fo_obj.ipaddressList.value = '';
	}
	fo_obj.submit();
}

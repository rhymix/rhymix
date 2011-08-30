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

	if(some == 'ipaddressList'){
		var reg = /^(\d{1,3}(?:.(\d{1,3}|\*)){3}\s*(\/\/[^\r\n]*)?[\r\n]*)*$/;
		var matchStr = fo_obj.ipaddressList.value;
		fo_obj.wordList.value = '';
	}
	else if(some == 'wordList'){
		var reg = /^(.{2,40}[\r\n]+)*.{2,40}$/;
		var matchStr = fo_obj.wordList.value;
		fo_obj.ipaddressList.value = '';
	}

	if(!matchStr.match(reg)){
		alert('형식에 맞게 입력하세요'); return false;
	}

	fo_obj.submit();
}

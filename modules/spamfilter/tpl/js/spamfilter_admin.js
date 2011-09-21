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

function checkSomeValid(some){
	var fo_obj = get_by_id('spamfilterInsert');

	var reg_ipaddress = /^(\d{1,3}(?:.(\d{1,3}|\*)){3}\s*(\/\/[^\r\n]*)?[\r\n]*)*$/;
	var matchStr_ipaddress = fo_obj.ipaddressList.value;
	
	var reg_word = /^(.{2,40}[\r\n]+)*.{0,40}$/;
	var matchStr_word = fo_obj.wordList.value;
	
	var valid_word = true;
	var valid_ip = true;
	
	if(!matchStr_ipaddress.match(reg_ipaddress)) valid_ip = false;
	if(!matchStr_word.match(reg_word)) valid_word = false;

	if(some == 'ip'){
		fo_obj.wordList.value = '';
		valid_word = true;
	} else if(some == 'word'){
		fo_obj.ipaddressList.value = '';
		valid_ip = true;
	}

	if(valid_ip && valid_word) return true; 
	else return false;
}


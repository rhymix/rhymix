/**
 * @brief 금지 IP 삭제
 **/
function doDeleteDeniedIP(ipaddress) {
    var fo_obj = xGetElementById('fo_denied_ip');
    fo_obj.ipaddress.value = ipaddress;
    procFilter(fo_obj, delete_denied_ip);
}

/**
 * @brief 금지 단어 삭제
 **/
function doDeleteDeniedWord(word) {
    var fo_obj = xGetElementById('fo_denied_word');
    fo_obj.word.value = word;
    procFilter(fo_obj, delete_denied_word);
}

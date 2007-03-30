/* 사용자 추가 */
function completeInsert(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    var url = new Array();
    var mid = fo_obj.mid.value;
    var document_srl = fo_obj.document_srl.value;
    var page = fo_obj.page.value;

    if(mid) url[url.length] = "mid="+mid;
    if(document_srl) url[url.length] = "document_srl="+document_srl;
    if(page) url[url.length] = "page="+page;

    if(url.length>0) location.href="./?"+url.join("&");
    else location.href="./";
}

/* 로그인 후 */
function completeLogin(ret_obj) {
    location.href = location.href.setQuery('act','');
}

/* 로그아웃 후 */
function completeLogout(ret_obj) {
    location.href = location.href.setQuery('act','');
}

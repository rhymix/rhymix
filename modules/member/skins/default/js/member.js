/* 사용자 추가 */
function completeInsert(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href = location.href.setQuery('act','');
}

/* 정보 수정 */
function completeModify(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href = location.href.setQuery('act','dispMemberInfo');
}

/* 로그인 후 */
function completeLogin(ret_obj) {
    var url =  location.href.setQuery('act','');
    location.href = location.href.setQuery('act','');
}

/* 로그아웃 후 */
function completeLogout(ret_obj) {
    location.href = location.href.setQuery('act','');
}

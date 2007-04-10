/* 로그인 영역에 포커스 */
function doFocusUserId(fo_id) {
    var fo_obj = xGetElementById(fo_id);
    if(xGetCookie('user_id')) {
        fo_obj.user_id.value = xGetCookie('user_id');
        fo_obj.remember_user_id.checked = true;
    }
    fo_obj.user_id.focus();
}

/* 로그인 후 */
function completeLogin(ret_obj, response_tags, params, fo_obj) {
    if(fo_obj.remember_user_id && fo_obj.remember_user_id.checked) {
        var expire = new Date();
        expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
        xSetCookie('user_id', fo_obj.user_id.value, expire);
    }

    var url =  location.href.setQuery('act','');
    location.href = location.href.setQuery('act','');
}





/* 로그인 영역에 포커스 */
function doFocusUserId(fo_id) {
    if(xScrollTop()) return;
    var fo_obj = xGetElementById(fo_id);
    if(fo_obj.user_id) {
        try{
            fo_obj.user_id.focus();
        } catch(e) {};
    }
}

/* 로그인 후 */
function completeLogin(ret_obj, response_tags, params, fo_obj) {
    if(fo_obj.remember_user_id && fo_obj.remember_user_id.checked) {
        var expire = new Date();
        expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
        xSetCookie('user_id', fo_obj.user_id.value, expire);
    }

    var url =  current_url.setQuery('act','');
    location.href = url;
}

/* 오픈아이디 로그인 후 */
function completeOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}




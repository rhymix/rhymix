/* 로그인 후 */
function completeMessageLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    location.href = url;
}

/* 오픈아이디 로그인 후 */
function completeMessageOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}


/* 오픈 아이디 폼 변환 */
function toggleLoginForm(obj) {
    if(xGetElementById('messageLogin').style.display != "none") {
        xGetElementById('messageLogin').style.display = "none";
        xGetElementById('messageOpenidLogin').style.display = "block";
        xGetElementById('messageOpenIDForm').use_open_id_2.checked = true;
        xGetElementById('messageOpenIDForm').openid.focus();
    } else {
        xGetElementById('messageLogin').style.display = "block";
        xGetElementById('messageOpenidLogin').style.display = "none";
        xGetElementById('messageLoginForm').use_open_id.checked = false;
        xGetElementById('messageLoginForm').user_id.focus();
    }
}

/* 사용자 추가 */
function completeInsert(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var redirect_url = ret_obj['redirect_url'];

    alert(message);

    if(redirect_url) location.href = redirect_url;
    else location.href = current_url.setQuery('act','');
}

/* 정보 수정 */
function completeModify(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href = current_url.setQuery('act','dispMemberInfo');
}

/* 이미지 업로드 */
function _doUploadImage(fo_obj, act) {
    // 업로드용 iframe을 생성
    if(!xGetElementById('tmp_upload_iframe')) {
        if(xIE4Up) {
            window.document.body.insertAdjacentHTML("afterEnd", "<iframe id='tmp_upload_iframe' name='tmp_upload_iframe' style='display:none;width:1px;height:1px;position:absolute;top:-10px;left:-10px'></iframe>");
        } else {
            var obj_iframe = xCreateElement('IFRAME');
            obj_iframe.name = obj_iframe.id = 'tmp_upload_iframe';
            obj_iframe.style.display = 'none';
            obj_iframe.style.width = '1px';
            obj_iframe.style.height = '1px';
            obj_iframe.style.position = 'absolute';
            obj_iframe.style.top = '-10px';
            obj_iframe.style.left = '-10px';
            window.document.body.appendChild(obj_iframe);
        }
    }

    fo_obj.target = "tmp_upload_iframe";
    fo_obj.act.value = act;
    fo_obj.submit();
}

/* 이미지 이름/마크 등록 */
function doUploadImageName() {
    var fo_obj = xGetElementById("fo_insert_member");
    if(!fo_obj.image_name.value) return;
    _doUploadImage(fo_obj, 'procMemberInsertImageName');
}

function doUploadImageMark() {
    var fo_obj = xGetElementById("fo_insert_member");
    if(!fo_obj.image_mark.value) return;
    _doUploadImage(fo_obj, 'procMemberInsertImageMark');
}

/* 로그인 영역에 포커스 */
function doFocusUserId(fo_id) {
    var fo_obj = xGetElementById(fo_id);
    if(xGetCookie('user_id')) {
        fo_obj.user_id.value = xGetCookie('user_id');
        fo_obj.remember_user_id.checked = true;
        fo_obj.password.focus();
    } else {
        fo_obj.user_id.focus();
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
    location.href = current_url.setQuery('act','');
}

/* 로그아웃 후 */
function completeLogout(ret_obj) {
    location.href = current_url.setQuery('act','');
}

/* 이미지 이름, 마크 삭제 */
function doDeleteImageName(member_srl) {
        var fo_obj = xGetElementById("fo_insert_member");
        fo_obj.member_srl.value = member_srl;
        procFilter(fo_obj, delete_image_name);
}

function doDeleteImageMark(member_srl) {
        var fo_obj = xGetElementById("fo_insert_member");
        fo_obj.member_srl.value = member_srl;
        procFilter(fo_obj, delete_image_mark);
}


/* 쪽지 발송 */
function completeSendMessage(ret_obj) {
    alert(ret_obj['message']);
    window.close();
}

/* 쪽지 모두 선택 */
function doCheckAll(obj, fo_id) {
    var fo_obj = xGetElementById(fo_id);
    for(var i=0; i<fo_obj.length; i++) {
        if(fo_obj[i].type == "checkbox" && fo_obj[i] != obj) fo_obj[i].checked = obj.checked;
    }
}

/* 개별 쪽지 삭제 */
function doDeleteMessage(message_srl) {
    if(!message_srl) return;

    var params = new Array();
    params['message_srl'] = message_srl;
    exec_xml('member', 'procMemberDeleteMessage', params, completeDeleteMessage);
}

function completeDeleteMessage(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('message_srl','');
}

/* 개별 쪽지 보관 */
function doStoreMessage(message_srl) {
    if(!message_srl) return;

    var params = new Array();
    params['message_srl'] = message_srl;
    exec_xml('member', 'procMemberStoreMessage', params, completeStoreMessage);
}

function completeStoreMessage(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('message_srl','');
}

/* 친구 추가 후 */
function completeAddFriend(ret_obj) {
    alert(ret_obj['message']);
    var member_srl = ret_obj['member_srl'];
    if(opener && opener.loaded_member_menu_list) {
        opener.loaded_member_menu_list[ret_obj['member_srl']] = '';
    }
    window.close();
}

/* 친구 그룹 추가 후 */
function completeAddFriendGroup(ret_obj) {
    alert(ret_obj['message']);
    if(opener) opener.location.href = opener.location.href;
    window.close();
}

/* 친구 그룹 삭제 */
function doDeleteFriendGroup(friend_group_srl) {
    var fo_obj = xGetElementById('for_delete_group');
    fo_obj.friend_group_srl.value = friend_group_srl;
    procFilter(fo_obj, delete_friend_group);
}

function completeDeleteFriendGroup(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('friend_group_srl','');
}

/* 친구 그룹의 이름 변경 */
function doRenameFriendGroup(friend_group_srl) {
    popopen("./?module=member&act=dispMemberAddFriendGroup&friend_group_srl="+friend_group_srl);
}

/* 친구 그룹 이동 */
function doMoveFriend() {
    var fo_obj = xGetElementById('fo_friend_list');
    procFilter(fo_obj, move_friend);
}

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

/* 회원 탈퇴 */ 
function completeLeave(ret_obj, response_tags, args, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href = current_url.setQuery('act','');
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

/* 프로필 이미지/ 이미지 이름/마크 등록 */
function doUploadProfileImage() {
    var fo_obj = xGetElementById("fo_insert_member");
    if(!fo_obj.profile_image.value) return;
    _doUploadImage(fo_obj, 'procMemberInsertProfileImage');
}
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
    location.href = current_url.setQuery('act','');
}

/* 로그아웃 후 */
function completeLogout(ret_obj) {
    location.href = current_url.setQuery('act','');
}

/* 오픈아이디 로그인 후 */
function completeOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}


/* 프로필 이미지/이미지 이름, 마크 삭제 */
function doDeleteProfileImage(member_srl) {
        var fo_obj = xGetElementById("fo_insert_member");
        fo_obj.member_srl.value = member_srl;
        procFilter(fo_obj, delete_profile_image);
}

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

/* 스크랩 삭제 */
function doDeleteScrap(document_srl) {
    var params = new Array();
    params['document_srl'] = document_srl;
    exec_xml('member', 'procMemberDeleteScrap', params, function() { location.reload(); });
}

/* 비밀번호 찾기 후 */
function completeFindMemberAccount(ret_obj, response_tags) {
    alert(ret_obj['message']);
}

/* 저장글 삭제 */
function doDeleteSavedDocument(document_srl, confirm_message) {
    if(!confirm(confirm_message)) return false;

    var params = new Array();
    params['document_srl'] = document_srl;
    exec_xml('member', 'procMemberDeleteSavedDocument', params, function() { location.reload(); });
}

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

/* 로그인 후 */
function completeLogin(ret_obj) {
    var url =  location.href.setQuery('act','');
    location.href = location.href.setQuery('act','');
}

/* 로그아웃 후 */
function completeLogout(ret_obj) {
    location.href = location.href.setQuery('act','');
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


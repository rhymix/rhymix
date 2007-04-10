/**
 * @file   modules/board/js/board.js
 * @author zero (zero@nzeo.com)
 * @brief  board 모듈의 javascript
 **/

/* 관리자가 카트 선택시 세션에 넣음 */
function doAddCart(mid, obj) {
    var srl = obj.value;
    var check_flag = obj.checked?'add':'remove';

    var params = new Array();
    params["mid"] = mid;
    params["srl"] = srl;
    params["check_flag"] = check_flag;

    exec_xml("board","procBoardAdminAddCart", params, null);
}

/* 글쓰기 작성후 */
function completeDocumentInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var category_srl = ret_obj['category_srl'];

    alert(message);

    var url = location.href.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','');
    if(category_srl) url = url.setQuery('category',category_srl);
    location.href = url;
}

/* 글 삭제 */
function completeDeleteDocument(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var page = ret_obj['page'];

    var url = "./?mid="+mid;
    if(page) url += "&page="+page;

    alert(message);

    location.href = url;
}

/* 검색 실행 */
function completeSearch(fo_obj, params) {
    fo_obj.submit();
}

/* 추천, 추천은 별도의 폼입력이 필요 없어 직접 필터 사용 */
function doVote() {
    var fo_obj = document.getElementById('fo_document_info');
    procFilter(fo_obj, vote);
}

function completeVote(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    alert(message);
    location.href = location.href;
}

// 현재 페이지 reload
function completeReload(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    location.href = location.href;
}

/* 댓글 글쓰기 작성후 */
function completeInsertComment(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var comment_srl = ret_obj['comment_srl'];

    var url = "./?mid="+mid+"&document_srl="+document_srl;
    if(comment_srl) url += "#comment_"+comment_srl;

    alert(message);

    location.href = url;
}

/* 댓글 삭제 */
function completeDeleteComment(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var page = ret_obj['page'];

    var url = "./?mid="+mid+'&document_srl='+document_srl;
    if(page) url += "&page="+page;

    alert(message);

    location.href = url;
}

/* 트랙백 삭제 */
function completeDeleteTrackback(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var page = ret_obj['page'];

    var url = "./?mid="+mid+'&document_srl='+document_srl;
    if(page) url += "&page="+page;

    alert(message);

    location.href = url;
}

/* 카테고리 이동 */
function doChangeCategory(sel_obj, url) {
    var category_srl = sel_obj.options[sel_obj.selectedIndex].value;
    if(!category_srl) location.href=url;
    else location.href=url+'&category='+category_srl;
}

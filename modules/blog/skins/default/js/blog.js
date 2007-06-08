/**
 * @file   modules/blog/js/blog.js
 * @author zero (zero@nzeo.com)
 * @brief  blog 모듈의 javascript
 **/

/**
 * 댓글 오픈
 * 댓글의 경우 editor를 동반해서 불러야 하기에 ajax로 tpl파일을 가져와서 쓰는걸로 한다.
 **/
var _opend_comment = new Array();
function doDisplayComment(document_srl) {
    var comment_zone = xGetElementById('comment_'+document_srl);

    // 닫혀 있는 상태라면 한번이라도 열렸었는지 검사하여 열린적이 없다면 에디터를 가져온다
    if(comment_zone.style.display != "block") {
        if(!_opend_comment[document_srl]) {
            _opend_comment[document_srl] = true;
            doGetCommentEditorForm(document_srl);
        }
        comment_zone.style.display = "block";
    }
    else comment_zone.style.display = "none";
}

function doGetCommentEditorForm(document_srl) {
    var params = new Array();
    params['document_srl'] = document_srl;

    var response_tags = new Array('error','message','document_srl','upload_target_srl','tpl');
    show_waiting_message = false;
    exec_xml('blog','getBlogCommentEditorForm', params, completeCommentEditorForm, response_tags);
    show_waiting_message = true;
}

var editor_path = "./modules/editor/tpl";
function completeCommentEditorForm(ret_obj) {
    var document_srl = ret_obj['document_srl'];
    var upload_target_srl = ret_obj['upload_target_srl'];
    var tpl = ret_obj['tpl'];
    var comment_form_zone = xGetElementById('comment_form_'+document_srl);
    if(!comment_form_zone) return;
    
    // tpl 입력
    xInnerHtml(comment_form_zone, tpl);

    // 에디터 실행
    editorStart(upload_target_srl, true, 100);
}

/**
 * 엮인글 오픈
 **/
function doDisplayTrackback(document_srl) {
    var trackback_zone = xGetElementById('trackback_'+document_srl);
    if(trackback_zone.style.display != "block") trackback_zone.style.display = "block";
    else trackback_zone.style.display = "none";
}

/* 글쓰기 작성후 */
function completeDocumentInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var category_srl = ret_obj['category_srl'];

    alert(message);

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','');
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

    alert(message);
    location.href = current_url.setQuery('comment_srl','').setQuery('act','');
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

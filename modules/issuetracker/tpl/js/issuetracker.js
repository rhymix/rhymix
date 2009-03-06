/**
 * @file   modules/board/js/board.js
 * @author zero (zero@nzeo.com)
 * @brief  board 모듈의 javascript
 **/

/* 글쓰기 작성후 */
function completeIssueInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];

    //alert(message);

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','dispIssuetrackerViewIssue');
    location.href = url;
}

/* 히스토리 작성후 */
function completeHistoryInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','dispIssuetrackerViewIssue');
    location.href = url;
}

/* 글 삭제 */
function completeDeleteIssue(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var page = ret_obj['page'];

    var url = current_url.setQuery('mid',mid).setQuery('act','dispIssuetrackerViewIssue').setQuery('document_srl','');
    if(page) url = url.setQuery('page',page);

    //alert(message);

    location.href = url;
}

/* 트랙백 삭제 */
function completeDeleteTrackback(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var page = ret_obj['page'];

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','dispIssuetrackerViewIssue');
    if(page) url = url.setQuery('page',page);

    location.href = url;
}

/* 내용 숨김/열기 */
function openSummaryText(evt) {
    var pObj = evt.target;
    if(!pObj) return;

    while(pObj) {
        if(pObj.nodeName == "DIV" && (pObj.className == "open" || pObj.className == "close")) {
            if(pObj.className == 'open') {
                pObj.className = 'close';
            } else if(pObj.className == 'close') {
                pObj.className = 'open';
            }
        }
        pObj = pObj.parentNode;
    }
}

/* title 레이어 */
function showTitleLayer(evt) {
    var obj = jQuery(evt.target);
    var layer = jQuery("#titleLayer");

    if(!layer.size()) {
        layer = jQuery("<div>")
            .attr('id', "titleLayer")
            .css({
                border : "1px solid #F3B95E",
                backgroundColor : "#FBF2E4",
                padding : "5px",
                color : "#000000",
                display : "none",
                position : "absolute"
            })
            .appendTo(document.body);
    }

    layer.text(obj.attr('rel'));
    layer.css({left:evt.pageX+5, top:evt.pageY+5}).show();

    evt.cancel = true;
    //evt.returnValue = false;
}

/* issue list에서 배포판 선택 */
function showRelease(obj, fo_obj) {
    var packge_srl = jQuery('option:selected', obj).val();
    if(!packge_srl) return;

    var target = jQuery('#release_'+packge_srl);
    if(!target.size()) return;

    var releaseEl = fo_obj.release_srl;
    jQuery('option', releaseEl).remove();
    jQuery('option', target).clone().appendTo(releaseEl);
}


jQuery(function ($) {
    $('.summaryText').click(openSummaryText);
    $('td.filename a[rel]')
        .mouseover(showTitleLayer)
        .mouseout(function() { $("#titleLayer").hide() });
});


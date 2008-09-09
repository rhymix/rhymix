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
xAddEventListener(document,'click',openSummaryText);
function openSummaryText(evt) {
    var e = new xEvent(evt);
    if(!e.target) return;
    var pObj = e.target;
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
xAddEventListener(document,'mouseover',showTitleLayer);
function showTitleLayer(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var layer = xGetElementById("titleLayer");

    if(!obj || obj.nodeName != 'A' || !obj.getAttribute('rel')) {
        if(layer) layer.style.visibility = "hidden";
        return;
    }

    if(!layer) {
        layer = xCreateElement("DIV");
        layer.id = "titleLayer";
        layer.style.border = "1px solid #F3B95E";
        layer.style.backgroundColor = "#FBF2E4";
        layer.style.padding = "5px";
        layer.style.color = "#000000";
        layer.style.visibility = "hidden";
        layer.style.position = "absolute";
        window.document.body.appendChild(layer);
    }

    var text = obj.getAttribute('rel');
    xInnerHtml(layer, text);
    xLeft(layer, e.pageX+5);
    xTop(layer, e.pageY+5);
    layer.style.visibility = "visible";

    evt.cancel = true;
    //evt.returnValue = false;
}

/* issue list에서 배포판 선택 */
function showRelease(obj, fo_obj) {
    var packge_srl = obj.options[obj.selectedIndex].value;
    var target = xGetElementById('release_'+packge_srl);
    if(!packge_srl || !target) return;

    var sel = fo_obj.release_srl;
    while(sel.options.length) {
        sel.remove(0);
    }

    for(var i=0;i<target.options.length;i++) {
        var opt = xCreateElement('option');
        opt.text = target.options[i].text;
        opt.value = target.options[i].value;
        try {
            sel.add(opt, null);
        } catch(e) {
            sel.add(opt);
        }
    }
}

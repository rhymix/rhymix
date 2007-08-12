/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 block이 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var poll_index = 1;
function setPoll() {
    var node = opener.editorPrevNode;
    if(node && node.getAttribute('editor_component')=='poll_maker') {
        alert(msg_poll_cannot_modify);
        window.close();
        return;
    }

    var obj = xCreateElement("div");
    var source = xGetElementById("poll_source");

    var html = xInnerHtml(source);
    html = html.replace(/tidx/g, poll_index);
    xInnerHtml(obj, html);

    obj.id = "poll_"+poll_index;
    obj.className = "poll_box";
    obj.style.display = "block";

    source.parentNode.insertBefore(obj, source);

    setFixedPopupSize();
}

/**
 * 부모창의 위지윅에디터에 데이터를 삽입
 **/
function completeInsertPoll(ret_obj) {
    if(typeof(opener)=="undefined") return null;

    var poll_srl = ret_obj["poll_srl"];
    if(!poll_srl) return null;

    var text = "<img src=\"./common/tpl/images/blank.gif\" poll_srl=\""+poll_srl+"\" editor_component=\"poll_maker\" style=\"width:400px;height:300px;border:2px dotted #4371B9;background:url(./modules/editor/components/poll_maker/tpl/poll_maker_component.gif) no-repeat center;\"  />";

    alert(ret_obj['message']);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
    opener.editorReplaceHTML(iframe_obj, text);

    opener.focus();
    window.close();

    return null;
}

xAddEventListener(window, "load", setPoll);

/**
 * 새 설문 추가
 **/
function doPollAdd() {
    var obj = xCreateElement("div");
    var source = xGetElementById("poll_source");
    if(poll_index+1>8) return null;
    poll_index++;

    var html = xInnerHtml(source);
    html = html.replace(/tidx/g, poll_index);
    xInnerHtml(obj, html);

    obj.id = "poll_"+poll_index;
    obj.className = "poll_box";
    obj.style.display = "block";

    source.parentNode.insertBefore(obj, source);

    setFixedPopupSize();

    return null;
}

/**
 * 항목 삭제
 **/
function doPollDelete(obj) {
    var pobj = obj.parentNode.parentNode.parentNode.parentNode;
    var tmp_arr = pobj.id.split('_');
    var index = tmp_arr[1];
    if(index==1) return;

    pobj.parentNode.removeChild(pobj);

    var obj_list = xGetElementsByClassName('poll_box');
    for(var i=0;i<obj_list.length;i++) {
        var nobj = obj_list[i];
        if(nobj.id == 'poll_source') continue;
        var tmp_arr = nobj.id.split('_');
        var index = tmp_arr[1];
        nobj.id = 'poll_'+(i+1);
    }
    poll_index = i-1;

    setFixedPopupSize();
}

/**
 * 새 항목 추가
 **/
function doPollAddItem(obj) {
    var tbl = xPrevSib(obj.parentNode.parentNode.parentNode);
    var tbody = tbl.lastChild;
    var tmp = tbody.firstChild;
    var source = null;
    while(tmp.nextSibling) {
        tmp = tmp.nextSibling;
        if(tmp.nodeName == "TR") source = tmp;
    }

    var new_obj = source.cloneNode(true);
    new_obj.className = source.className;
    source.parentNode.appendChild(new_obj);

    var html = xInnerHtml(new_obj);
    var idx_match = html.match(/ ([0-9]+)</i);
    var idx = parseInt(idx_match[1],10);

    var tmp = new_obj.firstChild;
    while(tmp) {
        if(tmp.nodeName == "TH") {
            var html = xInnerHtml(tmp);
            html = html.replace(/ ([0-9]+)/, ' '+(idx+1));
            xInnerHtml(tmp, html);
        } else if(tmp.nodeName == "TD") {
            var html = xInnerHtml(tmp);
            html = html.replace(/item_([0-9]+)_([0-9]+)/, 'item_$1_'+(idx+1));
            xInnerHtml(tmp, html);
        }
        tmp = tmp.nextSibling;
    }

    setFixedPopupSize();

    return null;
}

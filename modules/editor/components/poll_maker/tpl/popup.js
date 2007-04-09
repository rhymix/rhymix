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
    if(poll_index+1>3) return null;
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
    var pobj = obj.parentNode.parentNode.parentNode;
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
    var pobj = obj.parentNode.parentNode;
    var source = xPrevSib(pobj);
    var new_obj = xCreateElement("div");
    var html = xInnerHtml(source);

    var idx_match = html.match(/ ([0-9]+)</i);
    if(!idx_match) return null;

    var idx = parseInt(idx_match[1],10);
    html = html.replace( / ([0-9]+)</, ' '+(idx+1)+'<');
    html = html.replace( /value=("){0,1}([^"^\s]*)"{0,1}/, 'value=""');
    html = html.replace( /item_([0-9]+)_([0-9]+)/, 'item_$1_'+(idx+1));

    xInnerHtml(new_obj, html);
    new_obj.className = source.className;

    pobj.parentNode.insertBefore(new_obj, pobj);

    var box_obj = pobj.parentNode;
    var box_height = xHeight(box_obj);
    xHeight(box_obj, box_height+29);

    setFixedPopupSize();

    return null;
}

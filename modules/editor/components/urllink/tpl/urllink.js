/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 block이 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getText() {
    if(typeof(opener)=="undefined") return;
    var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
    var fo_obj = xGetElementById("fo_component");
    if(fo_obj.text.value) return;
    fo_obj.text.value = text;
    self.focus();
}

/**
 * 부모창의 위지윅에디터에 데이터를 삽입
 **/
function setText() {
    if(typeof(opener)=="undefined") return;

    var fo_obj = xGetElementById("fo_component");

    var text = fo_obj.text.value;
    var url = fo_obj.url.value;

    var link = text;
    if(url) link = "<a href=\""+url+"\" target=\"_blank\">"+text+"</a>";

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
    opener.editorReplaceHTML(iframe_obj, link);

    opener.focus();
    self.close();
}

xAddEventListener(window, "load", getText);

function setText() {
    if(typeof(opener)=='undefined') return;
    var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
    xGetElementById("editor").value = text;
    xGetElementById("editor").focus();
}

function insertHtml() {
    var text = xGetElementById("editor").value;
    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

xAddEventListener(window, 'load', setText);

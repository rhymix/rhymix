function insertEmoticon(obj) {
    if(typeof(opener)=='undefined') return;

    var text = "<img src=\""+obj.src+"\" border=\"0\" alt=\"emoticon\" />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

function editorStartTextarea(editor_sequence, content_key, primary_key) {
    var obj = xGetElementById('editor_'+editor_sequence);
    obj.form.setAttribute('editor_sequence', editor_sequence);

    xWidth(obj,xWidth(obj.parentNode)-20);

    editorRelKeys[editor_sequence] = new Array();
    editorRelKeys[editor_sequence]["primary"] = obj.form[primary_key];
    editorRelKeys[editor_sequence]["content"] = obj.form[content_key];
    editorRelKeys[editor_sequence]["func"] = editorGetContentTextarea;

    var content = obj.form[content_key].value;
    content = content.replace(/<br([^>]+)>/ig,"");
    obj.value = content;
}

function editorGetContentTextarea(editor_sequence) {
    var obj = xGetElementById('editor_'+editor_sequence);
    var content = obj.value.trim();
    content = content.replace(/(\r\n|\n)/g, "<br />$1");
    return content;
}

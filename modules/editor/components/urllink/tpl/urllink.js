function setText() {
    if(typeof(opener)=="undefined") return;
    var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
    var fo_obj = xGetElementById("fo");
    if(fo_obj.text.value) return;
    fo_obj.text.value = text;
    self.focus();
}

function insertUrl() {
if(typeof(opener)=="undefined") return;
    var fo_obj = xGetElementById("fo");
    var text = fo_obj.text.value;
    var url = fo_obj.url.value;
    var link_type = fo_obj.link_type.options[fo_obj.link_type.selectedIndex].value;
    if(fo_obj.bold.checked) link_type = "bold "+link_type;
    if(text && url) opener.editorInsertUrl(text, url, link_type);

    opener.focus();
    self.close();
}
xAddEventListener(window, "load", setText);

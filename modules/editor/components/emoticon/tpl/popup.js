function insertImage(obj) {
    if(typeof(opener)=='undefined') return;

    opener.editorInsertEmoticon(obj);
    opener.editorFocus(opener.editorPrevSrl);

    self.close();
}

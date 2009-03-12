function editorGetForm_fck(element) {
    var fo_obj = element.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    if(fo_obj.nodeName == 'FORM') return fo_obj;
    return;
}


function editorGetContent_fck(editor_sequence) {
    return getEditor(editor_sequence).GetHTML();
}

function getEditor(editor_sequence){
    return FCKeditorAPI.GetInstance('fckeditor_'+editor_sequence);
}

function editorStart_fck(editor, element, editor_sequence, content_key, editor_height, primary_key, basepath) {

    var fo_obj = editorGetForm_fck(element);
    fo_obj.setAttribute('editor_sequence', editor_sequence);

    try{
        element.innerHTML = fo_obj[content_key].value;
    }catch(e) {
    }
    try{
        element.value = fo_obj[content_key].value;
    }catch(e) {
    }


//alert(fo_obj[content_key].value);

    editor = new FCKeditor('fckeditor_'+editor_sequence,null,editor_height,'XE') ;
    editor.BasePath = basepath ;

    if(primary_key == 'comment_srl') editor.Config['ToolbarStartExpanded'] = false ;
    editor.ReplaceTextarea();
    editor.getFrame = function(){ return getEditor(editor_sequence).EditorWindow._FCKEditingArea.IFrame;}


    editorRelKeys[editor_sequence] = new Array();
    editorRelKeys[editor_sequence]['editor'] = editor;
    editorRelKeys[editor_sequence]['func'] = editorGetContent_fck;

    editorRelKeys[editor_sequence]['content'] = fo_obj[content_key];
    editorRelKeys[editor_sequence]['primary'] = fo_obj[primary_key];
    editorMode[editor_sequence]=='wysiwyg';

        // saved document(자동저장 문서)에 대한 확인
    if(typeof(fo_obj._saved_doc_title)!="undefined" ) { ///<< _saved_doc_title field가 없으면 자동저장 하지 않음
        var saved_title = fo_obj._saved_doc_title.value;
        var saved_content = fo_obj._saved_doc_content.value;
        if(saved_title || saved_content) {
            // 자동저장된 문서 활용여부를 물은 후 사용하지 않는다면 자동저장된 문서 삭제
            if(confirm(fo_obj._saved_doc_message.value)) {
                if(typeof(fo_obj.title)!='undefined') fo_obj.title.value = saved_title;
                setTimeout(function(){
                            setContent(editor_sequence,saved_content);
                        }, 100);
            } else {
                editorRemoveSavedDoc();
            }
        }
    }

    // 위젯 감시를 위한 더블클릭 이벤트 걸기
    try {
        xAddEventListener(editor.getFrame().contentWindow.document,'dblclick',editorSearchComponent);
    } catch(e) {
    }

    if(typeof(fo_obj._saved_doc_title)!="undefined" ) editorEnableAutoSave(fo_obj, editor_sequence);
}


function setContent(editor_sequence,content){
    try {
       var editor = getEditor(editor_sequence);
       editor.SetHTML(content);
    } catch(e) {
        setTimeout(function(){
            setContent(editor_sequence,content);
        }, 100);

    }
}
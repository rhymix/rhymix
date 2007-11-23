/**
 * 에디터에서 사용하기 위한 변수
 **/
var editorMode = new Array(); ///<< 에디터의 html편집 모드 flag 세팅 변수 (html or null)
var editorAutoSaveObj = {fo_obj:null, editor_sequence:0, title:'', content:'', locked:false} ///< 자동저장을 위한 정보를 가진 object
var editorRelKeys = new Array(); ///< 에디터와 각 모듈과의 연동을 위한 key 값을 보관하는 변수
var editorDragObj = {isDrag:false, y:0, obj:null, id:'', det:0, source_height:0}

function editorGetContent(editor_sequence) {
    return editorRelKeys[editor_sequence]["func"](editor_sequence);
}

/**
 * 자동 저장 기능
 **/
// 자동 저장 활성화 시키는 함수 (10초마다 자동저장)
function editorEnableAutoSave(fo_obj, editor_sequence) {
    var title = fo_obj.title.value;
    var content = editorRelKeys[editor_sequence]['content'].value;
    editorAutoSaveObj = {"fo_obj":fo_obj, "editor_sequence":editor_sequence, "title":title, "content":content, locked:false};
    setTimeout(_editorAutoSave, 10000);
}

// ajax를 이용하여 editor.procEditorSaveDoc 호출하여 자동 저장시킴
function _editorAutoSave() {
    var fo_obj = editorAutoSaveObj.fo_obj;
    var editor_sequence = editorAutoSaveObj.editor_sequence;

    // 현재 자동저장중이면 중지
    if(editorAutoSaveObj.locked == true) return;

    // 대상이 없으면 자동저장 시키는 기능 자체를 중지
    if(!fo_obj || typeof(fo_obj.title)=='undefined' || !editor_sequence) return;

    // 자동저장을 위한 준비
    var title = fo_obj.title.value;
    var content = editorGetContent(editor_sequence);

    // 내용이 이전에 저장하였던 것과 다르면 자동 저장을 함
    if(title != editorAutoSaveObj.title || content != editorAutoSaveObj.content ) {
        var params = new Array();

        params["title"] = title;
        params["content"] = content;
        params["document_srl"] = editorRelKeys[editor_sequence]['primary'].value;

        editorAutoSaveObj.title = title;
        editorAutoSaveObj.content = content;

        var obj = xGetElementById("editor_autosaved_message_"+editor_sequence);
        var oDate = new Date();
        html = oDate.getHours()+':'+oDate.getMinutes()+' '+auto_saved_msg;
        xInnerHtml(obj, html);
        obj.style.display = "block";

        // 현재 자동저장중임을 설정
        editorAutoSaveObj.locked = true;

        // 서버 호출 (서버와 교신중이라는 메세지를 보이지 않도록 함)
        show_waiting_message = false;
        exec_xml("editor","procEditorSaveDoc", params, function() { editorAutoSaveObj.locked = false; } );
        show_waiting_message = true;
    }

    // 10초마다 동기화를 시킴
    setTimeout(_editorAutoSave, 10000);
}

// 자동저장된 모든 메세지를 삭제하는 루틴
function editorRemoveSavedDoc() {
    exec_xml("editor","procEditorRemoveSavedDoc");
}

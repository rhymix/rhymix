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


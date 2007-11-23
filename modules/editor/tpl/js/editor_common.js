/**
 * 에디터에서 사용하기 위한 변수
 **/
var editorMode = new Array(); ///<< 에디터의 html편집 모드 flag 세팅 변수 (html or null)
var editorAutoSaveObj = {fo_obj:null, editor_sequence:0, title:'', content:'', locked:false} ///< 자동저장을 위한 정보를 가진 object
var editorRelKeys = new Array(); ///< 에디터와 각 모듈과의 연동을 위한 key 값을 보관하는 변수
var editorDragObj = {isDrag:false, y:0, obj:null, id:'', det:0, source_height:0}

/**
 * 에디터 사용시 사용되는 이벤트 연결 함수 호출
 **/
xAddEventListener(document, 'mousedown', editorDragStart);
xAddEventListener(document, 'mouseup', editorDragStop);

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

/**
 * 에디터의 상태나 객체를 구하기 위한 함수
 **/

// editor_sequence값에 해당하는 iframe의 object를 return
function editorGetIFrame(editor_sequence) {
    if(editorRelKeys[editor_sequence]['editor'] != undefined)
	return editorRelKeys[editor_sequence]['editor'].getFrame();
    return xGetElementById( 'editor_iframe_'+ editor_sequence );
}

/**
 * iframe 세로 크기 조절 드래그 관련
 **/
function editorDragStart(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(typeof(obj.id)=='undefined'||!obj.id) return;

    var id = obj.id;
    if(id.indexOf('editor_drag_bar_')!=0) return;

    editorDragObj.isDrag = true;
    editorDragObj.y = e.pageY;
    editorDragObj.obj = e.target;
    editorDragObj.id = id.substr('editor_drag_bar_'.length);

    var iframe_obj = editorGetIFrame(editorDragObj.id);

    editorDragObj.source_height = xHeight(iframe_obj);

    xAddEventListener(document, 'mousemove', editorDragMove, false);
    xAddEventListener(editorDragObj.obj, 'mousemove', editorDragMove, false);
}

function editorDragMove(evt) {
    if(!editorDragObj.isDrag) return;

    var e = new xEvent(evt);
    var h = e.pageY - editorDragObj.y;

    editorDragObj.isDrag = true;
    editorDragObj.y = e.pageY;
    editorDragObj.obj = e.target;

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    xHeight(iframe_obj, xHeight(iframe_obj)+h);
    xHeight(iframe_obj.parentNode, xHeight(iframe_obj)+10);
}

function editorDragStop(evt) {
    if(!editorDragObj.isDrag) return;

    xRemoveEventListener(document, 'mousemove', editorDragMove, false);
    xRemoveEventListener(editorDragObj.obj, 'mousemove', editorDragMove, false);

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    if(typeof(fixAdminLayoutFooter)=='function') fixAdminLayoutFooter(xHeight(iframe_obj)-editorDragObj.source_height);

    editorDragObj.isDrag = false;
    editorDragObj.y = 0;
    editorDragObj.obj = null;
    editorDragObj.id = '';
}

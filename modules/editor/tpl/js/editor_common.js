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
xAddEventListener(document, 'click', editorEventCheck);
xAddEventListener(document, 'mousedown', editorDragStart);
xAddEventListener(document, 'mouseup', editorDragStop);

function editorGetContent(editor_sequence) {
    // 입력된 내용을 받아옴
    var content = editorRelKeys[editor_sequence]["func"](editor_sequence);

    // 첨부파일 링크시 url을 변경
    var reg_pattern = new RegExp( request_uri.replace(/\//g,'\\/')+"(files|common|modules|layouts|widgets)", 'ig' );
    return content.replace(reg_pattern, "$1");
}

// 에디터에 포커스를 줌
function editorFocus(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    iframe_obj.contentWindow.focus();
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
        obj.style.display = 'block';
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
    if(editorRelKeys != undefined && editorRelKeys[editor_sequence] != undefined && editorRelKeys[editor_sequence]['editor'] != undefined)
    return editorRelKeys[editor_sequence]['editor'].getFrame();
    return xGetElementById( 'editor_iframe_'+ editor_sequence );
}
function editorGetTextarea(editor_sequence) {
    return xGetElementById( 'editor_textarea_'+ editor_sequence );
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
    var textarea_obj = editorGetTextarea(editorDragObj.id);
    var preview_obj = xGetElementById('editor_preview_'+editorDragObj.id);
    editorDragObj.source_height = xHeight(iframe_obj) || xHeight(preview_obj);
    xGetElementById('xeEditorMask_' + editorDragObj.id).style.display='block';

    xAddEventListener(document, 'mousemove', editorDragMove, true);
//    xAddEventListener(editorDragObj.obj, 'mousemove', editorDragMove, false);
}

function editorDragMove(evt) {

    if(!editorDragObj.isDrag){
        if(editorDragObj.id) xGetElementById('xeEditorMask_' + editorDragObj.id).style.display='none';
        return;
    }

    var e = new xEvent(evt);
    var h = e.pageY - editorDragObj.y;

    editorDragObj.isDrag = true;
    editorDragObj.y = e.pageY;
    editorDragObj.obj = e.target;


    var iframe_obj = editorGetIFrame(editorDragObj.id);
    var textarea_obj = editorGetTextarea(editorDragObj.id);
    var preview_obj = xGetElementById('editor_preview_'+editorDragObj.id);
    var height = xHeight(iframe_obj) || xHeight(textarea_obj) || xHeight(preview_obj);
    height += h;
    xHeight(iframe_obj, height);
    xHeight(textarea_obj, height);
    xHeight(preview_obj, height);
//    xHeight(iframe_obj.parentNode, height+200);
}

function editorDragStop(evt) {
    if(editorDragObj.id) xGetElementById('xeEditorMask_'+editorDragObj.id).style.display='none';
    if(!editorDragObj.isDrag){
        return;
    }


    xRemoveEventListener(document, 'mousemove', editorDragMove, false);
//    xRemoveEventListener(editorDragObj.obj, 'mousemove', editorDragMove, false);

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    var textarea_obj = editorGetTextarea(editorDragObj.id);
    if(typeof(fixAdminLayoutFooter)=='function') fixAdminLayoutFooter(xHeight(iframe_obj)-editorDragObj.source_height);


    editorDragObj.isDrag = false;
    editorDragObj.y = 0;
    editorDragObj.obj = null;
    editorDragObj.id = '';
}

// Editor Option Button
function eOptionOver(obj) {
    obj.style.marginTop='-21px';
    obj.style.zIndex='99';
}
function eOptionOut(obj) {
    obj.style.marginTop='0';
    obj.style.zIndex='1';
}
function eOptionClick(obj) {
    obj.style.marginTop='-42px';
    obj.style.zIndex='99';
}

/**
 * 에디터 컴포넌트 구현 부분
 **/

// 에디터 상단의 컴포넌트 버튼 클릭시 action 처리 (마우스다운 이벤트 발생시마다 요청이 됨)
var editorPrevSrl = null;
function editorEventCheck(evt) {
    editorPrevNode = null;

    // 이벤트가 발생한 object의 ID를 구함
    var e = new xEvent(evt);
    var target_id = e.target.id;
    if(!target_id) return;

    // editor_sequence와 component name을 구함 (id가 포맷과 다르면 return)
    var info = target_id.split('_');
    if(info[0]!="component") return;
    var editor_sequence = info[1];
    var component_name = target_id.replace(/^component_([0-9]+)_/,'');
    if(!editor_sequence || !component_name) return;

    if(editorMode[editor_sequence]=='html') return;

    switch(component_name) {

        // 기본 기능에 대한 동작 (바로 실행)
        case 'Bold' :
        case 'Italic' :
        case 'Underline' :
        case 'StrikeThrough' :
        case 'undo' :
        case 'redo' :
        case 'JustifyLeft' :
        case 'JustifyCenter' :
        case 'JustifyRight' :
        case 'JustifyFull' :
        case 'Indent' :
        case 'Outdent' :
        case 'InsertOrderedList' :
        case 'InsertUnorderedList' :
        case 'SaveAs' :
        case 'Print' :
        case 'Copy' :
        case 'Cut' :
        case 'Paste' :
        case 'RemoveFormat' :
        case 'Subscript' :
        case 'Superscript' :
                editorDo(component_name, '', editor_sequence);
            break;

        // 추가 컴포넌트의 경우 서버에 요청을 시도
        default :
                openComponent(component_name, editor_sequence);
            break;
    }

    return;
}

// 컴포넌트 팝업 열기
function openComponent(component_name, editor_sequence, manual_url) {
    editorPrevSrl = editor_sequence;
    if(editorMode[editor_sequence]=='html') return;

    var popup_url = request_uri+"?module=editor&act=dispEditorPopup&editor_sequence="+editor_sequence+"&component="+component_name;
    if(typeof(manual_url)!="undefined" && manual_url) popup_url += "&manual_url="+escape(manual_url);

    popopen(popup_url, 'editorComponent');
}

// 더블클릭 이벤트 발생시에 본문내에 포함된 컴포넌트를 찾는 함수
var editorPrevNode = null;
function editorSearchComponent(evt) {
    var e = new xEvent(evt);

    editorPrevNode = null;
    var obj = e.target;

    // 위젯인지 일단 체크
    if(obj.getAttribute("widget")) {
        // editor_sequence을 찾음
        var tobj = obj;
        while(tobj && tobj.nodeName != "BODY") {
            tobj = xParent(tobj);
        }
        if(!tobj || tobj.nodeName != "BODY" || !tobj.getAttribute("editor_sequence")) {
            editorPrevNode = null;
            return;
        }
        var editor_sequence = tobj.getAttribute("editor_sequence");
        var widget = obj.getAttribute("widget");
        editorPrevNode = obj;

        if(editorMode[editor_sequence]=='html') return;
        popopen(request_uri+"?module=widget&act=dispWidgetGenerateCodeInPage&selected_widget="+widget+"&module_srl="+editor_sequence,'GenerateCodeInPage');
        return;
    }

    // 선택되어진 object부터 상단으로 이동하면서 editor_component attribute가 있는지 검사
    if(!obj.getAttribute("editor_component")) {
        while(obj && !obj.getAttribute("editor_component")) {
            if(obj.parentElement) obj = obj.parentElement;
            else obj = xParent(obj);
        }
    }

    if(!obj) obj = e.target;

    var editor_component = obj.getAttribute("editor_component");

    // editor_component를 찾지 못했을 경우에 이미지/텍스트/링크의 경우 기본 컴포넌트와 연결
    if(!editor_component) {
        // 이미지일 경우
        if(obj.nodeName == "IMG") {
            editor_component = "image_link";
            editorPrevNode = obj;

        // 테이블의 td일 경우
        } else if(obj.nodeName == "TD") {
            editor_component = "table_maker";
            editorPrevNode = obj;

        // 링크거나 텍스트인 경우
        } else if(obj.nodeName == "A" || obj.nodeName == "BODY" || obj.nodeName.indexOf("H")==0 || obj.nodeName == "LI" || obj.nodeName == "P") {
            editor_component = "url_link";
            editorPrevNode = obj;
        }
    } else {
        editorPrevNode = obj;
    }

    // 아무런 editor_component가 없다면 return
    if(!editor_component) {
        editorPrevNode = null;
        return;
    }

    // editor_sequence을 찾음
    var tobj = obj;
    while(tobj && tobj.nodeName != "BODY") {
        tobj = xParent(tobj);
    }
    if(!tobj || tobj.nodeName != "BODY" || !tobj.getAttribute("editor_sequence")) {
        editorPrevNode = null;
        return;
    }
    var editor_sequence = tobj.getAttribute("editor_sequence");

    // 해당 컴포넌트를 찾아서 실행
    openComponent(editor_component, editor_sequence);
}

// 에디터 내의 선택된 부분의 html코드를 변경
function editorReplaceHTML(iframe_obj, html) {
    // 에디터가 활성화 되어 있는지 확인 후 비활성화시 활성화
    var editor_sequence = iframe_obj.contentWindow.document.body.getAttribute("editor_sequence");

    // iframe 에디터에 포커스를 둠
    iframe_obj.contentWindow.focus();

    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        if(range.pasteHTML) {
            range.pasteHTML(html);
        } else if(editorPrevNode) {
            editorPrevNode.outerHTML = html;
        }

    } else {

        try {
            if(iframe_obj.contentWindow.getSelection().focusNode.tagName == "HTML") {
                var range = iframe_obj.contentDocument.createRange();
                range.setStart(iframe_obj.contentDocument.body,0);
                range.setEnd(iframe_obj.contentDocument.body,0);
                range.insertNode(range.createContextualFragment(html));
            } else {
                var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
                range.deleteContents();
                range.insertNode(range.createContextualFragment(html));
            }
        } catch(e) {
            xInnerHtml(iframe_obj.contentWindow.document.body, html+xInnerHtml(iframe_obj.contentWindow.document.body));
        }

    }
}

// 에디터 내의 선택된 부분의 html 코드를 return
function editorGetSelectedHtml(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        var html = range.htmlText;
        return html;
    } else {
        var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
        var dummy = xCreateElement('div');
        dummy.appendChild(range.cloneContents());
        var html = xInnerHtml(dummy);
        return html;
    }
}

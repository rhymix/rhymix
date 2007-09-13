/**
 * @author zero (zero@nzeo.com)
 * @version 0.1
 * @brief 에디터 관련 스크립트
 **/

/**
 * 에디터에서 사용하는 iframe, textarea의 prefix
 **/
var iframe_id = 'editor_iframe_'; ///< 에디터로 사용하는 iframe의 prefix
var textarea_id = 'editor_textarea_'; ///< 에디터의 html편집 모드에서 사용하는 textarea의 prefix
var editor_mode = new Array(); ///<< 에디터의 html편집 모드 flag 세팅 변수
var _editorSyncList = new Array(); ///< 에디터와 form 동기화를 위한 동기화 대상 목록
var _autoSaveObj = {fo_obj:null, editor_sequence:0, title:'', content:'', locked:false} ///< 자동저장을 위한 정보를 가진 object
var editor_rel_keys = new Array(); ///< 에디터와 각 모듈과의 연동을 위한 key 값을 보관하는 변수

/**
 * 에디터 사용시 사용되는 이벤트 연결 함수 호출
 **/
xAddEventListener(window, 'load', _editorSync); ///< 에디터의 동기화를 하는 함수를 window.load시 실행



/**
 * 에디터의 상태나 객체를 구하기 위한 함수
 **/

// editor_sequence값에 해당하는 iframe의 object를 return
function editorGetIFrame(editor_sequence) {
    var obj_id = iframe_id + editor_sequence;
    return xGetElementById(obj_id);
}

// editor_sequence값에 해당하는 textarea object를 return
function editorGetTextArea(editor_sequence) {
    var obj_id = textarea_id + editor_sequence;
    return xGetElementById(obj_id);
}

// editor_sequence에 해당하는 form문 구함
function editorGetForm(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(!iframe_obj) return;
    var fo_obj = iframe_obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    if(fo_obj.nodeName == 'FORM') return fo_obj;
    return;
}

// 에디터의 전체 내용 return, HTML 편집모드일 경우에 데이터를 이전후 값 return
function editorGetContent(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(!iframe_obj) return null;

    var html = null;
    if(editor_mode[editor_sequence]=='html') {
        var textarea_obj = editorGetTextArea(editor_sequence);
        var html = textarea_obj.value;
        var contentDocument = iframe_obj.contentWindow.document;
        contentDocument.body.innerHTML = html;
    }

    html = xInnerHtml(iframe_obj.contentWindow.document.body);
    if(html) html = html.replace(/^<br([^>]*)>$/i,'');
    return html;
}

// 에디터 내의 선택된 부분의 html 코드를 return
function editorGetSelectedHtml(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        var html = range.htmlText;
        //range.select();
        return html;
    } else {
        var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
        var dummy = xCreateElement('div');
        dummy.appendChild(range.cloneContents());
        var html = xInnerHtml(dummy);
        return html;
    }
}

// 에디터 내의 선택된 부분의 NODE를 return
function editorGetSelectedNode(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        var div = xCreateElement('div');
        xInnerHtml(div, range.htmlText);
        var node = div.firstChild;
        return node;
    } else {
        var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
        var node = xCreateElement('div');
        node.appendChild(range.cloneContents());
        return node.firstChild;
    }
}


/**
 * editor 시작 (editor_sequence로 iframe객체를 얻어서 쓰기 모드로 전환)
 **/
function editorStart(editor_sequence, primary_key, content_key, resizable, editor_height) {
    // resize 가/불가에 대한 체크
    if(typeof(resizable)=="undefined"||!resizable) resizable = false;
    else resizable = true;

    // iframe obj를 찾음
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(!iframe_obj) return;
    iframe_obj.style.width = '100%'; ///<< iframe_obj의 가로 크기를 100%로 고정

    // 현 에디터를 감싸고 있는 form문을 찾아서 content object를 찾아서 내용 sync
    var fo_obj = editorGetForm(editor_sequence);
    if(!fo_obj) return;

    // 모듈 연관 키 값을 세팅
    editor_rel_keys[editor_sequence] = new Array();
    editor_rel_keys[editor_sequence]["primary"] = fo_obj[primary_key];
    editor_rel_keys[editor_sequence]["content"] = fo_obj[content_key];

    // saved document(자동저장 문서)에 대한 확인
    if(typeof(fo_obj._saved_doc_title)!="undefined" ) { ///<< _saved_doc_title field가 없으면 자동저장 하지 않음

        var saved_title = fo_obj._saved_doc_title.value;
        var saved_content = fo_obj._saved_doc_content.value;

        if(saved_title || saved_content) {
            // 자동저장된 문서 활용여부를 물은 후 사용하지 않는다면 자동저장된 문서 삭제
            if(confirm(fo_obj._saved_doc_message.value)) {
                if(typeof(fo_obj.title)!='undefined') fo_obj.title.value = saved_title;
                editor_rel_keys[editor_sequence]['content'].value = saved_content;
            } else {
                editorRemoveSavedDoc();
            }
        }
    }

    // 대상 form의 content element에서 데이터를 구함
    var content = editor_rel_keys[editor_sequence]['content'].value;

    // IE가 아니고 내용이 없으면 <br /> 추가 (FF등에서 iframe 선택시 focus를 주기 위한 꽁수)
    if(!content && !xIE4Up) content = "<br />";

    // iframe내의 document element를 구함
    var contentDocument = iframe_obj.contentWindow.document;

    /**
     * 에디터를 위지윅 모드로 만들기 위해 내용 작성 후 designMode 활성화
     **/
    var contentHtml = ''+
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'+
        '<html lang="ko" xmlns="http://www.w3.org/1999/xhtml><head><meta http-equiv="content-type" content="text/html; charset=utf-8"/>'+
		'<base href="'+request_uri+'" />'+
        '<link rel="stylesheet" href="'+request_uri+'/common/css/default.css" type="text/css" />'+
        '<link rel="stylesheet" href="'+request_uri+editor_path+'/css/editor.css" type="text/css" />'+
        '<style style="text/css">'+
        'body {font-size:9pt;margin:0px; height:'+editor_height+'px;}'+
        'blockquote, ol, ul { margin-left:40px; }'+
        '</style>'+
        '</head><body editor_sequence="'+editor_sequence+'">'+
        content+
        '</body></html>'+
        '';
    contentDocument.designMode = 'on';
    try {
        contentDocument.execCommand("undo", false, null);
        contentDocument.execCommand("useCSS", false, true);
    }  catch (e) {
    }
    contentDocument.open("text/html","replace");
    contentDocument.write(contentHtml);
    contentDocument.close();

    /**
     * 더블클릭이나 키눌림등의 각종 이벤트에 대해 listener 추가
     **/
    // 작성시 필요한 이벤트 체크
    if(xIE4Up) xAddEventListener(contentDocument, 'keydown',editorKeyPress);
    else xAddEventListener(contentDocument, 'keypress',editorKeyPress);
    xAddEventListener(contentDocument,'mousedown',editorHideObject);

    // 위젯 감시를 위한 더블클릭 이벤트 걸기 (오페라에 대한 처리는 차후에.. 뭔가 이상함)
    xAddEventListener(contentDocument,'dblclick',editorSearchComponent);
    xAddEventListener(document,'dblclick',editorSearchComponent);
    xAddEventListener(document,'mouseup',editorEventCheck);
    xAddEventListener(document,'mousedown',editorHideObject);

    // IE일 경우 ctrl-Enter 안내 문구를 노출
    if(xIE4Up && xGetElementById('for_ie_help_'+editor_sequence)) {
        xGetElementById('for_ie_help_'+editor_sequence).style.display = "block";
    }

    /**
     * 에디터의 내용을 지속적으로 fo_obj.content의 값과 동기화를 시킴.
     * 차후 다른 에디터를 사용하더라도 fo_obj.content와 동기화만 된다면 어떤 에디터라도 사용 가능하도록 하기 위해
     * 별도의 동기화 루틴을 이용함
     **/
    editorSyncContent(editor_rel_keys[editor_sequence]['content'], editor_sequence);

    // 자동저장 필드가 있다면 자동 저장 기능 활성화
    if(typeof(fo_obj._saved_doc_title)!="undefined" ) editorEnableAutoSave(fo_obj, editor_sequence);

    // 크기 변경 불가일 경우 드래그바 숨김
    if(resizable == false) xGetElementById("editor_drag_bar_"+editor_sequence).style.display = "none";
    else xGetElementById("editor_drag_bar_"+editor_sequence).style.display = "block";

    // editor_mode를 기본으로 설정
    editor_mode[editor_sequence] = null;
}



/**
 * 에디터와 form문의 동기화를 위한 함수들
 **/
// 여러개의 편집기를 예상하여 전역 배열 변수에 form, iframe의 정보를 넣음
function editorSyncContent(obj, editor_sequence) {
    _editorSyncList[_editorSyncList.length] = {field:obj, editor_sequence:editor_sequence}
}

// 편집기와 폼의 정보를 주기적으로 동기화 시킴
function _editorSync() {
    // 등록된 모든 에디터에 대해 동기화를 시킴
    for(var i=0;i<_editorSyncList.length;i++) {
        var field = _editorSyncList[i].field;
        var editor_sequence = _editorSyncList[i].editor_sequence;
        var content = editorGetContent(editor_sequence);
        if(typeof(content)=='undefined') continue;
        field.value = content;
    }

    // 1.5초마다 계속 동기화 시킴
    setTimeout(_editorSync, 1500);
}



/**
 * 자동 저장 기능
 **/
// 자동 저장 활성화 시키는 함수 (5초마다 자동저장)
function editorEnableAutoSave(fo_obj, editor_sequence) {
    var title = fo_obj.title.value;
    var content = editor_rel_keys[editor_sequence]['content'].value;
    _autoSaveObj = {"fo_obj":fo_obj, "editor_sequence":editor_sequence, "title":title, "content":content, locked:false};
    setTimeout(_editorAutoSave, 5000);
}

// ajax를 이용하여 editor.procEditorSaveDoc 호출하여 자동 저장시킴
function _editorAutoSave() {
    var fo_obj = _autoSaveObj.fo_obj;
    var editor_sequence = _autoSaveObj.editor_sequence;

    // 현재 자동저장중이면 중지
    if(_autoSaveObj.locked == true) return;

    // 대상이 없으면 자동저장 시키는 기능 자체를 중지
    if(!fo_obj || typeof(fo_obj.title)=='undefined' || !editor_sequence) return;

    // 자동저장을 위한 준비
    var title = fo_obj.title.value;
    var content = editorGetContent(editor_sequence);

    // 내용이 이전에 저장하였던 것과 다르면 자동 저장을 함
    if(title != _autoSaveObj.title || content != _autoSaveObj.content ) {
        var params = new Array();

        params["title"] = title;
        params["content"] = content;
        params["document_srl"] = editor_rel_keys[editor_sequence]['primary'].value;

        _autoSaveObj.title = title;
        _autoSaveObj.content = content;

        var obj = xGetElementById("editor_autosaved_message_"+editor_sequence);
        var oDate = new Date();
        html = oDate.getHours()+':'+oDate.getMinutes()+' '+auto_saved_msg;
        xInnerHtml(obj, html);
        obj.style.display = "block";

        // 현재 자동저장중임을 설정
        _autoSaveObj.locked = true;

        // 서버 호출 (서버와 교신중이라는 메세지를 보이지 않도록 함)
        show_waiting_message = false;
        exec_xml("editor","procEditorSaveDoc", params, function() { _autoSaveObj.locked = false; } );
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
 * 에디터의 세부 설정과 데이터 핸들링을 정의한 함수들
 **/

// 에디터에 포커스를 줌
function editorFocus(editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    iframe_obj.contentWindow.focus();
}

// 에디터 내의 선택된 부분의 html코드를 변경
function editorReplaceHTML(iframe_obj, html) {
    iframe_obj.contentWindow.focus();
    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        if(range.pasteHTML) {
            range.pasteHTML(html);
        } else if(editorPrevNode) {
            editorPrevNode.outerHTML = html;
        }
    } else {
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
    }
}

/**
 * 키 또는 마우스 이벤트 핸들링 정의 함수
 **/

// 입력 키에 대한 이벤트 체크
function editorKeyPress(evt) {
    var e = new xEvent(evt);

    // 대상을 구함
    var obj = e.target;
    var body_obj = null;
    if(obj.nodeName == "BODY") body_obj = obj;
    else body_obj = obj.firstChild.nextSibling;
    if(!body_obj) return;

    // editor_sequence는 에디터의 body에 attribute로 정의되어 있음
    var editor_sequence = body_obj.getAttribute("editor_sequence");
    if(!editor_sequence) return;

    // IE에서 enter키를 눌렀을때 P 태그 대신 BR 태그 입력
    if (xIE4Up && !e.ctrlKey && !e.shiftKey && e.keyCode == 13 && editor_mode[editor_sequence]!='html') {
        var iframe_obj = editorGetIFrame(editor_sequence);
        if(!iframe_obj) return;

        var contentDocument = iframe_obj.contentWindow.document;

        var obj = contentDocument.selection.createRange();

        var pTag = obj.parentElement().tagName.toLowerCase();

        switch(pTag) {
            case 'li' :
                    return;
                break; 
            default :
                    obj.pasteHTML("<br />\n");
                break;
        }
        obj.select();
        evt.cancelBubble = true;
        evt.returnValue = false;
        return;
    }

    // ctrl-S, alt-S 클릭시 submit하기
    if( e.keyCode == 115 && (e.altKey || e.ctrlKey) ) {
        // iframe 에디터를 찾음
        var iframe_obj = editorGetIFrame(editor_sequence);
        if(!iframe_obj) return;

        // 대상 form을 찾음
        var fo_obj = editorGetForm(editor_sequence);
        if(!fo_obj) return;

        // 데이터 동기화
        editor_rel_keys[editor_sequence]['content'].value = editorGetContent(editor_sequence);

        // form문 전송
        if(fo_obj.onsubmit) fo_obj.onsubmit();

        // 이벤트 중단
        evt.cancelBubble = true;
        evt.returnValue = false;
        xPreventDefault(evt);
        xStopPropagation(evt);
        return;
    }

    // ctrl-b, i, u, s 키에 대한 처리 (파이어폭스에서도 에디터 상태에서 단축키 쓰도록)
    if (e.ctrlKey) {
        // iframe 에디터를 찾음
        var iframe_obj = editorGetIFrame(editor_sequence);
        if(!iframe_obj) return;

        // html 에디터 모드일 경우 이벤트 취소 시킴
        if(editor_mode[editor_sequence]=='html') {
            evt.cancelBubble = true;
            evt.returnValue = false;
            xPreventDefault(evt);
            xStopPropagation(evt);
            return;
        }

        switch(e.keyCode) {
            // ctrl+1~6
            case 49 :
            case 50 :
            case 51 :
            case 52 :
            case 53 :
            case 54 :
                    editorDo('formatblock',"<H"+(e.keyCode-48)+">",e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            // ctrl+7
            case 55 :
                    editorDo('formatblock',"<P>",e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            // ie에서 ctrlKey + enter일 경우 P 태그 입력
            case 13 :
                    if(xIE4Up) {
                        if(e.target.parentElement.document.designMode!="On") return;
                        var obj = e.target.parentElement.document.selection.createRange();
                        obj.pasteHTML('<P>');
                        obj.select();
                        evt.cancelBubble = true;
                        evt.returnValue = false;
                        return;
                    }
            // bold
            case 98 :
                    editorDo('Bold',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            // italic
            case 105 :
                    editorDo('Italic',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            // underline
            case 117 : 
                    editorDo('Underline',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            // strike
            /*
            case 83 :
            case 115 :
                    editorDo('StrikeThrough',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            */
        }
    }
}

// 편집 기능 실행
function editorDo(command, value, target) {

    var doc = null;

    // target이 object인지 editor_sequence인지에 따라 document를 구함
    if(typeof(target)=="object") {
        if(xIE4Up) doc = target.parentElement.document;
        else doc = target.parentNode;
    } else {
        var iframe_obj = editorGetIFrame(target);
        doc = iframe_obj.contentWindow.document;
    }

    var editor_sequence = doc.body.getAttribute('editor_sequence');
    if(editor_mode[editor_sequence]=='html') return;

    // 포커스
    if(typeof(target)=="object") target.focus();
    else editorFocus(target);

    // 실행
    doc.execCommand(command, false, value);

    // 포커스
    if(typeof(target)=="object") target.focus();
    else editorFocus(target);
}

// 폰트를 변경
function editorChangeFontName(obj,srl) {
    var value = obj.options[obj.selectedIndex].value;
    if(!value) return;
    editorDo('FontName',value,srl);
    obj.selectedIndex = 0;
}

function editorChangeFontSize(obj,srl) {
    var value = obj.options[obj.selectedIndex].value;
    if(!value) return;
    editorDo('FontSize',value,srl);
    obj.selectedIndex = 0;
}

function editorChangeHeader(obj,srl) {
    var value = obj.options[obj.selectedIndex].value;
    if(!value) return;
    value = "<"+value+">";
    editorDo('formatblock',value,srl);
    obj.selectedIndex = 0;
}


/**
 * 에디터 컴포넌트 구현 부분
 **/

// 에디터 상단의 컴포넌트 버튼 클릭시 action 처리 (마우스다운 이벤트 발생시마다 요청이 됨)
var editorPrevObj = null;
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

    if(editor_mode[editor_sequence]=='html') return;

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
		case 'Copy' :
		case 'Cut' :
		case 'Paste' :
        case 'RemoveFormat' :
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
    if(editor_mode[editor_sequence]=='html') return;

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

        if(editor_mode[editor_sequence]=='html') return;
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

// 마우스 클릭시 이전 object정보를 숨김
function editorHideObject(evt) {
    if(!editorPrevObj) return;
    var e = new xEvent(evt);
    var tobj = e.target;
    while(tobj) {
        if(tobj.id == editorPrevObj.id) { 
            return;
        }
        tobj = xParent(tobj);
    }
    editorPrevObj.style.visibility = 'hidden';
    editorPrevObj = null;
    return;
}


/**
 * HTML 편집 기능 활성/비활성
 **/
function editorChangeMode(obj, editor_sequence) {
    var iframe_obj = editorGetIFrame(editor_sequence);
    if(!iframe_obj) return;

    var textarea_obj = editorGetTextArea(editor_sequence);
    xWidth(textarea_obj, xWidth(iframe_obj.parentNode));
    xHeight(textarea_obj, xHeight(iframe_obj.parentNode));

    var contentDocument = iframe_obj.contentWindow.document;

    // html 편집 사용시
    if(obj.checked) {
        var html = contentDocument.body.innerHTML;
        html = html.replace(/<br>/ig,"<br />\n");
        html = html.replace(/<br \/>\n\n/ig,"<br />\n");

        textarea_obj.value = html;

        iframe_obj.parentNode.style.display = "none";
        textarea_obj.style.display = "block";
        xGetElementById('xeEditorOption_'+editor_sequence).style.display = "none";

        editor_mode[editor_sequence] = 'html';

    // 위지윅 모드 사용시
    } else {
        var html = textarea_obj.value;
        contentDocument.body.innerHTML = html;
        iframe_obj.parentNode.style.display = "block";
        textarea_obj.style.display = "none";
        xGetElementById('xeEditorOption_'+editor_sequence).style.display = "block";
        editor_mode[editor_sequence] = null;
    }

}

/**
 * iframe 세로 크기 조절 드래그 관련
 **/
var editorDragObj = {isDrag:false, y:0, obj:null, id:'', det:0, source_height:0}
xAddEventListener(document, 'mousedown', editorDragStart);
xAddEventListener(document, 'mouseup', editorDragStop);
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

// Editor Info Close
function closeEditorInfo(editor_sequence) {
    xGetElementById('editorInfo_'+editor_sequence).style.display='none';	
    var expire = new Date();
    expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
    xSetCookie('EditorInfo', '1', expire);
}


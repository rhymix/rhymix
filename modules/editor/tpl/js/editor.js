/**
 * @author zero (zero@nzeo.com)
 * @version 0.1
 * @brief 에디터 관련 스크립트
 **/

// iframe의 id prefix
var iframe_id = 'editor_iframe_';

// upload_target_srl에 대한 form문을 객체로 보관함 
var editor_form_list = new Array();

// upload_target_srl값에 해당하는 iframe의 object를 return
function editorGetIFrame(upload_target_srl) {
    var obj_id = iframe_id+upload_target_srl;
    return xGetElementById(obj_id);
}

// editor 초기화를 onload이벤트 후에 시작시킴
function editorInit(upload_target_srl, resizable, height) {
    xAddEventListener(window, 'load', function() { editorStart(upload_target_srl, resizable, height); });
}

// editor 시작 (upload_target_srl로 iframe객체를 얻어서 쓰기 모드로 전환)
function editorStart(upload_target_srl, resizable, height) {
    if(typeof(height)=="undefined"||!height) height = 350;
    if(typeof(resizable)=="undefined"||!resizable) resizable = false;
    else resizable = true;

    // iframe_area를 찾음
    var iframe_area = xGetElementById("editor_iframe_area_"+upload_target_srl);
    xHeight(iframe_area, height+10);
    xInnerHtml(iframe_area, '<iframe id="editor_iframe_'+upload_target_srl+'" frameBorder="0" style="width:100%;height:'+height+'px;"></iframe>');

    // iframe obj를 찾음
    var iframe_obj = editorGetIFrame(upload_target_srl);
    if(!iframe_obj) return;

    // 현 에디터를 감싸고 있는 form문을 찾아서 content object를 찾아서 내용 sync
    var fo_obj = iframe_obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    // saved document에 대한 체크
    if(typeof(fo_obj._saved_doc_title)!="undefined" ) {
        var saved_title = fo_obj._saved_doc_title.value;
        var saved_content = fo_obj._saved_doc_content.value;
        if(saved_title || saved_content) {
            if(confirm(fo_obj._saved_doc_message.value)) {
                fo_obj.title.value = saved_title;
                fo_obj.content.value = saved_content;
            } else {
                editorRemoveSavedDoc();
            }
        }
    }

    // 구해진 form 객체를 저장
    editor_form_list[upload_target_srl] = fo_obj;

    // 대상 form의 content object에서 데이터를 구함
    var content = fo_obj.content.value;

    // iframe내의 document object 
    var contentDocument = iframe_obj.contentWindow.document;

    // editing가능하도록 설정 시작

    // 기본 내용 작성
    var contentHtml = ''+
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'+
        '<html lang="ko" xmlns="http://www.w3.org/1999/xhtml><head><meta http-equiv="content-type" content="text/html; charset=utf-8"/>'+
        '<link rel="stylesheet" href="'+request_uri+'/common/css/default.css" type="text/css" />'+
        '<link rel="stylesheet" href="'+request_uri+editor_path+'/css/editor.css" type="text/css" />'+
        '<style>'+
        'html {position:static}'+
        'body {margin:0px}'+
        '</style>'+
        '</head><body upload_target_srl="'+upload_target_srl+'">'+
        content+
        "\n"+
        '</body></html>'+
        '';
    contentDocument.designMode = 'on';
    contentDocument.open("text/html","replace");
    contentDocument.write(contentHtml);
    contentDocument.close();

    // 작성시 필요한 이벤트 체크
    if(xIE4Up) xAddEventListener(contentDocument, 'keydown',editorKeyPress);
    else xAddEventListener(contentDocument, 'keypress',editorKeyPress);
    xAddEventListener(contentDocument,'mousedown',editorHideObject);

    // 위젯 감시를 위한 더블클릭 이벤트 걸기 (오페라에 대한 처리는 차후에.. 뭔가 이상함)
    xAddEventListener(contentDocument,'dblclick',editorSearchComponent);
    xAddEventListener(document,'dblclick',editorSearchComponent);

    xAddEventListener(document,'mouseup',editorEventCheck);
    xAddEventListener(document,'mousedown',editorHideObject);

    if(xIE4Up && xGetElementById('for_ie_help_'+upload_target_srl)) {
        xGetElementById('for_ie_help_'+upload_target_srl).style.display = "block";
    }

    // 에디터의 내용을 지속적으로 fo_obj.content.value에 입력
    editorSyncContent(fo_obj.content, upload_target_srl);

    if(typeof(fo_obj._saved_doc_title)!="undefined" ) editorEnableAutoSave(fo_obj, upload_target_srl);

    // 크기 변경 불가일 경우 드래그바 숨김
    if(resizable == false) xGetElementById("editor_drag_bar_"+upload_target_srl).style.display = "none";

    if(typeof(fixAdminLayoutFooter)=='function') fixAdminLayoutFooter(height);
}

// 여러개의 편집기를 예상하여 전역 배열 변수에 form, iframe의 정보를 넣음
var _editorSyncList = new Array(); 
function editorSyncContent(obj, upload_target_srl) {
    _editorSyncList[_editorSyncList.length] = {field:obj, upload_target_srl:upload_target_srl}
}

// 편집기와 폼의 정보를 주기적으로 동기화 시킴
function _editorSync() {
    for(var i=0;i<_editorSyncList.length;i++) {
        var field = _editorSyncList[i].field;
        var upload_target_srl = _editorSyncList[i].upload_target_srl;
        var content = editorGetContent(upload_target_srl);
        if(typeof(content)=='undefined'||!content) continue;
        field.value = content;
    }
    setTimeout(_editorSync, 1000);
}
xAddEventListener(window, 'load', _editorSync);

// 자동 저장 기능
var _autoSaveObj = {fo_obj:null, upload_target_srl:0, title:'', content:''}
function editorEnableAutoSave(fo_obj, upload_target_srl) {
    var title = fo_obj.title.value;
    var content = fo_obj.content.value;
    _autoSaveObj = {"fo_obj":fo_obj, "upload_target_srl":upload_target_srl, "title":title, "content":content};
    setTimeout(_editorAutoSave, 5000);
}

function _editorAutoSave() {
    var fo_obj = _autoSaveObj.fo_obj;
    var upload_target_srl = _autoSaveObj.upload_target_srl;

    if(fo_obj && upload_target_srl) {
        var title = fo_obj.title.value;
        var content = editorGetContent(upload_target_srl);
        if(title.trim() != _autoSaveObj.title.trim() || content.trim() != _autoSaveObj.content.trim()) {
            var params = new Array();
            params["document_srl"] = upload_target_srl;
            params["title"] = title;
            params["content"] = content;

            _autoSaveObj.title = title;
            _autoSaveObj.content = content;

            /*
            xTop("editor_autosaved_message", xScrollTop()+10);
            xGetElementById("editor_autosaved_message").style.display = "block";
            setTimeout(function() {xGetElementById("editor_autosaved_message").style.display = "none";}, 1000);
            */

            var obj = xGetElementById("editor_autosaved_message");
            var oDate = new Date();
            html = oDate.getHours()+':'+oDate.getMinutes()+' '+auto_saved_msg;
            xInnerHtml(obj, html);
            obj.style.display = "block";

            show_waiting_message = false;
            exec_xml("editor","procEditorSaveDoc", params, _editorAutoSaved);
            show_waiting_message = true;
            return;
        }
    }

    setTimeout(_editorAutoSave, 15000);
}

function _editorAutoSaved(ret_obj) {
    setTimeout(_editorAutoSave, 15000);
    return null;
}

function editorRemoveSavedDoc() {
    exec_xml("editor","procEditorRemoveSavedDoc");
}

// 에디터의 전체 내용 return
function editorGetContent(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    if(!iframe_obj) return null;
    var html = null;
    html = xInnerHtml(iframe_obj.contentWindow.document.body);
    if(html) html = html.replace(/^<br>$/i,'');
    return html;
}

// 에디터 내의 선택된 부분의 html 코드를 return
function editorGetSelectedHtml(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
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
function editorGetSelectedNode(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
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

// 에디터에 포커스를 줌
function editorFocus(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    iframe_obj.contentWindow.focus();
}

// 입력 키에 대한 이벤트 체크
function editorKeyPress(evt) {
    var e = new xEvent(evt);

    // IE에서 enter키를 눌렀을때 P 태그 대신 BR 태그 입력
    if (xIE4Up && !e.ctrlKey && !e.shiftKey && e.keyCode == 13) {
        if(e.target.parentElement.document.designMode!="On") return;
        var obj = e.target.parentElement.document.selection.createRange();
        obj.pasteHTML('<br />');
        obj.select();
        evt.cancelBubble = true;
        evt.returnValue = false;
        return;
    }

    // ctrl-S, alt-S 클릭시 submit하기
    if( e.keyCode == 115 && (e.altKey || e.ctrlKey) ) {
        var obj = e.target;
        var body_obj = obj.firstChild.nextSibling;
        if(!body_obj) return;

        var upload_target_srl = body_obj.getAttribute("upload_target_srl");
        if(!upload_target_srl) return;

        var iframe_obj = editorGetIFrame(upload_target_srl);
        if(!iframe_obj) return;

        var fo_obj = iframe_obj.parentNode;
        while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
        if(fo_obj.onsubmit) fo_obj.onsubmit();

        evt.cancelBubble = true;
        evt.returnValue = false;
        xPreventDefault(evt);
        xStopPropagation(evt);
        return;
    }

    // ctrl-b, i, u, s 키에 대한 처리 (파이어폭스에서도 에디터 상태에서 단축키 쓰도록)
    if (e.ctrlKey) {
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

// 에디터 상단의 버튼 클릭시 action 처리 (마우스다운 이벤트 발생시마다 요청이 됨)
var editorPrevObj = null;
var editorPrevSrl = null;
function editorEventCheck(evt) {
    editorPrevNode = null;

    // 이벤트가 발생한 object의 ID를 구함 
    var e = new xEvent(evt);
    var target_id = e.target.id;
    if(!target_id) return;

    // upload_target_srl와 component name을 구함 (id가 포맷과 다르면 return)
    var info = target_id.split('_');
    if(info[0]!="component") return;
    var upload_target_srl = info[1];
    var component_name = target_id.replace(/^component_([0-9]+)_/,'');
    if(!upload_target_srl || !component_name) return;

    switch(component_name) {

        // 기본 기능에 대한 동작 (바로 실행) 
        case 'Bold' :
        case 'Italic' :
        case 'Underline' :
        case 'StrikeThrough' :
        case 'undo' :
        case 'redo' :
        case 'justifyleft' :
        case 'justifycenter' :
        case 'justifyright' :
        case 'indent' :
        case 'outdent' :
        case 'insertorderedlist' :
        case 'insertunorderedlist' :
                editorDo(component_name, '', upload_target_srl);
            break;

        // 추가 컴포넌트의 경우 서버에 요청을 시도
        default :
                openComponent(component_name, upload_target_srl);
            break;
    }

    return;
}

// 컴포넌트 팝업 열기
function openComponent(component_name, upload_target_srl, manual_url) {
    editorPrevSrl = upload_target_srl;
    var popup_url = request_uri+"?module=editor&act=dispEditorPopup&upload_target_srl="+upload_target_srl+"&component="+component_name;
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
        // upload_target_srl을 찾음
        var tobj = obj;
        while(tobj && tobj.nodeName != "BODY") {
            tobj = xParent(tobj);
        }
        if(!tobj || tobj.nodeName != "BODY" || !tobj.getAttribute("upload_target_srl")) {
            editorPrevNode = null;
            return;
        }
        var upload_target_srl = tobj.getAttribute("upload_target_srl");
        var widget = obj.getAttribute("widget");
        editorPrevNode = obj;
        popopen(request_uri+"?module=widget&act=dispWidgetGenerateCodeInPage&selected_widget="+widget+"&module_srl="+upload_target_srl,'GenerateCodeInPage');
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

    // upload_target_srl을 찾음
    var tobj = obj;
    while(tobj && tobj.nodeName != "BODY") {
        tobj = xParent(tobj);
    }
    if(!tobj || tobj.nodeName != "BODY" || !tobj.getAttribute("upload_target_srl")) {
        editorPrevNode = null;
        return;
    }
    var upload_target_srl = tobj.getAttribute("upload_target_srl");

    // 해당 컴포넌트를 찾아서 실행
    openComponent(editor_component, upload_target_srl);
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
 * 편집기능 실행
 */

// 편집 기능 실행
function editorDo(command, value, target) {
    var doc = null;
    // target이 object인지 upload_target_srl인지에 따라 document를 구함
    if(typeof(target)=="object") {
        if(xIE4Up) doc = target.parentElement.document;
        else doc = target.parentNode;
    } else {
        var iframe_obj = editorGetIFrame(target);
        doc = iframe_obj.contentWindow.document;
    }

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
function closeEditorInfo() {
    xGetElementById('editorInfo').style.display='none';	
    var expire = new Date();
    expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
    xSetCookie('EditorInfo', '1', expire);
}


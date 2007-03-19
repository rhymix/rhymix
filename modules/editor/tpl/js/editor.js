/**
 * @author zero (zero@nzeo.com)
 * @version 0.1
 * @brief 에디터 관련 스크립트
 **/

// iframe의 id prefix
var iframe_id = 'editor_iframe_';

// upload_target_srl에 대한 form문을 객체로 보관함 
var editor_form_list = new Array();

// srl값에 해당하는 iframe의 object를 return
function editorGetIFrame(upload_target_srl) {
    var obj_id = iframe_id+upload_target_srl;
    return xGetElementById(obj_id);
}

// editor 초기화를 onload이벤트 후에 시작시킴
function editorInit(upload_target_srl) {
    var start_func = function() { editorStart(upload_target_srl); }
    xAddEventListener(window, 'load', start_func);
}

// editor 시작 (upload_target_srl로 iframe객체를 얻어서 쓰기 모드로 전환)
function editorStart(upload_target_srl) {
    // iframe obj를 찾음
    var iframe_obj = editorGetIFrame(upload_target_srl);
    if(!iframe_obj) return;

    // 현 에디터를 감싸고 있는 form문을 찾아서 content object를 찾아서 내용 sync
    var fo_obj = iframe_obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }

    // 구해진 form 객체를 저장
    editor_form_list[upload_target_srl] = fo_obj;

    // 대상 form의 content object에서 데이터를 구함
    var content = fo_obj.content.value;

    // 기본 폰트를 가져옴
    var default_font = xGetElementById('editor_font_'+upload_target_srl).options[1].value;

    // iframe내의 document object 
    var contentDocument = iframe_obj.contentWindow.document;

    // editing가능하도록 설정 시작

    // 기본 내용 작성
    var contentHtml = ''+
        //'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'+
        '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"/>'+
        '<link rel="stylesheet" href="./common/css/default.css" type="text/css" />'+
        '<link rel="stylesheet" href="'+editor_path+'/css/editor.css" type="text/css" />'+
        '</head><body style="background-color:#FFFFFF;font-family:'+default_font+';font-size:9pt;">'+
        content+
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

    //xAddEventListener(document,'keypress',editorKeyPress);
    xAddEventListener(document,'mouseup',editorEventCheck);
    xAddEventListener(document,'mousedown',editorHideObject);

    // 문단작성기능 on/off
    if(xIE4Up) {
        xDisplay('editor_paragraph_'+upload_target_srl, 'none');
        xDisplay('editor_use_paragraph_box_'+upload_target_srl, 'inline');
    } else {
        xDisplay('editor_paragraph_'+upload_target_srl, 'block');
        xDisplay('editor_use_paragraph_box_'+upload_target_srl, 'none');
    }

    // 에디터의 내용을 지속적으로 fo_obj.content.value에 입력
    editorSyncContent(fo_obj.content, upload_target_srl);
    editorFocus(upload_target_srl);
}

var _editorSyncList = new Array(); 
function editorSyncContent(obj, upload_target_srl) {
    _editorSyncList[_editorSyncList.length] = {field:obj, upload_target_srl:upload_target_srl}
}

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

// 문단기능 toggle
function editorUseParagraph(obj, upload_target_srl) { 
    toggleDisplay('editor_paragraph_'+upload_target_srl);
}

// 에디터의 내용 return
function editorGetContent(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    if(!iframe_obj) return;
    var html = '';
    html = xInnerHtml(iframe_obj.contentWindow.document.body);
    if(!html) return;
    return html.trim();
}

// 에디터 내의 선택된 부분의 html 코드를 return
function editorGetSelectedHtml(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    if(xIE4Up) {
        var range = iframe_obj.contentWindow.document.selection.createRange();
        var html = range.htmlText;
        range.select();
        return html;
    } else {
        var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
        var dummy = xCreateElement('div');
        dummy.appendChild(range.cloneContents());
        var html = xInnerHtml(dummy);
        return html;
    }
}

// 에디터 내의 선택된 부분의 html코드를 변경
function editorReplaceHTML(iframe_obj, html) {

    if(xIE4Up) {
        iframe_obj.contentWindow.document.selection.createRange().pasteHTML(html);
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

// 입력 키에 대한 이벤트 체크
function editorKeyPress(evt) {
    var e = new xEvent(evt);
    if (e.keyCode == 13) {
        if(xIE4Up && e.shiftKey == false && !xGetElementById("use_paragraph").checked ) {
            if(e.target.parentElement.document.designMode!="On") return;
            var obj = e.target.parentElement.document.selection.createRange();
            obj.pasteHTML('<br />');
            obj.select();
            evt.cancelBubble = true;
            evt.returnValue = false;
            return;
        }
    }

    if (e.ctrlKey) {
        switch(e.keyCode) {
            case 98 : // b
                    editorDo('Bold',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            case 105 : // i
                    editorDo('Italic',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            case 117 : // u
                    editorDo('Underline',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
            case 83 : // s
            case 115 : // s
                    editorDo('StrikeThrough',null,e.target);
                    xPreventDefault(evt);
                    xStopPropagation(evt);
                break;
        }
    }
}

// 에디터 상단의 버튼 클릭시 action 처리 (마우스다운 이벤트 발생시마다 요청이 됨)
var editorPrevObj = null;
var editorPrevSrl = null;
function editorEventCheck(evt) {
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
                editorPrevSrl = upload_target_srl;

                var params = new Array();
                params['component'] = component_name;
                params['upload_target_srl'] = upload_target_srl;

                var response_tags = new Array('error', 'message', 'component', 'upload_target_srl', 'tpl', 'open_window', 'popup_url');
                exec_xml('editor', 'dispComponent', params, editorProcComponent, response_tags, editor_form_list[upload_target_srl]);
            break;
    }

    return;
}

// 컴포넌트 요청 후 결과를 처리할 함수
function editorProcComponent(ret_obj, response_tags) {
    var upload_target_srl = ret_obj['upload_target_srl'];
    var tpl = ret_obj['tpl'];
    var open_window = ret_obj['open_window'];
    var popup_url = ret_obj['popup_url'];

    // 컴포넌트의 동작이 새창 열기 일 경우
    if(open_window == 'Y') {
        var win_left = screen.availWidth / 3;
        var win_top = screen.availHeight / 3;
        winopen(popup_url, 'editorComponent', 'left='+win_left+',top='+win_top+',width=10,height=10,toolbars=no,scrollbars=no');

    // 새창 열기가 아니면 정해진 영역에 innerHTML로 tpl 입력
    } else {
        var zone = xGetElementById("editor_component_area_"+upload_target_srl);
        xInnerHtml(zone, tpl);
    }
}

// focus
function editorFocus(upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    iframe_obj.contentWindow.focus();
}

// 편집 기능 실행
function editorDo(name, value, target) {
    if(typeof(target)=='object') _editorDoObject(name, value, target);
    else _editorDoSrl(name, value, target);
}

function _editorDoSrl(name, value, upload_target_srl) {
    var iframe_obj = editorGetIFrame(upload_target_srl);
    editorFocus(upload_target_srl);
    if(xIE4Up) iframe_obj.contentWindow.document.execCommand(name, false, value);
    else iframe_obj.contentWindow.document.execCommand(name, false, value);
    editorFocus(upload_target_srl);
}

function _editorDoObject(name, value, obj) {
    if(xIE4Up) {
        obj.parentElement.document.execCommand(name, false, value);
    } else {
        obj.parentNode.execCommand(name, false, value);
    }
}

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

function editorDoInsertUrl(link, upload_target_srl) {
    editorFocus(upload_target_srl);
    var iframe_obj = editorGetIFrame(srl);
    editorReplaceHTML(iframe_obj, link);
}

function editorInsertImage(url, src_align) {
    if(!url) return;
    //if(!/^(http|ftp)/i.test(url)) url = 'http://'+url;

    editorFocus(editorPrevSrl);

    var html = "<img src=\""+url+"\" border=\"0\" alt=\"i\" ";
    if(typeof(src_align)!='undefined'&&src_align) html += " align=\""+src_align+"\"";
    html += " />";
    var obj = editorGetIFrame(editorPrevSrl);
    editorReplaceHTML(obj, html);
}

function editorGetMultimediaHtml(url, width, height, source_filename) {
    if(typeof(width)=='undefined'||!width) width = 540;
    if(typeof(height)=='undefined'||!height) height= 420;

    var type = "application/x-mplayer2";
    var pluginspace = "http://www.microsoft.com/Windows/MediaPlayer/";
    var kind = 'movie';

    if(/\.swf$/i.test(url)) kind = 'flash';
    if(typeof(source_filename)!='undefined' && /\.swf$/i.test(source_filename)) kind = 'flash';

    if(kind=='flash') {
        type = "application/x-shockwave-flash";
        pluginspace = "http://www.macromedia.com/go/getflashplayer";
    }
    var html = "<embed src=\""+url+"\" width=\""+width+"\" height=\""+height+"\" autostart=\"0\"    type=\""+type+"\" pluginspage=\""+pluginspace+"\"></embed><BR />";
    return html;
}

function editorInsertMultimedia(url, width, height) {
    if(url) {
        var html = editorGetMultimediaHtml(url, width, height);
        editorFocus(editorPrevSrl);
        var obj = editorGetIFrame(editorPrevSrl)
        editorReplaceHTML(obj, html);
        editorFocus(editorPrevSrl);
    }
}

function editorInsertQuotation(html) {
    if(!html) return;

    if(!xIE4Up) html += "<br />";
    editorFocus(editorPrevSrl);
    var obj = editorGetIFrame(editorPrevSrl)
    editorReplaceHTML(obj, html);
    editorFocus(editorPrevSrl);
}

function editorHighlight(ret_obj, response_tags, obj) {
    var html = ret_obj['html'];
    html = "<div class='php_code'>"+html+"</div>";
    if(!xIE4Up) html += "<br />";
    editorReplaceHTML(obj, html);
}

/**
 * iframe 드래그 관련
 **/
var editorDragObj = {isDrag:false, y:0, obj:null, id:'', det:0}
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

    xAddEventListener(document, 'mousemove', editorDragMove, false);
    xAddEventListener(editorDragObj.obj, 'mouseout', editorDragMove, false);

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    if(iframe_obj) xAddEventListener(iframe_obj.contentWindow.document, 'mouseover', editorDragStop, false);
}

function editorDragMove(evt) {
    if(!editorDragObj.isDrag) return;

    var e = new xEvent(evt);
    var h = e.pageY - editorDragObj.y;
    editorDragObj.y = e.pageY;

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    xHeight(iframe_obj, xHeight(iframe_obj)+h);
}

function editorDragStop(evt) {
    if(!editorDragObj.isDrag) return;

    xRemoveEventListener(document, 'mousemove', editorDragMove, false);
    xRemoveEventListener(editorDragObj.obj, 'mouseout', editorDragMove, false);

    var iframe_obj = editorGetIFrame(editorDragObj.id);
    if(iframe_obj) xRemoveEventListener(iframe_obj.contentWindow.document, 'mouseover', editorDragStop, false);

    editorDragObj.isDrag = false;
    editorDragObj.y = 0;
    editorDragObj.obj = null;
    editorDragObj.id = '';
}

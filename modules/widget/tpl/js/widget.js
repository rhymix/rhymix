/**
 * @file   modules/widget/js/widget.js
 * @author zero (zero@nzeo.com)
 * @brief  위젯 관리용 자바스크립트
 **/

/* document.write(ln)의 경우 ajax로 처리시 가로채기 위한 함수 */
document.write = document.writeln = function(str){
	if ( str.match(/^<\//) ) return;
	if ( !window.opera ) str = str.replace(/&(?![#a-z0-9]+;)/g, "&");
    str = str.replace(/(<[a-z]+)/g, "$1 xmlns='http://www.w3.org/1999/xhtml'");
    var div = null;
    if(document.createElementNS) div = document.createElementNS("http://www.w3.org/1999/xhtml","div");
    else div = xCreateElement('div');
    xInnerHtml(div, str);
	var pos;
    pos = document.getElementsByTagName("*");
    pos = pos[pos.length - 1];
	var nodes = div.childNodes;
	while ( nodes.length ) {
		pos.parentNode.appendChild( nodes[0] );
    }
};

/* DOM 속성을 구하기 위한 몇가지 함수들.. */
// style의 값을 구하는게 IE랑 그외가 다름.
function getStyle(obj) {
    var style = obj.getAttribute("style");
    if(typeof(style)=="object") style = style["cssText"];
    return style;
}

// float: 값을 구하는게 IE랑 그외가 다름
function getFloat(obj) {
    var cssFloat = xIE4Up?obj.style.styleFloat:obj.style.cssFloat;
    if(!cssFloat) cssFloat = 'left';
    return cssFloat;
}
function setFloat(obj, fl) {
    if(xIE4Up) obj.style.styleFloat = fl;
    else obj.style.cssFloat = fl;
}

// padding값을 구하는 함수 (없을 경우 0으로 세팅), zbxe의 위젯에서만 사용
function getPadding(obj, direct) {
    var padding = obj.getAttribute("widget_padding_"+direct);
    if(!padding || padding == null) padding = 0;
    return padding;
}


/* 위젯 핸들링 시작 */
var zonePageObj = null;
var zoneModuleSrl = 0;
function doStartPageModify(zoneID, module_srl) {
    zonePageObj = xGetElementById(zoneID);
    zoneModuleSrl = module_srl;

    // 위젯 크기/여백 조절 레이어를 가장 밖으로 뺌
    var obj = xGetElementById("tmpPageSizeLayer");
    var dummy = xCreateElement("div");
    xInnerHtml(dummy, xInnerHtml(obj));
    dummy.id="pageSizeLayer";
    dummy.style.visibility = "hidden";
    dummy.style.position = "absolute";
    dummy.style.left = 0;
    dummy.style.top = 0;
    document.body.appendChild(dummy);
    obj.parentNode.removeChild(obj);


    // 모든 위젯들의 크기를 정해진 크기로 맞춤
    doFitBorderSize();

    // 드래그와 리사이즈와 관련된 이벤트 리스너 생성
    xAddEventListener(document,"click",doCheckWidget);
    xAddEventListener(document,"mousedown",doCheckWidgetDrag);
}


// 내용 모두 삭제
function removeAllWidget() {
    if(!confirm(confirm_delete_msg)) return;
    var response_tags = new Array('error','message');
    var params = new Array();
    params['module_srl'] = xGetElementById('pageFo').module_srl.value;
    exec_xml('widget',"procWidgetRemoveContents",params,function() { xInnerHtml(zonePageObj,'') });
}

/** 
 * 특정 영역에 편집된 위젯들을 약속된 태그로 변환하여 return
 **/
function getWidgetContent(obj) {
    var html = "";
    if(typeof(obj)=='undefined' || !obj) obj = zonePageObj;
    var childObj = obj.firstChild;

    while(childObj) {
        if(childObj.nodeName == "DIV" && childObj.getAttribute("widget"))  {
            var widget = childObj.getAttribute("widget");
            if(widget) {
                switch(widget) {
                    case 'widgetBox' :
                            html += getWidgetBoxCode(childObj, widget);
                        break;
                    case 'widgetContent' :
                            html += getContentWidgetCode(childObj, widget);
                        break;
                    default :
                            html += getWidgetCode(childObj, widget);
                        break;
                }
            }
        }
        childObj = childObj.nextSibling;
    }

    return html;
}

// 컨텐츠 위젯 코드 구함
function getContentWidgetCode(childObj, widget) {
    var cobj = childObj.firstChild;
    while(cobj) {
        if(cobj.nodeName == "DIV" && cobj.className == "widgetContent") {
            var body = xInnerHtml(cobj);
            var document_srl = childObj.getAttribute('document_srl');
            return '<img src="./common/tpl/images/widget_bg.jpg" class="zbxe_widget_output" widget="widgetContent" style="'+getStyle(childObj)+'" body="'+body+'" document_srl="'+document_srl+'" widget_padding_left="'+getPadding(childObj,'left')+'" widget_padding_right="'+getPadding(childObj, 'right')+'" widget_padding_top="'+getPadding(childObj, 'top')+'" widget_padding_bottom="'+getPadding(childObj,'bottom')+'" />';
        }
        cobj = cobj.nextSibling;
    }
}

// 위젯 박스 코드 구함
function getWidgetBoxCode(childObj, widget) {
    var cobj = childObj.firstChild;
    while(cobj) {
        if(cobj.className == "widgetBorder" || cobj.className == "widgetBoxBorder") {
            var c2obj = cobj.firstChild;
            while(c2obj) {
                if(c2obj.className == "nullWidget") {
                    var body = getWidgetContent(c2obj);
                    return '<div widget="widgetBox" style="'+getStyle(childObj)+'" widget_padding_left="'+getPadding(childObj,'left')+'" widget_padding_right="'+getPadding(childObj,'right')+'" widget_padding_top="'+getPadding(childObj, 'top')+'" widget_padding_bottom="'+getPadding(childObj, 'bottom')+'"><div><div>'+body+'<div class="clear"></div></div></div></div>';
                }
                c2obj = c2obj.nextSibling;
            }
        }
        cobj = cobj.nextSibling;
    }
}

// 일반 위젯 컨텐츠 코드 구함
function getWidgetCode(childObj, widget) {
    var attrs = "";
    var code = "";
    for(var i=0;i<childObj.attributes.length;i++) {
        if(!childObj.attributes[i].nodeName || !childObj.attributes[i].nodeValue) continue;
        var name = childObj.attributes[i].nodeName.toLowerCase();
        if(name == "contenteditable" || name == "id" || name=="style" || name=="src" || name=="widget" || name == "body" || name == "class" || name == "widget_width" || name == "widget_width_type" || name == "xdpx" || name == "xdpy" || name == "height") continue;
        var value = childObj.attributes[i].nodeValue;
        if(!value) continue;

        attrs += name+'="'+escape(value)+'" ';
    }
    var style = childObj.getAttribute("style");
    return '<img class="zbxe_widget_output" style="'+getStyle(childObj)+'" widget="'+widget+'" '+attrs+' />';
}

/**
 * 직접 내용을 입력하는 위젯을 추가
 **/
// 팝업 띄움
function doAddContent(mid) {
    var url = request_uri.setQuery('module','widget').setQuery('act','dispWidgetAdminAddContent').setQuery('module_srl',zoneModuleSrl).setQuery('mid',mid);
    popopen(url, "addContent");
}

// 직접 내용을 입력하기 위한 에디터 활성화 작업 및 form 데이터 입력
function doSyncPageContent() {
    if(opener && opener.selectedWidget) {
        var fo_obj = xGetElementById("content_fo");
        var sel_obj = opener.selectedWidget;
        fo_obj.style.value = getStyle(opener.selectedWidget);
        fo_obj.widget_padding_left.value = getPadding(sel_obj, 'left');
        fo_obj.widget_padding_right.value = getPadding(sel_obj,'right');
        fo_obj.widget_padding_bottom.value = getPadding(sel_obj,'bottom');
        fo_obj.widget_padding_top.value = getPadding(sel_obj,'top');

        var obj = sel_obj.firstChild;
        while(obj && obj.className != "widgetContent") obj = obj.nextSibling;
        if(obj && obj.className == "widgetContent") {
            if(!fo_obj.content.value) {
                var content = Base64.decode(xInnerHtml(obj));
                xGetElementById("content_fo").content.value = content;
            }
        }
    }

    editorStart(1, "module_srl", "content", false, 400 );
    //editor_upload_start(1);

    setFixedPopupSize();
}

// 부모창에 위젯을 추가
function addContentWidget(fo_obj) {
    var editor_sequence = fo_obj.getAttribute('editor_sequence');
    var mid = fo_obj.mid.value;
    var module_srl = fo_obj.module_srl.value;
    var document_srl = fo_obj.document_srl.value;
    var content = editorGetContent(editor_sequence);

    var response_tags = new Array('error','message','document_srl');
    var params = new Array();
    params['editor_sequence'] = editor_sequence;
    params['content'] = content;
    params['module_srl'] = module_srl;
    params['document_srl'] = document_srl;
    exec_xml('widget',"procWidgetInsertDocument",params,completeAddContent,response_tags,params,fo_obj);

    return false;
}

function completeAddContent(ret_obj, response_tags, params, fo_obj) {
    var document_srl = ret_obj['document_srl'];
    var tpl = ''+
        '<div class="widgetOutput" style="'+fo_obj.style.value+'" widget_padding_left="'+fo_obj.widget_padding_left.value+'" widget_padding_right="'+fo_obj.widget_padding_right.value+'" widget_padding_top="'+fo_obj.widget_padding_top.value+'" widget_padding_bottom="'+fo_obj.widget_padding_bottom.value+'" document_srl="'+document_srl+'" widget="widgetContent">'+
        '<div class="widgetResize"></div>'+
        '<div class="widgetResizeLeft"></div>'+
        '<div class="widgetBorder">'+
        '<div style="padding:'+fo_obj.widget_padding_top.value+'px '+fo_obj.widget_padding_right.value+'px'+fo_obj.widget_padding_bottom.value+'px'+fo_obj.widget_padding_left.value+'px"></div><div class="clear"></div>'+
        '</div>'+
        '<div class="widgetContent" style="display:none;width:1px;height:1px;overflow:hidden;"></div>'+
        '</div>';

    opener.doAddWidgetCode(tpl);
    window.close();
}

/* 박스 위젯 추가 */
function doAddWidgetBox() {
    var tpl = ''+
    '<div class="widgetOutput" style="float:left;width:100%;height:12px;" widget="widgetBox" >'+
        '<div class="widgetBoxResize"></div>'+
        '<div class="widgetBoxResizeLeft"></div>'+
        '<div class="widgetBoxBorder">'+
            '<div class="nullWidget" style="width:100%;height:100px;"></div>'+
            '<div class="clear"></div>'+
        '</div>'+
    '</div>';
    xInnerHtml(zonePageObj, xInnerHtml(zonePageObj)+tpl);
    doFitBorderSize();
}


/* 일반 위젯을 추가하기 위해 위젯 팝업창을 띄움 */
function doAddWidget(fo) {
    var sel = fo.widget_list;
    var idx = sel.selectedIndex;
    var val = sel.options[idx].value;
    var module_srl = fo.module_srl.value;

    var url = current_url.setQuery('module','widget').setQuery('act','dispWidgetGenerateCodeInPage').setQuery('selected_widget', val).setQuery('module_srl', module_srl);
    popopen(url,'GenerateWidgetCode');
}

// widgetBorder에 height를 widgetOutput와 맞춰줌
function doFitBorderSize() {
    var obj_list = xGetElementsByClassName('widgetBorder', zonePageObj);
    for(var i=0;i<obj_list.length;i++) {
        var obj = obj_list[i];
        xHeight(obj, xHeight(obj.parentNode));
        obj.parentNode.style.clear = '';
    }
    var obj_list = xGetElementsByClassName('widgetBoxBorder', zonePageObj);
    for(var i=0;i<obj_list.length;i++) {
        var obj = obj_list[i];
        xHeight(obj, xHeight(obj.parentNode));
        obj.parentNode.style.clear = '';
    }
}

var selectedWidget = null;

// 위젯 추가
function doAddWidgetCode(widget_code) {
    var dummy = xCreateElement('div');
    xInnerHtml(dummy, widget_code);

    var nodes = dummy.childNodes;

    var zoneObj = xGetElementById('zonePageContent');

    //zoneObj.style.visibility = 'hidden';
    zoneObj.style.opacity = 0.2;
    zoneObj.style.filter = "alpha(opacity=20)";


    if(selectedWidget  && selectedWidget.getAttribute("widget")) {
        while ( nodes.length ) {
            if(nodes[0].className == 'widgetClass') zoneObj.parentNode.insertBefore(nodes[0], zoneObj);
            else selectedWidget.parentNode.insertBefore(nodes[0], selectedWidget);
        }
        selectedWidget.parentNode.removeChild(selectedWidget);
    } else {
        while ( nodes.length ) {
            if(nodes[0].className == 'widgetClass') zoneObj.parentNode.insertBefore(nodes[0], zoneObj);
            else zoneObj.appendChild(nodes[0]);
        }
    }

    // 위젯 추가후 페이지 리로딩
    var tpl = getWidgetContent();

    var fo_obj = xGetElementById('pageFo');
    fo_obj.content.value = tpl;

    fo_obj.submit();
}

// 클릭 이벤트시 위젯의 수정/제거/이벤트 무효화 처리
function doCheckWidget(e) {
    var evt = new xEvent(e); if(!evt.target) return;
    var obj = evt.target; 

    selectedWidget = null;

    var pObj = obj.parentNode;
    while(pObj) {
        if(pObj.id == "pageSizeLayer") return;
        pObj = pObj.parentNode;
    }

    doHideWidgetSizeSetup();
    // 위젯 설정
    if(obj.className == 'widgetSetup') {
        var p_obj = obj.parentNode.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(!widget) return;
        selectedWidget = p_obj;
        if(widget == 'widgetContent') popopen(request_uri+"?module=widget&act=dispWidgetAdminAddContent&module_srl="+zoneModuleSrl+"&document_srl="+p_obj.getAttribute("document_srl"), "addContent");
        else popopen(request_uri+"?module=widget&act=dispWidgetGenerateCodeInPage&selected_widget="+widget,'GenerateCodeInPage');
        return;

    // 위젯 복사
    } else if(obj.className == 'widgetCopy' && obj.parentNode.parentNode.className == 'widgetOutput') {
        p_obj = obj.parentNode.parentNode;
        restoreWidgetButtons();

        if(p_obj.getAttribute('widget')=='widgetContent' && p_obj.getAttribute('document_srl') ) {
            var response_tags = new Array('error','message','document_srl');
            var params = new Array();
            params['document_srl'] =p_obj.getAttribute('document_srl');
            exec_xml('widget','procWidgetCopyDocument', params, completeCopyWidgetContent, response_tags, params, p_obj);
            return;
        } else {
            var dummy = xCreateElement("DIV");
            xInnerHtml(dummy,xInnerHtml(p_obj));

            dummy.widget_sequence = '';
            dummy.className = "widgetOutput";
            for(var i=0;i<p_obj.attributes.length;i++) {
                if(!p_obj.attributes[i].nodeName || !p_obj.attributes[i].nodeValue) continue;
                var name = p_obj.attributes[i].nodeName.toLowerCase();

                var value = p_obj.attributes[i].nodeValue;
                if(!value) continue;

                if(value && typeof(value)=="string") value = value.replace(/\"/ig,'&quot;');

                dummy.setAttribute(name, value);
            }

            if(xIE4Up) dummy.style["cssText"] = p_obj.getAttribute("style")["cssText"];
            p_obj.parentNode.insertBefore(dummy, p_obj);
        }
        return;

    // 위젯 사이트/ 여백 조절
    } else if(obj.className == 'widgetSize' || obj.className == 'widgetBoxSize') {
        var p_obj = obj.parentNode.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(!widget) return;
        selectedWidget = p_obj;
        doShowWidgetSizeSetup(evt.pageX, evt.pageY, selectedWidget);
        return;

    // 위젯 제거
    } else if(obj.className == 'widgetRemove' || obj.className == 'widgetBoxRemove') {
        var p_obj = obj.parentNode.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(confirm(confirm_delete_msg)) {
            restoreWidgetButtons();

            if(p_obj.getAttribute('widget')=='widgetContent') {
                var response_tags = new Array('error','message','document_srl');
                var params = new Array();
                params['document_srl'] =p_obj.getAttribute('document_srl');
                exec_xml('widget','procWidgetDeleteDocument', params, completeDeleteWidgetContent, response_tags, params, p_obj);
                return;
            } else {
                p_obj.parentNode.removeChild(p_obj);
            }
        }
        return;
    }

    // 내용 클릭 무효화
    var p_obj = obj;
    while(p_obj) {
        if(p_obj.className == 'widgetOutput') {
            evt.cancelBubble = true;
            evt.returnValue = false;
            xPreventDefault(e);
            xStopPropagation(e);
            break;
        }
        p_obj = p_obj.parentNode;
    }
}

// content widget 복사
function completeCopyWidgetContent(ret_obj, response_tags, params, p_obj) {
    var document_srl = ret_obj['document_srl'];
    var dummy = xCreateElement("DIV");
    xInnerHtml(dummy,xInnerHtml(p_obj));


    dummy.widget_sequence = '';
    dummy.className = "widgetOutput";
    for(var i=0;i<p_obj.attributes.length;i++) {
        if(!p_obj.attributes[i].nodeName || !p_obj.attributes[i].nodeValue) continue;
        var name = p_obj.attributes[i].nodeName.toLowerCase();

        var value = p_obj.attributes[i].nodeValue;
        if(!value) continue;

        if(value && typeof(value)=="string") value = value.replace(/\"/ig,'&quot;');

        dummy.setAttribute(name, value);
    }
    p_obj.setAttribute('document_srl', document_srl);

    if(xIE4Up) dummy.style["cssText"] = p_obj.getAttribute("style")["cssText"];
    p_obj.parentNode.insertBefore(dummy, p_obj);
}

// content widget 제거
function completeDeleteWidgetContent(ret_obj, response_tags, params, p_obj) {
    p_obj.parentNode.removeChild(p_obj);
}


// 마우스 다운 이벤트 발생시 위젯의 이동을 처리
function doCheckWidgetDrag(e) {
    var evt = new xEvent(e); if(!evt.target) return;
    var obj = evt.target; 

    var pObj = obj.parentNode;
    while(pObj) {
        if(pObj.id == "pageSizeLayer") return;
        pObj = pObj.parentNode;
    }

    doHideWidgetSizeSetup();

    if(obj.className == 'widgetSetup' || obj.className == 'widgetCopy' || obj.className == 'widgetBoxCopy' || obj.className == 'widgetSize' || obj.className == 'widgetBoxSize' || obj.className == 'widgetRemove' || obj.className == 'widgetBoxRemove') return;

    p_obj = obj;
    while(p_obj) {
        if(p_obj.className == 'widgetOutput' || p_obj.className == 'widgetResize' || p_obj.className == 'widgetResizeLeft' || p_obj.className == 'widgetBoxResize' || p_obj.className == 'widgetBoxResizeLeft') {
            widgetDragEnable(p_obj, widgetDragStart, widgetDrag, widgetDragEnd);
            widgetMouseDown(e);
            return;
        }
        p_obj = p_obj.parentNode;
    }
}

function _getInt(val) {
    if(!val || val == "null") return 0;
    if(parseInt(val,10)==NaN) return 0;
    return parseInt(val,10);
}

// 위젯 크기 조절 레이어를 보여줌
var selectedSizeWidget = null;
function doShowWidgetSizeSetup(px, py, obj) {
    var layer = xGetElementById("pageSizeLayer");
    var formObj = layer.firstChild;
    while(formObj && formObj.nodeName != "FORM") formObj = formObj.nextSibling;
    if(!formObj || formObj.nodeName != "FORM") return;

    selectedSizeWidget = obj;

    layer.style.display = "block";

    formObj.width.value = obj.style.width;
    formObj.height.value = obj.style.height;
    formObj.padding_left.value = _getInt(selectedSizeWidget.getAttribute('widget_padding_left'));
    formObj.padding_right.value = _getInt(selectedSizeWidget.getAttribute('widget_padding_right'));
    formObj.padding_top.value = _getInt(selectedSizeWidget.getAttribute('widget_padding_top'));
    formObj.padding_bottom.value = _getInt(selectedSizeWidget.getAttribute('widget_padding_bottom'));
    formObj.margin_left.value = _getInt(selectedSizeWidget.style.marginLeft);
    formObj.margin_right.value = _getInt(selectedSizeWidget.style.marginRight);
    formObj.margin_top.value = _getInt(selectedSizeWidget.style.marginTop);
    formObj.margin_bottom.value = _getInt(selectedSizeWidget.style.marginBottom);

    var widget_align = getFloat(selectedSizeWidget);
    if(widget_align == "left") formObj.widget_align.selectedIndex = 0;
    else formObj.widget_align.selectedIndex = 1;

    formObj.border_top_color.value = transRGB2Hex(selectedSizeWidget.style.borderTopColor);
    formObj.border_top_thick.value = selectedSizeWidget.style.borderTopWidth.replace(/px$/i,'');
    formObj.border_top_type.selectedIndex = selectedSizeWidget.style.borderTopStyle=='dotted'?1:0;

    formObj.border_bottom_color.value = transRGB2Hex(selectedSizeWidget.style.borderBottomColor);
    formObj.border_bottom_thick.value = selectedSizeWidget.style.borderBottomWidth.replace(/px$/i,'');
    formObj.border_bottom_type.selectedIndex = selectedSizeWidget.style.borderBottomStyle=='dotted'?1:0;

    formObj.border_right_color.value = transRGB2Hex(selectedSizeWidget.style.borderRightColor);
    formObj.border_right_thick.value = selectedSizeWidget.style.borderRightWidth.replace(/px$/i,'');
    formObj.border_right_type.selectedIndex = selectedSizeWidget.style.borderRightStyle=='dotted'?1:0;

    formObj.border_left_color.value = transRGB2Hex(selectedSizeWidget.style.borderLeftColor);
    formObj.border_left_thick.value = selectedSizeWidget.style.borderLeftWidth.replace(/px$/i,'');
    formObj.border_left_type.selectedIndex = selectedSizeWidget.style.borderLeftStyle=='dotted'?1:0;

    formObj.background_color.value = transRGB2Hex(selectedSizeWidget.style.backgroundColor);

    formObj.background_image_url.value = selectedSizeWidget.style.backgroundImage.replace(/^url\(/i,'').replace(/\)$/i,'');

    switch(selectedSizeWidget.style.backgroundRepeat) {
        case 'no-repeat' : formObj.background_repeat.selectedIndex = 1; break;
        case 'repeat-x' : formObj.background_repeat.selectedIndex = 2; break;
        case 'repeat-y' : formObj.background_repeat.selectedIndex = 3; break;
        default : formObj.background_repeat.selectedIndex = 0; break;
    }

    formObj.background_x.value = 0;
    formObj.background_y.value = 0;
    var pos = selectedSizeWidget.style.backgroundPosition;
    if(pos) {
        pos = pos.split(' ');
        if(pos.length==2) {
            formObj.background_x.value = pos[0];
            formObj.background_y.value = pos[1];
        }
    }

    if(px+xWidth(layer)>xPageX(zonePageObj)+xWidth(zonePageObj)) px = xPageX(zonePageObj)+xWidth(zonePageObj)-xWidth(layer)-5;
    xLeft(layer, px);
    xTop(layer, py);
    layer.style.visibility = "visible";

    try {
        formObj.width.focus();
    } catch(e) {
    }

}

function doHideWidgetSizeSetup() {
    var layer = xGetElementById("pageSizeLayer");
    layer.style.visibility = "hidden";
    layer.style.display = "none";
}

function _getSize(value) {
    if(!value) return 0;
    var type = "px";
    if(value.lastIndexOf("%")>=0)  type = "%";
    var num = parseInt(value,10);
    if(num<1) return 0;
    if(type == "%" && num > 100) num = 100;
    return ""+num+type;
}

function _getBorderStyle(fld_color, fld_thick, fld_type) {
    var color = fld_color.value;
    color = color.replace(/^#/,'');
    if(!color) color = '#FFFFFF';
    else color = '#'+color;
    var width = fld_thick.value;
    if(!width) width = '0px';
    else width = parseInt(width,10)+'px'; 
    var style = fld_type.options[fld_type.selectedIndex].value;
    if(!style) style = 'solid';

    var str = color+' '+width+' '+style;
    return str;
}

function _getBGColorStyle(fld_color) {
    var color = fld_color.replace(/^#/,'');
    if(!color) color = '#FFFFFF';
    else color = '#'+color;
    return color;
}

function doApplyWidgetSize(fo_obj) {
    if(selectedSizeWidget) {
        if(fo_obj.widget_align.selectedIndex == 1) setFloat(selectedSizeWidget, 'right');
        else setFloat(selectedSizeWidget, 'left');

        var width = _getSize(fo_obj.width.value);
        if(width) selectedSizeWidget.style.width = width;

        var height = _getSize(fo_obj.height.value);
        if(height && height != "100%") selectedSizeWidget.style.height = height;
        else {
            selectedSizeWidget.style.height = '';
            var widgetBorder = xGetElementsByClassName('widgetBorder',selectedSizeWidget);
            for(var i=0;i<widgetBorder.length;i++) {
                var obj = widgetBorder[i];
                obj.style.height = '';
            }
        }

        selectedSizeWidget.style.borderTop = _getBorderStyle(fo_obj.border_top_color, fo_obj.border_top_thick, fo_obj.border_top_type);
        selectedSizeWidget.style.borderBottom = _getBorderStyle(fo_obj.border_bottom_color, fo_obj.border_bottom_thick, fo_obj.border_bottom_type);
        selectedSizeWidget.style.borderLeft = _getBorderStyle(fo_obj.border_left_color, fo_obj.border_left_thick, fo_obj.border_left_type);
        selectedSizeWidget.style.borderRight = _getBorderStyle(fo_obj.border_right_color, fo_obj.border_right_thick, fo_obj.border_right_type);

        selectedSizeWidget.style.marginTop = _getSize(fo_obj.margin_top.value);
        selectedSizeWidget.style.marginRight = _getSize(fo_obj.margin_right.value);
        selectedSizeWidget.style.marginBottom = _getSize(fo_obj.margin_bottom.value);
        selectedSizeWidget.style.marginLeft = _getSize(fo_obj.margin_left.value);

        if(!fo_obj.background_color.value || fo_obj.background_color.value == 'transparent') selectedSizeWidget.style.backgroundColor = 'transparent';
        else selectedSizeWidget.style.backgroundColor = _getBGColorStyle(fo_obj.background_color.value);

        var image_url = fo_obj.background_image_url.value;
        if(image_url) selectedSizeWidget.style.backgroundImage = "url("+image_url+")";
        else selectedSizeWidget.style.backgroundImage = 'none';

        switch(fo_obj.background_repeat.selectedIndex) {
            case 1 : selectedSizeWidget.style.backgroundRepeat = 'no-repeat'; break;
            case 2 : selectedSizeWidget.style.backgroundRepeat = 'repeat-x'; break;
            case 3 : selectedSizeWidget.style.backgroundRepeat = 'repeat-y'; break;
            default : selectedSizeWidget.style.backgroundRepeat = 'repeat'; break;
        }


        selectedSizeWidget.style.backgroundPosition = fo_obj.background_x.value+' '+fo_obj.background_y.value;

        var borderObj = selectedSizeWidget.firstChild;
        while(borderObj) {
            if(borderObj.nodeName == "DIV" && (borderObj.className == "widgetBorder" || borderObj.className == "widgetBoxBorder")) {
                var contentObj = borderObj.firstChild;
                while(contentObj) {
                    if(contentObj.nodeName == "DIV") {
                        contentObj.style.padding = "";
                        var paddingLeft = _getSize(fo_obj.padding_left.value);
                        if(paddingLeft) {
                            contentObj.style.paddingLeft = paddingLeft;
                            selectedSizeWidget.setAttribute('widget_padding_left', paddingLeft);
                        } else {
                            contentObj.style.paddingLeft = '';
                            selectedSizeWidget.setAttribute('widget_padding_left', '');
                        }

                        var paddingRight = _getSize(fo_obj.padding_right.value);
                        if(paddingRight) {
                            contentObj.style.paddingRight = paddingRight;
                            selectedSizeWidget.setAttribute('widget_padding_right', paddingRight);
                        } else {
                            contentObj.style.paddingRight = '';
                            selectedSizeWidget.setAttribute('widget_padding_right', '');
                        }

                        var paddingTop = _getSize(fo_obj.padding_top.value);
                        if(paddingTop) {
                            contentObj.style.paddingTop = paddingTop;
                            selectedSizeWidget.setAttribute('widget_padding_top', paddingTop);
                        } else {
                            contentObj.style.paddingTop = '';
                            selectedSizeWidget.setAttribute('widget_padding_top', '');
                        }

                        var paddingBottom = _getSize(fo_obj.padding_bottom.value);
                        if(paddingBottom) {
                            contentObj.style.paddingBottom = paddingBottom;
                            selectedSizeWidget.setAttribute('widget_padding_bottom', paddingBottom);
                        } else {
                            contentObj.style.paddingBottom = '';
                            selectedSizeWidget.setAttribute('widget_padding_bottom', '');
                        }

                        break;
                    }
                    contentObj = contentObj.nextSibling;
                }

                break;
            }

            borderObj = borderObj.nextSibling;
        }

        selectedSizeWidget = null;
        doFitBorderSize();
    }
        
    doHideWidgetSizeSetup();
}

/* 위젯 조절 */
xAddEventListener(document,'mouseover',widgetSetup);

function restoreWidgetButtons() {
    var widgetButton = xGetElementById('widgetButton');
    var boxWidgetButton = xGetElementById('widgetBoxButton');
    if(!widgetButton || !boxWidgetButton) return;
    widgetButton.style.visibility = 'hidden';
    xGetElementById("zonePageContent").parentNode.appendChild(widgetButton);
    boxWidgetButton.style.visibility = 'hidden';
    xGetElementById("zonePageContent").parentNode.appendChild(boxWidgetButton);
}

function showWidgetButton(name, obj) {
    var widgetButton = xGetElementById(name);
    if(!widgetButton) return;
    widgetButton.style.visibility = 'visible';
    obj.appendChild(widgetButton);
}

function widgetSetup(evt) {

    var e = new xEvent(evt);
    var obj = e.target;
    while(obj) {
        if(obj.parentNode && typeof(obj.parentNode.className)!='undefined' && obj.parentNode.className == 'widgetButtons') return;
        if(typeof(obj.className)!='undefined' && obj.className == 'widgetOutput') break;
        obj = obj.parentNode;
    }
    if(!obj || typeof(obj.className)=='undefined' || obj.className != 'widgetOutput') {
        restoreWidgetButtons();
        return;
    }

    var widget = obj.getAttribute('widget');
    if(!widget) return;

    if(widget == 'widgetBox') {
        restoreWidgetButtons();
        showWidgetButton('widgetBoxButton', obj);
    } else {
        restoreWidgetButtons();
        showWidgetButton('widgetButton', obj);

        var p_obj = obj.parentNode;
        while(p_obj) {
            if(p_obj.getAttribute('widget')=='widgetBox') {
                showWidgetButton('widgetBoxButton', p_obj);
                break;
            }
            p_obj = p_obj.parentNode;
        }
    }
}

/* 위젯 드래그 */
// 드래그 중이라는 상황을 간직할 변수
var widgetDragManager = {obj:null, isDrag:false}
var widgetTmpObject = new Array();
var widgetDisappear = 0;

function widgetCreateTmpObject(obj) {
    var id = obj.getAttribute('id');
    tmpObj = xCreateElement('DIV');
    tmpObj.id = id + '_tmp';
    tmpObj.className = obj.className;
    tmpObj.style.overflow = 'hidden';
    tmpObj.style.margin= '0px';
    tmpObj.style.padding = '0px';
    tmpObj.style.width = obj.style.width;

    tmpObj.style.display = 'none';
    tmpObj.style.position = 'absolute';
    tmpObj.style.opacity = 1;
    tmpObj.style.filter = 'alpha(opacity=100)';

    xLeft(tmpObj, xPageX(obj));
    xTop(tmpObj, xPageY(obj));

    document.body.appendChild(tmpObj);
    widgetTmpObject[obj.id] = tmpObj;
    return tmpObj;
}

// 기생성된 임시 object를 찾아서 return, 없으면 만들어서 return
var idStep = 0;
function widgetGetTmpObject(obj) {
    if(!obj.id) obj.id = 'widget_'+idStep++;
    var tmpObj = widgetTmpObject[obj.id];
    if(!tmpObj) tmpObj = widgetCreateTmpObject(obj);
    return tmpObj;
}

// 메뉴에 마우스 클릭이 일어난 시점에 드래그를 위한 제일 첫 동작 (해당 object에 각종 함수나 상태변수 설정) 
function widgetDragEnable(obj, funcDragStart, funcDrag, funcDragEnd) {

    // 상위 object에 드래그 가능하다는 상태와 각 드래그 관련 함수를 설정
    obj.draggable = true;
    obj.dragStart = funcDragStart;
    obj.drag = funcDrag;
    obj.dragEnd = funcDragEnd;

    // 드래그 가능하지 않다면 드래그 가능하도록 상태 지정하고 mousemove이벤트 등록
    if (!widgetDragManager.isDrag) {
        widgetDragManager.isDrag = true;
        xAddEventListener(document, 'mousemove', widgetDragMouseMove, false);
    } 
} 

// 드래그를 시작할때 호출되는 함수 (이동되는 형태를 보여주기 위한 작업을 함)
function widgetDragStart(tobj, px, py) { 
    if(tobj.className == 'widgetResize' || tobj.className == 'widgetResizeLeft' || tobj.className == 'widgetBoxResize' || tobj.className == 'widgetBoxResizeLeft') return;
    var obj = widgetGetTmpObject(tobj);

    xInnerHtml(obj, xInnerHtml(tobj));

    xLeft(obj, xPageX(tobj));
    xTop(obj, xPageY(tobj));
    xWidth(obj, xWidth(tobj));
    xHeight(obj, xHeight(tobj));

    xDisplay(obj, 'block');
}

// 드래그 시작후 마우스를 이동할때 발생되는 이벤트에 의해 실행되는 함수
function widgetDrag(tobj, dx, dy) {
    var minWidth = 40;
    var minHeight = 10;

    var sx = xPageX(tobj.parentNode);
    var sy = xPageY(tobj.parentNode);

    var nx = tobj.xDPX;
    var ny = tobj.xDPY;

    var zoneWidth = xWidth(zonePageObj);
    var zoneLeft = xPageX(zonePageObj);
    var zoneRight = zoneLeft + zoneWidth;

    var pWidth = xWidth(tobj.parentNode);

    var cssFloat = getFloat(tobj.parentNode);
    if(!cssFloat) cssFloat = 'left';

    // 위젯 리사이즈 (우측)
    if(tobj.className == 'widgetResize' || tobj.className == 'widgetBoxResize') {
        if(nx < sx+minWidth) nx = sx+minWidth;
        if(nx > zoneRight) nx = zoneRight;

        if(cssFloat == 'right') nx = sx + pWidth;

        var new_width = nx  - sx;
        if(new_width < minWidth) new_width = minWidth;

        var new_height = ny - sy;
        if(new_height < minHeight) new_height = minHeight;

        if( zoneRight < sx+new_width) new_width = zoneRight - sx;

        // 위젯의 크기 조절
        xWidth(tobj.nextSibling.nextSibling, new_width);
        xHeight(tobj.nextSibling.nextSibling, new_height);

        xWidth(tobj.parentNode, new_width);
        xHeight(tobj.parentNode, new_height);

    // 위젯 리사이즈 (좌측)
    } else if(tobj.className == 'widgetResizeLeft' || tobj.className == 'widgetBoxResizeLeft') {

        if(nx < zoneLeft) nx = zoneLeft;

        if(cssFloat == 'left') nx = sx;

        var new_width = pWidth + (sx - nx);
        if(new_width < minWidth) new_width = minWidth;

        var new_height = ny - sy;
        if(new_height < minHeight) new_height = minHeight;

        // 위젯의 크기 조절
        xWidth(tobj.nextSibling, new_width);
        xHeight(tobj.nextSibling, new_height);

        xWidth(tobj.parentNode, new_width);
        xHeight(tobj.parentNode, new_height);

    // 위젯 드래그
    } else {
        var obj = widgetGetTmpObject(tobj);

        xLeft(obj, parseInt(xPageX(obj),10) + parseInt(dx,10));
        xTop(obj, parseInt(xPageY(obj),10) + parseInt(dy,10));

        // 박스 안에 있을 경우에는 박스내의 위젯하고 자리를 바꾸고 그 외의 경우에는 박스를 빠져 나간다
        if(tobj.parentNode != zonePageObj) {
            // 박스내에 있는 위젯들을 구함
            var widgetList = xGetElementsByClassName("widgetOutput",tobj.parentNode);

            for(var i=0;i<widgetList.length;i++) {
                var target_obj = widgetList[i];
                var l =  xPageX(target_obj);
                var t =  xPageY(target_obj);
                var ll =  parseInt(l,10) + parseInt(xWidth(target_obj),10);
                var tt =  parseInt(t,10) + parseInt(xHeight(target_obj),10);

                if( tobj != target_obj && tobj.xDPX >= l && tobj.xDPX <= ll && tobj.xDPY >= t && tobj.xDPY <= tt && tobj.parentNode == target_obj.parentNode) {
                    var next1 = target_obj.nextSibling;
                    if(!next1) {
                        next1 = xCreateElement("DIV");
                        target_obj.parentNode.appendChild(next1);
                    }
                    var next2 = tobj.nextSibling;
                    if(!next2) {
                        next2 = xCreateElement("DIV");
                        tobj.parentNode.appendChild(next2);
                    }

                    if(next1) next1.parentNode.insertBefore(tobj, next1);
                    if(next2) next2.parentNode.insertBefore(target_obj, next2);
                    doFitBorderSize();
                    widgetList = null;
                    return;
                }
            }
            widgetList = null;

            // 만약 다른 위젯과 자리를 바꾸지 못하였는데 자기 부모창밖에 있는게 확인이 되면 박스 밖으로 내보낸다.
            var l =  xPageX(tobj.parentNode);
            var t =  xPageY(tobj.parentNode);
            var ll =  parseInt(l,10) + parseInt(xWidth(tobj.parentNode),10);
            var tt =  parseInt(t,10) + parseInt(xHeight(tobj.parentNode),10);
            if( (tobj.xDPX < l || tobj.xDPX > ll) || (tobj.xDPY < t || tobj.xDPY > tt) ) {
                zonePageObj.insertBefore(tobj, tobj.parentNode.parentNode.parentNode);
                doFitBorderSize();
                return;
            }

        // 박스 밖에 있을 경우에는 다른 위젯과 자리를 바꾸거나 박스내에 들어가도록 한다
        } else {
            // 이동하려는 위젯이 박스 위젯이 아니라면 박스 위젯들을 구해서 입력 유무를 검사한다
            if(tobj.getAttribute("widget")!="widgetBox") {
                var boxList = xGetElementsByClassName("nullWidget", zonePageObj);
                for(var i=0;i<boxList.length;i++) {
                    var target_obj = boxList[i];

                    xHeight(target_obj, xHeight(target_obj.parentNode));
                    xWidth(target_obj, xWidth(target_obj.parentNode));

                    var l =  xPageX(target_obj);
                    var t =  xPageY(target_obj);
                    var ll =  parseInt(l,10) + parseInt(xWidth(target_obj),10);
                    var tt =  parseInt(t,10) + parseInt(xHeight(target_obj),10);
                    if( tobj.xDPX >= l && tobj.xDPX <= ll && tobj.xDPY >= t && tobj.xDPY <= tt) {
                        if(target_obj.className == "nullWidget") {
                            target_obj.appendChild(tobj);

                            // 이동을 멈춤
                            widgetManualEnd();
                            
                            doFitBorderSize();
                            boxList = null;
                            return;
                        }
                    }
                }
                boxList = null;
            }

            // 다른 위젯들을 구해서 자리를 바꿈
            var widgetList = xGetElementsByClassName("widgetOutput",zonePageObj);
            for(var i=0;i<widgetList.length;i++) {
                var target_obj = widgetList[i];
                var widget = target_obj.getAttribute("widget");
                if(widget == 'widgetBox' || target_obj.parentNode != zonePageObj) continue;
                var l =  xPageX(target_obj);
                var t =  xPageY(target_obj);
                var ll =  parseInt(l,10) + parseInt(xWidth(target_obj),10);
                var tt =  parseInt(t,10) + parseInt(xHeight(target_obj),10);

                if( tobj != target_obj && tobj.xDPX >= l && tobj.xDPX <= ll && tobj.xDPY >= t && tobj.xDPY <= tt && tobj.parentNode == target_obj.parentNode) {
                    var next1 = target_obj.nextSibling;
                    if(!next1) next1 = target_obj.parentNode.lastChild;
                    if(!next1) {
                        next1 = xCreateElement("DIV");
                        target_obj.parentNode.appendChild(next1);
                    }
                    var next2 = tobj.nextSibling;
                    if(!next2) {
                        next2 = xCreateElement("DIV");
                        tobj.parentNode.appendChild(next2);
                    }

                    if(next1) next1.parentNode.insertBefore(tobj, next1);
                    if(next2) next2.parentNode.insertBefore(target_obj, next2);
                    doFitBorderSize();
                    widgetList = null;
                    return;
                }
            }
            widgetList = null;
        }
    }
} 
  
// 드래그 종료 (이동되는 object가 이동할 곳에 서서히 이동되는 것처럼 보이는 효과)
function widgetDragEnd(tobj, px, py) {
    var obj = widgetGetTmpObject(tobj);
    widgetDisapear = widgetDisapearObject(obj, tobj);
    widgetDragDisable(tobj.getAttribute('id'));
}

// 스르르 사라지게 함 (일단 사라지게 하는 기능을 제거.. 속도 문제)
function widgetDisapearObject(obj, tobj) {
    xInnerHtml(tobj,xInnerHtml(obj));
    xInnerHtml(obj,'');
    xDisplay(obj, 'none');
    obj.parentNode.removeChild(obj);
    widgetTmpObject[tobj.id] = null;
    return;
    var it = 5;
    var ib = 1;

    var x = parseInt(xPageX(obj),10);
    var y = parseInt(xPageY(obj),10);
    var ldt = (x - parseInt(xPageX(tobj),10)) / ib;
    var tdt = (y - parseInt(xPageY(tobj),10)) / ib;

    return setInterval(function() {
        if(ib < 1) {
            clearInterval(widgetDisapear);
            xInnerHtml(tobj,xInnerHtml(obj));
            xInnerHtml(obj,'');
            xDisplay(obj, 'none');
            obj.parentNode.removeChild(obj);
            widgetTmpObject[tobj.id] = null;
            return;
        }
        ib -= 5;
        x-=ldt;
        y-=tdt;
        xLeft(obj, x);
        xTop(obj, y);
    }, it/ib);
}

// 마우스다운 이벤트 발생시 호출됨
function widgetMouseDown(e) {
    var evt = new xEvent(e);
    var obj = evt.target;

    while(obj && !obj.draggable) {
        obj = xParent(obj, true);
    }
    if(obj) {
        xPreventDefault(e);
        obj.xDPX = evt.pageX;
        obj.xDPY = evt.pageY;
        widgetDragManager.obj = obj;
        xAddEventListener(document, 'mouseup', widgetMouseUp, false);
        if (obj.dragStart) obj.dragStart(obj, evt.pageX, evt.pageY);
    }
}

// 마우스 버튼을 놓았을때 동작될 함수 (각종 이벤트 해제 및 변수 설정 초기화)
function widgetMouseUp(e) { 
    if (widgetDragManager.obj) {
        xPreventDefault(e);
        xRemoveEventListener(document, 'mouseup', widgetMouseUp, false);

        if (widgetDragManager.obj.dragEnd) {
            var evt = new xEvent(e);
            widgetDragManager.obj.dragEnd(widgetDragManager.obj, evt.pageX, evt.pageY);
        } 

        widgetDragManager.obj = null;
        widgetDragManager.isDrag = false;
    } 
}  

// 드래그할때의 object이동등을 담당 
function widgetDragMouseMove(e) {
    var evt = new xEvent(e);
    if(widgetDragManager.obj) {
        xPreventDefault(e);

        var obj = widgetDragManager.obj;
        var dx = evt.pageX - obj.xDPX;
        var dy = evt.pageY - obj.xDPY;

        obj.xDPX = evt.pageX;
        obj.xDPY = evt.pageY;

        if (obj.drag) {
            obj.drag(obj, dx, dy);
        } else {
            xMoveTo(obj, xLeft(obj) + dx, xTop(obj) + dy);
        }
    }
}

// 해당 object 에 더 이상 drag가 되지 않도록 설정
function widgetDragDisable(id) {
    if (!widgetDragManager) return;
    var obj = xGetElementById(id);
    obj.draggable = false;
    obj.dragStart = null;
    obj.drag = null;
    obj.dragEnd = null;
    //obj.style.backgroundColor = obj.getAttribute('source_color');

    xRemoveEventListener(obj, 'mousedown', widgetMouseDown, false);

    return;
}

// 강제로 드래그를 종료시킴
function widgetManualEnd() {
    var tobj = widgetDragManager.obj;
    if(!tobj) return;

    xRemoveEventListener(document, 'mouseup', widgetMouseUp, false);

    var obj = widgetGetTmpObject(tobj);
    widgetDisapear = widgetDisapearObject(obj, tobj);
    widgetDragDisable(tobj.getAttribute('id'));

    widgetDragManager.obj = null;
    widgetDragManager.isDrag = false;
}

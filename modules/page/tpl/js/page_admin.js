/**
 * @file   modules/page/js/page_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  page모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function completeInsertPage(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = '';
    if(location.href.getQuery('module')=='admin') {
        url = current_url.setQuery('module_srl',module_srl).setQuery('act','dispPageAdminInfo');
        if(page) url = url.setQuery('page',page);
    } else {
        url = current_url.setQuery('act','').setQuery('module_srl','');
    }

    location.href = url;
}

/* 내용 저장 후 */
function completeInsertPageContent(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    location.href = current_url.setQuery('act','');
}

/* 페이지 컨텐츠 저장 */
function doSubmitPageContent(fo_obj) {
    var zoneObj = xGetElementById("zonePageContent");
    var html = "";
    var childObj = zoneObj.firstChild;
    while(childObj) {
        if(childObj.nodeName == "DIV" && childObj.getAttribute("widget"))  {
            var widget = childObj.getAttribute("widget");
            if(!widget) continue; 

            // 내장 위젯인 에디터 컨텐츠인 경우
            if(widget == "widgetContent") {
                var style = childObj.getAttribute("style");
                if(typeof(style)=="object") style = style["cssText"];
                var cobj = childObj.firstChild;
                var code = "";
                while(cobj && cobj.className != "widgetContent") { cobj = cobj.nextSibling; }
                if(cobj && cobj.className == "widgetContent") {
                    var body = xInnerHtml(cobj);
                    code = '<img src="./common/tpl/images/widget_bg.jpg" class="zbxe_widget_output" widget="widgetContent" style="'+style+'" body="'+body+'" widget_margin_left="'+childObj.getAttribute("widget_margin_left")+'" widget_margin_right="'+childObj.getAttribute("widget_margin_right")+'" widget_margin_top="'+childObj.getAttribute("widget_margin_top")+'" widget_margin_bottom="'+childObj.getAttribute("widget_margin_bottom")+'" />';
                }
                html += code;

            // 위젯의 경우
            } else {
                var attrs = "";
                var code = "";
                for(var i=0;i<childObj.attributes.length;i++) {
                    if(!childObj.attributes[i].nodeName || !childObj.attributes[i].nodeValue) continue;
                    var name = childObj.attributes[i].nodeName.toLowerCase();
                    if(name == "contenteditable" || name == "id" || name=="style" || name=="src" || name=="widget" || name == "body" || name == "class" || name == "widget_width" || name == "widget_width_type" || name == "xdpx" || name == "xdpy" || name == "height") continue;

                    var value = childObj.attributes[i].nodeValue;
                    if(!value) continue;

                    if(value && typeof(value)=="string") value = value.replace(/\"/ig,'&quot;');

                    attrs += name+'="'+value+'" ';
                }
                var style = childObj.getAttribute("style");
                if(typeof(style)=="object" && style["cssText"]) style = style["cssText"];

                code = '<img class="zbxe_widget_output" style="'+style+'" widget="'+widget+'" '+attrs+' />';
                html += code;
            }
        }
        childObj = childObj.nextSibling;
    }

    fo_obj.content.value = html;

    return procFilter(fo_obj, insert_page_content);
}

/* 모듈 삭제 후 */
function completeDeletePage(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispPageAdminContent');
    if(page) url = url.setQuery('page',page);

    location.href = url;
}

/* 카테고리 이동 */
function doChangeCategory(fo_obj) {
    var module_category_srl = fo_obj.module_category_srl.options[fo_obj.module_category_srl.selectedIndex].value;
    if(module_category_srl==-1) {
        location.href = current_url.setQuery('act','dispModuleAdminCategory');
        return false;
    }
    return true;
}

/* 위젯 재컴파일 */
function doRemoveWidgetCache(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;
    exec_xml('page', 'procPageAdminRemoveWidgetCache', params, completeRemoveWidgetCache);
}

function completeRemoveWidgetCache(ret_obj) {
    var message = ret_obj['message'];
    alert(message);
    location.reload(); 
}

/* 권한 관련 */
function doSelectAll(obj, key) {
    var fo_obj = obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { 
        fo_obj = fo_obj.parentNode; 
    }

    for(var i=0;i<fo_obj.length;i++) {
        var tobj = fo_obj[i];
        if(tobj.name == key) tobj.checked=true;
    }
}

function doUnSelectAll(obj, key) {
    var fo_obj = obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { 
        fo_obj = fo_obj.parentNode; 
    }

    for(var i=0;i<fo_obj.length;i++) {
        var tobj = fo_obj[i];
        if(tobj.name == key) tobj.checked = false;
    }
}

/* 컨텐츠 추가 */
function doAddContent(module_srl) {
    popopen("./?module=page&act=dispPageAdminAddContent&module_srl="+module_srl, "addContent");
}

function doSyncPageContent() {
    if(opener && opener.selectedWidget) {
        var fo_obj = xGetElementById("content_fo");
        var style = opener.selectedWidget.getAttribute("style");
        var sel_obj = opener.selectedWidget;
        if(typeof(style)=="object") style = style["cssText"];
        fo_obj.style.value = style;
        fo_obj.widget_margin_left.value = sel_obj.getAttribute("widget_margin_left");
        fo_obj.widget_margin_right.value = sel_obj.getAttribute("widget_margin_right");
        fo_obj.widget_margin_bottom.value = sel_obj.getAttribute("widget_margin_bottom");
        fo_obj.widget_margin_top.value = sel_obj.getAttribute("widget_margin_top");

        var obj = sel_obj.firstChild;
        while(obj && obj.className != "widgetContent") obj = obj.nextSibling;
        if(obj && obj.className == "widgetContent") {
            var content = Base64.decode(xInnerHtml(obj));
            xGetElementById("content_fo").content.value = content;
        }
    }

    editorStart(1, "module_srl", "content", false, 400 );
    editor_upload_start(1);

    setFixedPopupSize();
}

function completeAddContent(ret_obj) {
    var tpl = ret_obj["tpl"];

    selected_node = opener.selectedWidget;

    if(selected_node  && selected_node.getAttribute("widget")) {
        selected_node = replaceOuterHTML(selected_node, tpl);
    } else {
        var obj = opener.xGetElementById('zonePageContent');
        xInnerHtml(obj, xInnerHtml(obj)+tpl);
    }

    if(opener.doFitBorderSize) opener.doFitBorderSize();
    window.close();

    return false;
}


/* 위젯 추가 */
function doAddWidget(fo) {
    var sel = fo.widget_list;
    var idx = sel.selectedIndex;
    var val = sel.options[idx].value;
    var module_srl = fo.module_srl.value;

    var url = current_url.setQuery('module','widget').setQuery('act','dispWidgetGenerateCodeInPage').setQuery('selected_widget', val).setQuery('module_srl', module_srl);
    popopen(url,'GenerateCodeInPage');
}

/* 페이지 수정 시작 */
function doStartPageModify() {

    // 위젯 크기/여백 조절 레이어를 가장 밖으로 뺌
    var obj = xGetElementById("tmpPageSizeLayer");
    var dummy = xCreateElement("div");
    xInnerHtml(dummy, xInnerHtml(obj));
    dummy.id="pageSizeLayer";
    dummy.style.visibility = "hidden";
    dummy.style.position = "absolute";
    dummy.style.left = 0;
    dummy.style.top = 0;

    var oObj = xGetElementById("waitingforserverresponse");
    oObj.parentNode.insertBefore(dummy, oObj);

    // 모든 위젯들의 크기를 정해진 크기로 맞춤
    doFitBorderSize();

    // 드래그와 리사이즈와 관련된 이벤트 리스너 생성
    xAddEventListener(document,"click",doCheckWidget);
    xAddEventListener(document,"mousedown",doCheckWidgetDrag);
}

// widgetBorder에 height를 widgetOutput와 맞춰줌
function doFitBorderSize() {
    var obj_list = xGetElementsByClassName('widgetBorder', xGetElementById('zonePageContent'));
    for(var i=0;i<obj_list.length;i++) {
        var obj = obj_list[i];
        if(xWidth(obj)<74) xWidth(obj,74);
        if(xHeight(obj)<40) xHeight(obj,40);
        xHeight(obj, xHeight(obj.parentNode));
    }
}

var selectedWidget = null;

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
        var p_obj = obj.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(!widget) return;
        selectedWidget = p_obj;
        if(widget == 'widgetContent') popopen("./?module=page&act=dispPageAdminAddContent&module_srl="+xGetElementById("pageFo").module_srl.value, "addContent");
        else popopen(request_uri+"?module=widget&act=dispWidgetGenerateCodeInPage&selected_widget="+widget,'GenerateCodeInPage');
        return;
    // 위젯 사이트/ 여백 조절
    } else if(obj.className == 'widgetSize') {
        var p_obj = obj.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(!widget) return;
        selectedWidget = p_obj;
        doShowWidgetSizeSetup(evt.pageX, evt.pageY, selectedWidget);
        return;
    // 위젯 제거
    } else if(obj.className == 'widgetRemove') {
        var p_obj = obj.parentNode;
        var widget = p_obj.getAttribute("widget");
        if(confirm(confirm_delete_msg)) p_obj.parentNode.removeChild(p_obj);
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

    if(obj.className == 'widgetSetup' || obj.className == 'widgetSize' || obj.className == 'widgetRemove') return;

    p_obj = obj;
    while(p_obj) {
        if(p_obj.className == 'widgetOutput' || p_obj.className == 'widgetResize' || p_obj.className == 'widgetResizeLeft') {
            widgetDragEnable(p_obj, widgetDragStart, widgetDrag, widgetDragEnd);
            widgetMouseDown(e);
            return;
        }
        p_obj = p_obj.parentNode;
    }
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
    formObj.margin_left.value = selectedSizeWidget.getAttribute('widget_margin_left');
    formObj.margin_right.value = selectedSizeWidget.getAttribute('widget_margin_right');
    formObj.margin_top.value = selectedSizeWidget.getAttribute('widget_margin_top');
    formObj.margin_bottom.value = selectedSizeWidget.getAttribute('widget_margin_bottom');

    var widget_align = '';
    if(xIE4Up) widget_align = selectedSizeWidget.style.styleFloat;
    else widget_align = selectedSizeWidget.style.cssFloat;
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

    if(px+xWidth(layer)>xPageX('zonePageContent')+xWidth('zonePageContent')) px = xPageX('zonePageContent')+xWidth('zonePageContent')-xWidth(layer)-5;
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
    if(!value) return;
    var type = "px";
    if(value.lastIndexOf("%")>=0)  type = "%";
    var num = parseInt(value,10);
    if(num<1) return;
    if(type == "%" && num > 100) num = 100;
    return ""+num+type;
}

function _getBorderStyle(fld_color, fld_thick, fld_type) {
    var color = fld_color.value;
    if(!color) color = '#FFFFFF';
    else color = '#'+color;
    var width = fld_thick.value;
    if(!width) width = '0px';
    else width = parseInt(width,10)+'px'; 
    var style = fld_type.value;
    if(!style) style = 'solid';

    var str = color+' '+width+' '+style;
    return str;
}

function doApplyWidgetSize(fo_obj) {
    if(selectedSizeWidget) {
        if(fo_obj.widget_align.selectedIndex== 1) {
            if(xIE4Up) selectedSizeWidget.style.styleFloat = 'right';
            else selectedSizeWidget.style.cssFloat = 'right';
        } else {
            if(xIE4Up) selectedSizeWidget.style.styleFloat = 'left';
            else selectedSizeWidget.style.cssFloat = 'left';
        }

        var width = _getSize(fo_obj.width.value);
        if(width) selectedSizeWidget.style.width = width;

        var height = _getSize(fo_obj.height.value);
        if(height) selectedSizeWidget.style.height = height;

        selectedSizeWidget.style.borderTop = _getBorderStyle(fo_obj.border_top_color, fo_obj.border_top_thick, fo_obj.border_top_type);
        selectedSizeWidget.style.borderBottom = _getBorderStyle(fo_obj.border_bottom_color, fo_obj.border_bottom_thick, fo_obj.border_bottom_type);
        selectedSizeWidget.style.borderLeft = _getBorderStyle(fo_obj.border_left_color, fo_obj.border_left_thick, fo_obj.border_left_type);
        selectedSizeWidget.style.borderRight = _getBorderStyle(fo_obj.border_right_color, fo_obj.border_right_thick, fo_obj.border_right_type);


        var borderObj = selectedSizeWidget.firstChild;
        while(borderObj) {
            if(borderObj.nodeName == "DIV" && borderObj.className == "widgetBorder") {
                var contentObj = borderObj.firstChild;
                while(contentObj) {
                    if(contentObj.nodeName == "DIV") {
                        contentObj.style.margin = "";
                        var marginLeft = _getSize(fo_obj.margin_left.value);
                        if(marginLeft) {
                            contentObj.style.marginLeft = marginLeft;
                            selectedSizeWidget.setAttribute('widget_margin_left', marginLeft);
                        } else {
                            contentObj.style.marginLeft = '';
                            selectedSizeWidget.setAttribute('widget_margin_left', '');
                        }

                        var marginRight = _getSize(fo_obj.margin_right.value);
                        if(marginRight) {
                            contentObj.style.marginRight = marginRight;
                            selectedSizeWidget.setAttribute('widget_margin_right', marginRight);
                        } else {
                            contentObj.style.marginRight = '';
                            selectedSizeWidget.setAttribute('widget_margin_right', '');
                        }

                        var marginTop = _getSize(fo_obj.margin_top.value);
                        if(marginTop) {
                            contentObj.style.marginTop = marginTop;
                            selectedSizeWidget.setAttribute('widget_margin_top', marginTop);
                        } else {
                            contentObj.style.marginTop = '';
                            selectedSizeWidget.setAttribute('widget_margin_top', '');
                        }

                        var marginBottom = _getSize(fo_obj.margin_bottom.value);
                        if(marginBottom) {
                            contentObj.style.marginBottom = marginBottom;
                            selectedSizeWidget.setAttribute('widget_margin_bottom', marginBottom);
                        } else {
                            contentObj.style.marginBottom = '';
                            selectedSizeWidget.setAttribute('widget_margin_bottom', '');
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

/* 위젯 드래그 */
// 드래그 중이라는 상황을 간직할 변수
var widgetDragManager = {obj:null, isDrag:false}
var widgetTmpObject = new Array();
var widgetDisappear = 0;

function widgetCreateTmpObject(obj) {
    var id = obj.getAttribute('id');
    var tmpObj = widgetTmpObject[id];
    if(tmpObj) return tmpObj;

    tmpObj = xCreateElement('DIV');
    tmpObj.id = id + '_tmp';
    tmpObj.className = obj.className;
    //tmpObj.setAttribute('widget', obj.getAttribute('widget'));
    tmpObj.style.overflow = 'hidden';
    tmpObj.style.padding = '0px';
    tmpObj.style.margin = '0px';
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
function widgetGetTmpObject(obj) {
    var tmpObj = widgetTmpObject[obj.id];
    if(!tmpObj) tmpObj = widgetCreateTmpObject(obj);
    return tmpObj;
}

// 메뉴에 마우스 클릭이 일어난 시점에 드래그를 위한 제일 첫 동작 (해당 object에 각종 함수나 상태변수 설정) 
var id_step = 0;
function widgetDragEnable(obj, funcDragStart, funcDrag, funcDragEnd) {
    var id = obj.getAttribute('id');
    if(!id) {
        id = 'zLayer_'+id_step;
        id_step++;
        obj.setAttribute('id', id);
    }

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
    if(tobj.className == 'widgetResize' || tobj.className == 'widgetResizeLeft' ) return;
    var obj = widgetGetTmpObject(tobj);

    xInnerHtml(obj, xInnerHtml(tobj));

    tobj.setAttribute('source_color', tobj.style.backgroundColor);
    tobj.style.backgroundColor = "#BBBBBB";

    xLeft(obj, xPageX(tobj));
    xTop(obj, xPageY(tobj));
    xWidth(obj, xWidth(tobj));
    xHeight(obj, xHeight(tobj));

    xDisplay(obj, 'block');
}

// 드래그 시작후 마우스를 이동할때 발생되는 이벤트에 의해 실행되는 함수
function widgetDrag(tobj, dx, dy) {
    var minWidth = 74;
    var minHeight = 40;

    var sx = xPageX(tobj.parentNode);
    var sy = xPageY(tobj.parentNode);

    var nx = tobj.xDPX;
    var ny = tobj.xDPY;

    var zoneWidth = xWidth('zonePageContent');
    var zoneLeft = xPageX('zonePageContent');
    var zoneRight = zoneLeft + zoneWidth;

    var pWidth = xWidth(tobj.parentNode);

    var float = xIE4Up?tobj.parentNode.style.styleFloat:tobj.parentNode.style.cssFloat;
    if(!float) float = 'left';

    // 위젯 리사이즈 (우측)
    if(tobj.className == 'widgetResize') {
        if(nx < sx+minWidth) nx = sx+minWidth;
        if(nx > zoneRight) nx = zoneRight;

        if(float == 'right') nx = sx + pWidth;

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
    } else if(tobj.className == 'widgetResizeLeft') {

        if(nx < zoneLeft) nx = zoneLeft;

        if(float == 'left') nx = sx;

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
        var zoneObj = xGetElementById('zonePageContent');
        var target_obj = zoneObj.firstChild;

        xLeft(obj, parseInt(xPageX(obj),10) + parseInt(dx,10));
        xTop(obj, parseInt(xPageY(obj),10) + parseInt(dy,10));

        while(target_obj) {
            //if(target_obj.nodeName == 'DIV' && target_obj.getAttribute('widget')) {
            if(target_obj.parentNode.id == "zonePageContent" && target_obj.getAttribute && target_obj.getAttribute("widget") ) {
                var l =  xPageX(target_obj);
                var t =  xPageY(target_obj);
                var ll =  parseInt(l,10) + parseInt(xWidth(target_obj),10);
                var tt =  parseInt(t,10) + parseInt(xHeight(target_obj),10);

                if( tobj != target_obj && tobj.xDPX >= l && tobj.xDPX <= ll && tobj.xDPY >= t && tobj.xDPY <= tt) {
                    //target_obj.parentNode.insertBefore(tobj, target_obj.nextSibling);
                    var next1 = target_obj.nextSibling;
                    if(!next1) next1 = target_obj.parentNode.lastChild;
                    var next2 = tobj.nextSibling;
                    if(!next2) next2 = tobj.parentNode.lastChild;

                    if(next1) next1.parentNode.insertBefore(tobj, next1);

                    if(next2) next2.parentNode.insertBefore(target_obj, next2);
                }
            }

            target_obj = target_obj.nextSibling;
        }
    }
} 
  
// 드래그 종료 (이동되는 object가 이동할 곳에 서서히 이동되는 것처럼 보이는 효과)
function widgetDragEnd(tobj, px, py) {
    var obj = widgetGetTmpObject(tobj);
    widgetDisapear = widgetDisapearObject(obj, tobj);
    widgetDragDisable(tobj.getAttribute('id'));
}

// 스르르 사라지게 함;;
function widgetDisapearObject(obj, tobj) {
    var it = 150;
    var ib = 15;

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
    obj.style.backgroundColor = obj.getAttribute('source_color');

    xRemoveEventListener(obj, 'mousedown', widgetMouseDown, false);

    return;
}

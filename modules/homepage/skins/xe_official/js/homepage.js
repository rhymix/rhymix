var max_menu_depth = 1;
var menuList = new Array();
var mousePos = {x:0,y:0}

function chkMousePosition(evt) {
    var e = new xEvent(evt);
    mousePos = {x:e.pageX, y:e.pageY};

    var pobj = e.target;
    while(pobj) {
        pobj = pobj.parentNode;
        if(pobj && pobj.id == 'menuItem') return;
    }

    hideMenuItem();
}

function homepageLoadMenuInfo(xml_file) {
    var oXml = new xml_handler();
    oXml.reset();
    oXml.xml_path = xml_file;
    oXml.request(completeHomepageLoadMenuInfo, oXml);
}

function completeHomepageLoadMenuInfo(oXml) {
    var waiting_obj = xGetElementById("waitingforserverresponse");
    if(waiting_obj) waiting_obj.style.visibility = "hidden";

    var xmlDoc = oXml.getResponseXml();
    if(!xmlDoc) return null;

    // node 태그에 해당하는 값들을 가져와서 html을 작성
    var node_list = xmlDoc.getElementsByTagName("node");
    if(node_list.length<1) return;

    // select 내용 없앰
    xInnerHtml('menu','');

    var root = xmlDoc.getElementsByTagName("root")[0];
    root.setAttribute('node_srl',0);
    root.setAttribute('parent_srl',0);
    xGetElementById('menu').appendChild(getGabItem(0,0,0));
    homepageInsertMenuObject(xGetElementById('menu'), root, 0);

}

function getGabItem(parent_srl, up_srl, depth) {
    if(typeof(parent_srl)=='undefined' || !parent_srl) parent_srl = 0;
    if(typeof(up_srl)=='undefined' || !up_srl) up_srl = 0;
    if(typeof(depth)=='undefined' || !depth) depth = 0;
    
    var gabObj = xCreateElement('div');
    gabObj.id = 'gab_'+parent_srl+'_'+up_srl;
    gabObj.style.cursor = "pointer";
    gabObj.style.width = '100%';
    gabObj.style.height = '1px';
    gabObj.style.marign = '5px 0 0 0';
    gabObj.style.padding = '0 0 5px 0';
    gabObj.style.overflow = "hidden";
    gabObj.style.whitespace = "nowrap";
    return gabObj;
}

// root부터 시작해서 recursive하게 노드를 표혐
function homepageInsertMenuObject(drawObj, parent_node, depth) {

    for (var i=0; i< parent_node.childNodes.length; i++) {

        var html = "";

        var node = parent_node.childNodes.item(i);
        if(node.nodeName!="node") continue;

        var node_srl = node.getAttribute("node_srl");
        var parent_srl = node.getAttribute("parent_srl");
        var text = node.getAttribute("text");
        var url = node.getAttribute("url");

        if(!text) continue;

        var itemObj = xCreateElement('div');
        itemObj.style.margin = "0 0 0 "+(depth*20)+"px";

        if(parent_srl>0 && i<1) itemObj.appendChild(getGabItem(parent_srl, 0, depth));

        var textObj = xCreateElement('div');
        textObj.className = "page";
        textObj.style.cursor = "pointer";
        textObj.id = "node_"+node_srl;
        textObj.style.padding = "5px 0 5px 20px";
        xInnerHtml(textObj, text);

        if(depth < max_menu_depth-1) 
            xInnerHtml(textObj, xInnerHtml(textObj) + '<a href="#" onclick="homepageAddMenu('+node_srl+'); return false;" class="insert"><img src="./common/tpl/images/blank.gif" alt="" /></a> ');

        xInnerHtml(textObj, xInnerHtml(textObj) + '<a href="#" onclick="homepageModifyMenu('+node_srl+'); return false;" class="modify"><img src="./common/tpl/images/blank.gif" alt="" /></a> ');

        if(!node.hasChildNodes()) {
            xInnerHtml(textObj, xInnerHtml(textObj) + '<a href="#" onclick="homepageDeleteMenu('+node_srl+'); return false;" class="delete"><img src="./common/tpl/images/blank.gif" alt="" /></a> ');
        }
        itemObj.appendChild(textObj);

        if(node.hasChildNodes()) homepageInsertMenuObject(itemObj, node, depth+1);
        itemObj.appendChild(getGabItem(parent_srl, node_srl, depth));

        drawObj.appendChild(itemObj);
    }

}

function homepageAddMenu(node_srl) {
    menuFormReset();
    var obj = new Array();
    obj['mode'] = 'insert';
    if(typeof(node_srl)!='undefined' && node_srl > 0) {
        obj['parent_srl'] = node_srl;
    }
    menuFormInsert(obj) 
    showMenuItem();
}

function homepageModifyMenu(node_srl) {
    var params = new Array();
    params['node_srl'] = node_srl;
    var response_tags = new Array('error','message','menu_info');
    exec_xml('homepage','getHomepageMenuItem', params, completeModifyMenu, response_tags);
}

function completeModifyMenu(ret_obj) {
    var menu_info = ret_obj['menu_info'];
    menu_info['mode'] = 'update';
    menuFormInsert(menu_info) 
    showMenuItem();
    showMenuButton();
}

function homepageDeleteMenu(node_srl) {
    var fo_obj = xGetElementById('menu_item_form');
    fo_obj.menu_item_srl.value = node_srl;

    procFilter(fo_obj, delete_menu_item);
}

function completeChangeLayout(ret_obj) {
    location.reload();
}

function hideMenuItem() {
    xGetElementById('menuItem').style.visibility = 'hidden';
    menuFormReset();
}

function showMenuButton() {
    xGetElementById('itemAttr4').style.display = 'block';
}

function showMenuItem() {
    var obj = xGetElementById('menuItem');
    xLeft(obj, mousePos.x - xWidth('navigation') - 40);
    xTop(obj, mousePos.y - xHeight('header') - 70 );
    obj.style.visibility = 'visible';
}

function menuFormReset() {
    var fo_obj = xGetElementById("fo_menu");

    fo_obj.parent_srl.value = '';
    fo_obj.menu_item_srl.value = '';
    fo_obj.mode.value = '';

    var names = xGetElementsByClassName("menu_names");
    for(var i in names) names[i].value = "";

    fo_obj.browser_title.value = '';

    fo_obj.menu_open_window.checked = false;
    fo_obj.menu_expand.checked = false;

    for(var i=0; i<fo_obj.group_srls.length;i++) fo_obj.group_srls[i].checked = false;

    fo_obj.module_type.selectedIndex = 0;
    fo_obj.module_type.disabled = "";

    fo_obj.module_id.value = '';
    fo_obj.url.value = '';
    xGetElementById('itemAttr3').style.display = "none";

    xGetElementById("menu_normal_btn_zone").style.display = "none";
    xGetElementById('menu_normal_btn_img').src = "";
    xGetElementById("menu_hover_btn_zone").style.display = "none";
    xGetElementById('menu_hover_btn_img').src = "";
    xGetElementById("menu_active_btn_zone").style.display = "none";
    xGetElementById('menu_active_btn_img').src = "";

    xGetElementById('itemAttr4').style.display = 'none';

    fo_obj.reset();
}

function menuFormInsert(obj) {
    if(typeof(obj)=='undefined') return;

    var fo_obj = xGetElementById("fo_menu");

    if(typeof(obj.parent_srl)!='undefined') fo_obj.parent_srl.value = obj.parent_srl;
    if(typeof(obj.menu_item_srl)!='undefined') fo_obj.menu_item_srl.value = obj.menu_item_srl;
    if(typeof(obj.mode)!='undefined') fo_obj.mode.value = obj.mode;

    if(typeof(obj.name)!='undefined') {
        for(var i in obj.name) {
            var o = fo_obj['menu_name_'+i];
            if(!o) continue;
            o.value = obj.name[i];
        }
    }

    if(typeof(obj.browser_title)!='undefined') fo_obj.browser_title.value = obj.browser_title;

    if(typeof(obj.open_window)!='undefined' && obj.open_window=='Y') fo_obj.menu_open_window.checked = true;
    if(typeof(obj.expand)!='undefined' && obj.expand=='Y') fo_obj.menu_expand.checked = true;

    if(typeof(obj.group_srls)!='undefined' && obj.group_srls && typeof(obj.group_srls.item)!='undefined' && obj.group_srls.item) {
        for(var j in obj.group_srls.item) {
            for(var i=0; i<fo_obj.group_srls.length;i++) {
                if(obj.group_srls.item[j]==fo_obj.group_srls[i].value) fo_obj.group_srls[i].checked = true;
            }
        }
    }

    if(typeof(obj.module_type)!='undefined') {
        if(obj.module_type == 'url') {
            fo_obj.module_type.selectedIndex = 2;
            if(typeof(obj.url)!='undefined') fo_obj.url.value = obj.url;
            fo_obj.module_type.disabled = "disabled";
            xGetElementById('itemAttr2').style.display = 'none';
            xGetElementById('itemAttr3').style.display = 'block';
        } else {
            if(obj.module_type == 'page') fo_obj.module_type.selectedIndex = 1;
            else fo_obj.module_type.selectedIndex = 1;
            if(typeof(obj.module_id)!='undefined') fo_obj.module_id.value = obj.module_id;
            fo_obj.module_type.disabled = "disabled";
            xGetElementById('itemAttr2').style.display = 'block';
            xGetElementById('itemAttr3').style.display = 'none';
        }
    }

    if(typeof(obj.normal_btn)!='undefined' && obj.normal_btn) {
        xGetElementById('menu_normal_btn_img').src = obj.normal_btn;
        xGetElementById('menu_normal_btn_zone').style.display = "block";
        xGetElementById('itemAttr4').style.display = 'block';
        fo_obj.normal_btn.value = obj.normal_btn;
    }
    if(typeof(obj.hover_btn)!='undefined' && obj.hover_btn) {
        xGetElementById('menu_hover_btn_img').src = obj.hover_btn;
        xGetElementById('menu_hover_btn_zone').style.display = "block";
        xGetElementById('itemAttr4').style.display = 'block';
        fo_obj.hover_btn.value = obj.hover_btn;
    }
    if(typeof(obj.active_btn)!='undefined' && obj.active_btn) {
        xGetElementById('menu_active_btn_img').src = obj.active_btn;
        xGetElementById('menu_active_btn_zone').style.display = "block";
        xGetElementById('itemAttr4').style.display = 'block';
        fo_obj.active_btn.value = obj.active_btn;
    }
}

function changeMenuType(obj) {
    if(obj.selectedIndex == 2) {
        xGetElementById('itemAttr2').style.display = 'none';
        xGetElementById('itemAttr3').style.display = 'block';
        return;
    } 

    xGetElementById('itemAttr2').style.display = 'block';
    xGetElementById('itemAttr3').style.display = 'none';

}

function completeInsertMenuItem(ret_obj) {
    var xml_file = ret_obj['xml_file'];
    if(!xml_file) return;

    hideMenuItem();
    homepageLoadMenuInfo(xml_file);
}

function doHomepageMenuUploadButton(obj) {
    // 이미지인지 체크
    if(!/\.(gif|jpg|jpeg|png)$/i.test(obj.value)) return alert(alertImageOnly);

    var fo_obj = xGetElementById("fo_menu");
    var act = fo_obj.act.value;
    fo_obj.act.value = "procHomepageMenuUploadButton";
    fo_obj.target.value = obj.name;
    fo_obj.submit();
    fo_obj.act.value = act;
    fo_obj.target.value = "";
}

/* 메뉴 이미지 업로드 후처리 */
function completeMenuUploadButton(target, filename) {
    var column_name = target.replace(/^menu_/,'');
    var fo_obj = xGetElementById("fo_menu");
    var zone_obj = xGetElementById(target+'_zone');
    var img_obj = xGetElementById(target+'_img');

    fo_obj[column_name].value = filename;

    var img = new Image();
    img.src = filename;
    img_obj.src = img.src;
    zone_obj.style.display = "block";
}

function doDeleteButton(target) {
    var fo_obj = xGetElementById("fo_menu");

    var col_name = target.replace(/^menu_/,'');

    var params = new Array();
    params['target'] = target;
    params['menu_srl'] = fo_obj.menu_srl.value;
    params['menu_item_srl'] = fo_obj.menu_item_srl.value;
    params['filename'] = fo_obj[col_name].value;

    var response_tags = new Array('error','message', 'target');

    exec_xml('homepage','procHomepageDeleteButton', params, completeDeleteButton, response_tags);
}

function completeDeleteButton(ret_obj) {
    var target = ret_obj['target'];

    var column_name = target.replace(/^menu_/,'');
    var fo_obj = xGetElementById("fo_menu");
    var zone_obj = xGetElementById(target+'_zone');
    var img_obj = xGetElementById(target+'_img');
    fo_obj[column_name].value = "";
    img_obj.src = "";
    zone_obj.style.display = "none";
}

/* drag item */
xAddEventListener(document, 'mousedown', dragItem);

var dragObj = null;
var dragTarget = null;
var dragTmpObjectect = new Array();
var dragDisappear = 0;

function dragItem(evt) {
    var e = new xEvent(evt);
    if(!e.target) return;
    var obj = e.target;
    while(obj) {
        if(obj && obj.nodeName == 'DIV' && typeof(obj.id)!='undefined' && obj.id.indexOf('node_')>-1) {
            dragEnable(obj, evt);
            return;
        }
        obj = obj.parentNode;
    }
}

function getDragTmpObject(obj) {
    if(!dragTmpObjectect[obj.id]) {
        tmpObj = xCreateElement('div');
        tmpObj.id = obj.id + '_tmp';
        tmpObj.style.display = 'none';
        tmpObj.style.position = 'absolute';
        tmpObj.style.opacity = 0.5;
        tmpObj.style.filter = 'alpha(opacity=50)';
        tmpObj.style.cursor = "pointer";

        xInnerHtml(tmpObj,xInnerHtml(obj));

        document.body.appendChild(tmpObj);
        dragTmpObjectect[obj.id] = tmpObj;
    }
    return dragTmpObjectect[obj.id];
}

function removeDragTmpObject(obj) {
    if(!dragTmpObjectect[obj.id]) return;
    dragTmpObjectect[obj.id] = null;
}

function dragEnable(obj, evt) {
    if(obj.id.indexOf('node_')<0) return;
    obj.draggable = true;

    dragObj = obj;
    dragObj.id = obj.id;

    var e = new xEvent(evt);
    xPreventDefault(evt);
    obj.xDPX = e.pageX;
    obj.xDPY = e.pageY;

    xAddEventListener(document, 'mouseup', dragUp, false);
    xAddEventListener(document, 'mousemove', dragMove, false);

    var tmpObj = getDragTmpObject(obj);
    xLeft(tmpObj, e.pageX+1);
    xTop(tmpObj, e.pageY+1);
    xWidth(tmpObj, xWidth(obj));
    xHeight(tmpObj, xHeight(obj));
    xDisplay(tmpObj, 'block');
}

function dragMove(evt) {
    if(!dragObj) return;

    var e = new xEvent(evt);
    var target = e.target;
    var obj = dragObj;
    var tobj = getDragTmpObject(obj);
    xLeft(tobj, e.pageX+1);
    xTop(tobj, e.pageY+1);

    if(target && target.nodeName == "DIV" && typeof(target.id)!='undefined' && (target.id.indexOf('gab_')>-1||target.id.indexOf('node_')>-1)) {
        var isChilds = false;
        var pObj = target.parentNode;
        while(pObj) {
            if(pObj.firstChild && typeof(pObj.firstChild.id)!='undefined' && pObj.firstChild.id == dragObj.id) {
                isChilds = true;
                break;
            }
            pObj = pObj.parentNode;
        }
        if(dragTarget) {
            dragTarget.style.backgroundColor = '';
            dragTarget.style.borderTop = '0px solid #000';
            dragTarget = null;
        }

        if(!isChilds) {
            dragTarget = target;
            if(target.id.indexOf('gab_')>-1) {
                dragTarget.style.borderTop = '1px solid #000';
            } else {
                dragTarget.style.backgroundColor = '#DDDDDD';
            }
        }
    } else if(dragTarget) {
        dragTarget.style.backgroundColor = '';
        dragTarget.style.borderTop = '0px solid #000';
        dragTarget = null;
    }

    xPreventDefault(evt);
}

function dragUp(evt) { 
    if(!dragObj) return;

    if(dragTarget && dragTarget.id != dragObj.id && confirm(confirmMenuMove)) {
        var mode = null;
        if(dragTarget.id.indexOf('gab_')>-1) mode = 'move';
        else mode = 'insert';

        var tmpArr = dragTarget.id.split('_');
        var parent_srl = tmpArr[1];
        var source_srl = mode=='move'?tmpArr[2]:0;

        var tmpArr = dragObj.id.split('_');
        var target_srl = tmpArr[1];

        var params = new Array();
        params['menu_srl'] = xGetElementById('fo_menu').menu_srl.value;
        params['mode'] = mode;
        params['parent_srl'] = parent_srl;
        params['source_srl'] = source_srl;
        params['target_srl'] = target_srl;
        var response_tags = new Array('error','message','xml_file');
        exec_xml('homepage','procHomepageMenuItemMove', params, completeInsertMenuItem, response_tags);
    }

    var tobj = getDragTmpObject(dragObj);

    xRemoveEventListener(document, 'mouseup', dragUp, false);
    xRemoveEventListener(document, 'mousemove', dragMove, false);

    dragDisappear = dragDisapearObject(tobj, dragObj);

    var e = new xEvent(evt);
    xPreventDefault(evt);
    dragObj = null;

    if(dragTarget) {
        dragTarget.style.backgroundColor = '';
        dragTarget.style.borderTop = '0px solid #000';
        dragTarget = null;
    }
}  

  
// 스르르 사라지게 함;;
function dragDisapearObject(obj, tobj) {
    var it = 20;
    var ib = 20;

    var x = parseInt(xPageX(obj),10);
    var y = parseInt(xPageY(obj),10);
    var ldt = (x - parseInt(xPageX(tobj),10)) / ib;
    var tdt = (y - parseInt(xPageY(tobj),10)) / ib;

    return setInterval(function() {
        if(ib < 1) {
            clearInterval(dragDisappear);
            xDisplay(obj, 'none');
            removeDragTmpObject(tobj);
            return;
        }
        ib -= 3;
        x-=ldt;
        y-=tdt;
        xLeft(obj, x);
        xTop(obj, y);
    }, it/ib);
}

function completeInsertBoard(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function completeInsertPage(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function doDeleteGroup(group_srl) {
    var fo_obj = xGetElementById('fo_group');
    fo_obj.group_srl.value = group_srl;
    procFilter(fo_obj, delete_group);
}

function completeInsertGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');
}

function completeDeleteGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');

}

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

function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);
}


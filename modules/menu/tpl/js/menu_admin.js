/* 메뉴 입력후 */ 
function completeInsertMenu(ret_obj) {
    var menu_srl = ret_obj['menu_srl'];
    alert(ret_obj['message']);
    location.href = current_url.setQuery('act','dispMenuAdminManagement').setQuery('menu_srl',menu_srl);
} 

/* 메뉴 삭제 */
function doDeleteMenu(menu_srl) {
      var fo_obj = xGetElementById("fo_menu");
      if(!fo_obj) return;
      fo_obj.menu_srl.value = menu_srl;
      procFilter(fo_obj, delete_menu);
}

/* 메뉴 삭제 후 */ 
function completeDeleteMenu(ret_obj) {
    var menu_srl = ret_obj['menu_srl'];
    alert(ret_obj['message']);
    location.href = current_url.setQuery('act','dispMenuAdminContent').setQuery('menu_srl','');
} 

/* 빈 메뉴 아이템 추가 */
function doInsertMenuItem(parent_srl) {
    if(typeof(parent_srl)=='undefined') parent_srl = 0;
    var params = {node_srl:0, parent_srl:parent_srl}
    doGetMenuItemInfo('menu', params);
    deSelectNode();
}

/* 메뉴 클릭시 적용할 함수 */
function doGetMenuItemInfo(menu_id, obj) {
    // menu, menu_id, node_srl을 추출
    var fo_obj = xGetElementById("fo_menu");
    var node_srl = 0;
    var parent_srl = 0;

    if(typeof(obj)!="undefined") {
        if(typeof(obj.getAttribute)!="undefined") { 
          node_srl = obj.getAttribute("node_srl");
        } else {
            node_srl = obj.node_srl; 
            parent_srl = obj.parent_srl; 
        }
    }

    var params = new Array();
    params["menu_item_srl"] = node_srl;
    params["parent_srl"] = parent_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','tpl');
    exec_xml('menu', 'getMenuAdminTplInfo', params, completeGetMenuItemTplInfo, response_tags, params);
}

/* 서버로부터 받아온 메뉴 정보를 출력 */
xAddEventListener(document,'mousedown',checkMousePosition);
var _xPos = 0;
var _yPos = 0;
function checkMousePosition(e) {
    var evt = new xEvent(e);
    _xPos = evt.pageX;
    _yPos = evt.pageY;
}   

function hideCategoryInfo() {
    var obj = xGetElementById('menu_zone_info');
    obj.style.display = "none";
}

function completeGetMenuItemTplInfo(ret_obj, response_tags) {
    var obj = xGetElementById('menu_zone_info');
    var tpl = ret_obj['tpl'];
    xInnerHtml(obj, tpl);
    obj.style.display = 'block';

    var fo_obj = xGetElementById("fo_menu");
    fo_obj.menu_name.focus();

    var x = _xPos + 50;
    var y = _yPos - xHeight(obj)/2 + 80 + xScrollTop();
    xLeft(obj, x);
    xTop(obj, y);
    xRemoveEventListener(document,'mousedown',checkMousePosition);

    if(xGetElementById('cBody')) {
        xHeight('cBody', y + xHeight(obj) + 50);
    }
}

/* 메뉴 아이템 입력후 */ 
function completeInsertMenuItem(ret_obj) {
    var menu_id = ret_obj['menu_id'];
    var xml_file = ret_obj['xml_file'];
    var menu_title = ret_obj['menu_title'];
    var menu_srl = ret_obj['menu_srl'];
    var menu_item_srl = ret_obj['menu_item_srl'];
    var parent_srl = ret_obj['parent_srl'];

    if(!xml_file) return;

    loadTreeMenu(xml_file, 'menu', 'menu_zone_menu', menu_title, '', doGetMenuItemInfo, menu_item_srl, doMoveTree);

    if(!menu_srl) xInnerHtml("menu_zone_info", "");
    else {
        var params = {node_srl:menu_item_srl, parent_srl:parent_srl}
        doGetMenuItemInfo('menu', params)
    }
} 


/* 메뉴를 드래그하여 이동한 후 실행할 함수 , 이동하는 item_srl과 대상 item_srl을 받음 */
function doMoveTree(menu_id, source_item_srl, target_item_srl) {
    var fo_obj = xGetElementById("fo_move_menu");
    fo_obj.menu_id.value = menu_id;
    fo_obj.source_item_srl.value = source_item_srl;
    fo_obj.target_item_srl.value = target_item_srl;

    // 이동 취소를 선택하였을 경우 다시 그림;;
    if(!procFilter(fo_obj, move_menu_item)) {
        var params = new Array();
        params["xml_file"] = xGetElementById('fo_menu').xml_file.value;
        params["source_item_srl"] = source_item_srl;
        completeMoveMenuItem(params);
    }
}

function completeMoveMenuItem(ret_obj) {
    var source_item_srl = ret_obj['source_item_srl'];
    var xml_file = ret_obj['xml_file'];

    var fo_menu = xGetElementById("fo_menu");
    if(!fo_menu) return;

    var title = fo_menu.title.value;
    loadTreeMenu(xml_file, 'menu', "menu_zone_menu", title, '', doGetMenuItemInfo, source_item_srl, doMoveTree);
}

/* 메뉴 목록 갱신 */
function doReloadTreeMenu(menu_srl) {
    var params = new Array();
    params["menu_srl"] = menu_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message', 'xml_file', 'menu_title');
    exec_xml('menu', 'procMenuAdminMakeXmlFile', params, completeInsertMenuItem, response_tags, params);
}

/* 메뉴 삭제 */
function doDeleteMenuItem(menu_item_srl) {
      var fo_obj = xGetElementById("fo_menu");
      if(!fo_obj) return;

      procFilter(fo_obj, delete_menu_item);
}

/* 메뉴 아이템 삭제 후 */ 
function completeDeleteMenuItem(ret_obj) {
    var menu_title = ret_obj['menu_title'];
    var menu_srl = ret_obj['menu_srl'];
    var menu_item_srl = ret_obj['menu_item_srl'];
    var xml_file = ret_obj['xml_file'];
    alert(ret_obj['message']);

    loadTreeMenu(xml_file, 'menu', 'menu_zone_menu', menu_title, '', doGetMenuItemInfo, menu_item_srl, doMoveTree);
    xInnerHtml("menu_zone_info", "");
} 


/* 레이아웃의 메뉴에 mid 추가 */
function doInsertMid(mid, menu_id) {
    if(!opener) {
        window.close();
        return;
    }

    var fo_obj = opener.xGetElementById("fo_menu");
    if(!fo_obj) {
        window.close();
        return;
    }

    fo_obj.menu_url.value = mid;
    window.close();
}

/* 메뉴 입력후 */ 
function completeInsertMenu(ret_obj) {
    var menu_srl = ret_obj['menu_srl'];
    alert(ret_obj['message']);
    location.href = location.href.setQuery('act','dispMenuAdminManagement').setQuery('menu_srl',menu_srl);
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
    location.href = location.href.setQuery('act','dispMenuAdminContent').setQuery('menu_srl','');
} 

/* 빈 메뉴 아이템 추가 */
function doInsertLayoutMenuItem(parent_srl) {
    if(typeof(parent_srl)=='undefined') parent_srl = 0;
    var params = {node_srl:0, parent_srl:parent_srl}
    doGetMenuItemInfo(params);
    deSelectNode();
}

/* 메뉴 클릭시 적용할 함수 */
function doGetMenuItemInfo(obj) {
    // layout, menu_id, node_srl을 추출
    var fo_obj = xGetElementById("fo_menu");
    var node_srl = 0;
    var parent_srl = 0;

    if(typeof(obj.getAttribute)!="undefined") { 
      node_srl = obj.getAttribute("node_srl");
    } else {
      node_srl = obj.node_srl; 
      parent_srl = obj.parent_srl; 
    }

    var params = new Array();
    params["menu_srl"] = node_srl;
    params["parent_srl"] = parent_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','tpl');
    exec_xml('menu', 'getMenuAdminTplInfo', params, completeGetMenuItemTplInfo, response_tags, params);
}

/* 서버로부터 받아온 메뉴 정보를 출력 */
function completeGetMenuItemTplInfo(ret_obj, response_tags) {
    var tpl = ret_obj['tpl'];
    xInnerHtml("menu_zone_info", tpl);
    var fo_obj = xGetElementById("fo_menu");
    fo_obj.menu_name.focus();
}


/* 메뉴를 드래그하여 이동한 후 실행할 함수 , 이동하는 node_srl과 대상 node_srl을 받음 */
function doMoveTree(menu_id, source_node_srl, target_node_srl) {
    var fo_obj = xGetElementById("fo_move_menu");
    fo_obj.menu_id.value = menu_id;
    fo_obj.source_node_srl.value = source_node_srl;
    fo_obj.target_node_srl.value = target_node_srl;

    // 이동 취소를 선택하였을 경우 다시 그림;;
    if(!procFilter(fo_obj, move_layout_menu)) {
        var params = new Array();
        params["menu_id"] = menu_id;
        params["source_node_srl"] = source_node_srl;
        completeMoveLayoutMenu(params);
    }
}

function completeMoveLayoutMenu(ret_obj) {
    var menu_id = ret_obj['menu_id'];
    var source_node_srl = ret_obj['source_node_srl'];

    var fo_menu = xGetElementById("fo_"+menu_id);
    if(!fo_menu) return;

    var params = new Array();
    params["menu_id"] = menu_id;
    params["layout"] = fo_menu.layout.value;
    params["layout_srl"] = fo_menu.layout_srl.value;
    var xml_file = fo_menu.xml_file.value;
    var menu_title = fo_menu.menu_title.value;
    var tmp = source_node_srl.split('_');
    var menu_srl = tmp[tmp.length-1];
    loadTreeMenu(xml_file, menu_id, "menu_zone_"+menu_id, menu_title, doGetMenuItemInfo, menu_srl, doMoveTree);
}











/* 메뉴 아이템 입력후 */ 
function completeInsertMenuItem(ret_obj) {
    var menu_id = ret_obj['menu_id'];
    var xml_file = ret_obj['xml_file'];
    var menu_title = ret_obj['menu_title'];
    var menu_srl = ret_obj['menu_srl'];

    if(!xml_file) return;
    loadTreeMenu(xml_file, menu_id, "menu_zone_"+menu_id, menu_title, doGetMenuItemInfo, menu_srl, doMoveTree);

    if(!menu_srl) xInnerHtml("menu_zone_info_"+menu_id, "");
} 

/* 메뉴 삭제 */
function doDeleteLayoutMenu(menu_srl, menu_id) {
      var fo_obj = xGetElementById("fo_"+menu_id);
      if(!fo_obj) return;

      procFilter(fo_obj, delete_layout_menu);
}

/* 메뉴 목록 갱신 */
function doReloadTreeMenu(menu_id) {
    var fo_obj = xGetElementById("fo_"+menu_id);
    if(!fo_obj) return;

    var params = new Array();
    params["menu_id"] = menu_id;
    params["layout"] = fo_obj.layout.value;
    params["layout_srl"] = fo_obj.layout_srl.value;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','menu_id', 'xml_file', 'menu_title');
    exec_xml('layout', 'procLayoutAdminMakeXmlFile', params, completeInsertLayoutMenu, response_tags, params);
}

/* 레이아웃의 메뉴에 mid 추가 */
function doInsertMid(mid, menu_id) {
    if(!opener) {
        window.close();
        return;
    }

    var fo_obj = opener.xGetElementById("fo_"+menu_id);
    if(!fo_obj) {
        window.close();
        return;
    }

    fo_obj.menu_url.value = "mid="+mid;
    window.close();
}

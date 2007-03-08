/* 레이아웃 신규 생성시 완료 후 요청하는 함수 */
function completeInsertLayout(ret_obj) {
      var layout_srl = ret_obj['layout_srl'];
      location.href="./?module=admin&mo=layout&act=dispLayoutInfo&layout_srl="+layout_srl;
} 

/* 레이아웃메뉴 입력후 */ 
function completeInsertLayoutMenu(ret_obj) {
    var menu_id = ret_obj['menu_id'];
    var xml_file = ret_obj['xml_file'];
    var menu_title = ret_obj['menu_title'];
    var menu_srl = ret_obj['menu_srl'];

    if(!xml_file) return;
    loadTreeMenu(xml_file, menu_id, "menu_zone_"+menu_id, menu_title, doGetMenuInfo, menu_srl);

    if(!menu_srl) {
        xInnerHtml("menu_zone_info_"+menu_id, "");
    }
} 

/* 레이아웃 삭제 */
function doDeleteLayout(layout_srl) {
    var fo_obj = xGetElementById("fo_layout");
    if(!fo_obj) return;
    fo_obj.layout_srl.value = layout_srl;

    procFilter(fo_obj, delete_layout);
}

/* 레이아웃 메뉴 삭제 */
function doDeleteLayoutMenu(menu_srl, menu_id) {
      var fo_obj = xGetElementById("fo_"+menu_id);
      if(!fo_obj) return;

      procFilter(fo_obj, delete_layout_menu);
}

/* 레이아웃 메뉴에를 클릭시 적용할 함수 */
function doGetMenuInfo(menu_id, obj) {
    // layout, menu_id, node_srl을 추출
    var fo_obj = xGetElementById("fo_layout");
    var layout = fo_obj.layout.value;
    var node_srl = 0;
    var parent_srl = 0;
    if(typeof(obj.getAttribute)!="undefined") { 
      node_srl = obj.getAttribute("node_srl");
    } else {
      node_srl = obj.node_srl; 
      parent_srl = obj.parent_srl; 
    }

    var params = new Array();
    params["menu_id"] = menu_id;
    params["layout"] = layout;
    params["menu_srl"] = node_srl;
    params["parent_srl"] = parent_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','menu_id', 'tpl');
    exec_xml('layout', 'getMenuTplInfo', params, completeGetMenuInfo, response_tags, params);
}

/* 서버로부터 받아온 메뉴 정보를 출력 */
function completeGetMenuInfo(ret_obj, response_tags) {
    var menu_id = ret_obj['menu_id'];
    var tpl = ret_obj['tpl'];
    xInnerHtml("menu_zone_info_"+menu_id, "");
    xInnerHtml("menu_zone_info_"+menu_id, tpl);

    var fo_obj = xGetElementById("fo_"+menu_id);
    fo_obj.menu_name.focus();
}

/* 빈 메뉴 추가시 사용 */
function doInsertLayoutMenu(menu_id, parent_srl) {
    if(typeof(parent_srl)=='undefined') parent_srl = 0;
    var params = {node_srl:0, parent_srl:parent_srl}
    doGetMenuInfo(menu_id, params);
    deSelectNode();
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
    exec_xml('layout', 'procMakeXmlFile', params, completeInsertLayoutMenu, response_tags, params);
}

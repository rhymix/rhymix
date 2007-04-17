/* 레이아웃 신규 생성시 완료 후 요청하는 함수 */
function completeInsertLayout(ret_obj) {
      var layout_srl = ret_obj['layout_srl'];
      var url = location.href.setQuery('act','dispLayoutAdminModify').setQuery('layout_srl',layout_srl);
      location.href = url;
} 

/* 레이아웃 삭제 */
function doDeleteLayout(layout_srl) {
    var fo_obj = xGetElementById("fo_layout");
    if(!fo_obj) return;
    fo_obj.layout_srl.value = layout_srl;

    procFilter(fo_obj, delete_layout);
}

/* 메뉴 관리로 이동 */
function doMenuManagement(menu_id) {
    var fo_obj = xGetElementById("fo_layout");
    var sel_obj = fo_obj[menu_id];
    if(sel_obj.selectedIndex == 0) return;
    var menu_srl = sel_obj.options[sel_obj.selectedIndex].value;

    var url = location.href.setQuery('act','dispMenuAdminManagement').setQuery('menu_srl',menu_srl);
    location.href = url;
}

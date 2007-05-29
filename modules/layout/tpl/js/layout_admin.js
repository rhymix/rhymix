/* 레이아웃 신규 생성시 완료 후 요청하는 함수 */
function completeInsertLayout(ret_obj) {
      var layout_srl = ret_obj['layout_srl'];
      var url = current_url.setQuery('act','dispLayoutAdminModify').setQuery('layout_srl',layout_srl);
      location.href = url;
} 

/* 레이아웃 삭제 */
function doDeleteLayout(layout_srl) {
    var fo_obj = xGetElementById("fo_layout");
    if(!fo_obj) return;
    fo_obj.layout_srl.value = layout_srl;

    procFilter(fo_obj, delete_layout);
}

/* 수정된 레이아웃을 원본으로 돌림 */
function doResetLayoutCode(layout_srl) {
    var fo_obj = xGetElementById('fo_layout');
    procFilter(fo_obj, reset_layout_code);
    return false;
}

/* 수정중인 레이아웃 미리보기 */
function doPreviewLayoutCode() {
    var fo_obj = xGetElementById('fo_layout');
    fo_obj.target = "_LayoutPreview";
    fo_obj.act.value = "dispLayoutAdminPreview";
    fo_obj.submit();
    fo_obj.act.value = "";
    fo_obj.target = "";
}


/* 메뉴 관리로 이동 */
function doMenuManagement(menu_id) {
    var fo_obj = xGetElementById("fo_layout");
    var sel_obj = fo_obj[menu_id];
    if(sel_obj.selectedIndex == 0) return;
    var menu_srl = sel_obj.options[sel_obj.selectedIndex].value;

    var url = current_url.setQuery('act','dispMenuAdminManagement').setQuery('menu_srl',menu_srl);
    location.href = url;
}

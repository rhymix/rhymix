/* 레이아웃 신규 생성시 완료 후 요청하는 함수 */
function completeInsertLayout(ret_obj) {
      var layout_srl = ret_obj['layout_srl'];
      var url = location.href.setQuery('act','dispLayoutAdminMenu').setQuery('layout_srl',layout_srl);
      location.href = url;
} 

/* 레이아웃 삭제 */
function doDeleteLayout(layout_srl) {
    var fo_obj = xGetElementById("fo_layout");
    if(!fo_obj) return;
    fo_obj.layout_srl.value = layout_srl;

    procFilter(fo_obj, delete_layout);
}

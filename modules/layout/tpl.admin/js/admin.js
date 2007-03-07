/* 레이아웃 메뉴에를 클릭시 적용할 함수 */
function doDisplayMenuInfo(menu_id, obj) {
  // layout, menu_id, node_srl을 추출
  var fo_obj = xGetElementById("fo_layout");
  var node_srl = obj.getAttribute("node_srl");
  var layout = fo_obj.layout.value;

  // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
}

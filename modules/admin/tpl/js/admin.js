/**
 * @file   : modules/admin/js/admin.js
 * @author : zero <zero@nzeo.com>
 * @desc   : admin 모듈의 javascript
 **/

// 숏컷 삭제
function doDeleteShortCut(selected_module) {
  var fo_obj = xGetElementById('fo_shortcut_info');
  fo_obj.selected_module.value = selected_module;
  procFilter(fo_obj, delete_shortcut);
}

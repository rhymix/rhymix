/**
 * @file   : modules/admin/js/admin.js
 * @author : zero <zero@nzeo.com>
 * @desc   : admin 모듈의 javascript
 **/

// 로그아웃
function completeLogout(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];

  location.href = "./admin.php";
}

// 로그인폼에서 아이디 포커스
function doAdminLoginFocus() {
  var fo = xGetElementById('user_id');
  if(fo) fo.focus();
}

// 숏컷 삭제
function doDeleteShortCut(shortcut_srl) {
  var fo_obj = xGetElementById('fo_shortcut_info');
  fo_obj.shortcut_srl.value = shortcut_srl;
  procFilter(fo_obj, delete_shortcut);
}

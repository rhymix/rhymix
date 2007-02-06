/**
 * @file   : modules/board/js/admin.js
 * @author : zero <zero@nzeo.com>
 * @desc   : board 모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function procInsertModule(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  var module_srl = ret_obj['module_srl'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&module_srl="+module_srl+"&page="+page+"&act="+act;
  location.href = url;
}

/* 카테고리 관련 작업들 */
function doUpdateCategory(category_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_module_category_info');
  fo_obj.category_srl.value = category_srl;
  fo_obj.mode.value = mode;

  procFormFilter(fo_obj, update_category_info, procReload);
}

/* 메세지 출력후 현페이지 리로드 */
function procReload(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  if(message) alert(message);

  location.href = location.href;
}

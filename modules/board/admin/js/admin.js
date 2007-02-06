/**
 * @file   : modules/board/js/admin.js
 * @author : zero <zero@nzeo.com>
 * @desc   : board 모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function procInsert(ret_obj, response_tags) {
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

/* 모듈 삭제 후 */
function procDelete(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&page="+page+"&act="+act;
  location.href = url;
}

/* 카테고리 관련 작업들 */
function doUpdateCategory(category_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_category_info');
  fo_obj.category_srl.value = category_srl;
  fo_obj.mode.value = mode;

  procFormFilter(fo_obj, update_category, procReload);
}

/* 카테고리 정보 수정 후 */
function procUpdateCategory(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var module_srl = ret_obj['module_srl'];
  alert(message);

  var url = "./admin.php?sid=board&module_srl="+module_srl+"&act=dispCategoryInfo";
  location.href = url;
}

/* 메세지 출력후 현페이지 리로드 */
function procReload(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  if(message) alert(message);
  location.href = location.href;
}

/* 권한 관련 */
function procSelectAll(obj, key) {
  var fo_obj = obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  for(var i=0;i<fo_obj.length;i++) {
    var tobj = fo_obj[i];
    if(tobj.name == key) tobj.checked=true;
  }
}

function procUnSelectAll(obj, key) {
  var fo_obj = obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  for(var i=0;i<fo_obj.length;i++) {
    var tobj = fo_obj[i];
    if(tobj.name == key) tobj.checked = false;
  }
}

function procInsertGrant(ret_obj, response_tags) {
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


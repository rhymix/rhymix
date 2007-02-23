/**
 * @file   : modules/board/js/admin.js
 * @author : zero <zero@nzeo.com>
 * @desc   : board 모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function completeBoardInsert(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];

  var module = ret_obj['module'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  var module_srl = ret_obj['module_srl'];

  alert(message);

  var url =  "./?module=admin&mo="+module+"&module_srl="+module_srl+"&page="+page+"&act="+act;
  location.href = url;
}

/* 모듈 삭제 후 */
function completeBoardDelet(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var mo = ret_obj['mo'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  alert(message);

  var url =  "./?module=admin&mo="+mo+"&page="+page+"&act="+act;
  location.href = url;
}

/* 카테고리 관련 작업들 */
function doUpdateCategory(category_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_category_info');
  fo_obj.category_srl.value = category_srl;
  fo_obj.mode.value = mode;

  procFilter(fo_obj, update_category);
}

/* 카테고리 정보 수정 후 */
function completCategoryUpdate(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var module_srl = ret_obj['module_srl'];

  alert(message);

  var url = "./?module=admin&mo=board&module_srl="+module_srl+"&act=dispAdminCategoryInfo";
  location.href = url;
}

/* 권한 관련 */
function doSelectAll(obj, key) {
  var fo_obj = obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  for(var i=0;i<fo_obj.length;i++) {
    var tobj = fo_obj[i];
    if(tobj.name == key) tobj.checked=true;
  }
}

function doUnSelectAll(obj, key) {
  var fo_obj = obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  for(var i=0;i<fo_obj.length;i++) {
    var tobj = fo_obj[i];
    if(tobj.name == key) tobj.checked = false;
  }
}

function completedInsertGrant(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var mo = ret_obj['mo'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  var module_srl = ret_obj['module_srl'];

  alert(message);

  var url =  "./?module=admin&mo="+mo+"&module_srl="+module_srl+"&page="+page+"&act="+act;
  location.href = url;
}


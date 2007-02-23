/* 사용자 추가 */
function completeInsert(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var member_srl = ret_obj['member_srl'];
  var page = ret_obj['page'];

  alert(message);

  var url = "./?module=admin&mo=member&act=dispMemberInfo&member_srl="+member_srl;
  if(page) url += "&page="+page;

  location.href = url;
}

/* 사용자 삭제 */
function completeDelete(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispMemberList";
  if(page) url += "&page="+page;

  location.href = url;
}

/* 그룹 추가 */
function completeInsertGroup(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispGroupList";
  if(page) url += "&page="+page;

  location.href = url;
}

/* 그룹 관련 작업들 */
function doUpdateGroup(group_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_group_info');
  fo_obj.group_srl.value = group_srl;
  fo_obj.mode.value = mode;
  procFilter(fo_obj, update_group);
}

function completeUpdateGroup(ret_obj) {
  var page = ret_obj['page'];
  var url =  "./?module=admin&mo=member&act=dispGroupList";
  if(page) url += "&page="+page;

  location.href = url;
}


/* 금지아이디 추가 */
function completeInsertDeniedID(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispDeniedIDList";
  if(page) url += "&page="+page;

  location.href = url;
}

/* 금지아이디 관련 작업들 */
function doUpdateDeniedID(user_id, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_denied_id_info');
  fo_obj.user_id.value = user_id;
  fo_obj.mode.value = mode;
  procFilter(fo_obj, update_denied_id);
}

/* 가입폼 관련 */
function completeInsertJoinForm(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispJoinFormList";
  if(page) url += "&page="+page;

  location.href = url;
}

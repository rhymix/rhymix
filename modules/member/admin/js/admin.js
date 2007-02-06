/* 메세지 출력후 현페이지 리로드 */
function procReload(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  if(message) alert(message);

  location.href = location.href;
}

/* 사용자 추가 */
function procInsert(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var member_srl = ret_obj['member_srl'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&member_srl="+member_srl+"&page="+page+"&act="+act;
  location.href = url;
}

/* 사용자 삭제 */
function procDelete(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var page = ret_obj['page'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&page="+page;
  location.href = url;
}

/* 그룹 추가 */
function procInsertGroup(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&page="+page+"&act="+act;
  location.href = url;
}

/* 그룹 관련 작업들 */
function doUpdateGroup(group_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_group_info');
  fo_obj.group_srl.value = group_srl;
  fo_obj.mode.value = mode;
  procFormFilter(fo_obj, update_group, procReload);
}


/* 금지아이디 추가 */
function procInsertDeniedID(ret_obj, response_tags) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var sid = ret_obj['sid'];
  var act = ret_obj['act'];
  var page = ret_obj['page'];
  alert(message);

  url =  "./admin.php?sid="+sid+"&page="+page+"&act="+act;
  location.href = url;
}

/* 금지아이디 관련 작업들 */
function doUpdateDeniedID(user_id, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_denied_id_info');
  fo_obj.user_id.value = user_id;
  fo_obj.mode.value = mode;
  procFormFilter(fo_obj, update_denied_id, procReload);
}

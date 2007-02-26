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

/* 가입폼 관련 작업들 */
function doUpdateJoinForm(member_join_form_srl, mode, message) {
  if(typeof(message)!='undefined'&&!confirm(message)) return;

  var fo_obj = xGetElementById('fo_join_form_info');
  fo_obj.member_join_form_srl.value = member_join_form_srl;
  fo_obj.mode.value = mode;

  procFilter(fo_obj, update_member_join_form);
}

function completeUpdateJoinForm(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispJoinFormList";
  if(page) url += "&page="+page;

  location.href = url;
}

/* 가입폼 추가 */
function completeInsertJoinForm(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=member&act=dispJoinFormList";
  if(page) url += "&page="+page;

  location.href = url;
}

/* 가입폼의 기본 값 관리 */
function doShowJoinFormValue(sel_obj) {
  var val = sel_obj.options[sel_obj.selectedIndex].value;
  switch(val) {
    case 'checkbox' :
    case 'select' :
        xGetElementById('zone_default_value').style.display = 'block';
      break;
    default :
        xGetElementById('zone_default_value').style.display = 'none';
      break;
  }
}

function doEditDefaultValue(obj, cmd) {
  var listup_obj = xGetElementById('default_value_listup');
  var item_obj = xGetElementById('default_value_item');
  var idx = listup_obj.selectedIndex;
  var lng = listup_obj.options.length;
  var val = item_obj.value;
  switch(cmd) {
    case 'insert' :
        if(!val) return;
        var opt = new Option(val, val, false, true);
        listup_obj.options[listup_obj.length] = opt;
        item_obj.value = '';
        item_obj.focus();
      break;
    case 'up' :
        if(lng < 2 || idx<1) return;

        var value1 = listup_obj.options[idx].value;
        var value2 = listup_obj.options[idx-1].value;
        listup_obj.options[idx] = new Option(value2,value2,false,false);
        listup_obj.options[idx-1] = new Option(value1,value1,false,true);
      break;
    case 'down' :
        if(lng < 2 || idx == lng-1) return;

        var value1 = listup_obj.options[idx].value;
        var value2 = listup_obj.options[idx+1].value;
        listup_obj.options[idx] = new Option(value2,value2,false,false);
        listup_obj.options[idx+1] = new Option(value1,value1,false,true);
      break;
    case 'delete' :
        listup_obj.remove(idx);
        if(idx==0) listup_obj.selectedIndex = 0;
        else listup_obj.selectedIndex = idx-1;
      break;
  }
}

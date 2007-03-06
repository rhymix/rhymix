function doEditDefaultValue(obj, cmd, menu_id, max_depth) {
  var listup_obj = xGetElementById('default_value_listup_'+menu_id);
  var item_obj = xGetElementById('default_value_item_'+menu_id);
  var idx = listup_obj.selectedIndex;
  var lng = listup_obj.options.length;
  var val = 1;
  var text = item_obj.value;
  switch(cmd) {
    case 'insert' :
        if(!text) return;
        var opt = new Option(text, val, false, true);
        listup_obj.options[listup_obj.length] = opt;
        item_obj.value = '';
        item_obj.focus();
      break;
    case 'up' :
        if(lng < 2 || idx<1) return;

        var value1 = listup_obj.options[idx].value;
        var text1 = listup_obj.options[idx].text;
        var depth1 = getDepth(listup_obj.options[idx]);

        var value2 = listup_obj.options[idx-1].value;
        var text2 = listup_obj.options[idx-1].text;
        var depth2 = getDepth(listup_obj.options[idx-1]);

        listup_obj.options[idx] = new Option(text2,value2,false,false);
        setDepth(listup_obj.options[idx], depth1);

        listup_obj.options[idx-1] = new Option(text1,value1,false,true);
        setDepth(listup_obj.options[idx-1], depth2);
      break;
    case 'down' :
        if(lng < 2 || idx == lng-1) return;

        var value1 = listup_obj.options[idx].value;
        var text1 = listup_obj.options[idx].text;
        var depth1 = getDepth(listup_obj.options[idx]);

        var value2 = listup_obj.options[idx+1].value;
        var text2 = listup_obj.options[idx+1].text;
        var depth2 = getDepth(listup_obj.options[idx+1]);

        listup_obj.options[idx] = new Option(text2,value2,false,false);
        setDepth(listup_obj.options[idx], depth1);

        listup_obj.options[idx+1] = new Option(text1,value1,false,true);
        setDepth(listup_obj.options[idx+1], depth2);
      break;
    case 'delete' :
        listup_obj.remove(idx);
        if(idx==0) listup_obj.selectedIndex = 0;
        else listup_obj.selectedIndex = idx-1;
      break;
    case 'add_indent' :
        if(lng<2||idx<1) return;

        var opt_cur = listup_obj.options[idx];
        var opt_up = listup_obj.options[idx-1];

        var cur_depth = getDepth(opt_cur);
        var up_depth = getDepth(opt_up);

        if(up_depth >= cur_depth) addDepth(opt_cur, max_depth);
      break;
    case 'remove_indent' :
        var opt_cur = listup_obj.options[idx];
        removeDepth(opt_cur);
      break;
  }

  var value_list = new Array();
  for(var i=0;i<listup_obj.options.length;i++) {
    value_list[value_list.length] = listup_obj.options[i].value;
  }
}

function getDepth(obj) {
  var pl = obj.style.paddingLeft;
  if(!pl) return 0;
  var depth = parseInt(pl);
  return depth/20;
}

function setDepth(obj, depth) {
  obj.style.paddingLeft = (depth*20)+'px';
}

function addDepth(obj, max_depth) {
  max_depth=5;
  var depth = getDepth(obj);
  var depth = depth + 1;
  if(depth>=max_depth) return;
  obj.style.paddingLeft = (depth*20)+'px';
}

function removeDepth(obj) {
  var depth = getDepth(obj);
  var depth = depth - 1;
  if(depth<0) return;
  obj.style.paddingLeft = (depth*20)+'px';
}

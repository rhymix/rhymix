function doEditDefaultValue(obj, cmd, menu_id) {
  var listup_obj = xGetElementById('default_value_listup_'+menu_id);
  var item_obj = xGetElementById('default_value_item_'+menu_id);
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

  var value_list = new Array();
  for(var i=0;i<listup_obj.options.length;i++) {
    value_list[value_list.length] = listup_obj.options[i].value;
  }

  //xGetElementById('fo_layout').default_value.value = value_list.join('|@|');
}

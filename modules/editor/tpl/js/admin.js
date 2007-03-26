function doEnableComponent(component_name) {
  var params = new Array();
  params['component_name'] = component_name;

  exec_xml('editor', 'procEnableComponent', params, completeUpdate);
}

function doDisableComponent(component_name) {
  var params = new Array();
  params['component_name'] = component_name;

  exec_xml('editor', 'procDisableComponent', params, completeUpdate);
}

function doMoveListOrder(component_name, mode) {
  var params = new Array();
  params['component_name'] = component_name;
  params['mode'] = mode;

  exec_xml('editor', 'procMoveListOrder', params, completeUpdate);
}

function completeUpdate(ret_obj) {
  alert(ret_obj['message']);
  location.href = location.href;
}

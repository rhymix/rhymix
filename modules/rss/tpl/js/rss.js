
function doToggleRss(module_srl) {
  var params = new Array();
  params['module_srl'] = module_srl;

  var response_tags = new Array('error','message','module_srl','open_total_feed');
  exec_xml('rss','procRssAdminToggleActivate',params, doChangeIcon ,response_tags );
}

function doChangeIcon(ret_obj, response_tags) {
  var obj = document.getElementById('dotogglerss_'+ret_obj['module_srl']);
  if(ret_obj['open_total_feed'] == 'T_N') {
    obj.className = "buttonSet buttonDisable";
  } else {
    obj.className = "buttonSet buttonActive"; 
  }
}
